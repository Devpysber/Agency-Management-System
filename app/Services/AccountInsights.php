<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\AccountInsight;
use App\Models\company;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use App\Models\Quotation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Builds an AI-written analysis of a client company's account (projects,
 * finances, milestones, open documents) using the Anthropic Messages API.
 *
 * The feature is inert until ANTHROPIC_API_KEY is set — callers check
 * configured() and show a setup notice otherwise.
 */
class AccountInsights
{
    public function configured(): bool
    {
        return $this->driver() !== null;
    }

    /**
     * Which provider to use: 'openrouter' (preferred if set) or 'anthropic'.
     */
    public function driver(): ?string
    {
        if (filled(config('services.openrouter.key'))) {
            return 'openrouter';
        }
        if (filled(config('services.anthropic.key'))) {
            return 'anthropic';
        }
        return null;
    }

    /**
     * Latest stored insight for a company, or null.
     */
    public function latest(company $company): ?AccountInsight
    {
        return AccountInsight::where('company_id', $company->id)
            ->latest()->first();
    }

    /**
     * Digest of the company's current account data — compare with an insight's
     * stored input_digest to know whether it is out of date.
     */
    public function currentDigest(company $company): string
    {
        return sha1(json_encode($this->snapshot($company)));
    }

    public function isStale(company $company, ?AccountInsight $insight): bool
    {
        return $insight !== null && $insight->input_digest !== $this->currentDigest($company);
    }

    /**
     * Return a stored insight if one exists for the current data snapshot
     * (and is < 24h old); otherwise generate, persist and return a new one.
     */
    public function forCompany(company $company, bool $force = false): AccountInsight
    {
        $metrics = $this->snapshot($company);
        $digest = sha1(json_encode($metrics));

        if (! $force) {
            $existing = AccountInsight::where('company_id', $company->id)
                ->where('input_digest', $digest)
                ->where('created_at', '>=', now()->subDay())
                ->latest()->first();

            if ($existing) {
                return $existing;
            }
        }

        return $this->generate($company, $metrics, $digest);
    }

    /**
     * Numeric snapshot of the account. Kept deterministic so the digest is
     * stable and we can cache the analysis against it.
     */
    public function snapshot(company $company): array
    {
        $projectIds = Project::where('company_id', $company->id)->pluck('id');

        $byStatus = Project::where('company_id', $company->id)
            ->selectRaw('status, count(*) as c')->groupBy('status')
            ->pluck('c', 'status')->toArray();

        $paid = ProjectPayment::whereIn('project_id', $projectIds)->where('status', 'paid')->sum('amount');
        $pendingPay = ProjectPayment::whereIn('project_id', $projectIds)->where('status', 'pending')->sum('amount');

        $now = now();
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i)->startOfMonth();
            $months[$m->format('Y-m')] = 0.0;
        }
        $earliest = $now->copy()->subMonths(5)->startOfMonth();
        ProjectPayment::whereIn('project_id', $projectIds)->where('status', 'paid')
            ->where(fn ($q) => $q->where('paid_at', '>=', $earliest)
                ->orWhere(fn ($q2) => $q2->whereNull('paid_at')->where('created_at', '>=', $earliest)))
            ->get(['amount', 'paid_at', 'created_at'])
            ->each(function ($p) use (&$months) {
                $k = optional($p->paid_at ?? $p->created_at)->format('Y-m');
                if ($k && isset($months[$k])) {
                    $months[$k] += (float) $p->amount;
                }
            });

        $milestones = ProjectMilestone::whereIn('project_id', $projectIds)->get();

        return [
            'company' => $company->company_name,
            'generated_at' => $now->toDateString(),
            'projects' => [
                'total' => (int) $projectIds->count(),
                'by_status' => $byStatus,
                'avg_progress' => (float) round((float) Project::where('company_id', $company->id)->avg('progress'), 1),
                'total_budget' => (float) Project::where('company_id', $company->id)->sum('budget'),
            ],
            'milestones' => [
                'total' => $milestones->count(),
                'completed' => $milestones->where('status', 'completed')->count(),
                'overdue' => $milestones->filter(fn ($m) => $m->status !== 'completed' && $m->due_date && $m->due_date->isPast())->count(),
                'due_next_30d' => $milestones->filter(fn ($m) => $m->status !== 'completed' && $m->due_date
                    && $m->due_date->between($now, $now->copy()->addDays(30)))->count(),
            ],
            'payments' => [
                'total_paid' => (float) $paid,
                'outstanding' => (float) $pendingPay,
                'last_6_months' => $months,
            ],
            'estimates' => [
                'awaiting_response' => Estimate::where('company_id', $company->id)->where('status', 'sent')->count(),
                'approved' => Estimate::where('company_id', $company->id)->where('status', 'approved')->count(),
                'open_value' => (float) Estimate::where('company_id', $company->id)->whereIn('status', ['sent', 'approved'])->sum('total'),
            ],
            'quotations' => [
                'awaiting_response' => Quotation::where('company_id', $company->id)->where('status', 'quoted')->count(),
                'accepted' => Quotation::where('company_id', $company->id)->where('status', 'accepted')->count(),
                'quoted_value' => (float) Quotation::where('company_id', $company->id)->where('status', 'quoted')->sum('quoted_amount'),
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'SYS'
        You are a client-success analyst writing an upbeat, reassuring account summary for a
        client viewing their own agency portal. You are given a JSON snapshot of their
        projects, milestones, payments, estimates and quotations.

        Tone: positive, encouraging and confidence-building. Lead with progress and wins.
        The client should finish reading feeling good about the engagement.
        - Frame anything unfinished as a normal next step, an "opportunity", or something
          "on track", never as a problem, risk, warning or failure.
        - Do NOT use the words risk, warning, concern, overdue, stalled, behind, or failure.
        - An outstanding balance is simply "the remaining scheduled payment" — mention it
          neutrally and briefly, never as a worry.
        - Still be accurate: use the real numbers from the snapshot, never invent data.
        - No legal or tax advice.

        Respond with ONLY a JSON object, no prose or code fences, in this exact shape:
        {
          "headline": "one upbeat sentence, <= 90 chars",
          "summary": "2-4 sentence positive overview",
          "sections": [
            { "title": "string", "body": "1-3 encouraging sentences", "sentiment": "positive" | "neutral" }
          ]
        }
        Provide 3 to 4 sections covering delivery progress, payments & budget, and
        estimates & quotations. Use sentiment "positive" wherever it is reasonable;
        "neutral" only for a plain factual note. Never use any other sentiment value.
        SYS;
    }

    private function generate(company $company, array $metrics, string $digest): AccountInsight
    {
        $driver = $this->driver();
        if ($driver === null) {
            throw new RuntimeException('AI analysis is not configured. Set OPENROUTER_API_KEY or ANTHROPIC_API_KEY.');
        }

        $system = $this->systemPrompt();
        $userContent = "Account snapshot:\n\n" . json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        [$text, $model] = $driver === 'openrouter'
            ? $this->callOpenRouter($system, $userContent)
            : $this->callAnthropic($system, $userContent);

        $parsed = $this->parseJson($text);

        return AccountInsight::create([
            'company_id' => $company->id,
            'headline' => mb_substr((string) ($parsed['headline'] ?? 'Account overview'), 0, 240),
            'summary' => (string) ($parsed['summary'] ?? $text),
            'sections' => $this->normaliseSections($parsed['sections'] ?? []),
            'metrics' => $metrics,
            'model' => $model,
            'input_digest' => $digest,
            'generated_by' => auth()->user()?->name,
        ]);
    }

    /**
     * @return array{0:string,1:string} [responseText, modelId]
     */
    private function callOpenRouter(string $system, string $userContent): array
    {
        $model = config('services.openrouter.model', 'anthropic/claude-sonnet-5');

        try {
            $response = Http::withToken(config('services.openrouter.key'))
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name') . ' — Client Portal',
                ])
                // Ship a known-good CA bundle so verification works on hosts
                // (e.g. Windows) whose PHP has no curl.cainfo configured.
                ->withOptions(['verify' => \Composer\CaBundle\CaBundle::getBundledCaBundlePath()])
                ->timeout(60)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 1500,
                    'temperature' => 0.4,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('The AI service could not be reached: ' . $e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('The AI service returned an error: ' . mb_substr((string) $msg, 0, 200));
        }

        $text = (string) $response->json('choices.0.message.content', '');
        if ($text === '') {
            throw new RuntimeException('The AI service returned an empty response.');
        }

        return [$text, $model];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function callAnthropic(string $system, string $userContent): array
    {
        $model = config('services.anthropic.model', 'claude-opus-5');
        $client = new Client(apiKey: config('services.anthropic.key'));

        try {
            $message = $client->messages->create(
                maxTokens: 1500,
                model: $model,
                system: $system,
                messages: [
                    ['role' => 'user', 'content' => $userContent],
                ],
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('The AI service could not be reached: ' . $e->getMessage(), 0, $e);
        }

        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        return [$text, $model];
    }

    private function parseJson(string $text): array
    {
        $text = trim($text);
        // Strip ```json ... ``` fences if the model added them.
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fall back to the first {...} block.
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('The AI response could not be parsed.');
    }

    private function normaliseSections($sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $allowed = ['positive', 'neutral', 'watch'];

        return collect($sections)
            ->filter(fn ($s) => is_array($s) && filled($s['title'] ?? null))
            ->map(fn ($s) => [
                'title' => (string) $s['title'],
                'body' => (string) ($s['body'] ?? ''),
                'sentiment' => in_array($s['sentiment'] ?? 'neutral', $allowed, true) ? $s['sentiment'] : 'neutral',
            ])
            ->values()->all();
    }
}

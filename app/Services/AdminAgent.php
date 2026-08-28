<?php

namespace App\Services;

use App\Models\AttendanceAppeal;
use App\Models\AttendanceRecord;
use App\Models\Contact;
use App\Models\company;
use App\Models\deal;
use App\Models\Project;
use App\Models\staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Natural-language admin assistant. The admin types an instruction; the LLM
 * plans and calls the tools below to create projects / staff / client accounts,
 * assign teams, set progress, and mark attendance. Read-only helpers let it
 * resolve names first. Nothing is ever deleted.
 */
class AdminAgent
{
    /** @var array<int,array{role:string,text:string}> */
    public array $log = [];

    public function configured(): bool
    {
        return filled(config('services.openrouter.key')) || filled(config('services.anthropic.key'));
    }

    /**
     * @param array<int,array{role:string,content:string}> $priorTurns  earlier user/assistant turns for context
     */
    public function run(string $instruction, array $priorTurns = []): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('AI is not configured. Set OPENROUTER_API_KEY.');
        }

        $messages = [['role' => 'system', 'content' => $this->systemPrompt()]];
        foreach (array_slice($priorTurns, -6) as $t) {
            if (in_array($t['role'] ?? '', ['user', 'assistant'], true) && filled($t['content'] ?? '')) {
                $messages[] = ['role' => $t['role'], 'content' => (string) $t['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $instruction];

        for ($i = 0; $i < 6; $i++) {
            $reply = $this->chat($messages);

            $calls = $reply['tool_calls'] ?? [];

            // Echo the assistant turn back in the exact shape the API expects.
            $assistant = ['role' => 'assistant', 'content' => $reply['content'] !== '' ? $reply['content'] : null];
            if (! empty($calls)) {
                $assistant['tool_calls'] = $calls;
            }
            $messages[] = $assistant;

            if (empty($calls)) {
                return trim((string) ($reply['content'] ?? 'Done.'));
            }

            foreach ($calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $result = $this->dispatch($name, $args);
                $this->log[] = ['role' => 'tool', 'text' => $name . '(' . json_encode($args) . ') → ' . $result];
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? Str::uuid()->toString(),
                    // Keep tool output compact so it doesn't balloon the next request.
                    'content' => Str::limit($result, 800),
                ];
            }
        }

        return 'Stopped after several steps. ' . collect($this->log)->pluck('text')->last();
    }

    // ---------------------------------------------------------------- LLM call

    private function chat(array $messages, ?int $maxTokens = null): array
    {
        $maxTokens ??= max(200, (int) config('services.openrouter.max_tokens', 700));
        $useOpenRouter = filled(config('services.openrouter.key'));
        $model = $useOpenRouter
            ? config('services.openrouter.model', 'anthropic/claude-sonnet-5')
            : config('services.anthropic.model', 'claude-sonnet-5');

        $http = Http::withToken(
            $useOpenRouter ? config('services.openrouter.key') : config('services.anthropic.key')
        )->timeout(90);

        if (class_exists(\Composer\CaBundle\CaBundle::class)) {
            $http = $http->withOptions(['verify' => \Composer\CaBundle\CaBundle::getBundledCaBundlePath()]);
        }

        $endpoint = $useOpenRouter
            ? 'https://openrouter.ai/api/v1/chat/completions'
            : 'https://api.anthropic.com/v1/chat/completions'; // OpenRouter path only officially; anthropic direct kept as fallback shape

        $resp = $http->post($endpoint, [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => 0.2,
            'messages' => $messages,
            'tools' => $this->toolSchema(),
        ]);

        if ($resp->failed()) {
            $err = (string) ($resp->json('error.message') ?? $resp->body());

            // OpenRouter reserves credit against max_tokens before it runs the
            // request. When the balance can't cover the reservation it tells us
            // exactly how many tokens are affordable — retry once, smaller, so a
            // low balance still produces a usable reply instead of a hard error.
            if (preg_match('/can only afford (\d+)/i', $err, $m)) {
                $afford = (int) $m[1] - 48;
                if ($afford >= 120 && $afford < $maxTokens) {
                    return $this->chat($messages, $afford);
                }
            }
            if (stripos($err, 'more credits') !== false && $maxTokens > 256) {
                return $this->chat($messages, 256);
            }

            throw new RuntimeException('AI error: ' . mb_substr($err, 0, 200));
        }

        $msg = $resp->json('choices.0.message', []);
        return [
            'role' => 'assistant',
            'content' => $msg['content'] ?? '',
            'tool_calls' => $msg['tool_calls'] ?? [],
        ];
    }

    // ---------------------------------------------------------------- tools

    private function dispatch(string $name, array $a): string
    {
        try {
            return match ($name) {
                'list_projects' => 'Projects: ' . (Project::orderBy('name')->pluck('name')->implode(', ') ?: 'none'),
                'list_staff' => 'Staff: ' . staff::orderBy('name')->get()->map(fn ($s) => $s->name . ' (' . $s->designation . ')')->implode(', '),
                'list_companies' => 'Companies: ' . company::orderBy('company_name')->pluck('company_name')->implode(', '),
                'list_deals' => 'Deals: ' . (deal::orderByDesc('id')->limit(30)->get()->map(fn ($d) => $d->deal_name . ' [' . $d->deal_stage . '/' . ($d->deal_status ?: 'open') . '] ' . number_format((float) $d->deal_value)) ->implode('; ') ?: 'none'),
                'attendance_status' => $this->attendanceStatus($a),
                'project_details' => $this->projectDetails($a),
                'pending_appeals' => $this->pendingAppeals(),
                'create_project' => $this->createProject($a),
                'update_project' => $this->updateProject($a),
                'create_staff' => $this->createStaff($a),
                'create_client' => $this->createClient($a),
                'create_company' => $this->createCompany($a),
                'create_deal' => $this->createDeal($a),
                'assign_staff_to_project' => $this->assignStaff($a),
                'set_project_progress' => $this->setProgress($a),
                'mark_attendance' => $this->markAttendance($a),
                'mark_all_attendance' => $this->markAllAttendance($a),
                'approve_appeal' => $this->approveAppeal($a),
                default => 'Unknown tool: ' . $name,
            };
        } catch (\Throwable $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function resolveCompany(?string $name): ?company
    {
        if (! $name) {
            return null;
        }
        return company::where('company_name', 'like', '%' . $name . '%')->first()
            ?? company::create(['company_name' => $name, 'status' => 'active']);
    }

    private function resolveProject(string $name): Project
    {
        $p = Project::where('name', 'like', '%' . $name . '%')->first();
        if (! $p) {
            throw new RuntimeException('No project matching "' . $name . '".');
        }
        return $p;
    }

    private function resolveStaff(string $name): staff
    {
        $s = staff::where('name', 'like', '%' . $name . '%')->first();
        if (! $s) {
            throw new RuntimeException('No staff matching "' . $name . '".');
        }
        return $s;
    }

    private function createProject(array $a): string
    {
        $name = trim($a['name'] ?? '');
        if ($name === '') {
            return 'Failed: project name is required.';
        }
        $company = $this->resolveCompany($a['company_name'] ?? null);

        $p = Project::create([
            'name' => $name,
            'company_id' => $company?->id,
            'description' => $a['description'] ?? null,
            'start_date' => $a['start_date'] ?? null,
            'end_date' => $a['end_date'] ?? null,
            'status' => in_array($a['status'] ?? '', ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'], true) ? $a['status'] : 'planning',
            'progress' => 0,
            'budget' => isset($a['budget']) ? (float) $a['budget'] : null,
            'created_by' => auth()->id(),
        ]);

        return 'Created project #' . $p->id . ' "' . $p->name . '"' . ($company ? ' for ' . $company->company_name : '') . '.';
    }

    private function createStaff(array $a): string
    {
        $name = trim($a['name'] ?? '');
        $email = trim($a['email'] ?? '');
        if ($name === '' || $email === '') {
            return 'Failed: staff name and email are required.';
        }
        if (staff::where('email', $email)->exists() || User::where('email', $email)->exists()) {
            return 'Failed: a staff member or user with ' . $email . ' already exists.';
        }

        $password = Str::password(12);
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => 'staff']);

        $member = staff::create([
            'name' => $name,
            'email' => $email,
            'user_id' => $user->id,
            'designation' => $a['designation'] ?? 'Staff',
            'employment_type' => in_array($a['employment_type'] ?? '', ['full_time', 'intern', 'contract'], true) ? $a['employment_type'] : 'full_time',
            'shift_start' => $a['shift_start'] ?? '09:00',
            'daily_hours' => (int) ($a['daily_hours'] ?? 8),
            'joining_date' => $a['joining_date'] ?? now()->toDateString(),
            'status' => 'active',
        ]);

        $extra = '';
        if (! empty($a['project_name'])) {
            $proj = $this->resolveProject($a['project_name']);
            $proj->staff()->syncWithoutDetaching([$member->id]);
            $extra = ' Added to project "' . $proj->name . '".';
        }

        return 'Created staff "' . $name . '" (' . $member->designation . ') with a login. Temp password: ' . $password . '.' . $extra;
    }

    private function createClient(array $a): string
    {
        $name = trim($a['name'] ?? '');
        $email = trim($a['email'] ?? '');
        if ($name === '' || $email === '') {
            return 'Failed: client name and email are required.';
        }
        if (User::where('email', $email)->exists()) {
            return 'Failed: a user with ' . $email . ' already exists.';
        }

        $company = $this->resolveCompany($a['company_name'] ?? null);
        if (! $company) {
            return 'Failed: a company name is required for a client account.';
        }

        $password = Str::password(12);
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => 'client']);

        $parts = explode(' ', $name, 2);
        Contact::create([
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? '',
            'email' => $email,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return 'Created client "' . $name . '" for ' . $company->company_name . ' with portal access. Temp password: ' . $password . '.';
    }

    private function assignStaff(array $a): string
    {
        $s = $this->resolveStaff($a['staff_name'] ?? '');
        $p = $this->resolveProject($a['project_name'] ?? '');
        $p->staff()->syncWithoutDetaching([$s->id]);
        return $s->name . ' assigned to "' . $p->name . '".';
    }

    private function setProgress(array $a): string
    {
        $p = $this->resolveProject($a['project_name'] ?? '');
        $pct = max(0, min(100, (int) ($a['percent'] ?? 0)));
        $p->update(['progress' => $pct]);
        $p->syncStatusToProgress();
        return '"' . $p->name . '" progress set to ' . $pct . '% (status: ' . $p->fresh()->status . ').';
    }

    private function attendanceStatus(array $a): string
    {
        $date = $a['date'] ?? now()->toDateString();
        $recs = AttendanceRecord::staff()->forDate($date)->get()->keyBy('person_id');
        $lines = staff::orderBy('name')->get()->map(function ($s) use ($recs, $date) {
            $r = $recs->get($s->id);
            $state = $date === now()->toDateString() ? AttendanceRecord::presenceState($s->id)['state'] : '-';
            return $s->name . ': ' . ($r->status ?? 'not marked') . ' (' . $state . ', ' . ($r?->activeHhMm() ?? '0m') . ' active)';
        });
        return 'Attendance ' . $date . ' — ' . $lines->implode('; ');
    }

    private function projectDetails(array $a): string
    {
        $p = $this->resolveProject($a['name'] ?? $a['project_name'] ?? '');
        $team = $p->staff()->pluck('name')->implode(', ') ?: 'none';
        return sprintf(
            '%s — status %s, %d%% progress, budget %s, company %s, team: %s, %d milestones, %d payments.',
            $p->name, $p->status, (int) $p->progress,
            $p->budget !== null ? number_format((float) $p->budget) : 'n/a',
            $p->company->company_name ?? 'n/a', $team,
            $p->milestones()->count(), $p->payments()->count()
        );
    }

    private function pendingAppeals(): string
    {
        $rows = AttendanceAppeal::pending()->with('staff:id,name')->get();
        if ($rows->isEmpty()) {
            return 'No pending absence appeals.';
        }
        return 'Pending appeals: ' . $rows->map(fn ($r) => ($r->staff->name ?? 'staff') . ' for ' . $r->date . ' — "' . $r->message . '"')->implode('; ');
    }

    private function updateProject(array $a): string
    {
        $p = $this->resolveProject($a['name'] ?? $a['project_name'] ?? '');
        $fields = [];
        foreach (['description', 'start_date', 'end_date', 'budget', 'status', 'submission_due_at'] as $f) {
            if (array_key_exists($f, $a) && $a[$f] !== null && $a[$f] !== '') {
                $fields[$f] = $f === 'budget' ? (float) $a[$f] : $a[$f];
            }
        }
        if (isset($a['new_name'])) {
            $fields['name'] = $a['new_name'];
        }
        if (! $fields) {
            return 'Nothing to update.';
        }
        $p->update($fields);
        return 'Updated "' . $p->name . '": ' . implode(', ', array_keys($fields)) . '.';
    }

    private function createCompany(array $a): string
    {
        $name = trim($a['name'] ?? $a['company_name'] ?? '');
        if ($name === '') {
            return 'Failed: company name is required.';
        }
        $c = company::firstOrCreate(['company_name' => $name], [
            'company_email' => $a['email'] ?? null,
            'company_phone' => $a['phone'] ?? null,
            'company_industry' => $a['industry'] ?? null,
            'status' => 'active',
        ]);
        return ($c->wasRecentlyCreated ? 'Created' : 'Already have') . ' company "' . $c->company_name . '".';
    }

    private function createDeal(array $a): string
    {
        $name = trim($a['name'] ?? $a['deal_name'] ?? '');
        if ($name === '') {
            return 'Failed: deal name is required.';
        }
        $company = $this->resolveCompany($a['company_name'] ?? null);
        $d = deal::create([
            'deal_name' => $name,
            'deal_value' => (float) ($a['value'] ?? 0),
            'currency' => $a['currency'] ?? 'USD',
            'deal_stage' => in_array($a['stage'] ?? '', ['lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'], true) ? $a['stage'] : 'lead',
            'deal_status' => 'open',
            'expected_close_date' => $a['expected_close_date'] ?? null,
            'company_id' => $company?->id,
            'created_by' => auth()->id(),
        ]);
        return 'Created deal "' . $d->deal_name . '"' . ($company ? ' for ' . $company->company_name : '') . ' (stage: ' . $d->deal_stage . ').';
    }

    private function approveAppeal(array $a): string
    {
        $s = $this->resolveStaff($a['staff_name'] ?? '');
        $date = $a['date'] ?? now()->toDateString();
        $appeal = AttendanceAppeal::where('staff_id', $s->id)->where('date', $date)->where('status', 'pending')->latest()->first();
        if (! $appeal) {
            return 'No pending appeal for ' . $s->name . ' on ' . $date . '.';
        }
        $rec = AttendanceRecord::firstOrNew(['person_type' => 'staff', 'person_id' => $s->id, 'date' => $date]);
        $full = ($s->daily_hours ?? 8) * 60;
        $rec->fill(['status' => 'present', 'source' => 'manual', 'recorded_by' => auth()->id()]);
        $rec->worked_minutes = $rec->worked_minutes ?: $full;
        $rec->active_minutes = $rec->active_minutes ?: $full;
        $rec->save();
        $appeal->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return $s->name . ' appeal approved — marked present for ' . $date . '.';
    }

    private function markAttendance(array $a): string
    {
        $s = $this->resolveStaff($a['staff_name'] ?? '');
        $status = strtolower($a['status'] ?? '');
        if (! in_array($status, AttendanceRecord::STATUSES, true)) {
            return 'Failed: status must be one of ' . implode(', ', AttendanceRecord::STATUSES) . '.';
        }
        $date = $a['date'] ?? now()->toDateString();

        $rec = AttendanceRecord::updateOrCreate(
            ['person_type' => 'staff', 'person_id' => $s->id, 'date' => $date],
            ['status' => $status, 'source' => 'manual', 'recorded_by' => auth()->id(), 'note' => $a['note'] ?? null]
        );
        $rec->recomputeWorkedMinutes();
        $rec->save();

        return $s->name . ' marked ' . $status . ' for ' . $date . '.';
    }

    /**
     * Mark every active staff member the same status in one shot — one tool call
     * instead of one per person, which keeps the assistant well inside its token
     * budget for "mark everyone present" style requests.
     */
    private function markAllAttendance(array $a): string
    {
        $status = strtolower($a['status'] ?? '');
        if (! in_array($status, AttendanceRecord::STATUSES, true)) {
            return 'Failed: status must be one of ' . implode(', ', AttendanceRecord::STATUSES) . '.';
        }
        $date = $a['date'] ?? now()->toDateString();

        $members = staff::where('status', 'active')->with('user:id,role')->get()
            ->reject(fn ($s) => $s->user && $s->user->role === 'admin'); // leader isn't tracked
        foreach ($members as $s) {
            $rec = AttendanceRecord::updateOrCreate(
                ['person_type' => 'staff', 'person_id' => $s->id, 'date' => $date],
                ['status' => $status, 'source' => 'manual', 'recorded_by' => auth()->id(), 'note' => $a['note'] ?? null]
            );
            $rec->recomputeWorkedMinutes();
            $rec->save();
        }

        return 'Marked ' . $members->count() . ' active staff ' . $status . ' for ' . $date . '.';
    }

    // ---------------------------------------------------------------- schemas

    private function toolSchema(): array
    {
        $fn = fn ($name, $desc, $props, $required = []) => [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $desc,
                // properties MUST serialise as a JSON object, even when empty
                'parameters' => ['type' => 'object', 'properties' => (object) $props, 'required' => $required],
            ],
        ];

        $str = ['type' => 'string'];
        $int = ['type' => 'integer'];

        return [
            $fn('list_projects', 'List all project names.', []),
            $fn('list_staff', 'List all staff with designations.', []),
            $fn('list_companies', 'List all client company names.', []),
            $fn('list_deals', 'List recent deals with stage, status and value.', []),
            $fn('attendance_status', "Today's attendance + live presence (online/inactive/offline) + active hours for every staff member.", [
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, defaults today'],
            ]),
            $fn('project_details', 'Full detail for one project: status, progress, budget, company, team, milestones, payments.', [
                'name' => $str,
            ], ['name']),
            $fn('pending_appeals', 'List staff absence appeals awaiting review.', []),
            $fn('update_project', 'Update fields on an existing project.', [
                'name' => $str, 'new_name' => $str, 'description' => $str,
                'start_date' => $str, 'end_date' => $str, 'budget' => ['type' => 'number'],
                'status' => ['type' => 'string', 'enum' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']],
                'submission_due_at' => ['type' => 'string', 'description' => 'YYYY-MM-DDTHH:MM'],
            ], ['name']),
            $fn('create_company', 'Create a client company.', [
                'name' => $str, 'email' => $str, 'phone' => $str, 'industry' => $str,
            ], ['name']),
            $fn('create_deal', 'Create a sales deal.', [
                'name' => $str, 'company_name' => $str, 'value' => ['type' => 'number'],
                'stage' => ['type' => 'string', 'enum' => ['lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost']],
                'expected_close_date' => $str,
            ], ['name']),
            $fn('approve_appeal', 'Approve a pending absence appeal — marks the staff member present.', [
                'staff_name' => $str, 'date' => $str,
            ], ['staff_name']),
            $fn('create_project', 'Create a project.', [
                'name' => $str, 'company_name' => $str, 'description' => $str,
                'start_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'budget' => ['type' => 'number'],
                'status' => ['type' => 'string', 'enum' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']],
            ], ['name']),
            $fn('create_staff', 'Create a staff member with a login account.', [
                'name' => $str, 'email' => $str, 'designation' => $str,
                'employment_type' => ['type' => 'string', 'enum' => ['full_time', 'intern', 'contract']],
                'shift_start' => ['type' => 'string', 'description' => 'HH:MM'],
                'daily_hours' => $int, 'joining_date' => $str, 'project_name' => $str,
            ], ['name', 'email']),
            $fn('create_client', 'Create a client portal account tied to a company.', [
                'name' => $str, 'email' => $str, 'company_name' => $str, 'project_name' => $str,
            ], ['name', 'email', 'company_name']),
            $fn('assign_staff_to_project', 'Assign an existing staff member to a project team.', [
                'staff_name' => $str, 'project_name' => $str,
            ], ['staff_name', 'project_name']),
            $fn('set_project_progress', 'Set a project completion percentage (0-100).', [
                'project_name' => $str, 'percent' => $int,
            ], ['project_name', 'percent']),
            $fn('mark_attendance', 'Mark ONE staff member present/absent/late/leave/remote/half_day for a day.', [
                'staff_name' => $str,
                'status' => ['type' => 'string', 'enum' => AttendanceRecord::STATUSES],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, defaults to today'],
                'note' => $str,
            ], ['staff_name', 'status']),
            $fn('mark_all_attendance', 'Mark EVERY active staff member the same status for a day in a single step. Use this for "mark all present", "everyone absent", etc. — never loop mark_attendance.', [
                'status' => ['type' => 'string', 'enum' => AttendanceRecord::STATUSES],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, defaults to today'],
                'note' => $str,
            ], ['status']),
        ];
    }

    private function systemPrompt(): string
    {
        $today = now()->toDateString();

        return <<<SYS
        You are the operations assistant for an agency CRM/ERP, acting on behalf of the
        admin. You can run the whole back office through the provided tools.

        CAPABILITIES (via tools):
        - Read: list_projects, list_staff, list_companies, list_deals, attendance_status,
          project_details, pending_appeals.
        - Projects: create_project, update_project, set_project_progress,
          assign_staff_to_project.
        - People: create_staff (makes a login), create_client (portal account + contact),
          create_company.
        - Sales: create_deal.
        - Attendance: mark_attendance (one person), mark_all_attendance (everyone at
          once), approve_appeal. Statuses: present|late|remote|half_day|leave|absent|holiday.

        RULES:
        - Today is $today. Dates are YYYY-MM-DD; datetimes YYYY-MM-DDTHH:MM. Default to today.
        - When a name is ambiguous, call a list_/details tool first; match loosely.
        - Act directly — never ask the admin to confirm. Do every part of the request.
        - To set the same attendance for the whole team, call mark_all_attendance ONCE.
          Never loop mark_attendance person by person.
        - You CANNOT delete or deactivate anything. Only create / assign / update / mark.
        - If a tool returns "Failed: ...", report that plainly; don't retry blindly.
        - Include any generated temp password in your final summary.
        - Keep every reply brief. Final reply: a short markdown bullet list of exactly
          what you did (and any failures) — no preamble, no restating the request.
        SYS;
    }
}

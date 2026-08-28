<?php

namespace App\Support;

use App\Models\Estimate;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Notifier;

/**
 * Wires model changes made in the admin/staff panel to client-portal
 * notifications (and vice-versa for the chat). Registered once from
 * AppServiceProvider::boot(). Every alert carries a deep link so the popup /
 * bell entry opens the exact thing that changed.
 */
class AlertHooks
{
    public static function register(): void
    {
        // --- Project progress / status --------------------------------------
        Project::updated(function (Project $p) {
            $actor = auth()->user();
            [$staff, $clients] = Notifier::projectAudience($p);
            $url = route('client.project-show', $p->id);

            if ($p->wasChanged('progress')) {
                Notifier::push($clients, "Progress updated — {$p->name}", [
                    'body' => "Now {$p->progress}% complete.",
                    'url' => $url, 'icon' => 'fa-bars-progress', 'level' => 'info', 'actor' => $actor,
                ]);
            }
            if ($p->wasChanged('status')) {
                $done = in_array($p->status, ['completed', 'cancelled'], true);
                Notifier::push($clients, "Project {$p->status} — {$p->name}", [
                    'body' => $done ? 'Your account manager marked this project ' . $p->status . '.' : "Status changed to {$p->status}.",
                    'url' => $url, 'icon' => 'fa-diagram-project',
                    'level' => $done ? 'success' : 'info', 'actor' => $actor,
                ]);
            }
        });

        // --- Milestone completed ------------------------------------------------
        ProjectMilestone::updated(function (ProjectMilestone $m) {
            if (! $m->wasChanged('status') || $m->status !== 'completed') {
                return;
            }
            $p = $m->project;
            if (! $p) {
                return;
            }
            [, $clients] = Notifier::projectAudience($p);
            Notifier::push($clients, "Milestone completed — {$m->title}", [
                'body' => "on {$p->name}.",
                'url' => route('client.project-show', $p->id),
                'icon' => 'fa-flag-checkered', 'level' => 'success', 'actor' => auth()->user(),
            ]);
        });

        // --- Payments --------------------------------------------------------
        $paymentAlert = function (ProjectPayment $pay, string $title) {
            $p = $pay->project;
            if (! $p) {
                return;
            }
            [, $clients] = Notifier::projectAudience($p);
            Notifier::push($clients, $title, [
                'body' => "{$pay->currency} " . number_format((float) $pay->amount, 2) . " · {$p->name}",
                'url' => route('client.payments'),
                'icon' => 'fa-credit-card', 'level' => $pay->status === 'paid' ? 'success' : 'info',
                'actor' => auth()->user(),
            ]);
        };
        ProjectPayment::created(fn (ProjectPayment $pay) => $paymentAlert(
            $pay, $pay->status === 'paid' ? 'Payment recorded' : 'New payment raised'
        ));
        ProjectPayment::updated(function (ProjectPayment $pay) use ($paymentAlert) {
            if ($pay->wasChanged('status') && $pay->status === 'paid') {
                $paymentAlert($pay, 'Payment received');
            }
        });

        // --- Project chat: notify the other side of a new message ----------
        ProjectMessage::created(function (ProjectMessage $msg) {
            if ($msg->author_role === 'system') {
                return; // system posts raise their own alerts via EventNotifier
            }
            $p = Project::find($msg->project_id);
            if (! $p) {
                return;
            }
            [$staff, $clients, $admins] = Notifier::projectAudience($p);
            $author = User::find($msg->user_id);
            $recipients = $staff->merge($admins);
            if ($msg->author_role !== 'client') {
                $recipients = $recipients->merge($clients);
            }
            Notifier::push($recipients, "New message — {$p->name}", [
                'body' => ($author?->name ?? 'Someone') . ': ' . \Illuminate\Support\Str::limit($msg->body, 90),
                'url' => route('client.project-show', $p->id),
                'icon' => 'fa-comment-dots', 'level' => 'info', 'actor' => $author,
                'dedupe_minutes' => 0,
            ]);
        });

        // --- Estimates / Quotations status ---------------------------------
        Estimate::updated(function (Estimate $e) {
            if (! $e->wasChanged('status')) {
                return;
            }
            Notifier::push(Notifier::companyClients($e->company_id), "Estimate {$e->status} — {$e->estimate_number}", [
                'url' => route('client.estimate-show', $e->id),
                'icon' => 'fa-file-invoice', 'level' => $e->status === 'approved' ? 'success' : 'info',
                'actor' => auth()->user(),
            ]);
        });
        Quotation::updated(function (Quotation $q) {
            if (! $q->wasChanged('status')) {
                return;
            }
            Notifier::push(Notifier::companyClients($q->company_id), "Quotation {$q->status}", [
                'body' => $q->service_interest ?: null,
                'url' => route('client.quotation-show', $q->id),
                'icon' => 'fa-file-signature', 'level' => $q->status === 'accepted' ? 'success' : 'info',
                'actor' => auth()->user(),
            ]);
        });
    }
}

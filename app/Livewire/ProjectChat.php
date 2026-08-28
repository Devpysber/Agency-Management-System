<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\staff;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shared project conversation — admin, assigned staff and the client of the
 * project's company all talk in one thread. Embedded on both the client and
 * admin project detail screens via <livewire:project-chat :project="$project" />.
 *
 * Live updates are handled with wire:poll (SMTP / real-time push come later).
 */
class ProjectChat extends Component
{
    public int $projectId;
    public string $companyName = '';
    public string $body = '';
    public bool $canView = false;
    public bool $canPost = false;

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
        $this->companyName = $project->company?->company_name ?? 'this project';

        $user = auth()->user();
        $this->canView = $this->participates($project, strict: false);
        $this->canPost = $this->participates($project, strict: true);
    }

    /**
     * strict=true  -> may post (admin, client of the company, assigned staff)
     * strict=false -> may read (also any other agency staff member)
     */
    public bool $locked = false;

    private function participates(Project $project, bool $strict): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role === 'client') {
            $ofCompany = $user->contact?->company_id === $project->company_id;
            if (! $ofCompany) {
                return false;
            }
            // Once the project is closed, the client can read the thread but not post.
            if ($strict && in_array($project->status, ['completed', 'cancelled'], true)) {
                $this->locked = true;
                return false;
            }
            return true;
        }

        // Agency staff — always keep posting, even after the project closes.
        $staffId = staff::where('user_id', $user->id)->value('id');
        if ($staffId && $project->staff()->where('staff.id', $staffId)->exists()) {
            return true;
        }

        return ! $strict; // non-assigned staff may still read
    }

    #[Computed]
    public function thread()
    {
        return ProjectMessage::with('user:id,name,role')
            ->where('project_id', $this->projectId)
            ->oldest()
            ->limit(300)
            ->get();
    }

    public function send(): void
    {
        $data = $this->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $project = Project::findOrFail($this->projectId);
        if (! $this->participates($project, strict: true)) {
            abort(403);
        }

        ProjectMessage::create([
            'project_id' => $this->projectId,
            'user_id' => auth()->id(),
            'author_role' => auth()->user()->role,
            'body' => trim($data['body']),
        ]);

        $this->body = '';
        unset($this->thread);
        $this->dispatch('chat-message-sent');
    }

    public function render()
    {
        // Recompute each render so a status change (project closed) locks the
        // composer within one poll cycle without a page reload.
        $project = Project::find($this->projectId);
        if ($project) {
            $this->locked = false;
            $this->canView = $this->participates($project, strict: false);
            $this->canPost = $this->participates($project, strict: true);
        }

        return view('livewire.project-chat');
    }
}

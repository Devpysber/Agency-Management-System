<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contact;
use App\Models\company;
use App\Models\deal;
use App\Models\Project;
use App\Models\staff;
use App\Models\Task;

class GlobalSearch extends Component
{
    public string $query = '';
    public bool $isOpen = false;

    protected $listeners = ['openGlobalSearch' => 'open'];

    public function open(): void
    {
        $this->isOpen = true;
        $this->query = '';
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    public function render()
    {
        $results = [
            'contacts' => collect(),
            'companies' => collect(),
            'deals' => collect(),
            'projects' => collect(),
            'tasks' => collect(),
            'staff' => collect(),
        ];

        $q = trim($this->query);
        if (strlen($q) >= 2) {
            $user = auth()->user();
            $isAdmin = $user && $user->role === 'admin';

            $results['contacts'] = Contact::where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->take(5)->get();

            $results['companies'] = company::where('company_name', 'like', "%{$q}%")
                ->orWhere('company_email', 'like', "%{$q}%")
                ->take(5)->get();

            $results['deals'] = deal::where('deal_name', 'like', "%{$q}%")
                ->take(5)->get();

            $results['projects'] = Project::where('name', 'like', "%{$q}%")
                ->take(5)->get();

            $results['tasks'] = Task::where('title', 'like', "%{$q}%")
                ->take(5)->get();

            if ($isAdmin) {
                $results['staff'] = staff::where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('designation', 'like', "%{$q}%")
                    ->take(5)->get();
            }
        }

        $totalMatches = collect($results)->sum(fn ($items) => $items->count());

        return view('livewire.global-search', [
            'results' => $results,
            'totalMatches' => $totalMatches,
        ]);
    }
}

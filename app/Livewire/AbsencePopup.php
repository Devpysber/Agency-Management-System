<?php

namespace App\Livewire;

use App\Models\AttendanceAppeal;
use App\Models\AttendanceRecord;
use App\Models\staff;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shown to a staff member who was auto-marked absent today. Lets them appeal
 * to the CEO; the popup stays until the appeal is approved (record flips to
 * present) — a rejection lets them appeal again.
 */
class AbsencePopup extends Component
{
    public string $message = '';

    private function staffMember(): ?staff
    {
        return staff::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function state()
    {
        $user = auth()->user();
        // Never nag admins / the CEO — they resolve appeals, they don't file them.
        if (! $user || $user->role === 'admin') {
            return null;
        }

        $s = $this->staffMember();
        if (! $s || $s->designation === 'CEO') {
            return null;
        }

        $today = now()->toDateString();
        $rec = AttendanceRecord::where([
            'person_type' => 'staff', 'person_id' => $s->id, 'date' => $today,
        ])->first();

        if (! $rec || $rec->status !== 'absent') {
            return null;
        }

        return [
            'staff' => $s,
            'rec' => $rec,
            'appeal' => AttendanceAppeal::where('staff_id', $s->id)->where('date', $today)->latest()->first(),
        ];
    }

    public function appeal(): void
    {
        $st = $this->state;
        if (! $st) {
            return;
        }

        $this->validate(['message' => ['required', 'string', 'min:5', 'max:1000']]);

        AttendanceAppeal::create([
            'staff_id' => $st['staff']->id,
            'date' => now()->toDateString(),
            'message' => $this->message,
            'status' => 'pending',
        ]);

        $this->message = '';
        unset($this->state);
        $this->dispatch('toast', message: 'Appeal sent to the CEO');
    }

    public function render()
    {
        return view('livewire.absence-popup');
    }
}

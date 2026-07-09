<?php

namespace App\Livewire\Admin;

use App\Models\BlacklistedNumber;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class BlacklistManager extends Component
{
    use WithPagination;

    public string $phone = '';

    public string $reason = '';

    public function add(): void
    {
        $this->validate([
            'phone' => 'required|string|unique:blacklisted_numbers,phone',
            'reason' => 'nullable|string|max:255',
        ]);

        BlacklistedNumber::create([
            'phone' => $this->phone,
            'reason' => $this->reason ?: null,
            'blocked_at' => now(),
        ]);

        $this->reset(['phone', 'reason']);
    }

    public function remove(int $id): void
    {
        BlacklistedNumber::whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.admin.blacklist-manager', [
            'entries' => BlacklistedNumber::latest()->paginate(10),
        ]);
    }
}

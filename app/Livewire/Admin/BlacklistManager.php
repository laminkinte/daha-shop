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

    public string $email = '';

    public string $reason = '';

    /**
     * A blocked customer could sign up again with a different phone number
     * tied to the same email, or vice versa - blocking on whichever
     * identifier the admin actually has (phone, email, or both) closes
     * that gap instead of requiring both every time.
     */
    public function add(): void
    {
        $this->validate([
            'phone' => 'required_without:email|nullable|string|unique:blacklisted_numbers,phone',
            'email' => 'required_without:phone|nullable|string|email|unique:blacklisted_numbers,email',
            'reason' => 'nullable|string|max:255',
        ]);

        BlacklistedNumber::create([
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'reason' => $this->reason ?: null,
            'blocked_at' => now(),
        ]);

        $this->reset(['phone', 'email', 'reason']);
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

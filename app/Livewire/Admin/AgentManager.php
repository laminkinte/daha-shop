<?php

namespace App\Livewire\Admin;

use App\Enums\AgentAvailability;
use App\Enums\UserRole;
use App\Enums\VendorOrderStatus;
use App\Models\DeliveryAgent;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class AgentManager extends Component
{
    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public ?int $stateId = null;

    public ?int $lgaId = null;

    public string $vehicleType = 'motorcycle';

    public function getLgasProperty()
    {
        return $this->stateId ? State::find($this->stateId)?->lgas()->orderBy('name')->get() : collect();
    }

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
            'stateId' => 'required|exists:states,id',
            'lgaId' => 'required|exists:lgas,id',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'role' => UserRole::Agent,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        DeliveryAgent::create([
            'user_id' => $user->id,
            'state_id' => $this->stateId,
            'lga_id' => $this->lgaId,
            'vehicle_type' => $this->vehicleType,
            'availability' => AgentAvailability::Available,
        ]);

        $this->reset(['showForm', 'name', 'email', 'phone', 'password', 'stateId', 'lgaId']);
    }

    public function render()
    {
        $agents = DeliveryAgent::with('user', 'state', 'lga')
            ->withCount(['vendorOrders as delivered_count' => fn ($query) => $query->where('status', VendorOrderStatus::Delivered)])
            ->get();

        return view('livewire.admin.agent-manager', [
            'agents' => $agents,
            'states' => State::orderBy('name')->get(),
        ]);
    }
}

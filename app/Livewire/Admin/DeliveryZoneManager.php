<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\State;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class DeliveryZoneManager extends Component
{
    public bool $showForm = false;

    public ?int $stateId = null;

    public ?int $lgaId = null;

    public string $fee = '';

    public function getLgasProperty()
    {
        return $this->stateId ? State::find($this->stateId)?->lgas()->orderBy('name')->get() : collect();
    }

    public function create(): void
    {
        $this->validate([
            'stateId' => 'required|exists:states,id',
            'lgaId' => 'required|exists:lgas,id',
            'fee' => 'required|numeric|min:0',
        ]);

        $lga = \App\Models\Lga::find($this->lgaId);

        $zone = DeliveryZone::firstOrCreate([
            'state_id' => $this->stateId,
            'lga_id' => $this->lgaId,
        ], [
            'name' => $lga->name.' Zone',
        ]);

        DeliveryFee::updateOrCreate(
            ['delivery_zone_id' => $zone->id, 'vendor_id' => null],
            ['fee' => (int) round((float) $this->fee * 100)]
        );

        $this->reset(['showForm', 'stateId', 'lgaId', 'fee']);
    }

    public function render()
    {
        $zones = DeliveryZone::with('state', 'lga', 'fees')->orderBy('state_id')->get();

        return view('livewire.admin.delivery-zone-manager', [
            'zones' => $zones,
            'states' => State::orderBy('name')->get(),
        ]);
    }
}

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

    public function openCreateForm(): void
    {
        $this->reset(['stateId', 'lgaId', 'fee']);
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'stateId', 'lgaId', 'fee']);
    }

    /**
     * Pre-fills the form with an existing zone's state/LGA/fee and opens it -
     * create() already does an updateOrCreate on the fee, so re-submitting
     * the same state+LGA here updates it in place rather than duplicating.
     * There was previously no way to change a zone's fee once set without
     * going around the UI entirely.
     */
    public function edit(int $zoneId): void
    {
        $zone = DeliveryZone::with('fees')->findOrFail($zoneId);

        $this->stateId = $zone->state_id;
        $this->lgaId = $zone->lga_id;
        $this->fee = number_format(($zone->fees->firstWhere('vendor_id', null)?->fee ?? 0) / 100, 2, '.', '');
        $this->showForm = true;
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

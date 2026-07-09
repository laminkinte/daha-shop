<?php

namespace App\Livewire\Agent;

use App\Enums\ReconciliationStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class RemittanceForm extends Component
{
    public function render()
    {
        $agent = Auth::user()->deliveryAgent;

        $outstanding = $agent->cashReconciliations()
            ->whereIn('status', [ReconciliationStatus::Collected, ReconciliationStatus::Short])
            ->with('vendorOrder.order', 'vendorOrder.vendor')
            ->latest()
            ->get();

        $remitted = $agent->cashReconciliations()->where('status', ReconciliationStatus::Remitted)->with('vendorOrder.order')->latest()->limit(10)->get();

        return view('livewire.agent.remittance-form', [
            'outstanding' => $outstanding,
            'remitted' => $remitted,
            'totalOwed' => $outstanding->sum('amount_collected'),
        ]);
    }
}

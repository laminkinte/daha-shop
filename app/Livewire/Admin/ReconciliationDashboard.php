<?php

namespace App\Livewire\Admin;

use App\Enums\ReconciliationStatus;
use App\Models\CashReconciliation;
use App\Services\ReconciliationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ReconciliationDashboard extends Component
{
    use WithPagination;

    public string $filter = 'collected';

    public array $remitAmount = [];

    public function remit(int $reconciliationId, ReconciliationService $service): void
    {
        $reconciliation = CashReconciliation::findOrFail($reconciliationId);
        $amount = (int) round((float) ($this->remitAmount[$reconciliationId] ?? 0) * 100);

        $service->remit($reconciliation, $amount);
    }

    public function render()
    {
        $query = CashReconciliation::with('deliveryAgent.user', 'vendorOrder.order', 'vendorOrder.vendor')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        $summary = [
            'expected' => CashReconciliation::sum('amount_expected'),
            'collected' => CashReconciliation::sum('amount_collected'),
            'remitted' => CashReconciliation::sum('remitted_amount'),
        ];

        return view('livewire.admin.reconciliation-dashboard', [
            'reconciliations' => $query->paginate(10),
            'statuses' => ReconciliationStatus::cases(),
            'summary' => $summary,
        ]);
    }
}

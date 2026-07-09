<?php

namespace App\Services;

use App\Enums\ReconciliationStatus;
use App\Events\CashRemitted;
use App\Models\CashReconciliation;

class ReconciliationService
{
    public function remit(CashReconciliation $reconciliation, int $remittedAmount): void
    {
        $reconciliation->update([
            'remitted_amount' => $remittedAmount,
            'remitted_at' => now(),
            'status' => $remittedAmount >= $reconciliation->amount_collected
                ? ReconciliationStatus::Remitted
                : ReconciliationStatus::Short,
        ]);

        CashRemitted::dispatch($reconciliation->fresh());
    }
}

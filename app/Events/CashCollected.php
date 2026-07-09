<?php

namespace App\Events;

use App\Models\CashReconciliation;
use Illuminate\Foundation\Events\Dispatchable;

class CashCollected
{
    use Dispatchable;

    public function __construct(public CashReconciliation $reconciliation) {}
}

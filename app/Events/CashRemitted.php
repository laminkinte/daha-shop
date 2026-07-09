<?php

namespace App\Events;

use App\Models\CashReconciliation;
use Illuminate\Foundation\Events\Dispatchable;

class CashRemitted
{
    use Dispatchable;

    public function __construct(public CashReconciliation $reconciliation) {}
}

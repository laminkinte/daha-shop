<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Collected = 'collected';
    case Remitted = 'remitted';
    case Short = 'short';
}

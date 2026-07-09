<?php

namespace App\Enums;

enum ConfirmationStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case PendingAdminReview = 'pending_admin_review';
}

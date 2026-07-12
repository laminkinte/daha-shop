<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vendor subscription plans
    |--------------------------------------------------------------------------
    |
    | Amounts are in kobo (smallest currency unit), matching how money is
    | stored everywhere else in this app. The annual plan is priced at
    | roughly 10 months' worth of the monthly plan as a loyalty discount.
    |
    */

    'plans' => [
        'monthly' => [
            'amount' => 500000, // NGN 5,000
        ],
        'annual' => [
            'amount' => 5000000, // NGN 50,000
        ],
    ],
];

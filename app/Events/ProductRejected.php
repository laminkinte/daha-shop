<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

class ProductRejected
{
    use Dispatchable;

    public function __construct(public Product $product) {}
}

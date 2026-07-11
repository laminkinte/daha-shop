<?php

namespace App\Livewire\Storefront;

use App\Services\CartResolver;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIcon extends Component
{
    public int $count = 0;

    public string $variant = 'header';

    public function mount(CartResolver $resolver, string $variant = 'header'): void
    {
        $this->variant = $variant;
        $this->refreshCount($resolver);
    }

    #[On('cart-updated')]
    public function refreshCount(CartResolver $resolver): void
    {
        $this->count = $resolver->current()->items()->sum('quantity');
    }

    public function render()
    {
        return view('livewire.storefront.cart-icon');
    }
}

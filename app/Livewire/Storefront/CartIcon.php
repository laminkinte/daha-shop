<?php

namespace App\Livewire\Storefront;

use App\Services\CartResolver;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIcon extends Component
{
    public int $count = 0;

    public function mount(CartResolver $resolver): void
    {
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

<?php

namespace App\Livewire\Storefront;

use App\Models\CartItem;
use App\Services\CartResolver;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Cart extends Component
{
    public function updateQuantity(int $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeItem($itemId);

            return;
        }

        CartItem::whereKey($itemId)->update(['quantity' => $quantity]);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId): void
    {
        CartItem::whereKey($itemId)->delete();
        $this->dispatch('cart-updated');
    }

    public function render(CartResolver $resolver)
    {
        $cart = $resolver->current();
        $items = $cart->items()->with(['product.vendor', 'variant'])->get();
        $subtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);

        return view('livewire.storefront.cart', [
            'items' => $items,
            'subtotal' => $subtotal,
        ]);
    }
}

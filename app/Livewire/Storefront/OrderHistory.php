<?php

namespace App\Livewire\Storefront;

use App\Models\CartItem;
use App\Services\CartResolver;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class OrderHistory extends Component
{
    use WithPagination;

    public function reorder(int $orderId, CartResolver $resolver): void
    {
        $order = Auth::user()->orders()->with('vendorOrders.items')->findOrFail($orderId);
        $cart = $resolver->current();

        foreach ($order->vendorOrders as $vendorOrder) {
            foreach ($vendorOrder->items as $item) {
                $cartItem = CartItem::firstOrNew([
                    'cart_id' => $cart->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                ]);
                $cartItem->quantity = ($cartItem->quantity ?? 0) + $item->quantity;
                $cartItem->save();
            }
        }

        $this->dispatch('cart-updated');
        $this->redirect(route('storefront.cart'), navigate: true);
    }

    public function render()
    {
        $orders = Auth::user()->orders()->with('vendorOrders')->latest()->paginate(10);

        return view('livewire.storefront.order-history', ['orders' => $orders]);
    }
}

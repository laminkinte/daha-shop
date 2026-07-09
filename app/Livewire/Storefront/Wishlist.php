<?php

namespace App\Livewire\Storefront;

use App\Models\CartItem;
use App\Models\WishlistItem;
use App\Services\CartResolver;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Wishlist extends Component
{
    public function remove(int $wishlistItemId): void
    {
        WishlistItem::where('user_id', Auth::id())->whereKey($wishlistItemId)->delete();
    }

    public function addToCart(int $productId, CartResolver $resolver): void
    {
        $cart = $resolver->current();

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'product_variant_id' => null,
        ]);
        $item->quantity = ($item->quantity ?? 0) + 1;
        $item->save();

        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $items = WishlistItem::where('user_id', Auth::id())->with('product.vendor', 'product.images')->latest()->get();

        return view('livewire.storefront.wishlist', ['items' => $items]);
    }
}

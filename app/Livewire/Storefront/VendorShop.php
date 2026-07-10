<?php

namespace App\Livewire\Storefront;

use App\Enums\VendorStatus;
use App\Models\CartItem;
use App\Models\Vendor;
use App\Services\CartResolver;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class VendorShop extends Component
{
    use WithPagination;

    public Vendor $vendor;

    public function mount(Vendor $vendor): void
    {
        abort_unless($vendor->status === VendorStatus::Approved, 404);

        $this->vendor = $vendor;
    }

    public function addToCart(int $productId, CartResolver $resolver): void
    {
        $product = $this->vendor->products()->where('status', 'published')->findOrFail($productId);
        $cart = $resolver->current();

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);
        $item->quantity = ($item->quantity ?? 0) + 1;
        $item->save();

        $this->dispatch('cart-updated');
        session()->flash('cart_message', "{$product->name} added to cart.");
    }

    public function render()
    {
        $products = $this->vendor->products()
            ->where('status', 'published')
            ->with(['images', 'category'])
            ->latest()
            ->paginate(12);

        return view('livewire.storefront.vendor-shop', ['products' => $products]);
    }
}

<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use App\Services\CartResolver;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class ProductDetail extends Component
{
    public Product $product;

    public ?int $variantId = null;

    public int $quantity = 1;

    public function mount(Product $product): void
    {
        abort_unless($product->status === ProductStatus::Published, 404);

        $this->product = $product->load(['vendor', 'category', 'images', 'variants', 'reviews.user']);
        $this->variantId = $this->product->variants->first()?->id;
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        return $this->variantId ? $this->product->variants->firstWhere('id', $this->variantId) : null;
    }

    public function getUnitPriceProperty(): int
    {
        return $this->selectedVariant?->price() ?? $this->product->base_price;
    }

    public function getInWishlistProperty(): bool
    {
        return Auth::check() && WishlistItem::where('user_id', Auth::id())->where('product_id', $this->product->id)->exists();
    }

    public function addToCart(CartResolver $resolver): void
    {
        $cart = $resolver->current();

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variantId,
        ]);
        $item->quantity = ($item->quantity ?? 0) + $this->quantity;
        $item->save();

        $this->dispatch('cart-updated');
        session()->flash('cart_message', "{$this->product->name} added to cart.");
    }

    public function toggleWishlist(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $existing = WishlistItem::where('user_id', Auth::id())->where('product_id', $this->product->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            WishlistItem::create(['user_id' => Auth::id(), 'product_id' => $this->product->id]);
        }
    }

    public function render()
    {
        return view('livewire.storefront.product-detail');
    }
}

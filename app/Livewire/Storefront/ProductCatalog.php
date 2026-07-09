<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartResolver;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class ProductCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public ?int $category = null;

    public string $sort = 'newest';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function addToCart(int $productId, CartResolver $resolver): void
    {
        $product = Product::findOrFail($productId);
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
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();

        $products = Product::query()
            ->where('status', 'published')
            ->when($this->q, fn ($query) => $query->where('name', 'like', "%{$this->q}%"))
            ->when($this->category, fn ($query) => $query->where('category_id', $this->category))
            ->when($this->sort === 'price_low', fn ($query) => $query->orderBy('base_price'))
            ->when($this->sort === 'price_high', fn ($query) => $query->orderByDesc('base_price'))
            ->when($this->sort === 'newest', fn ($query) => $query->latest())
            ->with(['vendor', 'category', 'images'])
            ->paginate(12);

        return view('livewire.storefront.product-catalog', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}

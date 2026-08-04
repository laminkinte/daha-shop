<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Vendor;
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
        $product = Product::where('status', ProductStatus::Published)->findOrFail($productId);
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
            ->where('status', ProductStatus::Published)
            ->when($this->q, fn ($query) => $query->where('name', 'like', "%{$this->q}%"))
            // Vendors only ever assign a child category to a product (see
            // ProductManager), never the parent shown here as a browsing
            // filter - so filtering by the parent's id alone would never
            // match anything. Match the parent itself (defensive) or any
            // of its children.
            ->when($this->category, function ($query) {
                $categoryIds = Category::where('id', $this->category)
                    ->orWhere('parent_id', $this->category)
                    ->pluck('id');

                $query->whereIn('category_id', $categoryIds);
            })
            ->when($this->sort === 'price_low', fn ($query) => $query->orderBy('base_price'))
            ->when($this->sort === 'price_high', fn ($query) => $query->orderByDesc('base_price'))
            ->when($this->sort === 'newest', fn ($query) => $query->latest())
            ->with(['vendor', 'category', 'images'])
            ->paginate(12);

        $stats = [
            'products' => Product::where('status', ProductStatus::Published)->count(),
            'vendors' => Vendor::where('status', VendorStatus::Approved)->count(),
        ];

        return view('livewire.storefront.product-catalog', [
            'categories' => $categories,
            'products' => $products,
            'stats' => $stats,
        ]);
    }
}

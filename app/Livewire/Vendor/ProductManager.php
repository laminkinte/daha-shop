<?php

namespace App\Livewire\Vendor;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ProductManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?ProductStatus $editingStatus = null;

    public ?string $editingRejectionReason = null;

    public ?int $categoryId = null;

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public int $stock = 0;

    public $image;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $productId): void
    {
        $product = Auth::user()->vendor->products()->findOrFail($productId);

        $this->editingId = $product->id;
        $this->editingStatus = $product->status;
        $this->editingRejectionReason = $product->rejection_reason;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->price = number_format($product->base_price / 100, 2, '.', '');
        $this->stock = $product->stock;
        $this->showForm = true;
    }

    /**
     * Whether the product currently being edited already went through
     * review at least once and is live or waiting on a decision - in
     * which case editing just updates its fields without resetting
     * the review state.
     */
    public function getIsAwaitingOrLiveProperty(): bool
    {
        return in_array($this->editingStatus, [ProductStatus::Published, ProductStatus::PendingReview], true);
    }

    public function saveAsDraft(): void
    {
        $this->persist(ProductStatus::Draft);
    }

    public function submitForReview(): void
    {
        $this->persist(ProductStatus::PendingReview);
    }

    public function saveChanges(): void
    {
        $this->persist($this->editingStatus);
    }

    private function persist(?ProductStatus $status): void
    {
        $this->validate([
            'categoryId' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:4096',
        ]);

        $vendor = Auth::user()->vendor;

        $data = [
            'vendor_id' => $vendor->id,
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => (int) round(((float) $this->price) * 100),
            'stock' => $this->stock,
            'status' => $status,
        ];

        if ($status === ProductStatus::PendingReview) {
            $data['rejection_reason'] = null;
            $data['reviewed_by'] = null;
            $data['reviewed_at'] = null;
        }

        if ($this->editingId) {
            $product = $vendor->products()->findOrFail($this->editingId);
            $product->update($data);
        } else {
            $data['slug'] = Str::slug($this->name).'-'.Str::random(6);
            $product = Product::create($data);
        }

        if ($this->image) {
            $path = $this->image->store('products', 'public');
            ProductImage::create(['product_id' => $product->id, 'path' => $path]);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $productId): void
    {
        Auth::user()->vendor->products()->findOrFail($productId)->delete();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'editingStatus', 'editingRejectionReason', 'categoryId', 'name', 'description', 'price', 'stock', 'image']);
    }

    public function render()
    {
        $products = Auth::user()->vendor->products()->with('category', 'images')->latest()->paginate(10);

        return view('livewire.vendor.product-manager', [
            'products' => $products,
            'categories' => Category::whereNotNull('parent_id')->orderBy('name')->get(),
        ]);
    }
}

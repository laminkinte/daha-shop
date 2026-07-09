<?php

namespace App\Livewire\Vendor;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Auth;
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

    public ?int $categoryId = null;

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public int $stock = 0;

    public string $status = 'draft';

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
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->price = number_format($product->base_price / 100, 2, '.', '');
        $this->stock = $product->stock;
        $this->status = $product->status;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'categoryId' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:draft,published',
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
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $product = $vendor->products()->findOrFail($this->editingId);
            $product->update($data);
        } else {
            $data['slug'] = \Illuminate\Support\Str::slug($this->name).'-'.\Illuminate\Support\Str::random(6);
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
        $this->reset(['editingId', 'categoryId', 'name', 'description', 'price', 'stock', 'image']);
        $this->status = 'draft';
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

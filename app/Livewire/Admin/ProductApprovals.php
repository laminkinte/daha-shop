<?php

namespace App\Livewire\Admin;

use App\Enums\ProductStatus;
use App\Events\ProductApproved;
use App\Events\ProductRejected;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ProductApprovals extends Component
{
    use WithPagination;

    public string $filter = 'pending_review';

    public array $rejectionReason = [];

    public function approve(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $product->update([
            'status' => ProductStatus::Published,
            'rejection_reason' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        ProductApproved::dispatch($product->fresh());
    }

    public function reject(int $productId): void
    {
        $reason = trim($this->rejectionReason[$productId] ?? '');

        $this->validate([
            'rejectionReason.'.$productId => 'required|string|max:500',
        ], [
            'rejectionReason.'.$productId.'.required' => 'Please give the vendor a reason for rejecting this product.',
        ]);

        $product = Product::findOrFail($productId);

        $product->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        unset($this->rejectionReason[$productId]);

        ProductRejected::dispatch($product->fresh());
    }

    public function render()
    {
        $query = Product::with('vendor', 'category', 'images')->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.product-approvals', [
            'products' => $query->paginate(10),
            'statuses' => ProductStatus::cases(),
        ]);
    }
}

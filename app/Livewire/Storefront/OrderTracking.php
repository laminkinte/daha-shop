<?php

namespace App\Livewire\Storefront;

use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class OrderTracking extends Component
{
    public Order $order;

    public array $ratings = [];

    public array $comments = [];

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $this->order = $order->load(['vendorOrders.vendor', 'vendorOrders.items.review', 'address.state', 'address.lga']);
    }

    public function submitReview(int $orderItemId): void
    {
        $item = $this->order->vendorOrders->flatMap->items->firstWhere('id', $orderItemId);

        abort_unless($item && in_array($item->vendorOrder->status, [VendorOrderStatus::Delivered, VendorOrderStatus::PickedUp], true), 403);

        Review::updateOrCreate(
            ['order_item_id' => $orderItemId],
            [
                'user_id' => auth()->id(),
                'product_id' => $item->product_id,
                'rating' => $this->ratings[$orderItemId] ?? 5,
                'comment' => $this->comments[$orderItemId] ?? null,
            ]
        );

        $this->order->refresh();
        $this->order->load('vendorOrders.items.review');
    }

    public function render()
    {
        return view('livewire.storefront.order-tracking');
    }
}

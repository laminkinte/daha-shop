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
        $this->order = $order->load(['vendorOrders.vendor', 'vendorOrders.items.review', 'vendorOrders.items.product.images', 'vendorOrders.deliveryPayment', 'vendorOrders.deliveryAgent', 'address.state', 'address.lga']);
    }

    /**
     * Polled every 10s while a vendor-order is out for delivery with an
     * agent assigned - dispatches a browser event rather than touching
     * $this->order, so it can't trigger a full-page re-render that would
     * disturb the map's wire:ignore'd DOM (see resources/js/delivery-map.js).
     */
    public function refreshAgentLocation(int $vendorOrderId): void
    {
        $vendorOrder = $this->order->vendorOrders->firstWhere('id', $vendorOrderId);

        if (! $vendorOrder || $vendorOrder->status !== VendorOrderStatus::OutForDelivery || ! $vendorOrder->deliveryAgent) {
            return;
        }

        $agent = $vendorOrder->deliveryAgent()->first();

        if ($agent->current_lat === null || $agent->current_lng === null) {
            return;
        }

        $this->dispatch(
            'agent-location-updated.'.$vendorOrderId,
            lat: (float) $agent->current_lat,
            lng: (float) $agent->current_lng,
        );
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

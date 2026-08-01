<?php

namespace App\Livewire\Storefront;

use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\VendorOrder;
use App\Services\GeocodingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class OrderTracking extends Component
{
    public Order $order;

    public array $ratings = [];

    public array $comments = [];

    public function mount(Order $order, GeocodingService $geocodingService): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $this->order = $order->load(['vendorOrders.vendor', 'vendorOrders.items.review', 'vendorOrders.items.product.images', 'vendorOrders.deliveryPayment', 'vendorOrders.deliveryAgent', 'address.state', 'address.lga']);

        // Only worth resolving the destination point when the live tracking
        // map will actually render (an assigned agent or self-delivering
        // vendor already has a position) - otherwise there's nothing to plot
        // it alongside, and this is a network call we don't want to make on
        // every order-tracking page load.
        if ($this->order->vendorOrders->contains(fn ($vo) => $this->trackedParty($vo)?->current_lat !== null)) {
            $geocodingService->geocode($this->order->address);
        }
    }

    private function trackedParty(VendorOrder $vendorOrder): mixed
    {
        if ($vendorOrder->status !== VendorOrderStatus::OutForDelivery) {
            return null;
        }

        return $vendorOrder->deliveryAgent ?? $vendorOrder->vendor;
    }

    /**
     * Polled every 10s while a vendor-order is out for delivery - dispatches
     * a browser event rather than touching $this->order, so it can't trigger
     * a full-page re-render that would disturb the map's wire:ignore'd DOM
     * (see resources/js/delivery-map.js). The position tracked is whichever
     * party is actually doing the delivering: the assigned agent, or the
     * vendor themselves when self-delivering (no agent assigned).
     */
    public function refreshAgentLocation(int $vendorOrderId): void
    {
        $vendorOrder = $this->order->vendorOrders->firstWhere('id', $vendorOrderId);

        if (! $vendorOrder || $vendorOrder->status !== VendorOrderStatus::OutForDelivery) {
            return;
        }

        $tracked = $vendorOrder->deliveryAgent()->first() ?? $vendorOrder->vendor()->first();

        if ($tracked->current_lat === null || $tracked->current_lng === null) {
            return;
        }

        $this->dispatch(
            'agent-location-updated.'.$vendorOrderId,
            lat: (float) $tracked->current_lat,
            lng: (float) $tracked->current_lng,
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

<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use App\Exceptions\CustomerBlacklistedException;
use App\Exceptions\EmptyCartException;
use App\Models\Address;
use App\Models\BlacklistedNumber;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private DeliveryFeeCalculator $feeCalculator,
        private OtpService $otpService,
    ) {}

    /**
     * @param  array<int, string>  $fulfillmentMethods  vendor_id => 'delivery'|'pickup'
     *
     * @throws EmptyCartException
     * @throws CustomerBlacklistedException
     */
    public function placeOrder(User $user, Cart $cart, Address $address, array $fulfillmentMethods = []): Order
    {
        if ($cart->items->isEmpty()) {
            throw new EmptyCartException;
        }

        if (BlacklistedNumber::where('phone', $address->phone)->exists()) {
            throw new CustomerBlacklistedException;
        }

        $order = DB::transaction(function () use ($user, $cart, $address, $fulfillmentMethods) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'items_subtotal' => 0,
                'delivery_fee_total' => 0,
                'cod_amount_expected' => 0,
            ]);

            $itemsByVendor = $cart->items->load(['product.vendor', 'variant'])
                ->groupBy(fn ($item) => $item->product->vendor_id);

            $itemsSubtotal = 0;
            $deliveryFeeTotal = 0;

            foreach ($itemsByVendor as $vendorItems) {
                $vendor = $vendorItems->first()->product->vendor;
                $vendorItemsSubtotal = $vendorItems->sum(fn ($item) => $item->unitPrice() * $item->quantity);
                $isPickup = ($fulfillmentMethods[$vendor->id] ?? 'delivery') === FulfillmentMethod::Pickup->value;
                $deliveryFee = $isPickup ? 0 : $this->feeCalculator->feeFor($address, $vendor);

                $vendorOrder = VendorOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'status' => VendorOrderStatus::Pending,
                    'fulfillment_method' => $isPickup ? FulfillmentMethod::Pickup : FulfillmentMethod::Delivery,
                    'items_subtotal' => $vendorItemsSubtotal,
                    'delivery_fee' => $deliveryFee,
                ]);

                foreach ($vendorItems as $item) {
                    OrderItem::create([
                        'vendor_order_id' => $vendorOrder->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unitPrice(),
                        'subtotal' => $item->unitPrice() * $item->quantity,
                    ]);

                    if ($item->variant) {
                        $item->variant->decrement('stock', $item->quantity);
                    } else {
                        $item->product->decrement('stock', $item->quantity);
                    }
                }

                $itemsSubtotal += $vendorItemsSubtotal;
                $deliveryFeeTotal += $deliveryFee;
            }

            // The delivery fee is prepaid via OPay, not collected as cash, so
            // cod_amount_expected - the cash the agent should collect - only
            // ever covers the items themselves.
            $order->update([
                'items_subtotal' => $itemsSubtotal,
                'delivery_fee_total' => $deliveryFeeTotal,
                'cod_amount_expected' => $itemsSubtotal,
            ]);

            $cart->items()->delete();

            return $order;
        });

        $this->otpService->generate($address->phone, 'order_confirmation');

        return $order;
    }

    private function generateOrderNumber(): string
    {
        return 'MH'.now()->format('ymd').strtoupper(Str::random(5));
    }
}

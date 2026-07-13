<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\DeliveryFeePaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DeliveryFeePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryFeeCallbackController extends Controller
{
    public function __invoke(Request $request, Order $order, DeliveryFeePaymentService $deliveryFeePayments): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $reference = (string) $request->query('reference', '');

        if ($reference !== '') {
            $payment = $deliveryFeePayments->verifyAndActivate($reference);

            return redirect()->route('storefront.orders.confirm', $order->order_number)
                ->with('delivery_fee_status', $payment?->status === DeliveryFeePaymentStatus::Paid ? 'paid' : 'failed');
        }

        return redirect()->route('storefront.orders.confirm', $order->order_number)
            ->with('delivery_fee_status', 'failed');
    }
}

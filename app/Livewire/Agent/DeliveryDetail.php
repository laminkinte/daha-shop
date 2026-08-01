<?php

namespace App\Livewire\Agent;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryPaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\VendorOrderStatus;
use App\Models\VendorOrder;
use App\Services\DeliveryPaymentService;
use App\Services\VendorOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class DeliveryDetail extends Component
{
    use WithFileUploads;

    public VendorOrder $vendorOrder;

    public string $cashCollected = '';

    public string $denominationNotes = '';

    public $proofPhoto;

    public string $failureReason = 'customer_unreachable';

    public string $paymentMethod = 'qr';

    public string $selectedGateway = 'opay';

    public string $customerPhone = '';

    public ?string $error = null;

    public function mount(int $vendorOrderId): void
    {
        $this->vendorOrder = Auth::user()->deliveryAgent->vendorOrders()->with('order.address', 'vendor', 'items', 'deliveryPayment', 'deliveryAgent')->findOrFail($vendorOrderId);
        $this->cashCollected = number_format($this->vendorOrder->cashDueAtDelivery() / 100, 2, '.', '');
    }

    /**
     * Called directly from resources/js/agent-location.js via $wire while
     * this screen is open and the browser has a GPS fix - the status guard
     * makes a stray late ping (e.g. arriving just after markDelivered()
     * already redirected) a silent no-op instead of writing stale
     * coordinates onto a completed delivery. No re-render needed - this
     * changes nothing the agent's own screen displays.
     */
    public function updateLocation(float $lat, float $lng): void
    {
        if ($this->vendorOrder->status !== VendorOrderStatus::OutForDelivery) {
            return;
        }

        $this->vendorOrder->deliveryAgent->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'location_updated_at' => now(),
        ]);
    }

    /**
     * Starts (or retries) digital collection of payment at the door -
     * completes Kambaza2003's "implement QR and manual delivery payments"
     * commit, which added DeliveryPaymentService/DeliveryPayment but never
     * wired this component up to call it.
     */
    public function startPayment(DeliveryPaymentService $service): void
    {
        $this->error = null;

        if ($this->paymentMethod === 'manual') {
            $this->validate(['customerPhone' => 'required|string']);
        }

        try {
            $service->initialize($this->vendorOrder, $this->paymentMethod, $this->customerPhone ?: null, PaymentGateway::from($this->selectedGateway));
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'We could not start the payment right now. Please try again shortly.';

            return;
        }

        $this->vendorOrder->refresh()->load('deliveryPayment');
    }

    /**
     * Polled every 5s by the view while a payment is pending, in case the
     * OPay webhook hasn't landed yet - re-verifies directly and redirects
     * once the order is confirmed delivered. The completion itself can also
     * happen out-of-band (e.g. the Test gateway's simulate-payment page
     * completes it immediately rather than waiting on this poll), so the
     * redirect check runs regardless of whether this call was the one that
     * did the completing.
     */
    public function refreshPaymentStatus(DeliveryPaymentService $service): void
    {
        $payment = $this->vendorOrder->deliveryPayment;

        if (! $payment) {
            return;
        }

        if ($payment->status === DeliveryPaymentStatus::Pending) {
            $service->verifyAndComplete($payment->reference);
        }

        $this->vendorOrder->refresh()->load('deliveryPayment');

        if ($this->vendorOrder->status === VendorOrderStatus::Delivered) {
            $this->redirect(route('agent.deliveries'), navigate: true);
        }
    }

    public function markDelivered(VendorOrderService $service)
    {
        $this->validate([
            'cashCollected' => 'required|numeric|min:0',
            'proofPhoto' => 'nullable|image|max:4096',
        ]);

        $path = $this->proofPhoto?->store('proof-of-delivery', 'public');

        $service->markDelivered(
            $this->vendorOrder,
            (int) round((float) $this->cashCollected * 100),
            $path,
            $this->denominationNotes ?: null,
        );

        return $this->redirect(route('agent.deliveries'), navigate: true);
    }

    public function markFailed(VendorOrderService $service)
    {
        $service->markFailed($this->vendorOrder, DeliveryFailureReason::from($this->failureReason));

        return $this->redirect(route('agent.deliveries'), navigate: true);
    }

    public function render()
    {
        return view('livewire.agent.delivery-detail', [
            'failureReasons' => DeliveryFailureReason::cases(),
        ]);
    }
}

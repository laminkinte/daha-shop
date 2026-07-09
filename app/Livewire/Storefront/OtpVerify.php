<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\OtpService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class OtpVerify extends Component
{
    public Order $order;

    public string $code = '';

    public ?string $message = null;

    public bool $resent = false;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $this->order = $order;
    }

    public function resend(OtpService $otpService): void
    {
        $otpService->generate($this->order->address->phone, 'order_confirmation');
        $this->resent = true;
        $this->message = null;
    }

    public function verify(OtpService $otpService, OrderService $orderService)
    {
        $ok = $otpService->verify($this->order->address->phone, 'order_confirmation', $this->code);

        if (! $ok) {
            $this->message = 'That code is invalid or has expired. Please try again or request a new one.';

            return;
        }

        $orderService->confirmFromOtp($this->order);

        return $this->redirect(route('storefront.orders.show', $this->order->order_number), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.otp-verify');
    }
}

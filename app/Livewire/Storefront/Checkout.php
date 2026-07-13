<?php

namespace App\Livewire\Storefront;

use App\Exceptions\CustomerBlacklistedException;
use App\Exceptions\DeliveryNotAvailableException;
use App\Exceptions\EmptyCartException;
use App\Models\Address;
use App\Models\State;
use App\Services\CartResolver;
use App\Services\CheckoutService;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Checkout extends Component
{
    public ?int $selectedAddressId = null;

    public bool $useNewAddress = false;

    public ?int $stateId = null;

    public ?int $lgaId = null;

    public string $area = '';

    public string $streetAddress = '';

    public string $phone = '';

    public string $landmark = '';

    public string $label = 'Home';

    public array $fulfillmentMethods = [];

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $default = Auth::user()->addresses()->where('is_default', true)->first()
            ?? Auth::user()->addresses()->first();

        if ($default) {
            $this->selectedAddressId = $default->id;
        } else {
            $this->useNewAddress = true;
        }
    }

    public function getLgasProperty()
    {
        return $this->stateId ? State::find($this->stateId)?->lgas()->orderBy('name')->get() : collect();
    }

    public function getResolvedAddressProperty(): ?Address
    {
        if (! $this->useNewAddress && $this->selectedAddressId) {
            return Auth::user()->addresses()->find($this->selectedAddressId);
        }

        if ($this->stateId && $this->lgaId) {
            return new Address([
                'state_id' => $this->stateId,
                'lga_id' => $this->lgaId,
                'phone' => $this->phone ?: Auth::user()->phone,
            ]);
        }

        return null;
    }

    public function getFeePreviewProperty(): array
    {
        $address = $this->resolvedAddress;
        $resolver = app(CartResolver::class);
        $calculator = app(DeliveryFeeCalculator::class);
        $cart = $resolver->current();
        $items = $cart->items()->with('product.vendor')->get();

        if (! $address || $items->isEmpty()) {
            return ['lines' => [], 'itemsSubtotal' => 0, 'deliveryTotal' => 0, 'grandTotal' => 0, 'unavailable' => false];
        }

        $lines = [];
        $itemsSubtotal = 0;
        $deliveryTotal = 0;
        $unavailable = false;

        foreach ($items->groupBy(fn ($item) => $item->product->vendor_id) as $vendorItems) {
            $vendor = $vendorItems->first()->product->vendor;
            $vendorSubtotal = $vendorItems->sum(fn ($item) => $item->unitPrice() * $item->quantity);
            $isPickup = ($this->fulfillmentMethods[$vendor->id] ?? 'delivery') === 'pickup';

            if ($isPickup) {
                $fee = 0;
            } else {
                try {
                    $fee = $calculator->feeFor($address, $vendor);
                } catch (DeliveryNotAvailableException) {
                    $unavailable = true;
                    $fee = 0;
                }
            }

            $lines[] = [
                'vendor_id' => $vendor->id,
                'vendor' => $vendor->business_name,
                'vendor_address' => $vendor->business_address,
                'subtotal' => $vendorSubtotal,
                'fee' => $fee,
                'pickup' => $isPickup,
            ];
            $itemsSubtotal += $vendorSubtotal;
            $deliveryTotal += $fee;
        }

        return [
            'lines' => $lines,
            'itemsSubtotal' => $itemsSubtotal,
            'deliveryTotal' => $deliveryTotal,
            'grandTotal' => $itemsSubtotal + $deliveryTotal,
            'unavailable' => $unavailable,
        ];
    }

    public function placeOrder(CartResolver $resolver, CheckoutService $checkoutService)
    {
        $this->errorMessage = null;

        $this->validate([
            'phone' => ['required_if:useNewAddress,true', 'string'],
        ]);

        $address = null;

        if ($this->useNewAddress) {
            $this->validate([
                'stateId' => 'required|exists:states,id',
                'lgaId' => 'required|exists:lgas,id',
                'area' => 'required|string|max:255',
                'streetAddress' => 'required|string|max:500',
                'phone' => 'required|string|max:20',
            ]);

            $address = Auth::user()->addresses()->create([
                'state_id' => $this->stateId,
                'lga_id' => $this->lgaId,
                'label' => $this->label ?: 'Home',
                'area' => $this->area,
                'street_address' => $this->streetAddress,
                'phone' => $this->phone,
                'landmark' => $this->landmark ?: null,
                'is_default' => Auth::user()->addresses()->count() === 0,
            ]);
        } else {
            $address = Auth::user()->addresses()->find($this->selectedAddressId);
        }

        if (! $address) {
            $this->errorMessage = 'Please select or add a delivery address.';

            return;
        }

        try {
            $order = $checkoutService->placeOrder(Auth::user(), $resolver->current(), $address, $this->fulfillmentMethods);
        } catch (EmptyCartException|CustomerBlacklistedException|DeliveryNotAvailableException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->dispatch('cart-updated');

        return $this->redirect(route('storefront.orders.confirm', $order->order_number), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.checkout', [
            'addresses' => Auth::user()->addresses,
            'states' => State::orderBy('name')->get(),
        ]);
    }
}

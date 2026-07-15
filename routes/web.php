<?php

use App\Http\Controllers\Admin\VendorDocumentController;
use App\Http\Controllers\Storefront\DeliveryFeeCallbackController;
use App\Http\Controllers\Vendor\SubscriptionCallbackController;
use App\Http\Controllers\Webhooks\OpayWebhookController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Livewire\Admin\AgentManager;
use App\Livewire\Admin\BlacklistManager;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\DeliveryZoneManager;
use App\Livewire\Admin\DispatchBoard;
use App\Livewire\Admin\OrderOverview;
use App\Livewire\Admin\ProductApprovals;
use App\Livewire\Admin\ReconciliationDashboard;
use App\Livewire\Admin\VendorApprovals;
use App\Livewire\Agent\AssignedDeliveries;
use App\Livewire\Agent\DeliveryDetail;
use App\Livewire\Agent\RemittanceForm;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\OrderHistory;
use App\Livewire\Storefront\OrderTracking;
use App\Livewire\Storefront\OtpVerify;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Storefront\VendorShop;
use App\Livewire\Storefront\Wishlist;
use App\Livewire\Vendor\Dashboard as VendorDashboard;
use App\Livewire\Vendor\IdentityVerification;
use App\Livewire\Vendor\OrderManager as VendorOrderManager;
use App\Livewire\Vendor\PayoutHistory;
use App\Livewire\Vendor\ProductManager;
use App\Livewire\Vendor\QrCode as VendorQrCode;
use App\Livewire\Vendor\Subscription as VendorSubscription;
use Illuminate\Support\Facades\Route;

Route::get('/', ProductCatalog::class)->name('storefront.home');
Route::get('/products/{product:slug}', ProductDetail::class)->name('storefront.product');
Route::get('/vendors/{vendor:slug}', VendorShop::class)->name('storefront.vendor');
Route::get('/cart', Cart::class)->name('storefront.cart');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', Checkout::class)->name('storefront.checkout');
    Route::get('/wishlist', Wishlist::class)->name('storefront.wishlist');
    Route::get('/orders', OrderHistory::class)->name('storefront.orders');
    Route::get('/orders/{order}/confirm', OtpVerify::class)->name('storefront.orders.confirm');
    Route::get('/orders/{order}/delivery-fee/callback', DeliveryFeeCallbackController::class)->name('storefront.orders.delivery-fee.callback');
    Route::get('/orders/{order}', OrderTracking::class)->name('storefront.orders.show');
});

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', VendorDashboard::class)->name('dashboard');
    Route::get('/products', ProductManager::class)->name('products');
    Route::get('/orders', VendorOrderManager::class)->name('orders');
    Route::get('/payouts', PayoutHistory::class)->name('payouts');
    Route::get('/qr-code', VendorQrCode::class)->name('qr-code');
    Route::get('/identity', IdentityVerification::class)->name('identity');
    Route::get('/subscription', VendorSubscription::class)->name('subscription');
    Route::get('/subscription/callback', SubscriptionCallbackController::class)->name('subscription.callback');
});

Route::post('/webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');
Route::post('/webhooks/opay', OpayWebhookController::class)->name('webhooks.opay');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/vendors', VendorApprovals::class)->name('vendors');
    Route::get('/vendors/{vendor}/document/{type}', [VendorDocumentController::class, 'show'])->name('vendors.document');
    Route::get('/products', ProductApprovals::class)->name('products');
    Route::get('/orders', OrderOverview::class)->name('orders');
    Route::get('/dispatch', DispatchBoard::class)->name('dispatch');
    Route::get('/reconciliation', ReconciliationDashboard::class)->name('reconciliation');
    Route::get('/agents', AgentManager::class)->name('agents');
    Route::get('/delivery-zones', DeliveryZoneManager::class)->name('delivery-zones');
    Route::get('/blacklist', BlacklistManager::class)->name('blacklist');
});

Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/', AssignedDeliveries::class)->name('deliveries');
    Route::get('/deliveries/{vendorOrderId}', DeliveryDetail::class)->name('deliveries.show');
    Route::get('/remittance', RemittanceForm::class)->name('remittance');
});

Route::get('dashboard', function () {
    $user = auth()->user();

    return match (true) {
        $user->isVendor() => redirect()->route('vendor.dashboard'),
        $user->isAdmin() => redirect()->route('admin.dashboard'),
        $user->isAgent() => redirect()->route('agent.deliveries'),
        default => redirect()->route('storefront.home'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

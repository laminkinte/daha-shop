<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use App\Models\Order;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function orders(Request $request): StreamedResponse
    {
        $filter = $request->query('filter', 'all');

        $query = Order::with('user', 'address')->latest();

        if ($filter !== 'all') {
            $query->where('confirmation_status', $filter);
        }

        return $this->streamCsv('orders.csv', [
            'Order Number', 'Customer Name', 'Customer Phone', 'Status', 'Confirmation Status',
            'Items Subtotal', 'Delivery Fee', 'COD Expected', 'COD Collected', 'Created At', 'Confirmed At',
        ], $query->cursor()->map(fn (Order $order) => [
            $order->order_number,
            $order->user->name,
            $order->address->phone,
            $order->status->value,
            $order->confirmation_status->value,
            number_format($order->items_subtotal / 100, 2, '.', ''),
            number_format($order->delivery_fee_total / 100, 2, '.', ''),
            number_format($order->cod_amount_expected / 100, 2, '.', ''),
            number_format($order->cod_amount_collected / 100, 2, '.', ''),
            $order->created_at->toDateTimeString(),
            $order->confirmed_at?->toDateTimeString() ?? '',
        ]));
    }

    public function reconciliation(Request $request): StreamedResponse
    {
        $filter = $request->query('filter', 'all');

        $query = CashReconciliation::with('deliveryAgent.user', 'vendorOrder.order')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return $this->streamCsv('reconciliation.csv', [
            'Agent', 'Order Number', 'Amount Expected', 'Amount Collected', 'Status', 'Remitted Amount', 'Remitted At',
        ], $query->cursor()->map(fn (CashReconciliation $recon) => [
            $recon->deliveryAgent->user->name,
            $recon->vendorOrder->order->order_number,
            number_format($recon->amount_expected / 100, 2, '.', ''),
            number_format($recon->amount_collected / 100, 2, '.', ''),
            $recon->status->value,
            number_format(($recon->remitted_amount ?? 0) / 100, 2, '.', ''),
            $recon->remitted_at?->toDateTimeString() ?? '',
        ]));
    }

    public function payouts(Request $request): StreamedResponse
    {
        $filter = $request->query('filter', 'all');

        $query = VendorPayout::with('vendor')->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return $this->streamCsv('payouts.csv', [
            'Vendor', 'Period Start', 'Period End', 'Total Amount', 'Status', 'Reference', 'Paid At',
        ], $query->cursor()->map(fn (VendorPayout $payout) => [
            $payout->vendor->business_name,
            $payout->period_start->toDateString(),
            $payout->period_end->toDateString(),
            number_format($payout->total_amount / 100, 2, '.', ''),
            $payout->status->value,
            $payout->reference ?? '',
            $payout->paid_at?->toDateTimeString() ?? '',
        ]));
    }

    private function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

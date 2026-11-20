<?php

namespace App\Http\Controllers;

use App\Models\InventoryDispatch;
use App\Models\InventoryReceipt;
use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InventoryDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $role = $request->user()->role;
        $data = match ($role) {
            'qc' => $this->qc(),
            'warehouse' => $this->warehouse(),
            'cs' => $this->cs(),
            default => abort(403),
        };

        return response()->view('pages.inventory.dashboard', $data + ['role' => $role], 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function qc(): array
    {
        $today = now('Asia/Jakarta')->toDateString();
        $todayTickets = InventoryTicket::whereDate('received_on', $today);
        $totals = InventoryReceipt::whereIn('inventory_ticket_id', (clone $todayTickets)->select('id'))
            ->selectRaw('SUM(received_quantity) AS received, SUM(failed_quantity) AS failed')->first();
        $failed = (int) $totals->failed;

        return [
            'metrics' => [
                ['inventory.dashboard.qc.today', $todayTickets->count(), route('inventory.qc', ['date' => $today])],
                ['inventory.dashboard.qc.passed', (int) $totals->received - $failed, route('inventory.qc', ['date' => $today, 'result' => 'passed'])],
                ['inventory.dashboard.qc.failed', $failed, route('inventory.qc', ['date' => $today, 'result' => 'failed'])],
            ],
            'receipts' => InventoryTicket::with('vendor', 'lines')->latest('id')->limit(5)->get(),
        ];
    }

    private function warehouse(): array
    {
        $receiving = InventoryTicket::whereNull('confirmed_at')->whereHas('passedLines');
        $statuses = [InventoryDispatch::PENDING, InventoryDispatch::PACKING, InventoryDispatch::READY];
        $counts = InventoryDispatch::whereIn('status', $statuses)->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')->pluck('total', 'status');

        return [
            'metrics' => [
                ['inventory.dashboard.warehouse.inbound', $receiving->count(), route('inventory.receiving', ['pending' => 1])],
                ...array_map(fn (string $status) => [
                    'inventory.dispatch.status.'.$status,
                    (int) ($counts[$status] ?? 0),
                    route('inventory.dispatch.index', ['status' => $status]),
                ], $statuses),
            ],
            'receipts' => $receiving->with('vendor', 'lines')->oldest('id')->limit(5)->get(),
            'dispatches' => InventoryDispatch::whereIn('status', $statuses)
                ->orderByRaw('CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [InventoryDispatch::READY, InventoryDispatch::PACKING])
                ->oldest('id')->limit(5)->get(),
        ];
    }

    private function cs(): array
    {
        $totals = InventoryVariant::selectRaw('SUM(stock) AS physical, SUM(reserved_stock) AS reserved')->first();
        $physical = (int) $totals->physical;
        $reserved = (int) $totals->reserved;
        $stockUrl = route('inventory.stock');

        return [
            'metrics' => [
                ['inventory.dispatch.stock.available', $physical - $reserved, $stockUrl],
                ['inventory.dispatch.stock.reserved', $reserved, $stockUrl],
                ['inventory.dispatch.stock.physical', $physical, $stockUrl],
            ],
            'variants' => InventoryVariant::orderBy('short_sku')->orderBy('sku')->limit(5)->get(),
            'dispatches' => InventoryDispatch::latest('id')->limit(5)->get(),
        ];
    }
}

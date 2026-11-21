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
        $today = now('Asia/Jakarta')->toImmutable();
        $firstDay = $today->subDays(6);
        $dailyTotals = InventoryReceipt::join('inventory_tickets', 'inventory_tickets.id', '=', 'inventory_receipts.inventory_ticket_id')
            ->whereDate('inventory_tickets.received_on', '>=', $firstDay->toDateString())
            ->whereDate('inventory_tickets.received_on', '<=', $today->toDateString())
            ->selectRaw('DATE(inventory_tickets.received_on) AS inspection_date, SUM(received_quantity) AS received, SUM(failed_quantity) AS failed')
            ->groupByRaw('DATE(inventory_tickets.received_on)')
            ->get()->keyBy('inspection_date');

        $inspectionTrend = array_map(function (int $offset) use ($firstDay, $dailyTotals) {
            $day = $firstDay->addDays($offset);
            $date = $day->toDateString();
            $totals = $dailyTotals->get($date);
            $failed = (int) ($totals?->failed ?? 0);

            return [
                'date' => $date,
                'label' => $day->locale(app()->getLocale())->translatedFormat('d M'),
                'shortLabel' => $day->format('j/n'),
                'passed' => (int) ($totals?->received ?? 0) - $failed,
                'failed' => $failed,
            ];
        }, range(0, 6));

        $date = $today->toDateString();
        $current = $inspectionTrend[6];

        return [
            'metrics' => [
                ['inventory.dashboard.qc.today', InventoryTicket::whereDate('received_on', $date)->count(), route('inventory.qc', ['date' => $date])],
                ['inventory.dashboard.qc.passed', $current['passed'], route('inventory.qc', ['date' => $date, 'result' => 'passed'])],
                ['inventory.dashboard.qc.failed', $current['failed'], route('inventory.qc', ['date' => $date, 'result' => 'failed'])],
            ],
            'receipts' => InventoryTicket::with('vendor', 'lines')->latest('id')->limit(5)->get(),
            'inspectionTrend' => $inspectionTrend,
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

<?php

namespace App\Http\Controllers;

use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Support\InventoryQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function qc(Request $request): View|Response
    {
        $filters = $request->validateWithBag('qcFilters', [
            'sku' => ['nullable', 'string', 'max:80'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'result' => ['nullable', 'in:passed,failed'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $date = $filters['date'] ?? null;
        $result = $filters['result'] ?? null;
        $search = $filters['q'] ?? '';
        $tickets = InventoryTicket::with('vendor', 'lines')->latest('id')
            ->when($date, fn ($query) => $query->whereDate('received_on', $date));

        match ($result) {
            'passed' => $tickets->whereHas('passedLines'),
            'failed' => $tickets->whereHas('lines', fn ($query) => $query->where('failed_quantity', '>', 0)),
            default => null,
        };

        if ($search !== '') {
            $number = preg_match('/\A(?:QC-)?([0-9]{1,19})\z/i', $search, $matches) ? $matches[1] : null;
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], Str::lower($search)).'%';
            $tickets->where(function ($query) use ($number, $term) {
                $query->whereRaw("LOWER(reference) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$term]));

                if ($number !== null) {
                    $query->orWhere('id', $number);
                }
            });
        }

        $tickets = $tickets->paginate(15, ['*'], 'tickets')->withQueryString();
        if ($request->ajax()) {
            return response()->view('components.main.inventory.tickets-table', compact('tickets'), 200, [
                'X-QC-Ticket-Results' => '1',
                'Vary' => 'X-Requested-With',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return view('pages.inventory.qc', [
            'variants' => InventoryVariant::orderBy('model')->orderBy('color')->orderBy('size')->get(),
            'vendors' => InventoryVendor::orderBy('name')->get(),
            'selectedSku' => $filters['sku'] ?? null,
            'tickets' => $tickets,
            'search' => $search,
            'date' => $date,
            'result' => $result,
        ]);
    }

    public function addVariant(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validateWithBag('variant', [
            'model' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:40'],
            'size' => ['required', 'string', 'max:12', 'regex:/\A[A-Za-z0-9]+(?:[ -][A-Za-z0-9]+)*\z/'],
        ]);
        $data = array_map(fn (string $value) => Str::squish($value), $data);
        $data['size'] = Str::upper($data['size']);
        $variant = InventoryVariant::register($data);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $variant->id,
                'label' => $variant->pickerLabel(),
                'searchLabel' => $variant->searchLabel(),
                'qrUrl' => route('inventory.variants.qr', $variant),
            ], $variant->wasRecentlyCreated ? 201 : 200);
        }

        return to_route('inventory.qc', ['sku' => $variant->displaySku()])->with('status', __('inventory.variant.saved'));
    }

    public function skuQr(Request $request, InventoryVariant $variant): Response
    {
        $filename = 'sku-'.Str::slug($variant->displaySku()).'.svg';

        return InventoryQr::render(route('inventory.qc', ['sku' => $variant->displaySku()]), $request->boolean('download') ? $filename : null);
    }

    public function ticketQr(Request $request, InventoryTicket $ticket): Response
    {
        abort_unless($ticket->passedLines()->exists(), 404);

        return InventoryQr::render(
            route('inventory.ticket.receive', $ticket),
            $request->boolean('download') ? Str::lower($ticket->code()).'.svg' : null,
        );
    }

    public function receiving(Request $request): View
    {
        $pending = $request->boolean('pending');

        return view('pages.inventory.receiving', [
            'pending' => $pending,
            'tickets' => InventoryTicket::with('vendor', 'lines')->whereHas('passedLines')
                ->when($pending, fn ($query) => $query->whereNull('confirmed_at'))
                ->orderByRaw('CASE WHEN confirmed_at IS NULL THEN 0 ELSE 1 END')->latest('id')
                ->paginate(15, ['*'], 'tickets')->withQueryString(),
        ]);
    }

    public function stock(Request $request): Response
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $variants = InventoryVariant::orderBy('short_sku')->orderBy('sku')->get();
        $sizes = $variants->pluck('size')->map(fn ($size) => mb_strtolower($size))->all();
        $terms = collect(preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY));

        $variants = $variants->filter(function (InventoryVariant $variant) use ($terms, $sizes) {
            $description = mb_strtolower($variant->searchLabel());

            return $terms->every(fn ($term) => in_array($term, $sizes, true)
                ? $term === mb_strtolower($variant->size)
                : str_contains($description, $term));
        });

        if ($request->ajax()) {
            if ($request->boolean('preview')) {
                $variants = $variants->take(5);
            }

            return response()->view('components.main.inventory.stock-table', compact('variants'), 200, [
                'X-Stock-Results' => '1',
                'Vary' => 'X-Requested-With',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response()->view('pages.inventory.stock', compact('variants', 'search'), 200, [
            'Vary' => 'X-Requested-With',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

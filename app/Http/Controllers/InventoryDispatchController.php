<?php

namespace App\Http\Controllers;

use App\Models\InventoryDispatch;
use App\Models\InventoryVariant;
use App\Support\InventoryQr;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryDispatchController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,packing,ready,shipped,cancelled'],
        ]);
        $search = $filters['q'] ?? '';
        $status = $filters['status'] ?? null;
        $query = InventoryDispatch::query()->latest('id');
        $query->when($status, fn ($query) => $query->where('status', $status));

        if ($search !== '') {
            $ticketId = preg_match('/\AOUT-([0-9]{1,19})\z/i', $search, $matches) ? $matches[1] : null;
            $query->where(function ($query) use ($search, $ticketId) {
                $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], Str::lower($search)).'%';
                $query->whereRaw("LOWER(sales_reference) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(recipient_name) LIKE ? ESCAPE '!'", [$term]);
                if ($ticketId !== null) {
                    $query->orWhere('id', $ticketId);
                }
            });
        }

        $tickets = $query->paginate(15)->withQueryString();
        $headers = ['Vary' => 'X-Requested-With', 'Cache-Control' => 'private, no-store'];

        return $request->ajax()
            ? response()->view('components.main.inventory.dispatch-table', compact('tickets'), 200, $headers + ['X-Dispatch-Results' => '1'])
            : response()->view('pages.inventory.dispatches', compact('tickets', 'search', 'status'), 200, $headers);
    }

    public function create(): Response
    {
        $variants = InventoryVariant::orderBy('model')->orderBy('short_sku')->get()->map(fn (InventoryVariant $variant) => [
            'id' => $variant->id,
            'label' => $variant->pickerLabel(),
            'searchLabel' => $variant->searchLabel(),
            'available' => $variant->availableStock(),
        ]);

        return response()->view('pages.inventory.dispatch-create', compact('variants'), 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['sales_reference' => is_string($request->input('sales_reference'))
            ? Str::upper(Str::squish($request->input('sales_reference'))) : $request->input('sales_reference')]);

        $data = $request->validate([
            'sales_reference' => ['required', 'string', 'max:80', 'unique:inventory_dispatches'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:32', 'regex:/\A\+?[0-9][0-9 ().-]*\z/'],
            'recipient_address' => ['required', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:inventory_variant_id,quantity'],
            'lines.*.inventory_variant_id' => ['required', 'integer', 'distinct', 'exists:inventory_variants,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ], ['sales_reference.unique' => __('inventory.dispatch.duplicate')]);

        try {
            $ticket = DB::transaction(function () use ($data, $request) {
                $ticket = InventoryDispatch::create([
                    ...collect($data)->except('lines')->all(),
                    'status' => InventoryDispatch::PENDING,
                    'requested_by' => $request->user()->id,
                ]);

                foreach (collect($data['lines'])->sortBy('inventory_variant_id', SORT_NUMERIC) as $index => $line) {
                    $quantity = (int) $line['quantity'];
                    $updated = InventoryVariant::whereKey($line['inventory_variant_id'])
                        ->whereRaw('stock - reserved_stock >= ?', [$quantity])
                        ->increment('reserved_stock', $quantity);

                    if (! $updated) {
                        $variant = InventoryVariant::findOrFail($line['inventory_variant_id']);
                        throw ValidationException::withMessages([
                            "lines.$index.quantity" => __('inventory.dispatch.insufficient', [
                                'sku' => $variant->displaySku(), 'count' => $variant->availableStock(),
                            ]),
                        ]);
                    }
                }

                $ticket->lines()->createMany($data['lines']);
                $ticket->events()->create(['user_id' => $request->user()->id, 'status' => $ticket->status]);

                return $ticket;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! InventoryDispatch::where('sales_reference', $data['sales_reference'])->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['sales_reference' => __('inventory.dispatch.duplicate')]);
        }

        return to_route('inventory.dispatch.show', $ticket)
            ->with('dispatch_created', $ticket->id)
            ->with('status', __('inventory.dispatch.saved'));
    }

    public function show(Request $request, InventoryDispatch $dispatch): Response
    {
        return response()->view('pages.inventory.dispatch-show', [
            'ticket' => $dispatch->load('lines.variant', 'events.user'),
            'scanned' => $request->boolean('scan'),
            'justCreated' => $request->session()->get('dispatch_created') === $dispatch->id,
            'pageMetaTitle' => $dispatch->code().' | '.__('inventory.dispatch.title'),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function qr(Request $request, InventoryDispatch $dispatch): Response
    {
        return InventoryQr::render(
            route('inventory.dispatch.show', ['dispatch' => $dispatch, 'scan' => 1]),
            $request->boolean('download') ? Str::lower($dispatch->code()).'.svg' : null,
        );
    }

    public function printable(InventoryDispatch $dispatch, string $copy): Response
    {
        if ($copy === 'report') {
            $dispatch->load('lines.variant', 'events.user');
        }

        return response()->view('pages.inventory.dispatch-print', [
            'ticket' => $dispatch,
            'labelOnly' => $copy === 'qr',
            'pageMetaTitle' => $dispatch->code().' | '.__('inventory.dispatch.'.$copy),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function lookup(Request $request): JsonResponse|RedirectResponse
    {
        $value = $request->validate(['q' => ['required', 'string', 'max:2048']])['q'];
        $path = Str::beforeLast(route('inventory.dispatch.show', ['dispatch' => 'id'], absolute: false), '/');
        $id = InventoryQr::ticketId($value, 'OUT', $path);

        if ($id === null) {
            throw ValidationException::withMessages(['q' => __('inventory.dispatch.invalid_qr')]);
        }
        $ticket = InventoryDispatch::find($id, ['id']);
        if (! $ticket) {
            throw ValidationException::withMessages(['q' => __('inventory.scan.not_found')]);
        }

        $parameters = ['dispatch' => $ticket, 'scan' => 1];

        return $request->expectsJson()
            ? response()->json(['url' => route('inventory.dispatch.show', $parameters)])
            : to_route('inventory.dispatch.show', $parameters);
    }

    public function advance(Request $request, InventoryDispatch $dispatch, string $action): RedirectResponse
    {
        [$current, $next] = match ($action) {
            'start' => [InventoryDispatch::PENDING, InventoryDispatch::PACKING],
            'pack' => [InventoryDispatch::PACKING, InventoryDispatch::READY],
            'ship' => [InventoryDispatch::READY, InventoryDispatch::SHIPPED],
        };

        return $this->transition($request, $dispatch, [$current], $next);
    }

    public function cancel(Request $request, InventoryDispatch $dispatch): RedirectResponse
    {
        return $this->transition(
            $request,
            $dispatch,
            [InventoryDispatch::PENDING, InventoryDispatch::PACKING, InventoryDispatch::READY],
            InventoryDispatch::CANCELLED,
        );
    }

    private function transition(Request $request, InventoryDispatch $dispatch, array $current, string $next): RedirectResponse
    {
        DB::transaction(function () use ($request, $dispatch, $current, $next) {
            $updated = InventoryDispatch::whereKey($dispatch->id)->whereIn('status', $current)->update(['status' => $next]);
            if (! $updated) {
                throw ValidationException::withMessages(['status' => __('inventory.dispatch.stale')]);
            }

            if (in_array($next, [InventoryDispatch::SHIPPED, InventoryDispatch::CANCELLED], true)) {
                foreach ($dispatch->lines()->reorder('inventory_variant_id')->get() as $line) {
                    $variant = InventoryVariant::whereKey($line->inventory_variant_id)->where('reserved_stock', '>=', $line->quantity);
                    $quantities = ['reserved_stock' => $line->quantity];
                    if ($next === InventoryDispatch::SHIPPED) {
                        $variant->where('stock', '>=', $line->quantity);
                        $quantities['stock'] = $line->quantity;
                    }
                    if (! $variant->decrementEach($quantities)) {
                        throw ValidationException::withMessages(['status' => __('inventory.dispatch.reservation_error')]);
                    }
                }
            }

            $dispatch->events()->create(['user_id' => $request->user()->id, 'status' => $next]);
        }, 3);

        return to_route('inventory.dispatch.show', $dispatch)->with('status', __('inventory.dispatch.updated'));
    }
}

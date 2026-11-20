<?php

namespace App\Http\Controllers;

use App\Models\InventoryTicket;
use App\Models\InventoryVariant;
use App\Models\InventoryVendor;
use App\Support\InventoryQr;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryTicketController extends Controller
{
    public function addVendor(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = Str::squish($data['name']);
        $vendor = InventoryVendor::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first()
            ?? InventoryVendor::firstOrCreate(['name' => $name]);

        return response()->json($vendor->option(), $vendor->wasRecentlyCreated ? 201 : 200);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_vendor_id' => ['required', 'integer', 'exists:inventory_vendors,id'],
            'reference' => ['required', 'string', 'max:80'],
            'received_on' => ['required', 'date_format:Y-m-d'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['required', 'array:inventory_variant_id,received_quantity,failed_quantity,notes'],
            'lines.*.inventory_variant_id' => ['required', 'integer', 'distinct', 'exists:inventory_variants,id'],
            'lines.*.received_quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'lines.*.failed_quantity' => ['required', 'integer', 'min:0', 'lte:lines.*.received_quantity'],
            'lines.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reference = Str::upper(Str::squish($data['reference']));
        if (Str::length($reference) > 80) {
            throw ValidationException::withMessages(['reference' => __('inventory.reference.length')]);
        }

        $header = [
            'inventory_vendor_id' => $data['inventory_vendor_id'],
            'reference' => $reference,
            'received_on' => $data['received_on'],
            'inspected_by' => $request->user()->id,
        ];

        try {
            $ticket = DB::transaction(function () use ($header, $data) {
                $ticket = InventoryTicket::create($header);
                $ticket->lines()->createMany(array_map(fn ($line) => $line + $header, $data['lines']));

                return $ticket;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['reference' => __('inventory.ticket.duplicate')]);
        }

        return to_route('inventory.ticket.show', $ticket)->with('status', __('inventory.ticket.saved'));
    }

    public function show(InventoryTicket $ticket): View
    {
        return $this->details($ticket, false);
    }

    public function receive(InventoryTicket $ticket): View
    {
        return $this->details($ticket, true);
    }

    public function lookup(Request $request): JsonResponse|RedirectResponse
    {
        $value = $request->validate(['q' => ['required', 'string', 'max:2048']])['q'];
        $ticketPath = Str::beforeLast(route('inventory.ticket.receive', ['ticket' => 'id'], absolute: false), '/');
        $id = InventoryQr::ticketId($value, 'QC', $ticketPath);

        if ($id === null) {
            throw ValidationException::withMessages(['q' => __('inventory.scan.invalid')]);
        }

        $ticket = InventoryTicket::whereKey($id)->whereHas('passedLines')->first();
        if (! $ticket) {
            throw ValidationException::withMessages(['q' => __('inventory.scan.not_found')]);
        }

        return $request->expectsJson()
            ? response()->json(['url' => route('inventory.ticket.receive', $ticket)])
            : to_route('inventory.ticket.receive', $ticket);
    }

    private function details(InventoryTicket $ticket, bool $warehouse): View
    {
        $ticket->load('vendor', 'lines.variant');
        abort_if($warehouse && $ticket->status() === 'failed', 404);

        return view('pages.inventory.ticket', [
            'ticket' => $ticket,
            'warehouse' => $warehouse,
            'pageMetaTitle' => $ticket->code().' | '.__('inventory.title'),
        ]);
    }

    public function report(InventoryTicket $ticket, string $copy = 'warehouse'): View
    {
        $ticket->load('vendor', 'lines.variant', 'inspector', 'receiver');
        abort_if($copy === 'warehouse' && $ticket->status() === 'failed', 404);

        return view('pages.inventory.ticket-report', [
            'ticket' => $ticket,
            'copy' => $copy,
            'pageMetaTitle' => $ticket->code().' - '.__('inventory.ticket.'.$copy.'_report_title'),
        ]);
    }

    public function confirm(Request $request, InventoryTicket $ticket): RedirectResponse
    {
        DB::transaction(function () use ($ticket, $request) {
            $locked = InventoryTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $lines = $locked->passedLines()->reorder('inventory_variant_id')->get();
            abort_if($lines->isEmpty(), 409);

            $updated = InventoryTicket::whereKey($locked->id)->whereNull('confirmed_at')->update([
                'confirmed_at' => now(),
                'confirmed_by' => $request->user()->id,
            ]);
            if ($updated) {
                foreach ($lines as $line) {
                    InventoryVariant::whereKey($line->inventory_variant_id)->increment('stock', $line->passedQuantity());
                }
            }
        }, 3);

        return to_route('inventory.ticket.receive', $ticket)->with('status', __('inventory.receiving.confirmed'));
    }
}

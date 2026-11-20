<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTicket extends Model
{
    protected $fillable = ['inventory_vendor_id', 'reference', 'received_on', 'inspected_by'];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'confirmed_at' => 'datetime'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(InventoryVendor::class, 'inventory_vendor_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryReceipt::class)->orderBy('id');
    }

    public function passedLines(): HasMany
    {
        return $this->lines()->whereColumn('received_quantity', '>', 'failed_quantity');
    }

    public function code(): string
    {
        return sprintf('QC-%06d', $this->id);
    }

    public function status(): string
    {
        if ($this->confirmed_at) {
            return 'confirmed';
        }

        return $this->lines->contains(fn (InventoryReceipt $line) => $line->passedQuantity() > 0) ? 'pending' : 'failed';
    }
}

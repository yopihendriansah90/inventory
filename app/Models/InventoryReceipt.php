<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReceipt extends Model
{
    protected $fillable = ['inventory_variant_id', 'inventory_vendor_id', 'reference', 'received_on', 'received_quantity', 'failed_quantity', 'notes', 'inspected_by'];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'confirmed_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(InventoryVariant::class, 'inventory_variant_id');
    }

    public function passedQuantity(): int
    {
        return $this->received_quantity - $this->failed_quantity;
    }
}

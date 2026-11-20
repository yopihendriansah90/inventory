<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDispatch extends Model
{
    public const PENDING = 'pending';

    public const PACKING = 'packing';

    public const READY = 'ready';

    public const SHIPPED = 'shipped';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['sales_reference', 'recipient_name', 'recipient_phone', 'recipient_address', 'status', 'requested_by'];

    public function code(): string
    {
        return 'OUT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function warehouseAction(): ?string
    {
        return match ($this->status) {
            self::PENDING => 'start',
            self::PACKING => 'pack',
            self::READY => 'ship',
            default => null,
        };
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::PENDING, self::PACKING, self::READY], true);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryDispatchLine::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InventoryDispatchEvent::class)->orderBy('id');
    }
}

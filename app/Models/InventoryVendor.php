<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryVendor extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function code(): string
    {
        return sprintf('VND-%06d', $this->id);
    }

    public function option(): array
    {
        return ['id' => $this->id, 'label' => $this->code().' · '.$this->name];
    }
}

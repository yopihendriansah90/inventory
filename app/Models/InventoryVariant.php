<?php

namespace App\Models;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryVariant extends Model
{
    protected $fillable = ['sku', 'short_sku', 'model', 'color', 'size', 'stock'];

    public static function register(array $data): self
    {
        $sku = self::skuFor($data['model'], $data['color'], $data['size']);
        $existing = self::where('sku', $sku)->first();
        if ($existing) {
            return $existing;
        }

        $base = self::shortSkuBase($data['model'], $data['color'], $data['size'], $sku);
        while (true) {
            $shortSku = self::availableShortSku($base);
            try {
                return self::firstOrCreate(['sku' => $sku], $data + ['short_sku' => $shortSku]);
            } catch (UniqueConstraintViolationException $exception) {
                if (! self::where('short_sku', $shortSku)->exists()) {
                    throw $exception;
                }
            }
        }
    }

    public static function shortSkuBase(string $model, string $color, string $size, string $original): string
    {
        if (! preg_match('/-[0-9A-F]{16}\z/', $original)) {
            return $original;
        }

        $modelCode = substr(preg_replace('/[^a-z0-9]/', '', Str::slug($model, '')), 0, 3) ?: 'ITM';
        $colorCode = substr(preg_replace('/[^a-z0-9]/', '', Str::slug($color, '')), 0, 3) ?: 'CLR';

        return Str::upper(implode('-', [$modelCode, $colorCode, Str::slug($size)]));
    }

    public static function availableShortSku(string $base): string
    {
        $candidate = $base;
        for ($number = 2; self::where('short_sku', $candidate)->exists(); $number++) {
            $candidate = $base.'-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    public static function skuFor(string $model, string $color, string $size): string
    {
        $identity = array_map(fn (string $value) => Str::lower(Str::squish($value)), [$model, $color, $size]);

        if ($identity[0] === 'gamis amina' && $identity[1] === 'black' && preg_match('/^[a-z0-9]+$/D', $identity[2])) {
            return 'AMN-HTM-'.strtoupper($identity[2]);
        }

        $limits = [28, 16, 12];
        $parts = array_map(
            fn (string $value, int $limit) => trim(substr(Str::slug($value), 0, $limit), '-'),
            $identity,
            $limits,
        );
        $prefix = implode('-', array_filter($parts, fn (string $value) => $value !== '')) ?: 'ITEM';
        $suffix = substr(hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR)), 0, 16);

        return strtoupper($prefix.'-'.$suffix);
    }

    public function description(): string
    {
        return implode(' · ', [$this->model, $this->color, $this->size]);
    }

    public function pickerLabel(): string
    {
        return $this->displaySku().' · '.$this->description();
    }

    public function displaySku(): string
    {
        return $this->short_sku ?? $this->sku;
    }

    public function searchLabel(): string
    {
        return $this->pickerLabel().($this->displaySku() !== $this->sku ? ' '.$this->sku : '');
    }

    protected function casts(): array
    {
        return ['stock' => 'integer', 'reserved_stock' => 'integer'];
    }

    public function availableStock(): int
    {
        return $this->stock - $this->reserved_stock;
    }
}

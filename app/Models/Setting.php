<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:$key", function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            if (! $row) {
                return $default;
            }
            return match ($row->type) {
                'int' => (int) $row->value,
                'float' => (float) $row->value,
                'json' => json_decode((string) $row->value, true),
                default => $row->value,
            };
        });
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $stored = match ($type) {
            'json' => json_encode($value),
            default => (string) $value,
        };
        static::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type, 'group' => $group]);
        Cache::forget("setting:$key");
    }
}

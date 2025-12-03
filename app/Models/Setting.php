<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get setting value (decrypt if encrypted)
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Set setting value (encrypt if needed)
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get or create setting
     */
    public static function getOrCreate(string $key, $defaultValue = null, string $group = 'general', string $type = 'string', bool $encrypt = false): self
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            $setting = self::create([
                'key' => $key,
                'value' => $defaultValue,
                'group' => $group,
                'type' => $type,
                'is_encrypted' => $encrypt,
            ]);
        }
        
        return $setting;
    }

    /**
     * Get setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, $value, string $group = 'general', string $type = 'string', bool $encrypt = false): void
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->update([
                'value' => $value,
                'is_encrypted' => $encrypt,
            ]);
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'is_encrypted' => $encrypt,
            ]);
        }
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): \Illuminate\Support\Collection
    {
        return self::where('group', $group)->get();
    }
}

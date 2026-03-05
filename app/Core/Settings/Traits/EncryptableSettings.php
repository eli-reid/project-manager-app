<?php

namespace App\Core\Settings\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait EncryptableSettings
{
    /**
     * Mutator for the value attribute - encrypts if needed
     */
    public function setValueAttribute($value)
    {
        // Check if this setting should be encrypted based on the encrypted flag
        if ($this->shouldBeEncrypted()) {
            $this->attributes['value'] = Crypt::encryptString((string) $value);
        } else {
            $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
        }
    }

    /**
     * Mutator for the default_value attribute - JSON encode arrays
     */
    public function setDefaultValueAttribute($value)
    {
        $this->attributes['default_value'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Accessor for the value attribute - decrypts if needed
     */
    public function getValueAttribute($value)
    {
        if ($this->shouldBeEncrypted()) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return the original value
                // This handles cases where the value wasn't encrypted yet
                Log::warning("Failed to decrypt setting '{$this->key}': " . $e->getMessage());
                return $value;
            }
        }
        
        // Try to decode JSON for array values
        if (is_string($value) && $this->type === 'array') {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        
        return $value;
    }

    /**
     * Check if this specific setting should be encrypted
     */
    protected function shouldBeEncrypted(): bool
    {
        // Use the encrypted flag from the database
        return (bool) ($this->encrypted ?? false);
    }

    /**
     * Static method to check if a setting key should be encrypted
     * Used when creating/updating settings
     */
    public static function shouldEncrypt(string $key): bool
    {
        $setting = static::where('key', $key)->first();
        return $setting ? (bool) $setting->encrypted : false;
    }
}

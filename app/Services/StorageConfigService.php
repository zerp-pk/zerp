<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StorageConfigService
{
    private static $config = null;

    /**
     * Get the active storage disk name
     */
    public static function getActiveDisk(): string
    {
        $config = self::getStorageConfig();
        return $config['disk'] ?? 'public';
    }

    /**
     * Get file validation rules based on settings
     */
    public static function getFileValidationRules(): array
    {
        $config = self::getStorageConfig();

        $allowedTypes = $config['allowed_file_types'] ?? '';
        $maxSize = $config['max_file_size_kb'] ?? 2048;

        $rules = ['max:' . $maxSize];

        if (!empty($allowedTypes)) {
            $rules[] = 'mimes:' . $allowedTypes;
        }

        return $rules;
    }

    /**
     * Get complete storage configuration
     */
    public static function getStorageConfig(): array
    {
        $cacheKey = 'active_storage_config';
        return Cache::remember($cacheKey, 300, function() {
            return self::loadStorageConfigFromDB();
        });
    }

    /**
     * Clear storage configuration cache
     */
    public static function clearCache(): void
    {
        Cache::forget('active_storage_config');
    }

    /**
     * Load storage configuration from database
     */
    private static function loadStorageConfigFromDB(): array
    {
        try {

            $settings = getAdminAllSetting();
            // Map storageType to correct disk name
            $storageType = $settings['storageType'] ?? 'local';
            $diskName = match($storageType) {
                'local' => 'public',
                'aws_s3' => 's3',
                'wasabi' => 'wasabi',
                default => 'public'
            };

            return [
                'disk' => $diskName,
                'allowed_file_types' => $settings['allowedFileTypes'] ?? 'jpg,jpeg,png,webp,gif,pdf,doc,docx,csv,txt,zip,mp4,mp3,xlsx,xls,xlsm,xlsb,ods',
                'max_file_size_kb' => (int)($settings['maxUploadSize'] ?? 2048),
                's3' => [
                    'key' => $settings['awsAccessKeyId'] ?? '',
                    'secret' => $settings['awsSecretAccessKey'] ?? '',
                    'bucket' => $settings['awsBucket'] ?? '',
                    'region' => $settings['awsDefaultRegion'] ?? 'us-east-1',
                    'url' => $settings['awsUrl'] ?? '',
                    'endpoint' => $settings['awsEndpoint'] ?? '',
                ],
                'wasabi' => [
                    'key' => $settings['wasabiAccessKey'] ?? '',
                    'secret' => $settings['wasabiSecretKey'] ?? '',
                    'bucket' => $settings['wasabiBucket'] ?? '',
                    'region' => $settings['wasabiRegion'] ?? 'us-east-1',
                    'url' => $settings['wasabiUrl'] ?? '',
                    'root' => $settings['wasabiRoot'] ?? '',
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to load storage config from DB', ['error' => $e->getMessage()]);
            return self::getDefaultConfig();
        }
    }

    /**
     * Get default storage configuration
     */
    private static function getDefaultConfig(): array
    {
        return [
            'disk' => 'public',
            'allowed_file_types' => 'jpg,jpeg,png,webp,gif,pdf,doc,docx,csv,txt,zip,mp4,mp3,xlsx,xls,xlsm,xlsb,ods',
            'max_file_size_kb' => 2048,
            's3' => [],
            'wasabi' => []
        ];
    }
}
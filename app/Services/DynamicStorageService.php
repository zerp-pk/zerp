<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    /**
     * Configure dynamic storage disks based on database settings
     */
    public static function configureDynamicDisks(): void
    {
        try {
            $config = StorageConfigService::getStorageConfig();
            
            // Configure S3 disk if credentials exist
            if (!empty($config['s3']['key']) && !empty($config['s3']['secret'])) {
                self::configureS3Disk($config['s3']);
            }
            
            // Configure Wasabi disk if credentials exist
            if (!empty($config['wasabi']['key']) && !empty($config['wasabi']['secret'])) {
                self::configureWasabiDisk($config['wasabi']);
            }
        } catch (\InvalidArgumentException $e) {
            \Log::error('Invalid storage configuration', [
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to configure dynamic storage disks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Configure S3 disk
     */
    private static function configureS3Disk(array $s3Config): void
    {
         config(
                [
                    'filesystems.disks.s3.key' => $s3Config['key'],
                    'filesystems.disks.s3.secret' => $s3Config['secret'],
                    'filesystems.disks.s3.region' => $s3Config['region'],
                    'filesystems.disks.s3.bucket' => $s3Config['bucket'],
                    // 'filesystems.disks.s3.url' => $storage_settings['s3_url'],
                    // 'filesystems.disks.s3.endpoint' => $storage_settings['s3_endpoint'],
                ]
            );
    }
    
    /**
     * Configure Wasabi disk
     */
    private static function configureWasabiDisk(array $wasabiConfig): void
    {
        $required = ['key', 'secret', 'bucket'];
        foreach ($required as $field) {
            if (empty($wasabiConfig[$field])) {
                throw new \InvalidArgumentException("Missing required Wasabi configuration: {$field}");
            }
        }

        $region = $wasabiConfig['region'] ?: 'us-east-1';
        $endpoint = $wasabiConfig['url'] ?: ('https://s3.' . $region . '.wasabisys.com');
        
        Config::set('filesystems.disks.wasabi', [
            'driver' => 's3',
            'key' => $wasabiConfig['key'],
            'secret' => $wasabiConfig['secret'],
            'region' => $region,
            'bucket' => $wasabiConfig['bucket'],
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => false,
            'visibility' => 'public',
        ]);
    }

    /**
     * Get the active storage disk instance
     */
    public static function getActiveDiskInstance()
    {
        $diskName = StorageConfigService::getActiveDisk();
        
        // Ensure disk is configured
        self::configureDynamicDisks();
        
        try {
            return Storage::disk($diskName);
        } catch (\Exception $e) {
            \Log::warning('Failed to get active storage disk, falling back to public', [
                'disk' => $diskName,
                'error' => $e->getMessage()
            ]);
            try {
                return Storage::disk('public');
            } catch (\Exception $fallbackError) {
                \Log::error('Fallback disk public also failed', [
                    'error' => $fallbackError->getMessage()
                ]);
                throw new \RuntimeException('No available storage disk');
            }
        }
    }

    /**
     * Test storage connection
     */
    public static function testConnection(string $diskName): bool
    {
        try {
            self::configureDynamicDisks();
            $disk = Storage::disk($diskName);
            
            // Try to write and read a test file with unique name
            $testContent = 'test-' . time();
            $testPath = 'test-connection-' . bin2hex(random_bytes(8)) . '.txt';
            
            $disk->put($testPath, $testContent);
            $retrieved = $disk->get($testPath);
            
            return $retrieved === $testContent;
        } catch (\Exception $e) {
            \Log::error('Storage connection test failed', [
                'disk' => $diskName,
                'error' => $e->getMessage()
            ]);
            return false;
        } finally {
            try {
                if (isset($testPath)) {
                    $disk->delete($testPath);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to cleanup test file', [
                    'path' => $testPath ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
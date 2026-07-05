<?php

// Get command line arguments
$packageName = $argv[1] ?? null;

// Define the base directories to scan (excluding packages for now)
$baseDirectories = [
    __DIR__ . '/resources/js/pages',
    __DIR__ . '/resources/js/components',
    __DIR__ . '/resources/js/layouts',
    __DIR__ . '/resources/views',
    __DIR__ . '/app'
];

$outputFile = __DIR__ . '/resources/lang/en.json';
$packagesPath = __DIR__ . '/packages/workdo';

// Initialize arrays to store translations
$translations = [];
$packageTranslations = [];

// Load existing translations if the file exists
if (file_exists($outputFile)) {
    $existingContent = @file_get_contents($outputFile);
    if ($existingContent !== false) {
        $decoded = json_decode($existingContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $translations = $decoded;
        }
    }
}

// Function to recursively scan directories
function scanDirectory($dir, &$translations, $packageName = null) {
    $realDir = realpath($dir);
    if (!$realDir || !is_dir($realDir)) {
        return;
    }
    
    $files = scandir($realDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $realDir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $translations, $packageName);
        } else {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $isBladeTemplate = str_ends_with($path, '.blade.php');
            if (in_array($extension, ['tsx', 'jsx', 'ts', 'php']) || $isBladeTemplate) {
                extractTranslations($path, $translations);
            }
        }
    }
}

// Function to extract translations from a file
function extractTranslations($file, &$translations) {
    $content = @file_get_contents($file);
    if ($content === false) {
        return;
    }
    
    // Match t("...") and t('...') patterns
    preg_match_all('/(?<![a-zA-Z0-9_])t\(["\']([^"\']*)["\'\)]/', $content, $tMatches);
    
    // Match __("...") and __('...') patterns
    preg_match_all('/__\(["\']([^"\']*)["\'\)]/', $content, $underscoreMatches);
    
    // Combine all matches and add to translations
    $allMatches = array_merge($tMatches[1], $underscoreMatches[1]);
    foreach ($allMatches as $match) {
        $translations[$match] = $match;
    }
}

// Function to process a specific package
function processPackage($packageDir, $packageName) {
    $packageTranslations = [];
    
    // Load existing package translations
    $packageLangFile = $packageDir . '/src/Resources/lang/en.json';
    if (file_exists($packageLangFile)) {
        $existingContent = @file_get_contents($packageLangFile);
        if ($existingContent !== false) {
            $decoded = json_decode($existingContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $packageTranslations = $decoded;
            }
        }
    }
    
    scanDirectory($packageDir, $packageTranslations, $packageName);
    
    // Sort and save package translations
    ksort($packageTranslations);
    
    $packageLangDir = dirname($packageLangFile);
    if (!is_dir($packageLangDir)) {
        mkdir($packageLangDir, 0755, true);
    }
    
    file_put_contents($packageLangFile, json_encode($packageTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Package '$packageName': Found " . count($packageTranslations) . " strings.\n";
    
    return $packageTranslations;
}

// Handle package extraction based on arguments
if ($packageName) {
    // Extract specific package only
    $specificPackageDir = $packagesPath . '/' . $packageName;
    if (is_dir($specificPackageDir)) {
        echo "Extracting translations for package: $packageName\n";
        processPackage($specificPackageDir, $packageName);
    } else {
        echo "Error: Package '$packageName' not found in $packagesPath\n";
        exit(1);
    }
} else {
    // Extract main translations + all packages
    foreach ($baseDirectories as $directory) {
        scanDirectory($directory, $translations);
    }
    
    echo "Extracting translations for all packages...\n";
    $packageDirs = glob($packagesPath . '/*', GLOB_ONLYDIR);
    foreach ($packageDirs as $packageDir) {
        $currentPackageName = basename($packageDir);
        processPackage($packageDir, $currentPackageName);
    }
    
    // Sort main translations alphabetically
    ksort($translations);
    
    // Create directory if it doesn't exist
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Write main translations file
    file_put_contents($outputFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "Main translation extraction complete. Found " . count($translations) . " strings.\n";
}
echo "\nUsage: php extract-translations.php [package_name]\n";
echo "  - No arguments: Extract all packages + main translations\n";
echo "  - package_name: Extract specific package only\n";
echo "  Example: php extract-translations.php Test\n";

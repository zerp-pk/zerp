<?php

namespace App\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CrudlyGenerator
{
    protected $fieldTypes;
    protected $stubPath;

    public function __construct()
    {
        $this->fieldTypes = require base_path('stubs/crudly/config/field-types.php');
        $this->stubPath = base_path('stubs/crudly/templates');
    }

    public function generate($name, $options)
    {
        $data = $this->prepareData($name, $options);
        
        $this->generateBackend($data);
        $this->generateFrontend($data);
        $this->generateConfig($data);
    }

    protected function prepareData($name, $options)
    {
        $fields = $this->parseFields($options['fields']);
        $relationships = $this->parseRelationships($options['relationships'] ?? '');
        $tableRelationships = $this->parseTableRelationships($options['table_relationships'] ?? '', $relationships);
        $tableFields = !empty($options['table_fields']) ? array_filter(explode(',', $options['table_fields']), fn($item) => !empty(trim($item))) : [];
        $searchable = explode(',', $options['searchable']);
        $filterable = !empty($options['filterable']) ? array_filter(explode(',', $options['filterable']), fn($item) => !empty(trim($item))) : [];
        $package = $options['package'] ?? null;
        $isSystemSetup = isset($options['system_setup']) && $options['system_setup'] === true && !empty($package);

        // Process name to handle multi-word names
        $processedName = Str::studly($name); // Convert "Priority Stage" to "PriorityStage"
        $nameForDisplay = $name; // Keep original for display purposes
        
        $data = [
            'name' => $processedName,
            'name_display' => $nameForDisplay,
            'name_lower' => Str::lower($processedName),
            'name_plural' => Str::plural($processedName),
            'name_plural_lower' => Str::lower(Str::plural($processedName)),
            'name_plural_display' => Str::plural($nameForDisplay),
            'name_kebab' => Str::kebab(Str::plural($processedName)),
            'name_snake' => Str::snake(Str::plural($processedName)),
            'fields' => $fields,
            'relationships' => $relationships,
            'table_relationships' => $tableRelationships,
            'table_fields' => $tableFields,
            'searchable' => $searchable,
            'filterable' => $filterable,
            'view' => $options['view'] ?? '',
            'icon' => $options['icon'] ?? 'Tag',
            'fillable' => $this->getFillableFields($fields, $relationships),
            'casts' => $this->getCasts($fields),
            'validation_rules' => $this->getValidationRules($fields, $relationships),
            'form_imports' => $this->getFormImports($fields, $relationships),
            'table_columns' => $this->getTableColumns($fields, $searchable, $filterable, $tableRelationships, $tableFields),
            'package' => $package,
            'is_package' => !empty($package),
            'is_system_setup' => $isSystemSetup,
            'system_setup_icon' => $options['system_setup_icon'] ?? 'Settings'
        ];

        // Add package-specific data
        if ($package) {
            $data['package_lower'] = Str::lower($package);
            $data['package_kebab'] = Str::kebab($package);
            $data['package_namespace'] = "Zerp\\{$package}";
            $data['table_name'] = $data['name_snake'];
            
            if ($isSystemSetup) {
                $data['route_prefix'] = Str::kebab($package) . '.' . $data['name_kebab'];
                $data['inertia_path'] = $package . '/SystemSetup/' . $data['name_plural'];
            } else {
                $data['route_prefix'] = Str::kebab($package) . '.' . $data['name_kebab'];
                $data['inertia_path'] = $package . '/' . $data['name_plural'];
            }
        } else {
            $data['table_name'] = $data['name_snake'];
            $data['route_prefix'] = $data['name_kebab'];
            $data['inertia_path'] = $data['name_kebab'];
        }

        return $data;
    }

    protected function parseFields($fieldsString)
    {
        $fields = [];
        foreach (explode(',', $fieldsString) as $field) {
            $field = trim($field);
            
            // Parse name:type:validation:options format
            // But be careful with validation rules that contain colons (like max:100)
            $firstColon = strpos($field, ':');
            if ($firstColon === false) {
                $name = $field;
                $type = 'textbox';
                $customValidation = null;
                $options = null;
            } else {
                $name = trim(substr($field, 0, $firstColon));
                $remaining = substr($field, $firstColon + 1);
                
                $secondColon = strpos($remaining, ':');
                if ($secondColon === false) {
                    $type = trim($remaining);
                    $customValidation = null;
                    $options = null;
                } else {
                    $type = trim(substr($remaining, 0, $secondColon));
                    $afterType = substr($remaining, $secondColon + 1);
                    
                    // Find the last colon for options (if exists)
                    $lastColon = strrpos($afterType, ':');
                    if ($lastColon !== false && (strpos($afterType, '|') > $lastColon || strpos(substr($afterType, $lastColon + 1), '@') === 0)) {
                        // Options exist (colon after pipe means options OR starts with @)
                        $customValidation = trim(substr($afterType, 0, $lastColon));
                        $options = trim(substr($afterType, $lastColon + 1));
                    } else {
                        // No options, everything is validation
                        $customValidation = trim($afterType);
                        $options = null;
                    }
                }
            }
            
            // Check if it's nullable from validation rules
            $nullable = $customValidation && str_contains($customValidation, 'nullable');
            
            // Extract showPreview parameter and remove it from validation
            $showPreview = false;
            if ($customValidation && str_contains($customValidation, 'showPreview')) {
                $showPreview = true;
                $customValidation = str_replace(['|showPreview', 'showPreview|', 'showPreview'], '', $customValidation);
                $customValidation = preg_replace('/\|+/', '|', trim($customValidation, '|'));
            }
            
            // Extract multiple parameter and remove it from validation
            $multiple = false;
            if ($customValidation && str_contains($customValidation, 'multiple')) {
                $multiple = true;
                $customValidation = str_replace(['|multiple', 'multiple|', 'multiple'], '', $customValidation);
                $customValidation = preg_replace('/\|+/', '|', trim($customValidation, '|'));
            }
            
            // Extract searchable parameter and remove it from validation
            $searchable = false;
            if ($customValidation && str_contains($customValidation, 'searchable')) {
                $searchable = true;
                $customValidation = str_replace(['|searchable', 'searchable|', 'searchable'], '', $customValidation);
                $customValidation = preg_replace('/\|+/', '|', trim($customValidation, '|'));
            }
            
            // Parse options if provided (format: option1|option2|option3 OR @Model.field)
            $parsedOptions = [];
            $dynamicModel = null;
            $dynamicField = null;
            
            if ($options) {
                // Check if it's a dynamic model reference (@Model.field)
                if (strpos($options, '@') === 0) {
                    $modelRef = substr($options, 1); // Remove @ prefix
                    if (strpos($modelRef, '.') !== false) {
                        list($dynamicModel, $dynamicField) = explode('.', $modelRef, 2);
                        $dynamicModel = trim($dynamicModel);
                        $dynamicField = trim($dynamicField);
                    }
                } else {
                    // Static options
                    $optionList = explode('|', $options);
                    foreach ($optionList as $i => $option) {
                        $parsedOptions[$i] = trim($option);
                    }
                }
            }
            
            // Also check for options in custom validation (format: array:option1|option2|option3)
            if (empty($parsedOptions) && $customValidation && strpos($customValidation, 'array:') !== false) {
                // Extract everything after 'array:'
                $arrayPos = strpos($customValidation, 'array:');
                $afterArray = substr($customValidation, $arrayPos + 6); // Skip 'array:'
                
                // Split by | and take all parts as options
                $optionList = explode('|', $afterArray);
                foreach ($optionList as $i => $option) {
                    $cleanOption = trim($option);
                    // Skip validation rules and empty options
                    if (!empty($cleanOption) && !in_array($cleanOption, ['nullable', 'required', 'array', 'string', 'numeric', 'boolean'])) {
                        $parsedOptions[] = $cleanOption;
                    }
                }
            }
            
            // Clean the custom validation to remove the options part for multiselect fields
            if ($type === 'multiselect' && strpos($customValidation, 'array:') !== false) {
                // Replace everything from 'array:' to the end with just 'array'
                $customValidation = preg_replace('/array:.*$/', 'array', $customValidation);
            }
            
            // Get base config and modify for media fields
            $config = $this->fieldTypes[$type] ?? $this->fieldTypes['textbox'];
            
            // Dynamic configuration for media fields
            if ($type === 'media' && $multiple) {
                $config['migration'] = "\$table->json('{field}')->nullable();";
                $config['validation'] = 'nullable|array';
                $config['validation_nullable'] = 'nullable|array';
                $config['cast'] = 'array';
                $config['faker'] = 'json_encode([fake()->imageUrl(640, 480, null, false), fake()->imageUrl(640, 480, null, false)])';
            }
            
            // Dynamic configuration for select fields with custom options
            if ($type === 'select' && !empty($parsedOptions)) {
                $config['migration'] = "\$table->string('{field}')->default('0');";
                $config['validation'] = 'required|string';
                $config['validation_nullable'] = 'nullable|string';
                $config['cast'] = 'string';
                // Keep status_badge for table rendering even with custom options
                $config['render_table'] = 'status_badge';
                $config['faker'] = 'fake()->randomElement(["0", "1", "2", "3", "4"])';
            }
            
            $fields[$name] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
                'custom_validation' => $customValidation,
                'options' => $parsedOptions,
                'config' => $config,
                'showPreview' => $showPreview,
                'multiple' => $multiple,
                'searchable' => $searchable,
                'dynamic_model' => $dynamicModel ?: null,
                'dynamic_field' => $dynamicField ?: null
            ];
        }
        return $fields;
    }

    protected function parseRelationships($relationshipsString)
    {
        $relationships = [];
        if (empty($relationshipsString)) {
            return $relationships;
        }

        foreach (explode(',', $relationshipsString) as $relationship) {
            $relationship = trim($relationship);
            
            // Parse relation:type:Model:foreign_key
            $parts = explode(':', $relationship);
            if (count($parts) >= 3) {
                $relationName = trim($parts[0]);
                $relationType = trim($parts[1]);
                $relatedModel = trim($parts[2]);
                $foreignKey = isset($parts[3]) ? trim($parts[3]) : $relationName . '_id';
                
                $relationships[$relationName] = [
                    'name' => $relationName,
                    'type' => $relationType,
                    'model' => $relatedModel,
                    'foreign_key' => $foreignKey,
                    'table' => Str::snake(Str::plural($relatedModel))
                ];
            }
        }
        
        return $relationships;
    }

    protected function parseTableRelationships($tableRelationshipsString, $relationships)
    {
        $tableRelationships = [];
        if (empty($tableRelationshipsString)) {
            return $tableRelationships;
        }

        foreach (explode(',', $tableRelationshipsString) as $tableRel) {
            $tableRel = trim($tableRel);
            
            // Parse relation.field format
            if (strpos($tableRel, '.') !== false) {
                list($relationName, $field) = explode('.', $tableRel, 2);
                $relationName = trim($relationName);
                $field = trim($field);
                
                if (isset($relationships[$relationName])) {
                    $tableRelationships[] = [
                        'relation' => $relationName,
                        'field' => $field,
                        'model' => $relationships[$relationName]['model'],
                        'label' => Str::title(str_replace('_', ' ', $relationName)) . ' ' . Str::title($field)
                    ];
                }
            }
        }
        
        return $tableRelationships;
    }

    protected function getFillableFields($fields, $relationships = [])
    {
        $fillable = [];
        foreach ($fields as $field) {
            if ($field['config']['fillable']) {
                $fillable[] = $field['name'];
            }
        }
        
        // Add relationship foreign keys to fillable
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $fillable[] = $relationship['foreign_key'];
            }
        }
        
        return $fillable;
    }

    protected function getCasts($fields)
    {
        $casts = [];
        foreach ($fields as $field) {
            if (!empty($field['dynamic_model'])) {
                // Dynamic model fields should be cast as integer for single selection
                if ($field['type'] === 'radiobutton' || $field['type'] === 'select') {
                    $casts[$field['name']] = 'integer';
                } else {
                    // Multiple selection fields (checkboxgroup, multiselect) as array
                    $casts[$field['name']] = 'array';
                }
            } elseif (isset($field['config']['cast'])) {
                $casts[$field['name']] = $field['config']['cast'];
            }
        }
        return $casts;
    }

    protected function getValidationRules($fields, $relationships = [])
    {
        $rules = [];
        foreach ($fields as $field) {
            // Handle slider fields specially
            if ($field['type'] === 'slider') {
                $rules[$field['name']] = 'nullable|array';
                $rules[$field['name'] . '.0'] = 'integer|min:0|max:100';
            } elseif (!empty($field['dynamic_model'])) {
                // Handle dynamic model fields
                if ($field['type'] === 'radiobutton' || $field['type'] === 'select') {
                    // Single selection - integer validation
                    $rules[$field['name']] = $field['nullable'] ? 'nullable|integer' : 'required|integer';
                } else {
                    // Multiple selection - array validation
                    $rules[$field['name']] = 'nullable|array';
                }
            } else {
                // Use custom validation if provided, otherwise use default
                if (isset($field['custom_validation']) && $field['custom_validation'] !== null) {
                    // If custom validation is empty string, no validation
                    if ($field['custom_validation'] === '') {
                        continue; // Skip this field - no validation
                    }
                    $rules[$field['name']] = $field['custom_validation'];
                } else {
                    // Use default validation from field-types.php
                    $validation = $field['nullable'] 
                        ? ($field['config']['validation_nullable'] ?? $field['config']['validation'])
                        : $field['config']['validation'];
                    $rules[$field['name']] = $validation;
                }
            }
        }
        
        // Add relationship validation rules
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $rules[$relationship['foreign_key']] = 'nullable|exists:' . $relationship['table'] . ',id';
            }
        }
        
        return $rules;
    }

    protected function getFormImports($fields, $relationships = [])
    {
        $imports = [];
        $importPaths = [];
        
        // Always include Label and InputError
        $importPaths[] = "import { Label } from '@/components/ui/label';";
        $importPaths[] = "import InputError from '@/components/ui/input-error';";
        
        foreach ($fields as $field) {
            $component = $field['config']['component'];
            $import = $field['config']['import'];
            
            if ($component === 'Input') {
                $importPaths[] = "import { Input } from '@/components/ui/input';";
            } elseif ($component === 'Textarea') {
                $importPaths[] = "import { Textarea } from '@/components/ui/textarea';";
            } elseif ($component === 'Select') {
                $importPaths[] = "import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';";
            } elseif ($component === 'RadioGroup') {
                $importPaths[] = "import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';";
            } elseif ($component === 'Checkbox') {
                $importPaths[] = "import { Checkbox } from '@/components/ui/checkbox';";
            } elseif ($component === 'CheckboxGroup') {
                $importPaths[] = "import { Checkbox } from '@/components/ui/checkbox';";
            } elseif ($component === 'DatePicker') {
                $importPaths[] = "import { DatePicker } from '@/components/ui/date-picker';";
            } elseif ($component === 'DateRangePicker') {
                $importPaths[] = "import { DateRangePicker } from '@/components/ui/date-range-picker';";
            } elseif ($component === 'DateTimeRangePicker') {
                $importPaths[] = "import { DateTimeRangePicker } from '@/components/ui/datetime-range-picker';";
            } elseif ($component === 'TagsInput') {
                $importPaths[] = "import { TagsInput } from '@/components/ui/tags-input';";
            } elseif ($component === 'CurrencyInput') {
                $importPaths[] = "import { CurrencyInput } from '@/components/ui/currency-input';";
            } elseif ($component === 'RichTextEditor') {
                $importPaths[] = "import { RichTextEditor } from '@/components/ui/rich-text-editor';";
            } elseif ($component === 'PhoneInputComponent') {
                $importPaths[] = "import { PhoneInputComponent } from '@/components/ui/phone-input';";
            } elseif ($component === 'Slider') {
                $importPaths[] = "import { Slider } from '@/components/ui/slider';";
            } elseif ($component === 'Switch') {
                $importPaths[] = "import { Switch } from '@/components/ui/switch';";
            } elseif ($component === 'Rating') {
                $importPaths[] = "import { Rating } from '@/components/ui/rating';";
            } elseif ($component === 'MediaPicker') {
                $importPaths[] = "import MediaPicker from '@/components/MediaPicker';";
            } elseif ($component === 'MultiSelectEnhanced') {
                $importPaths[] = "import { MultiSelectEnhanced } from '@/components/ui/multi-select-enhanced';";
            }
        }
        
        // Add Select import if there are relationships (they use Select for dropdowns)
        if (!empty($relationships)) {
            $importPaths[] = "import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';";
        }
        
        return implode("\n", array_unique($importPaths));
    }

    protected function getTableColumns($fields, $searchable, $filterable, $tableRelationships = [], $tableFields = [])
    {
        $columns = [];
        
        // If table_fields is specified, filter fields
        if (!empty($tableFields)) {
            foreach ($tableFields as $fieldName) {
                $fieldName = trim($fieldName);
                
                // Check if it's a regular field
                if (isset($fields[$fieldName])) {
                    $field = $fields[$fieldName];
                    $columns[] = [
                        'name' => $field['name'],
                        'type' => $field['type'],
                        'sortable' => in_array($field['name'], $searchable),
                        'render' => $field['config']['render_table'] ?? null
                    ];
                }
                
                // Check if it's a relationship field
                foreach ($tableRelationships as $tableRel) {
                    $relationFieldName = $tableRel['relation'] . '.' . $tableRel['field'];
                    if ($fieldName === $relationFieldName) {
                        $columns[] = [
                            'name' => $relationFieldName,
                            'type' => 'relationship',
                            'sortable' => false,
                            'render' => null,
                            'label' => $tableRel['label']
                        ];
                    }
                }
            }
        } else {
            // Default behavior: show all fields
            foreach ($fields as $field) {
                $columns[] = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'sortable' => in_array($field['name'], $searchable),
                    'render' => $field['config']['render_table'] ?? null
                ];
            }
            
            // Add all relationship columns
            foreach ($tableRelationships as $tableRel) {
                $columns[] = [
                    'name' => $tableRel['relation'] . '.' . $tableRel['field'],
                    'type' => 'relationship',
                    'sortable' => false,
                    'render' => null,
                    'label' => $tableRel['label']
                ];
            }
        }
        
        return $columns;
    }

    protected function generateBackend($data)
    {
        if ($data['is_package']) {
            $this->generatePackageMigration($data);
            $this->generatePackageModel($data);
            $this->generatePackageController($data);
            $this->generatePackageRequests($data);
            $this->generatePackageSeeder($data);
        } else {
            $this->generateMigration($data);
            $this->generateModel($data);
            $this->generateController($data);
            $this->generateRequests($data);
            $this->generateSeeder($data);
        }
    }

    protected function generateFrontend($data)
    {
        if ($data['is_package']) {
            if ($data['is_system_setup']) {
                $this->generatePackageSystemSetupPages($data);
            } else {
                $this->generatePackageIndexPage($data);
                $this->generatePackageCreatePage($data);
                $this->generatePackageEditPage($data);
                $this->generatePackageTypes($data);
                
                // Generate view component if view parameter is provided
                if (!empty($data['view'])) {
                    if ($data['view'] === 'modal') {
                        $this->generatePackageViewPage($data);
                    } elseif ($data['view'] === 'page') {
                        $this->generatePackageViewPageComponent($data);
                    }
                }
            }
        } else {
            $this->generateIndexPage($data);
            $this->generateCreatePage($data);
            $this->generateEditPage($data);
            $this->generateTypes($data);
            
            // Generate view component if view parameter is provided
            if (!empty($data['view'])) {
                if ($data['view'] === 'modal') {
                    $this->generateViewPage($data);
                } elseif ($data['view'] === 'page') {
                    $this->generateViewPageComponent($data);
                }
            }
        }
    }

    protected function generateConfig($data)
    {
        if ($data['is_package']) {
            $this->generatePackagePermissions($data);
            $this->generatePackageMenuItem($data);
            $this->generatePackageRoutes($data);
            $this->addSeederToPackageDatabase($data);
        } else {
            $this->generatePermissions($data);
            $this->generateMenuItem($data);
            $this->generateRoutes($data);
            $this->generateDependentDropdownRoutes($data);
            $this->addSeederToDatabase($data);
            $this->updateExistingModelsWithReverseRelationships($data);
        }
    }

    protected function generateMigration($data)
    {
        $stub = File::get($this->stubPath . '/backend/migration.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $filename = date('Y_m_d_His') . '_create_' . $data['name_snake'] . '_table.php';
        $path = database_path('migrations/' . $filename);
        
        File::put($path, $content);
    }

    protected function generateModel($data)
    {
        $stub = File::get($this->stubPath . '/backend/model.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $path = app_path('Models/' . $data['name'] . '.php');
        File::put($path, $content);
    }

    protected function generateController($data)
    {
        $stub = File::get($this->stubPath . '/backend/controller.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $path = app_path('Http/Controllers/' . $data['name'] . 'Controller.php');
        File::put($path, $content);
    }

    protected function generateRequests($data)
    {
        // Store Request
        $stub = File::get($this->stubPath . '/backend/store-request.stub');
        $content = $this->replacePlaceholders($stub, $data);
        $path = app_path('Http/Requests/Store' . $data['name'] . 'Request.php');
        File::put($path, $content);

        // Update Request
        $stub = File::get($this->stubPath . '/backend/update-request.stub');
        $content = $this->replacePlaceholders($stub, $data);
        $path = app_path('Http/Requests/Update' . $data['name'] . 'Request.php');
        File::put($path, $content);
    }

    protected function generatePackageRequests($data)
    {
        // Store Request
        $stub = File::get($this->stubPath . '/backend/store-request.stub');
        $content = $this->replacePlaceholders($stub, $data);
        $path = base_path("packages/local/{$data['package']}/src/Http/Requests/Store{$data['name']}Request.php");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        // Update Request
        $stub = File::get($this->stubPath . '/backend/update-request.stub');
        $content = $this->replacePlaceholders($stub, $data);
        $path = base_path("packages/local/{$data['package']}/src/Http/Requests/Update{$data['name']}Request.php");
        File::put($path, $content);
    }

    protected function generateSeeder($data)
    {
        $stub = File::get($this->stubPath . '/backend/seeder.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $path = database_path('seeders/' . $data['name'] . 'Seeder.php');
        File::put($path, $content);
    }

    protected function generateIndexPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/index.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        File::put($dir . '/index.tsx', $content);
    }

    protected function generateCreatePage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/create.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        File::put($dir . '/create.tsx', $content);
    }

    protected function generateEditPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/edit.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        File::put($dir . '/edit.tsx', $content);
    }

    protected function generateTypes($data)
    {
        $stub = File::get($this->stubPath . '/frontend/types.ts.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        File::put($dir . '/types.ts', $content);
    }

    protected function generateViewPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/view.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        File::put($dir . '/View.tsx', $content);
    }

    protected function generateViewPageComponent($data)
    {
        $stub = File::get($this->stubPath . '/frontend/view-page.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $dir = resource_path('js/pages/' . $data['name_kebab']);
        File::put($dir . '/Show.tsx', $content);
    }

    protected function generatePermissions($data)
    {
        $permissionSeederPath = database_path('seeders/PermissionRoleSeeder.php');
        $content = File::get($permissionSeederPath);
        
        $newPermissions = "            // {$data['name']} management
            ['name' => 'manage-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage {$data['name_plural']}'],
            ['name' => 'manage-any-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage All {$data['name_plural']}'],
            ['name' => 'manage-own-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage Own {$data['name_plural']}'],
            ['name' => 'view-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'View {$data['name_plural']}'],
            ['name' => 'create-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Create {$data['name_plural']}'],
            ['name' => 'edit-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Edit {$data['name_plural']}'],
            ['name' => 'delete-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Delete {$data['name_plural']}'],";
        
        $content = str_replace(
            "            // Language management",
            $newPermissions . "\n\n            // Language management",
            $content
        );
        
        $companyPermissions = "                    'manage-{$data['name_kebab']}', 'manage-any-{$data['name_kebab']}', 'manage-own-{$data['name_kebab']}', 'view-{$data['name_kebab']}', 'create-{$data['name_kebab']}', 'edit-{$data['name_kebab']}', 'delete-{$data['name_kebab']}',";
        
        $content = str_replace(
            "                    'manage-settings', 'edit-settings',",
            "                    {$companyPermissions}\n                    'manage-settings', 'edit-settings',",
            $content
        );
        
        File::put($permissionSeederPath, $content);
    }

    protected function generateMenuItem($data)
    {
        $menuPath = resource_path('js/utils/menus/company-menu.ts');
        $content = File::get($menuPath);
        
        // Add icon import if not already present
        $iconName = $data['icon'];
        if (!preg_match('/\b' . preg_quote($iconName, '/') . '\b/', $content)) {
            // Find and replace the import line
            $pattern = '/import\s*{([^}]+)}\s*from\s*[\'"]lucide-react[\'"];/';
            if (preg_match($pattern, $content, $matches)) {
                $currentIcons = $matches[1];
                // Add the new icon to the import
                $newImport = "import { {$currentIcons}, {$iconName} } from 'lucide-react';";
                $content = preg_replace($pattern, $newImport, $content);
            }
        }
        
        $newMenuItem = "    {
        title: t('{$data['name_plural_display']}'),
        href: route('{$data['name_kebab']}.index'),
        icon: {$data['icon']},
        permission: 'manage-{$data['name_kebab']}',
        order: 70,
    },";
        
        $content = str_replace(
            "    {
        title: t('Media Library'),",
            $newMenuItem . "\n    {
        title: t('Media Library'),",
            $content
        );
        
        File::put($menuPath, $content);
    }

    protected function generateRoutes($data)
    {
        $routesPath = base_path('routes/web.php');
        $content = File::get($routesPath);
        
        $newRoute = "    Route::resource('{$data['name_kebab']}', {$data['name']}Controller::class);";
        
        $content = str_replace(
            "    Route::resource('roles', RoleController::class);",
            "    Route::resource('roles', RoleController::class);\n{$newRoute}",
            $content
        );
        
        // Add controller import if not already present
        $controllerImport = "use App\\Http\\Controllers\\{$data['name']}Controller;";
        if (!str_contains($content, $controllerImport)) {
            $content = str_replace(
                "use App\Http\Controllers\WarehouseController;",
                "use App\Http\Controllers\WarehouseController;\n{$controllerImport}",
                $content
            );
        }
        
        File::put($routesPath, $content);
    }

    protected function generateDependentDropdownRoutes($data)
    {
        $routesPath = base_path('routes/web.php');
        $content = File::get($routesPath);
        
        $dependentRelationships = $this->getDependentRelationships($data['relationships']);
        
        // Only add route if this controller actually has the dependent dropdown method
        $hasMethod = !empty($this->getDependentDropdownMethods($data['relationships'], $data['name']));
        
        if ($hasMethod) {
            foreach ($dependentRelationships as $dependent) {
                $parentRoute = Str::kebab(Str::plural($dependent['parent_model']));
                $childRoute = Str::kebab(Str::plural($dependent['child_name']));
                $methodName = 'get' . Str::plural(Str::studly($dependent['child_name'])) . 'By' . Str::studly($dependent['parent_name']);
                
                $routeName = "{$parentRoute}.{$childRoute}";
                $newRoute = "    Route::get('{$parentRoute}/{{$dependent['parent_name']}}/{$childRoute}', [{$data['name']}Controller::class, '{$methodName}'])->name('{$routeName}');";
                
                // Check if route name already exists (more robust check)
                if (!str_contains($content, "->name('{$routeName}')")) {
                    $content = str_replace(
                        "    Route::resource('{$data['name_kebab']}', {$data['name']}Controller::class);",
                        $newRoute . "\n    Route::resource('{$data['name_kebab']}', {$data['name']}Controller::class);",
                        $content
                    );
                    
                    File::put($routesPath, $content);
                }
            }
        }
    }



    protected function replacePlaceholders($content, $data)
    {
        $replacements = [
            '{{NAME}}' => $data['name'],
            '{{NAME_DISPLAY}}' => $data['name_display'] ?? $data['name'],
            '{{NAME_LOWER}}' => $data['name_lower'],
            '{{NAME_PLURAL}}' => $data['name_plural'],
            '{{NAME_PLURAL_DISPLAY}}' => $data['name_plural_display'] ?? $data['name_plural'],
            '{{NAME_PLURAL_LOWER}}' => $data['name_plural_lower'],
            '{{NAME_KEBAB}}' => $data['name_kebab'],
            '{{NAME_SNAKE}}' => $data['name_snake'],
            '{{TABLE_NAME}}' => $data['table_name'],
            '{{ROUTE_PREFIX}}' => $data['route_prefix'],
            '{{INERTIA_PATH}}' => $data['inertia_path'],
            '{{PACKAGE}}' => $data['package'] ?? '',
            '{{PACKAGE_LOWER}}' => $data['package_lower'] ?? '',
            '{{PACKAGE_NAMESPACE}}' => $data['package_namespace'] ?? 'App',
            '{{IS_PACKAGE}}' => $data['is_package'] ? 'true' : 'false',
            '{{FILLABLE}}' => $this->arrayToString($data['fillable']),
            '{{CASTS}}' => $this->castsToString($data['casts']),
            '{{VALIDATION_RULES}}' => $this->validationToString($data['validation_rules']),
            '{{FORM_IMPORTS}}' => $data['form_imports'],
            '{{MIGRATION_FIELDS}}' => $this->migrationFieldsToString($data['fields']),
            '{{FORM_FIELDS}}' => $this->setCurrentModelName($data['name'])->setCurrentFields($data['fields'])->formFieldsToString($data['fields'], $data['relationships']),
            '{{TABLE_COLUMNS}}' => $this->setCurrentFields($data['fields'])->tableColumnsToString($data['table_columns']),
            '{{SEEDER_FIELDS}}' => $this->setCurrentModelName($data['name'])->seederFieldsToString($data['fields'], $data['relationships']),
            '{{SEARCHABLE_FILTERS}}' => $this->searchableFiltersToString($data['searchable']),
            '{{FILTERABLE_FILTERS}}' => $this->setCurrentRelationships($data['relationships'])->filterableFiltersToString($data['filterable'], $data['fields']),
            '{{STORE_ASSIGNMENTS}}' => str_replace('{{NAME_LOWER}}', $data['name_lower'], $this->setCurrentModelName($data['name'])->storeAssignmentsToString($data['fields'], $data['relationships'])),
            '{{UPDATE_ASSIGNMENTS}}' => str_replace('{{NAME_LOWER}}', $data['name_lower'], $this->setCurrentModelName($data['name'])->updateAssignmentsToString($data['fields'], $data['relationships'])),
            '{{STORE_IS_ACTIVE}}' => $this->hasIsActiveField($data['fields']) ? "            \$validated['is_active'] = \$request->boolean('is_active', true);" : '',
            '{{UPDATE_IS_ACTIVE}}' => $this->hasIsActiveField($data['fields']) ? "            \$validated['is_active'] = \$request->boolean('is_active', true);" : '',
            '{{STORE_SWITCH_FIELDS}}' => $this->getSwitchFieldsHandling($data['fields'], 'store'),
            '{{UPDATE_SWITCH_FIELDS}}' => $this->getSwitchFieldsHandling($data['fields'], 'update'),
            '{{STORE_IS_ACTIVE_ASSIGNMENT}}' => $this->hasIsActiveField($data['fields']) ? "            \$" . $data['name_lower'] . "->is_active = \$validated['is_active'];" : '',
            '{{UPDATE_IS_ACTIVE_ASSIGNMENT}}' => $this->hasIsActiveField($data['fields']) ? "            \$" . $data['name_lower'] . "->is_active = \$validated['is_active'];" : '',
            '{{ICON}}' => $data['icon'],
            '{{SYSTEM_SETUP_ICON}}' => $data['system_setup_icon'],
            '{{IS_SYSTEM_SETUP}}' => $data['is_system_setup'] ? 'true' : 'false',
            '{{SYSTEM_SETUP_SELECT_FIELDS}}' => $this->setCurrentRelationships($data['relationships'])->getSystemSetupSelectFields($data['fields']),
            '{{RELATIONSHIP_IMPORTS}}' => $data['is_package'] ? $this->getPackageRelationshipModelImports($data['relationships'], $data['name'], $data['package_namespace']) : $this->getRelationshipImports($data['relationships']),
            '{{RELATIONSHIP_METHODS}}' => $this->getRelationshipMethods($data['relationships']),
            '{{NAME_ACCESSOR}}' => $this->getNameAccessor($data['fields']),
            '{{RELATIONSHIP_MIGRATIONS}}' => $this->getRelationshipMigrations($data['relationships'], $data['name']),
            '{{RELATIONSHIP_CONTROLLER_IMPORTS}}' => $data['is_package'] ? $this->getPackageRelationshipControllerImports($data['relationships'], $data['name'], $data['package_namespace']) : $this->getRelationshipControllerImports($data['relationships'], $data['name']),
            '{{RELATIONSHIP_DATA}}' => $data['is_package'] ? $this->setCurrentFields($data['fields'])->getPackageRelationshipData($data['relationships'], $data['name'], $data['package_namespace']) : $this->setCurrentFields($data['fields'])->getRelationshipData($data['relationships'], $data['name']),
            '{{RELATIONSHIP_PAGE_DATA}}' => $this->setCurrentFields($data['fields'])->getRelationshipPageData($data['relationships'], $data['name']),
            '{{RELATIONSHIP_PAGE_DATA_DESTRUCTURE}}' => $this->setCurrentFields($data['fields'])->getRelationshipPageDataDestructure($data['relationships'], $data['name']),
            '{{RELATIONSHIP_PAGE_DATA_PROPS_PASS}}' => $this->setCurrentFields($data['fields'])->getRelationshipPageDataPropsPass($data['relationships'], $data['name']),
            '{{RELATIONSHIP_PAGE_DATA_PROPS}}' => $this->setCurrentFields($data['fields'])->getRelationshipPageDataProps($data['relationships'], $data['name']),
            '{{INTERFACE_FIELDS}}' => $this->getInterfaceFields($data['fields'], $data['relationships']),
            '{{CREATE_FORM_INTERFACE_FIELDS}}' => $this->getCreateFormInterfaceFields($data['fields'], $data['relationships']),
            '{{EDIT_FORM_INTERFACE_FIELDS}}' => $this->getEditFormInterfaceFields($data['fields'], $data['relationships']),
            '{{CREATE_FORM_DATA}}' => $this->getCreateFormData($data['fields'], $data['relationships']),
            '{{EDIT_FORM_DATA}}' => str_replace('{{NAME_LOWER}}', $data['name_lower'], $this->getEditFormData($data['fields'], $data['relationships'])),
            '{{TABLE_RELATIONSHIPS_WITH}}' => $this->getTableRelationshipsWith($data['table_relationships']),
            '{{SEEDER_RELATIONSHIP_IMPORTS}}' => $data['is_package'] ? $this->getPackageRelationshipImports($data['relationships'], $data['name'], $data['package_namespace']) : $this->getRelationshipImports($data['relationships'], $data['name']),
            '{{SEEDER_DYNAMIC_MODEL_IMPORTS}}' => $this->getSeederDynamicModelImports($data['fields']),
            '{{GRID_VIEW_CONTENT}}' => str_replace('{{ICON}}', $data['icon'] . 'Icon', $this->getGridViewContent($data['fields'], $data['name_lower'], $data['table_relationships'], $data['table_fields'])),
            '{{FILTER_SECTION}}' => $this->setCurrentRelationships($data['relationships'])->getFilterSection($data['fields'], $data['filterable'], $data['relationships']),
            '{{FILTER_STATE}}' => $this->setCurrentRelationships($data['relationships'])->getFilterState($data['searchable'], $data['filterable'], $data['fields'], $data['relationships']),
            '{{CLEAR_FILTER_STATE}}' => $this->setCurrentRelationships($data['relationships'])->getClearFilterState($data['searchable'], $data['filterable'], $data['fields'], $data['relationships']),
            '{{SEARCH_FIELD}}' => $this->getSearchField($data['searchable']),
            '{{HAS_FILTERS_CHECK}}' => $this->setCurrentRelationships($data['relationships'])->getHasFiltersCheck($data['searchable'], $data['filterable'], $data['fields'], $data['relationships']),
            '{{DEPENDENT_DROPDOWN_METHODS}}' => $this->getDependentDropdownMethods($data['relationships'], $data['name']),
            '{{DEPENDENT_DROPDOWN_STATE}}' => $this->getDependentDropdownState($data['relationships'], $data['name']),
            '{{DEPENDENT_DROPDOWN_EFFECTS}}' => $this->getDependentDropdownEffects($data['relationships'], $data['name'], $data),
            '{{FILTER_DEPENDENT_DROPDOWN_EFFECTS}}' => $this->getDependentFilterEffects($data['relationships'], $data['filterable'], $data),
            '{{FILTER_DEPENDENT_DROPDOWN_STATE}}' => $this->getFilterDependentDropdownState($data['relationships'], $data['filterable']),
            '{{RELATIONSHIP_IMPORTS_FOR_TYPES}}' => $this->getRelationshipImportsForTypes($data['relationships'], $data['filterable']),
            '{{SHOW_METHOD}}' => str_replace(['{{ROUTE_PREFIX}}', '{{INERTIA_PATH}}', '{{NAME}}', '{{NAME_LOWER}}', '{{NAME_KEBAB}}'], [$data['route_prefix'], $data['inertia_path'], $data['name'], $data['name_lower'], $data['name_kebab']], $this->getShowMethod($data['view'])),
            '{{HAS_FILTER_BUTTON_WITH_COUNTER}}' => $this->setCurrentRelationships($data['relationships'])->getFilterButtonWithCounter($data['filterable'], $data['fields'], $data['relationships']),
            '{{FILTER_INTERFACE_FIELDS}}' => $this->setCurrentRelationships($data['relationships'])->getFilterInterfaceFields($data['searchable'], $data['filterable'], $data['fields'], $data['relationships']),
            '{{VIEW_IMPORT}}' => $this->getViewImport($data['view']),
            '{{VIEW_STATE}}' => str_replace('{{NAME}}', $data['name'], $this->getViewState($data['view'])),
            '{{VIEW_BUTTON_TABLE}}' => str_replace(['{{ROUTE_PREFIX}}', '{{NAME_KEBAB}}', '{{NAME_LOWER}}'], [$data['route_prefix'], $data['name_kebab'], $data['name_lower']], $this->getViewButton($data['view'], 'table')),
            '{{VIEW_BUTTON_GRID}}' => str_replace(['{{ROUTE_PREFIX}}', '{{NAME_KEBAB}}', '{{NAME_LOWER}}'], [$data['route_prefix'], $data['name_kebab'], $data['name_lower']], $this->getViewButton($data['view'], 'grid')),
            '{{VIEW_DIALOG}}' => str_replace('{{NAME_LOWER}}', $data['name_lower'], $this->getViewDialog($data['view'])),
            '{{USE_EFFECT_IMPORT}}' => $this->getUseEffectImport($data['relationships'], $data['filterable']),
            '{{ACTIONS_COLUMN_PERMISSION_CHECK}}' => $this->getActionsColumnPermissionCheck($data['view'], $data['name_kebab'])
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function arrayToString($array)
    {
        return implode("',\n        '", $array);
    }

    protected function castsToString($casts)
    {
        if (empty($casts)) return '';
        
        $result = [];
        foreach ($casts as $field => $cast) {
            $result[] = "'$field' => '$cast'";
        }
        return implode(",\n            ", $result);
    }

    protected function validationToString($rules)
    {
        $result = [];
        foreach ($rules as $field => $rule) {
            $result[] = "'$field' => '$rule'";
        }
        return implode(",\n            ", $result);
    }

    protected function migrationFieldsToString($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            // Handle dynamic model fields
            if (!empty($field['dynamic_model'])) {
                if ($field['type'] === 'radiobutton' || $field['type'] === 'select') {
                    // Single selection - use integer field
                    $migration = $field['nullable'] ? "\$table->integer('{field}')->nullable();" : "\$table->integer('{field}');";
                } else {
                    // Multiple selection - use JSON field
                    $migration = "\$table->json('{field}')->nullable();";
                }
            } else {
                // Static options - use existing config
                $migration = $field['nullable'] 
                    ? ($field['config']['migration_nullable'] ?? $field['config']['migration'])
                    : $field['config']['migration'];
            }
            $result[] = '            ' . str_replace('{field}', $field['name'], $migration);
        }
        return implode("\n", $result);
    }

    protected function formFieldsToString($fields, $relationships = [])
    {
        // Set context for relationship field generation
        $this->currentRelationships = $relationships;
        $this->currentModelName = $this->currentModelName ?? 'Item';
        
        // Store relationships for filtering
        $this->relationships = $relationships;
        
        $result = [];
        foreach ($fields as $field) {
            $result[] = $this->generateFormField($field);
        }
        
        // Add relationship fields (exclude self-referencing relationships)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $this->currentModelName) {
                $result[] = $this->generateRelationshipField($relationship);
            }
        }
        
        return implode("\n                \n", $result);
    }

    protected function generateFormField($field)
    {
        $config = $field['config'];
        $fieldName = $field['name'];
        $fieldLabel = Str::title(str_replace('_', ' ', $fieldName));
        
        if ($config['component'] === 'Input') {
            $inputType = isset($config['props']['type']) ? $config['props']['type'] : 'text';
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Input
                        id=\"{$fieldName}\"
                        type=\"{$inputType}\"
                        value={data.{$fieldName}}
                        onChange={(e) => setData('{$fieldName}', e.target.value)}
                        placeholder={t('Enter {$fieldLabel}')}
                        " . ($field['nullable'] ? '' : 'required') . "
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'Textarea') {
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Textarea
                        id=\"{$fieldName}\"
                        value={data.{$fieldName}}
                        onChange={(e) => setData('{$fieldName}', e.target.value)}
                        placeholder={t('Enter {$fieldLabel}')}
                        rows={3}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'Select') {
            if (!empty($field['dynamic_model'])) {
                // Dynamic options from model
                $modelName = Str::plural(Str::lower($field['dynamic_model']));
                return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Select value={data.{$fieldName}?.toString() || ''} onValueChange={(value) => setData('{$fieldName}', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select {$fieldLabel}')} />
                        </SelectTrigger>
                        <SelectContent>
                            {{$modelName}?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.{$field['dynamic_field']}}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.{$fieldName}} />
                </div>";
            } else {
                // Static select with custom options
                if (!empty($field['options'])) {
                    $selectItems = [];
                    foreach ($field['options'] as $index => $option) {
                        $selectItems[] = "                            <SelectItem value=\"{$index}\">{t('{$option}')}</SelectItem>";
                    }
                    $selectItemsString = implode("\n", $selectItems);
                    
                    return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Select value={data.{$fieldName}?.toString() || '0'} onValueChange={(value) => setData('{$fieldName}', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
{$selectItemsString}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.{$fieldName}} />
                </div>";
                } else {
                    // Default boolean select (Active/Inactive)
                    return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Select value={data.{$fieldName} ? \"1\" : \"0\"} onValueChange={(value) => setData('{$fieldName}', value === \"1\")}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value=\"1\">{t('Active')}</SelectItem>
                            <SelectItem value=\"0\">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.{$fieldName}} />
                </div>";
                }
            }
        } elseif ($config['component'] === 'RadioGroup') {
            if (!empty($field['dynamic_model'])) {
                // Dynamic options from model
                $modelName = Str::plural(Str::lower($field['dynamic_model']));
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <RadioGroup value={data.{$fieldName}?.toString() || ''} onValueChange={(value) => setData('{$fieldName}', value)} className=\"flex gap-6 mt-2\">
                        {{$modelName}?.map((item: any) => (
                            <div key={item.id} className=\"flex items-center space-x-2\">
                                <RadioGroupItem value={item.id.toString()} id={`{$fieldName}_\${item.id}`} />
                                <Label htmlFor={`{$fieldName}_\${item.id}`} className=\"cursor-pointer\">{item.{$field['dynamic_field']}}</Label>
                            </div>
                        ))}
                    </RadioGroup>
                    <InputError message={errors.{$fieldName}} />
                </div>";
            } else {
                // Static options
                $options = !empty($field['options']) ? $field['options'] : ['Active', 'Inactive'];
                $radioItems = [];
                
                foreach ($options as $index => $option) {
                    $value = $index;
                    $radioItems[] = "                        <div className=\"flex items-center space-x-2\">\n                            <RadioGroupItem value=\"{$value}\" id=\"{$fieldName}_{$index}\" />\n                            <Label htmlFor=\"{$fieldName}_{$index}\" className=\"cursor-pointer\">{t('{$option}')}</Label>\n                        </div>";
                }
                
                $radioItemsString = implode("\n", $radioItems);
                
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <RadioGroup value={data.{$fieldName}?.toString() || '0'} onValueChange={(value) => setData('{$fieldName}', value)} className=\"flex gap-6 mt-2\">
{$radioItemsString}
                    </RadioGroup>
                    <InputError message={errors.{$fieldName}} />
                </div>";
            }
        } elseif ($config['component'] === 'Checkbox') {
            return "                <div className=\"flex items-center space-x-2\">
                    <Checkbox
                        id=\"{$fieldName}\"
                        checked={data.{$fieldName} || false}
                        onCheckedChange={(checked) => setData('{$fieldName}', !!checked)}
                    />
                    <Label htmlFor=\"{$fieldName}\" className=\"cursor-pointer\">{t('{$fieldLabel}')}</Label>
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'DatePicker') {
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <DatePicker
                        value={data.{$fieldName}}
                        onChange={(date) => setData('{$fieldName}', date)}
                        placeholder={t('Select {$fieldLabel}')}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'TimePicker') {
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Input
                        id=\"{$fieldName}\"
                        type=\"time\"
                        value={data.{$fieldName}}
                        onChange={(e) => setData('{$fieldName}', e.target.value)}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'DateRangePicker') {
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <DateRangePicker
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Select {$fieldLabel}')}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'DateTimeRangePicker') {
            $mode = isset($config['props']['mode']) ? $config['props']['mode'] : 'single';
            $placeholder = isset($config['props']['placeholder']) ? str_replace('{field_label}', $fieldLabel, $config['props']['placeholder']) : "Select {$fieldLabel}";
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <DateTimeRangePicker
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('{$placeholder}')}
                        mode=\"{$mode}\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($field['type'] === 'datetime') {
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <DateTimeRangePicker
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Select {$fieldLabel}')}
                        mode=\"single\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($field['type'] === 'datetimerange') {
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <DateTimeRangePicker
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Select {$fieldLabel}')}
                        mode=\"range\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($field['type'] === 'tagsinput') {
            return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <TagsInput
                        value={data.{$fieldName} || []}
                        onChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Add {$fieldLabel}...')}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'NumberInput') {
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Input
                        id=\"{$fieldName}\"
                        type=\"number\"
                        step=\"1\"
                        min=\"0\"
                        value={data.{$fieldName}}
                        onChange={(e) => setData('{$fieldName}', e.target.value)}
                        placeholder=\"0\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'CheckboxGroup') {
            if (!empty($field['dynamic_model'])) {
                // Dynamic options from model
                $modelName = Str::plural(Str::lower($field['dynamic_model']));
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <div className=\"flex flex-col gap-3 mt-2\">
                        {{$modelName}?.map((item: any) => (
                            <div key={item.id} className=\"flex items-center space-x-2\">
                                <Checkbox
                                    id={`{$fieldName}_\${item.id}`}
                                    checked={data.{$fieldName}?.includes(item.id.toString()) || false}
                                    onCheckedChange={(checked) => {
                                        const currentValues = data.{$fieldName} || [];
                                        if (checked) {
                                            setData('{$fieldName}', [...currentValues, item.id.toString()]);
                                        } else {
                                            setData('{$fieldName}', currentValues.filter(v => v !== item.id.toString()));
                                        }
                                    }}
                                />
                                <Label htmlFor={`{$fieldName}_\${item.id}`} className=\"cursor-pointer\">{item.{$field['dynamic_field']}}</Label>
                            </div>
                        ))}
                    </div>
                    <InputError message={errors.{$fieldName}} />
                </div>";
            } else {
                // Static options
                $options = !empty($field['options']) ? $field['options'] : [];
                $checkboxItems = [];
                
                foreach ($options as $index => $option) {
                    $value = $index;
                    $checkboxItems[] = "                        <div className=\"flex items-center space-x-2\">\n                            <Checkbox\n                                id=\"{$fieldName}_{$index}\"\n                                checked={data.{$fieldName}?.includes('{$value}') || false}\n                                onCheckedChange={(checked) => {\n                                    const currentValues = data.{$fieldName} || [];\n                                    if (checked) {\n                                        setData('{$fieldName}', [...currentValues, '{$value}']);\n                                    } else {\n                                        setData('{$fieldName}', currentValues.filter(v => v !== '{$value}'));\n                                    }\n                                }}\n                            />\n                            <Label htmlFor=\"{$fieldName}_{$index}\" className=\"cursor-pointer\">{t('{$option}')}</Label>\n                        </div>";
                }
                
                $checkboxItemsString = implode("\n", $checkboxItems);
                
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <div className=\"flex flex-col gap-3 mt-2\">
{$checkboxItemsString}
                    </div>
                    <InputError message={errors.{$fieldName}} />
                </div>";
            }
        } elseif ($config['component'] === 'CurrencyInput') {
            return "                <div>
                    <CurrencyInput
                        label={t('{$fieldLabel}')}
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        error={errors.{$fieldName}}
                    />
                </div>";
        } elseif ($config['component'] === 'RichTextEditor') {
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <RichTextEditor
                        content={data.{$fieldName}}
                        onChange={(content) => setData('{$fieldName}', content)}
                        placeholder={t('Enter {$fieldLabel}')}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'PhoneInputComponent') {
            return "                <div>
                    <PhoneInputComponent
                        label={t('{$fieldLabel}')}
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value || '')}
                        error={errors.{$fieldName}}
                    />
                </div>";
        } elseif ($config['component'] === 'Slider') {
            return "                <div className=\"space-y-3\">
                    <Label>{t('{$fieldLabel}')} ({data.{$fieldName}[0]})</Label>
                    <Slider
                        value={data.{$fieldName}}
                        onValueChange={(value) => setData('{$fieldName}', value)}
                        max={100}
                        step={1}
                        className=\"mt-2\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'Switch') {
            return "                <div className=\"flex items-center space-x-2\">
                    <Switch
                        id=\"{$fieldName}\"
                        checked={data.{$fieldName} || false}
                        onCheckedChange={(checked) => setData('{$fieldName}', !!checked)}
                    />
                    <Label htmlFor=\"{$fieldName}\" className=\"cursor-pointer\">{t('{$fieldLabel}')}</Label>
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'Rating') {
            return "                <div className=\"space-y-3\">
                    <Label>{t('{$fieldLabel}')}</Label>
                    <Rating
                        value={data.{$fieldName}}
                        onChange={(value) => setData('{$fieldName}', value)}
                        max={5}
                        size=\"md\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'MediaPicker') {
            $showPreview = isset($field['showPreview']) && $field['showPreview'] ? 'true' : 'false';
            $multiple = isset($field['multiple']) && $field['multiple'] ? 'true' : 'false';
            $isMultiple = isset($field['multiple']) && $field['multiple'];
            $onChangeHandler = $isMultiple 
                ? "onChange={(value) => setData('{$fieldName}', Array.isArray(value) ? value : [value])}"
                : "onChange={(value) => setData('{$fieldName}', Array.isArray(value) ? value[0] || '' : value)}";
            return "                <div>
                    <MediaPicker
                        label={t('{$fieldLabel}')}
                        value={data.{$fieldName}}
                        {$onChangeHandler}
                        placeholder={t('Select {$fieldLabel}...')}
                        showPreview={{$showPreview}}
                        multiple={{$multiple}}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'ColorInput') {
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Input
                        id=\"{$fieldName}\"
                        type=\"color\"
                        value={data.{$fieldName}}
                        onChange={(e) => setData('{$fieldName}', e.target.value)}
                        className=\"mt-2 h-10 w-20\"
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
        } elseif ($config['component'] === 'MultiSelectEnhanced') {
            if (!empty($field['dynamic_model'])) {
                // Dynamic options from model
                $modelName = Str::plural(Str::lower($field['dynamic_model']));
                $searchable = isset($field['searchable']) && $field['searchable'] ? 'true' : 'false';
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <MultiSelectEnhanced
                        options={{$modelName}?.map((item: any) => ({ value: item.id.toString(), label: item.{$field['dynamic_field']} })) || []}
                        value={data.{$fieldName}}
                        onValueChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Select {$fieldLabel}...')}
                        searchable={{$searchable}}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
            } else {
                // Static options
                $optionsArray = [];
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $index => $option) {
                        $optionsArray[] = "{ value: '{$option}', label: t('{$option}') }";
                    }
                }

                $optionsString = !empty($optionsArray) ? '[' . implode(', ', $optionsArray) . ']' : '[]';
                $searchable = isset($field['searchable']) && $field['searchable'] ? 'true' : 'false';
                
                return "                <div>
                    <Label>{t('{$fieldLabel}')}</Label>
                    <MultiSelectEnhanced
                        options={{$optionsString}}
                        value={data.{$fieldName}}
                        onValueChange={(value) => setData('{$fieldName}', value)}
                        placeholder={t('Select {$fieldLabel}...')}
                        searchable={{$searchable}}
                    />
                    <InputError message={errors.{$fieldName}} />
                </div>";
            }
        }
        
        return '';
    }

    protected function generateRelationshipField($relationship)
    {
        $fieldName = $relationship['foreign_key'];
        $fieldLabel = Str::title(str_replace('_', ' ', $relationship['name']));
        $pluralName = Str::plural(Str::lower($relationship['model']));
        
        // Check if this is a dependent field
        $dependentInfo = $this->getDependentFieldInfo($relationship, $this->currentRelationships ?? []);
        if ($dependentInfo) {
            $parentField = $dependentInfo['parent_foreign_key'];
            $parentLabel = Str::title($dependentInfo['parent_name']);
            $childModelName = str_replace('_id', '', $fieldName);
            $stateName = Str::plural(Str::lower($childModelName));
            $camelCaseStateName = 'filtered' . Str::studly($stateName);
            
            return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Select 
                        value={data.{$fieldName}?.toString() || ''} 
                        onValueChange={(value) => setData('{$fieldName}', value)}
                        disabled={!data.{$parentField}}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={data.{$parentField} ? t('Select {$fieldLabel}') : t('Select {$parentLabel} first')} />
                        </SelectTrigger>
                        <SelectContent>
                            {{$camelCaseStateName}?.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.{$fieldName}} />
                </div>";
        }
        
        return "                <div>
                    <Label htmlFor=\"{$fieldName}\">{t('{$fieldLabel}')}</Label>
                    <Select value={data.{$fieldName}?.toString() || ''} onValueChange={(value) => setData('{$fieldName}', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select {$fieldLabel}')} />
                        </SelectTrigger>
                        <SelectContent>
                            {{$pluralName}.map((item: any) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.{$fieldName}} />
                </div>";
    }

    protected function tableColumnsToString($columns)
    {
        $result = [];
        foreach ($columns as $column) {
            $columnName = $column['name'];
            $columnLabel = isset($column['label']) ? $column['label'] : Str::title(str_replace('_', ' ', $columnName));
            
            if ($column['render'] === 'status_badge') {
                // Get field data for select rendering
                $fieldName = $column['name'];
                $field = isset($this->currentFields[$fieldName]) ? $this->currentFields[$fieldName] : null;
                
                if ($field && $field['dynamic_model']) {
                    // Dynamic model field - show model field value
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string, row: any) => {
                const modelData = " . Str::plural(Str::lower($field['dynamic_model'])) . "?.find(item => item.id.toString() === value?.toString());
                return (
                    <span className=\"px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800\">
                        {modelData?.{$field['dynamic_field']} || value || '-'}
                    </span>
                );
            }
        }";
                } else {
                    // Check if field has custom options
                    if ($field && !empty($field['options'])) {
                        // Custom select options
                        $optionMap = [];
                        foreach ($field['options'] as $index => $option) {
                            $optionMap[$index] = $option;
                        }
                        $optionMapJson = json_encode($optionMap, JSON_FORCE_OBJECT);
                        
                        $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => {
                const options: any = {$optionMapJson};
                const displayValue = options[value] || value || '-';
                return (
                    <span className=\"px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800\">
                        {t(displayValue)}
                    </span>
                );
            }
        }";
                    } else {
                        // Static boolean select (Active/Inactive)
                        $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm \${
                    value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                    {value ? t('Active') : t('Inactive')}
                </span>
            )
        }";
                    }
                }
            } elseif ($column['render'] === 'radio_options') {
                // Get field options for radio rendering
                $fieldName = $column['name'];
                $field = isset($this->currentFields[$fieldName]) ? $this->currentFields[$fieldName] : null;
                
                if ($field && $field['dynamic_model']) {
                    // Dynamic model field - show model field value
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string, row: any) => {
                const modelData = " . Str::plural(Str::lower($field['dynamic_model'])) . "?.find(item => item.id.toString() === value?.toString());
                return modelData?.{$field['dynamic_field']} || value || '-';
            }
        }";
                } else {
                    // Static options
                    $options = $field && !empty($field['options']) ? $field['options'] : ['Active', 'Inactive'];
                    
                    $optionMap = [];
                    foreach ($options as $index => $option) {
                        $optionMap[$index] = $option;
                    }
                    
                    $optionMapString = json_encode($optionMap, JSON_FORCE_OBJECT);
                    
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: any) => {
                const options: any = {$optionMapString};
                return options[value] || value;
            }
        }";
                }
            } elseif ($column['render'] === 'checkbox_badge') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm \${
                    value ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                }`}>
                    {value ? t('Yes') : t('No')}
                </span>
            )
        }";
            } elseif ($column['render'] === 'checkbox_group_list') {
                // Get field options for checkbox group rendering
                $fieldName = $column['name'];
                $field = isset($this->currentFields[$fieldName]) ? $this->currentFields[$fieldName] : null;
                
                if ($field && $field['dynamic_model']) {
                    // Dynamic model field - show model field values
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string[], row: any) => {
                if (!value || !Array.isArray(value) || value.length === 0) return '-';
                const modelData = " . Str::plural(Str::lower($field['dynamic_model'])) . " || [];
                return value.map(v => {
                    const item = modelData.find(item => item.id.toString() === v?.toString());
                    return item?.{$field['dynamic_field']} || v;
                }).join(', ');
            }
        }";
                } else {
                    // Static options
                    $options = $field && !empty($field['options']) ? $field['options'] : [];
                    
                    $optionMap = [];
                    foreach ($options as $index => $option) {
                        $optionMap[$index] = $option;
                    }
                    
                    $optionMapString = json_encode($optionMap, JSON_FORCE_OBJECT);
                    
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string[]) => {
                if (!value || !Array.isArray(value) || value.length === 0) return '-';
                const options = {$optionMapString};
                return value.map(v => options[v] || v).join(', ');
            }
        }";
                }
            } elseif ($column['render'] === 'date_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => value ? formatDate(value) : '-'
        }";
            } elseif ($column['render'] === 'datetime_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => value ? formatDateTime(value) : '-'
        }";
            } elseif ($column['render'] === 'datetime_range_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => {
                if (!value) return '-';
                const parts = value.split(' - ');
                if (parts.length === 2) {
                    return formatDateTime(parts[0]) + ' - ' + formatDateTime(parts[1]);
                }
                return value;
            }
        }";
            } elseif ($column['render'] === 'tags_list') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string[]) => {
                if (!value || !Array.isArray(value) || value.length === 0) return '-';
                return (
                    <div className=\"flex flex-wrap gap-1\">
                        {value.slice(0, 3).map((tag, index) => (
                            <Badge key={index} variant=\"secondary\" className=\"text-xs\">
                                {tag}
                            </Badge>
                        ))}
                        {value.length > 3 && (
                            <Badge variant=\"outline\" className=\"text-xs\">
                                +{value.length - 3}
                            </Badge>
                        )}
                    </div>
                );
            }
        }";
            } elseif ($column['render'] === 'time_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => value ? formatTime(value) : '-'
        }";
            } elseif ($column['render'] === 'date_range_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => value || '-'
        }";
            } elseif ($column['render'] === 'number_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: number) => value || '-'
        }";
            } elseif ($column['render'] === 'currency_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: number) => value ? formatCurrency(value) : '-'
        }";
            } elseif ($column['render'] === 'html_content') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => {
                if (!value) return '-';
                const div = document.createElement('div');
                div.innerHTML = value;
                const text = div.textContent || div.innerText || '';
                return text.length > 50 ? text.substring(0, 50) + '...' : text;
            }
        }";
            } elseif ($column['render'] === 'phone_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => value || '-'
        }";
            } elseif ($column['render'] === 'slider_format') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: number) => (
                <div className=\"flex items-center gap-2\">
                    <div className=\"w-16 bg-gray-200 rounded-full h-2\">
                        <div className=\"bg-primary h-2 rounded-full\" style={{ width: `\${value || 0}%` }}></div>
                    </div>
                    <span className=\"text-sm font-medium\">{value || 0}%</span>
                </div>
            )
        }";
            } elseif ($column['render'] === 'switch_badge') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm \${
                    value ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                }`}>
                    {value ? t('On') : t('Off')}
                </span>
            )
        }";
            } elseif ($column['render'] === 'rating_stars') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: number) => {
                if (!value) return '-';
                return (
                    <div className=\"flex items-center gap-1\">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <span key={star} className={`text-xl \${star <= value ? 'text-yellow-400' : 'text-gray-300'}`}>
                                ★
                            </span>
                        ))}
                    </div>
                );
            }
        }";
            } elseif ($column['render'] === 'media_preview') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string | string[]) => {
                if (!value || (Array.isArray(value) && value.length === 0)) {
                    return (
                        <div className=\"w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center\">
                            <FileImage className=\"w-6 h-6 text-gray-400\" />
                        </div>
                    );
                }
                const files = Array.isArray(value) ? value : [value];
                return (
                    <div className=\"flex gap-1\">
                        {files.slice(0, 2).map((file, index) => {
                            const isImage = /\\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file);
                            return isImage ? (
                                <img
                                    key={index}
                                    src={getImagePath(file)}
                                    alt=\"{$columnLabel}\"
                                    className=\"w-12 h-12 object-cover rounded-md border hover:scale-110 transition-transform cursor-pointer\"
                                    onClick={() => window.open(getImagePath(file), '_blank')}
                                />
                            ) : (
                                <div key={index} className=\"w-12 h-12 bg-gray-100 rounded-md border flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors\" onClick={() => {
                                    const link = document.createElement('a');
                                    link.href = getImagePath(file);
                                    link.download = file.split('/').pop() || 'file';
                                    link.click();
                                }}>
                                    <Download className=\"w-6 h-6 text-gray-600\" />
                                </div>
                            );
                        })}
                        {files.length > 2 && (
                            <span className=\"text-xs text-gray-500 self-center\">+{files.length - 2}</span>
                        )}
                    </div>
                );
            }
        }";
            } elseif ($column['render'] === 'color_display') {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string) => (
                <div className=\"flex items-center justify-center\">
                    <div 
                        className=\"w-6 h-6 rounded border border-gray-300\" 
                        style={{ backgroundColor: value || '#FF6B6B' }}
                        title={value || '#FF6B6B'}
                    ></div>
                </div>
            )
        }";
            } elseif ($column['render'] === 'multiselect_badges') {
                // Generate option map for this field
                $fieldName = $column['name'];
                $field = isset($this->currentFields[$fieldName]) ? $this->currentFields[$fieldName] : null;
                
                if ($field && $field['dynamic_model']) {
                    // Dynamic model field - show model field values as badges
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string[] | string, row: any) => {
                if (!value) return '-';
                let items = [];
                if (typeof value === 'string') {
                    try {
                        items = JSON.parse(value);
                    } catch {
                        items = [value];
                    }
                } else if (Array.isArray(value)) {
                    items = value;
                }
                if (items.length === 0) return '-';
                const modelData = " . Str::plural(Str::lower($field['dynamic_model'])) . " || [];
                return (
                    <div className=\"flex flex-wrap gap-1\">
                        {items.slice(0, 2).map((item: any, index: number) => {
                            const modelItem = modelData.find((m: any) => m.id.toString() === item?.toString());
                            return (
                                <span key={index} className=\"px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs\">
                                    {modelItem?.{$field['dynamic_field']} || item}
                                </span>
                            );
                        })}
                        {items.length > 2 && (
                            <span className=\"text-xs text-gray-500\">+{items.length - 2}</span>
                        )}
                    </div>
                );
            }
        }";
                } else {
                    // Static options
                    $optionMap = [];
                    if ($field && !empty($field['options'])) {
                        foreach ($field['options'] as $index => $option) {
                            $optionMap[$option] = $option; // value -> label
                            $optionMap['option' . $index] = $option; // optionN -> label
                        }
                    }
                    $optionMapString = json_encode($optionMap, JSON_FORCE_OBJECT);
                    
                    $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: string[] | string) => {
                if (!value) return '-';
                let items = [];
                if (typeof value === 'string') {
                    try {
                        items = JSON.parse(value);
                    } catch {
                        items = [value];
                    }
                } else if (Array.isArray(value)) {
                    items = value;
                }
                if (items.length === 0) return '-';
                const optionMap: any = {$optionMapString};
                return (
                    <div className=\"flex flex-wrap gap-1\">
                        {items.slice(0, 2).map((item: any, index: number) => (
                            <span key={index} className=\"px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs\">
                                {optionMap[item] || item}
                            </span>
                        ))}
                        {items.length > 2 && (
                            <span className=\"text-xs text-gray-500\">+{items.length - 2}</span>
                        )}
                    </div>
                );
            }
        }";
                }
            } elseif ($column['type'] === 'relationship') {
                // Handle nested object access for relationships
                $parts = explode('.', $columnName);
                $accessor = 'row.' . implode('?.', $parts);
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . ",
            render: (value: any, row: any) => {$accessor} || '-'
        }";
            } else {
                $result[] = "        {
            key: '{$columnName}',
            header: t('{$columnLabel}'),
            sortable: " . ($column['sortable'] ? 'true' : 'false') . "
        }";
            }
        }
        return implode(",\n", $result);
    }

    protected function seederFieldsToString($fields, $relationships = [])
    {
        $result = [];
        foreach ($fields as $field) {
            if (!empty($field['dynamic_model'])) {
                // Dynamic model fields - use random ID from the model
                $modelName = $field['dynamic_model'];
                if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                    // Multiple selection - array of IDs
                    $result[] = "                '{$field['name']}' => {$modelName}::where('created_by', \$userId)->inRandomOrder()->limit(fake()->numberBetween(0, 3))->pluck('id')->toArray()";
                } else {
                    // Single selection - single ID with fallback
                    if ($field['nullable']) {
                        $result[] = "                '{$field['name']}' => {$modelName}::where('created_by', \$userId)->inRandomOrder()->first()?->id";
                    } else {
                        $result[] = "                '{$field['name']}' => {$modelName}::where('created_by', \$userId)->inRandomOrder()->first()?->id ?? {$modelName}::first()?->id ?? 1";
                    }
                }
            } else {
                $faker = $field['config']['faker'];
                $result[] = "                '{$field['name']}' => {$faker}";
            }
        }
        
        // Add relationship foreign keys with nullable handling (exclude self-references and created_by)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && 
                (!isset($this->currentModelName) || $relationship['model'] !== $this->currentModelName) &&
                $relationship['foreign_key'] !== 'created_by') {
                $modelClass = $relationship['model'];
                
                // Handle User model differently - use core User model
                if ($modelClass === 'User') {
                    $result[] = "                '{$relationship['foreign_key']}' => \App\Models\User::where('created_by', \$userId)->inRandomOrder()->first()?->id";
                } else {
                    $result[] = "                '{$relationship['foreign_key']}' => {$modelClass}::where('created_by', \$userId)->inRandomOrder()->first()?->id";
                }
            }
        }
        
        return implode(",\n", $result);
    }

    protected function searchableFiltersToString($searchable)
    {
        if (empty($searchable) || empty($searchable[0])) {
            return '';
        }
        
        $mainSearchField = trim($searchable[0]);
        $searchConditions = [];
        
        foreach ($searchable as $index => $field) {
            $field = trim($field);
            if (!empty($field)) {
                $whereMethod = $index === 0 ? 'where' : 'orWhere';
                $searchConditions[] = "                    \$query->{$whereMethod}('{$field}', 'like', '%' . request('{$mainSearchField}') . '%');";
            }
        }
        
        if (empty($searchConditions)) {
            return '';
        }
        
        $searchQuery = implode("\n", $searchConditions);
        
        return "                ->when(request('{$mainSearchField}'), function(\$q) {\n                    \$q->where(function(\$query) {\n{$searchQuery}\n                    });\n                })";
    }

    protected function filterableFiltersToString($filterable, $fields)
    {
        // Return empty string if no filterable fields
        if (empty($filterable)) {
            return '';
        }
        
        $result = [];
        foreach ($filterable as $field) {
            if (!empty($field) && isset($fields[$field])) {
                $fieldData = $fields[$field];
                
                if ($fieldData['dynamic_model']) {
                    // Handle dynamic model fields
                    if ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton') {
                        $result[] = "                ->when(request('{$field}') && request('{$field}') !== '', fn(\$q) => \$q->where('{$field}', request('{$field}')))";
                    } elseif ($fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect') {
                        $result[] = "                ->when(request('{$field}') && request('{$field}') !== '', fn(\$q) => \$q->whereJsonContains('{$field}', request('{$field}')))";
                    }
                } else {
                    // Handle static fields
                    if ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'rating') {
                        if ($fieldData['type'] === 'select' && empty($fieldData['dynamic_model']) && empty($fieldData['options'])) {
                            // Handle boolean select fields (is_active) - only if no custom options
                            $result[] = "                ->when(request('{$field}') !== null && request('{$field}') !== '', fn(\$q) => \$q->where('{$field}', request('{$field}') === '1' ? 1 : 0))";
                        } else {
                            $result[] = "                ->when(request('{$field}') !== null && request('{$field}') !== '', fn(\$q) => \$q->where('{$field}', request('{$field}')))";
                        }
                    } elseif ($fieldData['type'] === 'checkboxgroup') {
                        $result[] = "                ->when(request('{$field}') && request('{$field}') !== '', fn(\$q) => \$q->whereJsonContains('{$field}', request('{$field}')))";
                    } elseif ($fieldData['type'] === 'multiselect') {
                        $result[] = "                ->when(request('{$field}') && request('{$field}') !== '', fn(\$q) => \$q->whereJsonContains('{$field}', request('{$field}')))";
                    }
                }
            } elseif (!empty($field) && isset($this->currentRelationships[$field])) {
                // Handle relationship filtering
                $relationship = $this->currentRelationships[$field];
                if ($relationship['type'] === 'belongsTo') {
                    $foreignKey = $relationship['foreign_key'];
                    $result[] = "                ->when(request('{$foreignKey}') && request('{$foreignKey}') !== 'all', fn(\$q) => \$q->where('{$foreignKey}', request('{$foreignKey}')))";
                }
            }
        }
        return implode("\n", $result);
    }

    protected function storeAssignmentsToString($fields, $relationships = [])
    {
        $result = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'select' && empty($field['dynamic_model']) && empty($field['options'])) {
                continue;
            } elseif ($field['type'] === 'slider') {
                $result[] = "            \${{NAME_LOWER}}->{$field['name']} = \$validated['{$field['name']}'][0];";
            } else {
                $result[] = "            \${{NAME_LOWER}}->{$field['name']} = \$validated['{$field['name']}'];";
            }
        }
        
        // Add relationship assignments (exclude self-references)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && (!isset($this->currentModelName) || $relationship['model'] !== $this->currentModelName) && $relationship['foreign_key'] !== 'created_by') {
                $result[] = "            \${{NAME_LOWER}}->{$relationship['foreign_key']} = \$validated['{$relationship['foreign_key']}'];";
            }
        }
        
        return implode("\n", $result);
    }

    protected function updateAssignmentsToString($fields, $relationships = [])
    {
        $result = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'select' && empty($field['dynamic_model']) && empty($field['options'])) {
                continue;
            } elseif ($field['type'] === 'slider') {
                $result[] = "            \${{NAME_LOWER}}->{$field['name']} = \$validated['{$field['name']}'][0];";
            } else {
                $result[] = "            \${{NAME_LOWER}}->{$field['name']} = \$validated['{$field['name']}'];";
            }
        }
        
        // Add relationship assignments (exclude self-references)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && (!isset($this->currentModelName) || $relationship['model'] !== $this->currentModelName) && $relationship['foreign_key'] !== 'created_by') {
                $result[] = "            \${{NAME_LOWER}}->{$relationship['foreign_key']} = \$validated['{$relationship['foreign_key']}'];";
            }
        }
        
        return implode("\n", $result);
    }

    protected function getRelationshipImports($relationships, $currentModel = null)
    {
        $imports = [];
        foreach ($relationships as $relationship) {
            // Skip importing the current model to avoid duplicate imports
            if ($currentModel && $relationship['model'] === $currentModel) {
                continue;
            }
            $imports[] = "use App\\Models\\{$relationship['model']};";
        }
        return implode("\n", $imports);
    }

    protected function getPackageRelationshipModelImports($relationships, $currentModel = null, $packageNamespace = null)
    {
        $imports = [];
        foreach ($relationships as $relationship) {
            // Skip importing the current model to avoid duplicate imports
            if ($currentModel && $relationship['model'] === $currentModel) {
                continue;
            }
            
            // Always use core User model, not package User model
            if ($relationship['model'] === 'User') {
                $imports[] = "use App\\Models\\User;";
            } else if ($packageNamespace) {
                $imports[] = "use {$packageNamespace}\\Models\\{$relationship['model']};";
            } else {
                $imports[] = "use App\\Models\\{$relationship['model']};";
            }
        }
        return implode("\n", $imports);
    }

    protected function getPackageRelationshipImports($relationships, $currentModel = null, $packageNamespace = null)
    {
        $imports = [];
        foreach ($relationships as $relationship) {
            // Skip importing the current model to avoid duplicate imports
            if ($currentModel && $relationship['model'] === $currentModel) {
                continue;
            }
            
            // Always use core User model, not package User model
            if ($relationship['model'] === 'User') {
                // Don't import User model as it's handled in seeder logic
                continue;
            }
            
            if ($packageNamespace) {
                $imports[] = "use {$packageNamespace}\\Models\\{$relationship['model']};";
            } else {
                $imports[] = "use App\\Models\\{$relationship['model']};";
            }
        }
        return implode("\n", $imports);
    }

    protected function getRelationshipMethods($relationships)
    {
        $methods = [];
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $methods[] = "    public function {$relationship['name']}()
    {
        return \$this->belongsTo({$relationship['model']}::class);
    }";
            }
        }
        return implode("\n\n", $methods);
    }

    protected function getNameAccessor($fields)
    {
        // If the first field is 'title', add a name accessor for consistent relationship display
        $firstField = array_key_first($fields);
        if ($firstField === 'title') {
            return "    // Accessor for consistent relationship display
    public function getNameAttribute()
    {
        return \$this->title;
    }";
        }
        return '';
    }

    protected function getRelationshipMigrations($relationships, $currentModel = null)
    {
        $migrations = [];
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $currentModel) {
                // Skip created_by as it's handled in the template
                if ($relationship['foreign_key'] !== 'created_by') {
                    $migrations[] = "                \$table->foreignId('{$relationship['foreign_key']}')->nullable()->constrained('{$relationship['table']}')->onDelete('set null');";
                }
            }
        }
        return implode("\n", $migrations);
    }

    protected function getRelationshipControllerImports($relationships, $currentModel = null)
    {
        $imports = [];
        foreach ($relationships as $relationship) {
            // Skip importing the current model to avoid duplicate imports
            if ($currentModel && $relationship['model'] === $currentModel) {
                continue;
            }
            $imports[] = "use App\\Models\\{$relationship['model']};";
        }
        return implode("\n", $imports);
    }

    protected function getPackageRelationshipControllerImports($relationships, $currentModel = null, $packageNamespace = null)
    {
        $imports = [];
        foreach ($relationships as $relationship) {
            // Skip importing the current model to avoid duplicate imports
            if ($currentModel && $relationship['model'] === $currentModel) {
                continue;
            }
            
            // Always use core User model, not package User model
            if ($relationship['model'] === 'User') {
                $imports[] = "use App\\Models\\User;";
            } else if ($packageNamespace) {
                $imports[] = "use {$packageNamespace}\\Models\\{$relationship['model']};";
            } else {
                $imports[] = "use App\\Models\\{$relationship['model']};";
            }
        }
        return implode("\n", $imports);
    }

    protected function getRelationshipData($relationships, $currentModel = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $currentModel) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "                '{$pluralName}' => {$relationship['model']}::where('created_by', creatorId())->select('id', 'name')->get(),";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $modelName = $field['dynamic_model'];
                $pluralName = Str::plural(Str::lower($modelName));
                $fieldName = $field['dynamic_field'];
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "                '{$pluralName}' => \\App\\Models\\{$modelName}::where('created_by', creatorId())->select('id', '{$fieldName}')->get(),";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return implode("\n", $data);
    }

    protected function getPackageRelationshipData($relationships, $currentModel = null, $packageNamespace = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $currentModel) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "                '{$pluralName}' => {$relationship['model']}::where('created_by', creatorId())->select('id', 'name')->get(),";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $modelName = $field['dynamic_model'];
                $pluralName = Str::plural(Str::lower($modelName));
                $fieldName = $field['dynamic_field'];
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    // Dynamic models are always core models
                    $data[] = "                '{$pluralName}' => \\App\\Models\\{$modelName}::where('created_by', creatorId())->select('id', '{$fieldName}')->get(),";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return implode("\n", $data);
    }

    protected function getRelationshipPageData($relationships, $modelName = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $modelName) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Add all relationships to props (both independent and dependent)
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = $pluralName;
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $pluralName = Str::plural(Str::lower($field['dynamic_model']));
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = $pluralName;
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return implode(', ', $data);
    }

    protected function getRelationshipPageDataProps($relationships, $modelName = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $modelName) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Add all relationships to props (both independent and dependent)
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "\n    {$pluralName}: any[];";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $pluralName = Str::plural(Str::lower($field['dynamic_model']));
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "\n    {$pluralName}: any[];";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return implode('', $data);
    }

    protected function getInterfaceFields($fields, $relationships = [])
    {
        $interfaceFields = [];
        $addedFields = [];
        
        foreach ($fields as $field) {
            $fieldType = $this->getTypeScriptType($field);
            $optional = $field['nullable'] ? '?' : '';
            $interfaceFields[] = "    {$field['name']}{$optional}: {$fieldType};";
            $addedFields[] = $field['name'];
        }
        
        // Add relationship fields (avoid duplicates)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                if (!in_array($relationship['foreign_key'], $addedFields)) {
                    $interfaceFields[] = "    {$relationship['foreign_key']}?: number;";
                    $addedFields[] = $relationship['foreign_key'];
                }
                if (!in_array($relationship['name'], $addedFields)) {
                    $interfaceFields[] = "    {$relationship['name']}?: {$relationship['model']};";
                    $addedFields[] = $relationship['name'];
                }
            }
        }
        
        return implode("\n", $interfaceFields);
    }

    protected function getCreateFormInterfaceFields($fields, $relationships = [])
    {
        $interfaceFields = [];
        $addedFields = [];
        
        foreach ($fields as $field) {
            $fieldType = $this->getFormTypeScriptType($field);
            $interfaceFields[] = "    {$field['name']}: {$fieldType};";
            $addedFields[] = $field['name'];
        }
        
        // Add relationship foreign keys (avoid duplicates)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && !in_array($relationship['foreign_key'], $addedFields)) {
                $interfaceFields[] = "    {$relationship['foreign_key']}: string;";
                $addedFields[] = $relationship['foreign_key'];
            }
        }
        
        return implode("\n", $interfaceFields);
    }

    protected function getEditFormInterfaceFields($fields, $relationships = [])
    {
        $interfaceFields = [];
        $addedFields = [];
        
        foreach ($fields as $field) {
            $fieldType = $this->getFormTypeScriptType($field);
            $interfaceFields[] = "    {$field['name']}: {$fieldType};";
            $addedFields[] = $field['name'];
        }
        
        // Add relationship foreign keys (avoid duplicates)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && !in_array($relationship['foreign_key'], $addedFields)) {
                $interfaceFields[] = "    {$relationship['foreign_key']}: string;";
                $addedFields[] = $relationship['foreign_key'];
            }
        }
        
        return implode("\n", $interfaceFields);
    }

    protected function getTypeScriptType($field)
    {
        if (!empty($field['dynamic_model'])) {
            if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                return 'string[]';
            } else {
                return 'number';
            }
        }
        
        switch ($field['type']) {
            case 'textbox':
            case 'textarea':
            case 'richtext':
            case 'phone':
            case 'daterangepicker':
            case 'media':
                return 'string';
            case 'number':
            case 'currency':
            case 'rating':
                return 'number';
            case 'select':
            case 'checkbox':
            case 'switch':
                return 'boolean';
            case 'radiobutton':
                return 'string';
            case 'checkboxgroup':
            case 'multiselect':
                return 'string[]';
            case 'datepicker':
            case 'timepicker':
                return 'string';
            case 'slider':
                return 'number[]';
            default:
                return 'any';
        }
    }

    protected function getFormTypeScriptType($field)
    {
        if (!empty($field['dynamic_model'])) {
            if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                return 'string[]';
            } else {
                return 'string';
            }
        }
        
        switch ($field['type']) {
            case 'textbox':
            case 'textarea':
            case 'richtext':
            case 'phone':
            case 'daterangepicker':
            case 'radiobutton':
            case 'datepicker':
            case 'timepicker':
                return 'string';
            case 'number':
            case 'currency':
                return 'string';
            case 'rating':
                return 'number';
            case 'select':
            case 'checkbox':
            case 'switch':
                return 'boolean';
            case 'checkboxgroup':
            case 'multiselect':
                return 'string[]';
            case 'slider':
                return 'number[]';
            case 'media':
                return (isset($field['multiple']) && $field['multiple']) ? 'string[]' : 'string';
            default:
                return 'any';
        }
    }

    protected function getRelationshipPageDataDestructure($relationships, $modelName = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $modelName) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Add all relationships to destructure (both independent and dependent)
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = $pluralName;
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $pluralName = Str::plural(Str::lower($field['dynamic_model']));
                
                // Avoid duplicate keys
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = $pluralName;
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return !empty($data) ? ', ' . implode(', ', $data) : '';
    }

    protected function getRelationshipPageDataPropsPass($relationships, $modelName = null)
    {
        $data = [];
        $addedKeys = [];
        
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo' && $relationship['model'] !== $modelName) {
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "{$pluralName}={{$pluralName}}";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        // Add dynamic model data for fields
        foreach ($this->currentFields ?? [] as $field) {
            if (!empty($field['dynamic_model'])) {
                $pluralName = Str::plural(Str::lower($field['dynamic_model']));
                
                if (!in_array($pluralName, $addedKeys)) {
                    $data[] = "{$pluralName}={{$pluralName}}";
                    $addedKeys[] = $pluralName;
                }
            }
        }
        
        return !empty($data) ? ' ' . implode(' ', $data) : '';
    }

    protected function hasIsActiveField($fields)
    {
        return isset($fields['is_active']);
    }

    protected function getSwitchFieldsHandling($fields, $type)
    {
        $switchFields = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'switch') {
                $switchFields[] = "            \$validated['{$field['name']}'] = \$request->boolean('{$field['name']}', false);";
            }
        }
        return implode("\n", $switchFields);
    }

    protected function isFieldFilterable($fieldName, $filterable, $fields)
    {
        return !empty($filterable) && in_array($fieldName, $filterable) && isset($fields[$fieldName]) && ($fields[$fieldName]['type'] === 'select' || $fields[$fieldName]['type'] === 'radiobutton' || $fields[$fieldName]['type'] === 'checkboxgroup' || $fields[$fieldName]['type'] === 'multiselect');
    }

    protected function getFilterInterfaceFields($searchable, $filterable, $fields, $relationships = [])
    {
        $interfaceFields = [];
        
        // Add searchable fields
        foreach ($searchable as $field) {
            if (!empty($field)) {
                $interfaceFields[] = "    {$field}: string;";
            }
        }
        
        // CRITICAL: Only add filterable fields if explicitly provided and not empty
        if (is_array($filterable) && count($filterable) > 0) {
            $validFilterableFields = array_filter($filterable, function($field) {
                return !empty(trim($field));
            });
            
            if (count($validFilterableFields) > 0) {
                foreach ($validFilterableFields as $field) {
                    if (isset($fields[$field])) {
                        $fieldData = $fields[$field];
                        if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating' || $fieldData['type'] === 'switch')) {
                            $interfaceFields[] = "    {$field}: string;";
                        }
                    } elseif (isset($relationships[$field]) && $relationships[$field]['type'] === 'belongsTo') {
                        $foreignKey = $relationships[$field]['foreign_key'];
                        $interfaceFields[] = "    {$foreignKey}: string;";
                    }
                }
            }
        }
        
        return implode("\n", $interfaceFields);
    }

    protected function getCreateFormData($fields, $relationships = [])
    {
        $formData = [];
        
        // Add regular fields
        foreach ($fields as $field) {
            if (!empty($field['dynamic_model'])) {
                // Handle dynamic model fields
                if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                    $formData[] = "        {$field['name']}: [] as string[],";
                } else {
                    $formData[] = "        {$field['name']}: '',";
                }
            } elseif ($field['type'] === 'select' || $field['type'] === 'checkbox') {
                if ($field['type'] === 'select' && !empty($field['options'])) {
                    // Custom select options - default to first option (index 0)
                    $formData[] = "        {$field['name']}: '0',";
                } else {
                    // Boolean select or checkbox
                    $formData[] = "        {$field['name']}: " . ($field['type'] === 'select' ? 'true' : 'false') . ",";
                }
            } elseif ($field['type'] === 'radiobutton') {
                // For static radiobutton, default to first option (index 0)
                $formData[] = "        {$field['name']}: '0',";
            } elseif ($field['type'] === 'checkboxgroup') {
                $formData[] = "        {$field['name']}: [] as string[],";
            } elseif ($field['type'] === 'multiselect') {
                $formData[] = "        {$field['name']}: [] as string[],";
            } elseif ($field['type'] === 'datepicker') {
                $formData[] = "        {$field['name']}: '',";
            } elseif ($field['type'] === 'timepicker') {
                $formData[] = "        {$field['name']}: '',";
            } elseif ($field['type'] === 'daterangepicker') {
                $formData[] = "        {$field['name']}: '',";
            } elseif ($field['type'] === 'number') {
                $formData[] = "        {$field['name']}: '',";
            } elseif ($field['type'] === 'slider') {
                $formData[] = "        {$field['name']}: [50],";
            } elseif ($field['type'] === 'switch') {
                $formData[] = "        {$field['name']}: false,";
            } elseif ($field['type'] === 'rating') {
                $formData[] = "        {$field['name']}: 0,";
            } elseif ($field['type'] === 'media') {
                $isMultiple = isset($field['multiple']) && $field['multiple'];
                $formData[] = $isMultiple ? "        {$field['name']}: [] as string[]," : "        {$field['name']}: '',";
            } elseif ($field['type'] === 'color') {
                $formData[] = "        {$field['name']}: '#FF6B6B',";
            } else {
                $formData[] = "        {$field['name']}: '',";
            }
        }
        
        // Add relationship fields
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $formData[] = "        {$relationship['foreign_key']}: '',";
            }
        }
        
        return implode("\n", $formData);
    }

    protected function getEditFormData($fields, $relationships = [])
    {
        $formData = [];
        
        // Add regular fields
        foreach ($fields as $field) {
            if (!empty($field['dynamic_model'])) {
                // Handle dynamic model fields
                if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                    $formData[] = "        {$field['name']}: ({{NAME_LOWER}}.{$field['name']} as string[]) || [],";
                } else {
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '',";
                }
            } elseif ($field['type'] === 'select' || $field['type'] === 'checkbox') {
                if ($field['type'] === 'select' && !empty($field['options'])) {
                    // Custom select options - convert to string with fallback to '0'
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']}?.toString() ?? '0',";
                } else {
                    // Boolean select or checkbox
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? false,";
                }
            } elseif ($field['type'] === 'radiobutton') {
                // For static radiobutton, convert to string with fallback to '0'
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']}?.toString() || '0',";
            } elseif ($field['type'] === 'checkboxgroup') {
                $formData[] = "        {$field['name']}: ({{NAME_LOWER}}.{$field['name']} as string[]) || [],";
            } elseif ($field['type'] === 'multiselect') {
                $formData[] = "        {$field['name']}: ({{NAME_LOWER}}.{$field['name']} as string[]) || [],";
            } elseif ($field['type'] === 'datepicker') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} || '',";
            } elseif ($field['type'] === 'timepicker') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ? {{NAME_LOWER}}.{$field['name']}.substring(0, 5) : '',";
            } elseif ($field['type'] === 'daterangepicker') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} || '',";
            } elseif ($field['type'] === 'number') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '',";
            } elseif ($field['type'] === 'currency') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '',";
            } elseif ($field['type'] === 'slider') {
                $formData[] = "        {$field['name']}: Array.isArray({{NAME_LOWER}}.{$field['name']}) ? {{NAME_LOWER}}.{$field['name']} : [{{NAME_LOWER}}.{$field['name']} ?? 50],";
            } elseif ($field['type'] === 'switch') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? false,";
            } elseif ($field['type'] === 'rating') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? 0,";
            } elseif ($field['type'] === 'media') {
                $isMultiple = isset($field['multiple']) && $field['multiple'];
                if ($isMultiple) {
                    $formData[] = "        {$field['name']}: Array.isArray({{NAME_LOWER}}.{$field['name']}) ? {{NAME_LOWER}}.{$field['name']} : ({{NAME_LOWER}}.{$field['name']} ? [{{NAME_LOWER}}.{$field['name']}] : []),";
                } else {
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} || '',";
                }
            } elseif ($field['type'] === 'color') {
                $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '#FF6B6B',";
            } else {
                // Convert all string fields to ensure proper type
                if (in_array($field['type'], ['textbox', 'textarea', 'richtext', 'phone', 'daterangepicker', 'currency'])) {
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '',";
                } else {
                    $formData[] = "        {$field['name']}: {{NAME_LOWER}}.{$field['name']} ?? '',";
                }
            }
        }
        
        // Add relationship fields
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $formData[] = "        {$relationship['foreign_key']}: {{NAME_LOWER}}.{$relationship['foreign_key']}?.toString() || '',";
            }
        }
        
        return implode("\n", $formData);
    }

    protected function getTableRelationshipsWith($tableRelationships)
    {
        if (empty($tableRelationships)) {
            return '';
        }
        
        $relations = [];
        foreach ($tableRelationships as $tableRel) {
            $relations[] = $tableRel['relation'];
        }
        
        $uniqueRelations = array_unique($relations);
        return "                ->with([" . implode(', ', array_map(fn($rel) => "'$rel'", $uniqueRelations)) . "])";
    }

    protected function getGridViewContent($fields, $nameLower, $tableRelationships = [], $tableFields = [])
    {
        $firstField = array_key_first($fields);
        $secondField = count($fields) > 1 ? array_keys($fields)[1] : null;
        $hasIsActive = isset($fields['is_active']);
        
        $firstFieldLabel = Str::title(str_replace('_', ' ', $firstField));
        $secondFieldLabel = $secondField ? Str::title(str_replace('_', ' ', $secondField)) : 'Details';
        
        $content = "                                            <div className=\"flex items-center justify-between mb-4\">\n";
        $content .= "                                                <div className=\"flex items-center gap-3\">\n";
        $content .= "                                                    <div className=\"p-2 bg-primary/10 rounded-lg\">\n";
        $content .= "                                                        <{{ICON}} className=\"h-5 w-5 text-primary\" />\n";
        $content .= "                                                    </div>\n";
        $content .= "                                                    <h3 className=\"font-semibold text-lg\">{" . $nameLower . "." . $firstField . "}</h3>\n";
        $content .= "                                                </div>\n";
        
        if ($hasIsActive) {
            $content .= "                                                <span className={`px-3 py-1 rounded-full text-xs font-medium \${" . $nameLower . ".is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>\n";
            $content .= "                                                    {" . $nameLower . ".is_active ? t('Active') : t('Inactive')}\n";
            $content .= "                                                </span>\n";
        }
        
        $content .= "                                            </div>\n";
        
        $content .= "                                            <div className=\"space-y-3 mb-6\">\n";
        
        // Determine which fields to show in grid
        $fieldsToShow = !empty($tableFields) ? array_filter($tableFields, fn($f) => trim($f) && !str_contains($f, '.')) : array_keys($fields);
        
        // Add fields except the first one
        foreach ($fieldsToShow as $fieldName) {
            $fieldName = trim($fieldName);
            if ($fieldName === $firstField || !isset($fields[$fieldName])) continue; // Skip first field and invalid fields
            
            $field = $fields[$fieldName];
            
            $fieldLabel = Str::title(str_replace('_', ' ', $fieldName));
            
            if ($field['type'] === 'radiobutton' && !empty($field['options'])) {
                // Handle radiobutton with options
                $optionMap = [];
                foreach ($field['options'] as $index => $option) {
                    $optionMap[$index] = $option;
                }
                $optionMapJson = json_encode($optionMap, JSON_FORCE_OBJECT);
                
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{(() => { const options: any = {$optionMapJson}; return options[" . $nameLower . "." . $fieldName . "] || " . $nameLower . "." . $fieldName . " || '-'; })()}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'select' && !empty($field['options'])) {
                // Handle select with custom options
                $optionMap = [];
                foreach ($field['options'] as $index => $option) {
                    $optionMap[$index] = $option;
                }
                $optionMapJson = json_encode($optionMap, JSON_FORCE_OBJECT);
                
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <span className=\"px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800\">\n";
                $content .= "                                                        {(() => { const options: any = {$optionMapJson}; return t(options[" . $nameLower . "." . $fieldName . "] || " . $nameLower . "." . $fieldName . " || '-'); })()}\n";
                $content .= "                                                    </span>\n";
                $content .= "                                                </div>\n";
            } elseif ($fieldName === 'is_active') {
                // Skip is_active as it's shown as badge
                continue;
            } elseif ($field['type'] === 'datepicker') {
                // Handle datepicker
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " ? formatDateTime(" . $nameLower . "." . $fieldName . ") : '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'timepicker') {
                // Handle timepicker
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " ? formatTime(" . $nameLower . "." . $fieldName . ") : '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'daterangepicker') {
                // Handle daterangepicker
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " || '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'datetime') {
                // Handle datetime
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " ? formatDateTime(" . $nameLower . "." . $fieldName . ") : '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'datetimerange') {
                // Handle datetimerange
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{(() => {\n";
                $content .= "                                                        const value = " . $nameLower . "." . $fieldName . ";\n";
                $content .= "                                                        if (!value) return '-';\n";
                $content .= "                                                        const parts = value.split(' - ');\n";
                $content .= "                                                        if (parts.length === 2) {\n";
                $content .= "                                                            return formatDateTime(parts[0]) + ' - ' + formatDateTime(parts[1]);\n";
                $content .= "                                                        }\n";
                $content .= "                                                        return value;\n";
                $content .= "                                                    })()}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'tagsinput') {
                // Handle tagsinput
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <div className=\"flex flex-wrap gap-1\">\n";
                $content .= "                                                        {(" . $nameLower . "." . $fieldName . " && Array.isArray(" . $nameLower . "." . $fieldName . ") && " . $nameLower . "." . $fieldName . ".length > 0) ? (\n";
                $content .= "                                                            <>\n";
                $content .= "                                                                {" . $nameLower . "." . $fieldName . ".slice(0, 3).map((tag: string, index: number) => (\n";
                $content .= "                                                                    <Badge key={index} variant=\"secondary\" className=\"text-xs\">{tag}</Badge>\n";
                $content .= "                                                                ))}\n";
                $content .= "                                                                {" . $nameLower . "." . $fieldName . ".length > 3 && (\n";
                $content .= "                                                                    <Badge variant=\"outline\" className=\"text-xs\">+{" . $nameLower . "." . $fieldName . ".length - 3}</Badge>\n";
                $content .= "                                                                )}\n";
                $content .= "                                                            </>\n";
                $content .= "                                                        ) : (\n";
                $content .= "                                                            <span>-</span>\n";
                $content .= "                                                        )}\n";
                $content .= "                                                    </div>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'number') {
                // Handle number
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " || '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'currency') {
                // Handle currency
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " ? formatCurrency(" . $nameLower . "." . $fieldName . ") : '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'richtext') {
                // Handle richtext
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <div className=\"font-medium prose prose-sm max-w-none\" dangerouslySetInnerHTML={{ __html: " . $nameLower . "." . $fieldName . " || '-' }} />\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'phone') {
                // Handle phone
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " || '-'}</p>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'slider') {
                // Handle slider
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <div className=\"flex items-center gap-2\">\n";
                $content .= "                                                        <div className=\"w-20 bg-gray-200 rounded-full h-2\">\n";
                $content .= "                                                            <div className=\"bg-primary h-2 rounded-full\" style={{ width: `\${" . $nameLower . "." . $fieldName . " || 0}%` }}></div>\n";
                $content .= "                                                        </div>\n";
                $content .= "                                                        <span className=\"text-sm font-medium\">{" . $nameLower . "." . $fieldName . " || 0}%</span>\n";
                $content .= "                                                    </div>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'switch') {
                // Handle switch
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <span className={`px-2 py-1 rounded-full text-sm \${" . $nameLower . "." . $fieldName . " ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>\n";
                $content .= "                                                        {" . $nameLower . "." . $fieldName . " ? t('On') : t('Off')}\n";
                $content .= "                                                    </span>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'rating') {
                // Handle rating
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <div className=\"flex items-center gap-1\">\n";
                $content .= "                                                        {[1, 2, 3, 4, 5].map((star) => (\n";
                $content .= "                                                            <span key={star} className={`text-sm \${star <= (" . $nameLower . "." . $fieldName . " || 0) ? 'text-yellow-400' : 'text-gray-300'}`}>★</span>\n";
                $content .= "                                                        ))}\n";
                $content .= "                                                    </div>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'color') {
                // Handle color field
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <div className=\"flex items-center justify-start\">\n";
                $content .= "                                                        <div \n";
                $content .= "                                                            className=\"w-6 h-6 rounded border border-gray-300\" \n";
                $content .= "                                                            style={{ backgroundColor: " . $nameLower . "." . $fieldName . " || '#FF6B6B' }}\n";
                $content .= "                                                            title={" . $nameLower . "." . $fieldName . " || '#FF6B6B'}\n";
                $content .= "                                                        ></div>\n";
                $content .= "                                                    </div>\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'media') {
                // Handle media (both single and multiple)
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    {(() => {\n";
                $content .= "                                                        const files = Array.isArray(" . $nameLower . "." . $fieldName . ") ? " . $nameLower . "." . $fieldName . " : (" . $nameLower . "." . $fieldName . " ? [" . $nameLower . "." . $fieldName . "] : []);\n";
                $content .= "                                                        return files.length > 0 ? (\n";
                $content .= "                                                            <div className=\"flex gap-1 flex-wrap\">\n";
                $content .= "                                                                {files.slice(0, 3).map((file, index) => {\n";
                $content .= "                                                                    const isImage = /\\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file);\n";
                $content .= "                                                                    return isImage ? (\n";
                $content .= "                                                                        <img\n";
                $content .= "                                                                            key={index}\n";
                $content .= "                                                                            src={getImagePath(file)}\n";
                $content .= "                                                                            alt=\"{$fieldLabel}\"\n";
                $content .= "                                                                            className=\"w-12 h-12 object-cover rounded border hover:scale-110 transition-transform cursor-pointer\"\n";
                $content .= "                                                                            onClick={() => window.open(getImagePath(file), '_blank')}\n";
                $content .= "                                                                        />\n";
                $content .= "                                                                    ) : (\n";
                $content .= "                                                                        <div key={index} className=\"w-12 h-12 bg-gray-100 rounded border flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors\" onClick={() => {\n";
                $content .= "                                                                            const link = document.createElement('a');\n";
                $content .= "                                                                            link.href = getImagePath(file);\n";
                $content .= "                                                                            link.download = file.split('/').pop() || 'file';\n";
                $content .= "                                                                            link.click();\n";
                $content .= "                                                                        }}>\n";
                $content .= "                                                                            <Download className=\"w-6 h-6 text-gray-600\" />\n";
                $content .= "                                                                        </div>\n";
                $content .= "                                                                    );\n";
                $content .= "                                                                })}\n";
                $content .= "                                                                {files.length > 3 && (\n";
                $content .= "                                                                    <span className=\"text-xs text-gray-500 self-center\">+{files.length - 3}</span>\n";
                $content .= "                                                                )}\n";
                $content .= "                                                            </div>\n";
                $content .= "                                                        ) : (\n";
                $content .= "                                                            <span className=\"text-gray-500\">-</span>\n";
                $content .= "                                                        );\n";
                $content .= "                                                    })()}\n";
                $content .= "                                                </div>\n";
            } elseif ($field['type'] === 'checkboxgroup') {
                // Handle checkboxgroup - check if it has dynamic model or static options
                if (!empty($field['dynamic_model'])) {
                    // Dynamic model checkboxgroup - show model names as badges
                    $modelName = Str::plural(Str::lower($field['dynamic_model']));
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    $content .= "                                                    <div className=\"flex flex-wrap gap-1\">\n";
                    $content .= "                                                        {(() => {\n";
                    $content .= "                                                            const modelData = {$modelName} || [];\n";
                    $content .= "                                                            const values = " . $nameLower . "." . $fieldName . " || [];\n";
                    $content .= "                                                            if (!Array.isArray(values) || values.length === 0) return <span>-</span>;\n";
                    $content .= "                                                            return values.slice(0, 3).map((v, index) => {\n";
                    $content .= "                                                                const item = modelData.find(item => item.id.toString() === v?.toString());\n";
                    $content .= "                                                                return (\n";
                    $content .= "                                                                    <Badge key={index} variant=\"secondary\" className=\"text-xs\">\n";
                    $content .= "                                                                        {item?.{$field['dynamic_field']} || v}\n";
                    $content .= "                                                                    </Badge>\n";
                    $content .= "                                                                );\n";
                    $content .= "                                                            }).concat(values.length > 3 ? [\n";
                    $content .= "                                                                <Badge key=\"more\" variant=\"outline\" className=\"text-xs\">\n";
                    $content .= "                                                                    +{values.length - 3}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            ] : []);\n";
                    $content .= "                                                        })()}\n";
                    $content .= "                                                    </div>\n";
                    $content .= "                                                </div>\n";
                } else {
                    // Static options checkboxgroup
                    $optionMap = [];
                    foreach ($field['options'] as $index => $option) {
                        $optionMap[$index] = $option;
                    }
                    $optionMapJson = json_encode($optionMap, JSON_FORCE_OBJECT);
                    
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    $content .= "                                                    <div className=\"flex flex-wrap gap-1\">\n";
                    $content .= "                                                        {(() => {\n";
                    $content .= "                                                            const options = {$optionMapJson};\n";
                    $content .= "                                                            const values = " . $nameLower . "." . $fieldName . " || [];\n";
                    $content .= "                                                            if (!Array.isArray(values) || values.length === 0) return <span>-</span>;\n";
                    $content .= "                                                            return values.slice(0, 3).map((v, index) => (\n";
                    $content .= "                                                                <Badge key={index} variant=\"secondary\" className=\"text-xs\">\n";
                    $content .= "                                                                    {options[v] || v}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            )).concat(values.length > 3 ? [\n";
                    $content .= "                                                                <Badge key=\"more\" variant=\"outline\" className=\"text-xs\">\n";
                    $content .= "                                                                    +{values.length - 3}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            ] : []);\n";
                    $content .= "                                                        })()}\n";
                    $content .= "                                                    </div>\n";
                    $content .= "                                                </div>\n";
                }
            } elseif ($field['type'] === 'multiselect') {
                // Handle multiselect - check if it has dynamic model or static options
                if (!empty($field['dynamic_model'])) {
                    // Dynamic model multiselect - show model names as badges
                    $modelName = Str::plural(Str::lower($field['dynamic_model']));
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    $content .= "                                                    <div className=\"flex flex-wrap gap-1\">\n";
                    $content .= "                                                        {(() => {\n";
                    $content .= "                                                            let items = [];\n";
                    $content .= "                                                            if (typeof " . $nameLower . "." . $fieldName . " === 'string') {\n";
                    $content .= "                                                                try {\n";
                    $content .= "                                                                    items = JSON.parse(" . $nameLower . "." . $fieldName . ");\n";
                    $content .= "                                                                } catch {\n";
                    $content .= "                                                                    items = [" . $nameLower . "." . $fieldName . "];\n";
                    $content .= "                                                                }\n";
                    $content .= "                                                            } else if (Array.isArray(" . $nameLower . "." . $fieldName . ")) {\n";
                    $content .= "                                                                items = " . $nameLower . "." . $fieldName . ";\n";
                    $content .= "                                                            }\n";
                    $content .= "                                                            const modelData = {$modelName} || [];\n";
                    $content .= "                                                            if (items.length === 0) return <span>-</span>;\n";
                    $content .= "                                                            return items.slice(0, 3).map((item: any, index: number) => {\n";
                    $content .= "                                                                const modelItem = modelData.find((m: any) => m.id.toString() === item?.toString());\n";
                    $content .= "                                                                return (\n";
                    $content .= "                                                                    <Badge key={index} variant=\"secondary\" className=\"text-xs\">\n";
                    $content .= "                                                                        {modelItem?.{$field['dynamic_field']} || item}\n";
                    $content .= "                                                                    </Badge>\n";
                    $content .= "                                                                );\n";
                    $content .= "                                                            }).concat(items.length > 3 ? [\n";
                    $content .= "                                                                <Badge key=\"more\" variant=\"outline\" className=\"text-xs\">\n";
                    $content .= "                                                                    +{items.length - 3}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            ] : []);\n";
                    $content .= "                                                        })()}\n";
                    $content .= "                                                    </div>\n";
                    $content .= "                                                </div>\n";
                } else {
                    // Static options multiselect
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    // Generate option map for grid view
                    $optionMap = [];
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $index => $option) {
                            $optionMap[$option] = $option; // value -> label
                            $optionMap[(string)$index] = $option; // index -> label
                        }
                    }
                    $optionMapString = json_encode($optionMap, JSON_FORCE_OBJECT);
                    
                    $content .= "                                                    <div className=\"flex flex-wrap gap-1\">\n";
                    $content .= "                                                        {(() => {\n";
                    $content .= "                                                            let items = [];\n";
                    $content .= "                                                            if (typeof " . $nameLower . "." . $fieldName . " === 'string') {\n";
                    $content .= "                                                                try {\n";
                    $content .= "                                                                    items = JSON.parse(" . $nameLower . "." . $fieldName . ");\n";
                    $content .= "                                                                } catch {\n";
                    $content .= "                                                                    items = [" . $nameLower . "." . $fieldName . "];\n";
                    $content .= "                                                                }\n";
                    $content .= "                                                            } else if (Array.isArray(" . $nameLower . "." . $fieldName . ")) {\n";
                    $content .= "                                                                items = " . $nameLower . "." . $fieldName . ";\n";
                    $content .= "                                                            }\n";
                    $content .= "                                                            const optionMap: any = {$optionMapString};\n";
                    $content .= "                                                            if (items.length === 0) return <span>-</span>;\n";
                    $content .= "                                                            return items.slice(0, 3).map((item: any, index: number) => (\n";
                    $content .= "                                                                <Badge key={index} variant=\"secondary\" className=\"text-xs\">\n";
                    $content .= "                                                                    {optionMap[item] || item}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            )).concat(items.length > 3 ? [\n";
                    $content .= "                                                                <Badge key=\"more\" variant=\"outline\" className=\"text-xs\">\n";
                    $content .= "                                                                    +{items.length - 3}\n";
                    $content .= "                                                                </Badge>\n";
                    $content .= "                                                            ] : []);\n";
                    $content .= "                                                        })()}\n";
                    $content .= "                                                    </div>\n";
                    $content .= "                                                </div>\n";
                }
            } elseif (!empty($field['dynamic_model'])) {
                // Handle dynamic model fields
                $modelName = Str::plural(Str::lower($field['dynamic_model']));
                if ($field['type'] === 'checkboxgroup' || $field['type'] === 'multiselect') {
                    // Multiple selection - show model names as badges
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    $content .= "                                                    {(() => {\n";
                    $content .= "                                                        const values = " . $nameLower . "." . $fieldName . " || [];\n";
                    $content .= "                                                        const modelData = {$modelName} || [];\n";
                    $content .= "                                                        return Array.isArray(values) && values.length > 0 ? (\n";
                    $content .= "                                                            <div className=\"flex flex-wrap gap-1\">\n";
                    $content .= "                                                                {values.slice(0, 3).map((item: any, index: number) => {\n";
                    $content .= "                                                                    const modelItem = modelData.find((m: any) => m.id.toString() === item?.toString());\n";
                    $content .= "                                                                    return (\n";
                    $content .= "                                                                        <span key={index} className=\"px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs\">\n";
                    $content .= "                                                                            {modelItem?.{$field['dynamic_field']} || item}\n";
                    $content .= "                                                                        </span>\n";
                    $content .= "                                                                    );\n";
                    $content .= "                                                                })}\n";
                    $content .= "                                                                {values.length > 3 && (\n";
                    $content .= "                                                                    <span className=\"text-xs text-gray-500\">+{values.length - 3}</span>\n";
                    $content .= "                                                                )}\n";
                    $content .= "                                                            </div>\n";
                    $content .= "                                                        ) : (\n";
                    $content .= "                                                            <span className=\"text-gray-500\">-</span>\n";
                    $content .= "                                                        );\n";
                    $content .= "                                                    })()}\n";
                    $content .= "                                                </div>\n";
                } else {
                    // Single selection - show model name
                    $content .= "                                                <div className=\"text-sm\">\n";
                    $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                    $content .= "                                                    <p className=\"font-medium\">{" . $modelName . "?.find(item => item.id.toString() === " . $nameLower . "." . $fieldName . "?.toString())?.{$field['dynamic_field']} || " . $nameLower . "." . $fieldName . " || '-'}</p>\n";
                    $content .= "                                                </div>\n";
                }
            } else {
                $content .= "                                                <div className=\"text-sm\">\n";
                $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$fieldLabel}')}</p>\n";
                $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $fieldName . " || '-'}</p>\n";
                $content .= "                                                </div>\n";
            }
        }
        
        // Add relationship fields (filter by table_fields if specified)
        foreach ($tableRelationships as $tableRel) {
            $relationFieldName = $tableRel['relation'] . '.' . $tableRel['field'];
            
            // If table_fields is specified, only show selected relationship fields
            if (!empty($tableFields) && !in_array($relationFieldName, $tableFields)) {
                continue;
            }
            
            $content .= "                                                <div className=\"text-sm\">\n";
            $content .= "                                                    <p className=\"text-muted-foreground mb-1\">{t('{$tableRel['label']}')}</p>\n";
            $content .= "                                                    <p className=\"font-medium\">{" . $nameLower . "." . $tableRel['relation'] . "?." . $tableRel['field'] . " || '-'}</p>\n";
            $content .= "                                                </div>\n";
        }
        
        $content .= "                                            </div>\n";
        
        return $content;
    }

    protected function getFilterSection($fields, $filterable, $relationships = [])
    {
        // Check if there are any valid filterable fields first
        $validFilterableFields = [];
        foreach ($filterable as $filterField) {
            if (!empty($filterField) && isset($fields[$filterField])) {
                $fieldData = $fields[$filterField];
                if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating')) {
                    $validFilterableFields[] = $filterField;
                }
            } elseif (!empty($filterField) && isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                $validFilterableFields[] = $filterField;
            }
        }
        
        // If no valid filterable fields, return empty string
        if (empty($validFilterableFields)) {
            return "";
        }
        
        $hasFilters = false;
        $filterContent = "                {/* Advanced Filters */}\n                {showFilters && (\n                    <CardContent className=\"p-6 bg-blue-50/30 border-b\">\n                        <div className=\"grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4\">\n";
        
        // Add filterable fields
        foreach ($validFilterableFields as $filterField) {
            if (isset($fields[$filterField]) && $fields[$filterField]['dynamic_model']) {
                // Handle dynamic model fields
                $hasFilters = true;
                $fieldData = $fields[$filterField];
                $fieldLabel = Str::title(str_replace('_', ' ', $filterField));
                $modelName = Str::plural(Str::lower($fieldData['dynamic_model']));
                
                $filterContent .= "                            <div>\n";
                $filterContent .= "                                <label className=\"block text-sm font-medium text-gray-700 mb-2\">{t('{$fieldLabel}')}</label>\n";
                $filterContent .= "                                <Select value={filters.{$filterField}} onValueChange={(value) => setFilters({...filters, {$filterField}: value})}>\n";
                $filterContent .= "                                    <SelectTrigger>\n";
                $filterContent .= "                                        <SelectValue placeholder={t('Filter by {$fieldLabel}')} />\n";
                $filterContent .= "                                    </SelectTrigger>\n";
                $filterContent .= "                                    <SelectContent>\n";
                $filterContent .= "                                        {{$modelName}?.map((item: any) => (\n";
                $filterContent .= "                                            <SelectItem key={item.id} value={item.id.toString()}>\n";
                $filterContent .= "                                                {item.{$fieldData['dynamic_field']}}\n";
                $filterContent .= "                                            </SelectItem>\n";
                $filterContent .= "                                        ))}\n";
                $filterContent .= "                                    </SelectContent>\n";
                $filterContent .= "                                </Select>\n";
                $filterContent .= "                            </div>\n";
            } elseif (isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                // Handle relationship filtering
                $hasFilters = true;
                $relationship = $relationships[$filterField];
                $fieldLabel = Str::title(str_replace('_', ' ', $filterField));
                $foreignKey = $relationship['foreign_key'];
                $pluralName = Str::plural(Str::lower($relationship['model']));
                
                // Check if this is a dependent field
                $dependentInfo = $this->getDependentFieldInfo($relationship, $relationships);
                if ($dependentInfo) {
                    $dataSource = 'filtered' . Str::studly(Str::plural(str_replace('_id', '', $foreignKey)));
                } else {
                    $dataSource = $pluralName;
                }
                
                $filterContent .= "                            <div>\n";
                $filterContent .= "                                <label className=\"block text-sm font-medium text-gray-700 mb-2\">{t('{$fieldLabel}')}</label>\n";
                $filterContent .= "                                <Select value={filters.{$foreignKey}} onValueChange={(value) => setFilters({...filters, {$foreignKey}: value})}>\n";
                $filterContent .= "                                    <SelectTrigger>\n";
                $filterContent .= "                                        <SelectValue placeholder={t('All {$fieldLabel}s')} />\n";
                $filterContent .= "                                    </SelectTrigger>\n";
                $filterContent .= "                                    <SelectContent>\n";
                $filterContent .= "                                        <SelectItem value=\"all\">{t('All {$fieldLabel}s')}</SelectItem>\n";
                $filterContent .= "                                        {{$dataSource}?.map(({$filterField}: any) => (\n";
                $filterContent .= "                                            <SelectItem key={{$filterField}.id} value={{$filterField}.id.toString()}>\n";
                $filterContent .= "                                                {{$filterField}.name}\n";
                $filterContent .= "                                            </SelectItem>\n";
                $filterContent .= "                                        ))}\n";
                $filterContent .= "                                    </SelectContent>\n";
                $filterContent .= "                                </Select>\n";
                $filterContent .= "                            </div>\n";
            } elseif (isset($fields[$filterField]) && ($fields[$filterField]['type'] === 'select' || $fields[$filterField]['type'] === 'radiobutton' || $fields[$filterField]['type'] === 'checkboxgroup' || $fields[$filterField]['type'] === 'multiselect' || $fields[$filterField]['type'] === 'rating')) {
                $hasFilters = true;
                $fieldLabel = Str::title(str_replace('_', ' ', $filterField));
                $filterContent .= "                            <div>\n";
                $filterContent .= "                                <label className=\"block text-sm font-medium text-gray-700 mb-2\">{t('{$fieldLabel}')}</label>\n";
                $filterContent .= "                                <Select value={filters.{$filterField}} onValueChange={(value) => setFilters({...filters, {$filterField}: value})}>\n";
                $filterContent .= "                                    <SelectTrigger>\n";
                $filterContent .= "                                        <SelectValue placeholder={t('Filter by {$fieldLabel}')} />\n";
                $filterContent .= "                                    </SelectTrigger>\n";
                $filterContent .= "                                    <SelectContent>\n";
                
                if ($fields[$filterField]['type'] === 'radiobutton' && !empty($fields[$filterField]['options'])) {
                    // Use index values for radiobutton
                    foreach ($fields[$filterField]['options'] as $index => $option) {
                        $filterContent .= "                                        <SelectItem value=\"{$index}\">{t('{$option}')}</SelectItem>\n";
                    }
                } elseif ($fields[$filterField]['type'] === 'checkboxgroup' && !empty($fields[$filterField]['options'])) {
                    // Use index values for checkboxgroup
                    foreach ($fields[$filterField]['options'] as $index => $option) {
                        $filterContent .= "                                        <SelectItem value=\"{$index}\">{t('{$option}')}</SelectItem>\n";
                    }
                } elseif ($fields[$filterField]['type'] === 'multiselect' && !empty($fields[$filterField]['options'])) {
                    // Use index values for multiselect
                    foreach ($fields[$filterField]['options'] as $index => $option) {
                        $filterContent .= "                                        <SelectItem value=\"{$option}\">{t('{$option}')}</SelectItem>\n";
                    }
                } elseif ($fields[$filterField]['type'] === 'select' && !empty($fields[$filterField]['options'])) {
                    // Use index values for select with custom options
                    foreach ($fields[$filterField]['options'] as $index => $option) {
                        $filterContent .= "                                        <SelectItem value=\"{$index}\">{t('{$option}')}</SelectItem>\n";
                    }
                } elseif ($fields[$filterField]['type'] === 'rating') {
                    // Rating options 1-5 stars
                    for ($i = 1; $i <= 5; $i++) {
                        $stars = str_repeat('★', $i);
                        $filterContent .= "                                        <SelectItem value=\"{$i}\">{$i} {t('Stars')} ({$stars})</SelectItem>\n";
                    }
                } else {
                    // Default Active/Inactive for boolean select
                    $filterContent .= "                                        <SelectItem value=\"1\">{t('Active')}</SelectItem>\n";
                    $filterContent .= "                                        <SelectItem value=\"0\">{t('Inactive')}</SelectItem>\n";
                }
                
                $filterContent .= "                                    </SelectContent>\n";
                $filterContent .= "                                </Select>\n";
                $filterContent .= "                            </div>\n";
            }
        }
        
        if ($hasFilters) {
            $filterContent .= "                            <div className=\"flex items-end gap-2\">\n";
            $filterContent .= "                                <Button onClick={handleFilter} size=\"sm\">{t('Apply')}</Button>\n";
            $filterContent .= "                                <Button variant=\"outline\" onClick={clearFilters} size=\"sm\">{t('Clear')}</Button>\n";
            $filterContent .= "                            </div>\n";
        }
        
        $filterContent .= "                        </div>\n                    </CardContent>\n                )}";
        
        return $hasFilters ? $filterContent : "";
    }

    protected function getFilterState($searchable, $filterable, $fields, $relationships = [])
    {
        $filterState = [];
        
        // Add searchable fields
        foreach ($searchable as $field) {
            if (!empty($field)) {
                $filterState[] = "        {$field}: urlParams.get('{$field}') || '',";
            }
        }
        
        // Only add filterable fields if filterable array is not empty
        if (!empty($filterable)) {
            foreach ($filterable as $field) {
                if (!empty($field) && isset($fields[$field])) {
                    $fieldData = $fields[$field];
                    if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating' || $fieldData['type'] === 'switch')) {
                        $filterState[] = "        {$field}: urlParams.get('{$field}') || '',";
                    }
                } elseif (!empty($field) && isset($relationships[$field]) && $relationships[$field]['type'] === 'belongsTo') {
                    $foreignKey = $relationships[$field]['foreign_key'];
                    $filterState[] = "        {$foreignKey}: urlParams.get('{$foreignKey}') || 'all',";
                }
            }
        }
        
        return implode("\n", $filterState);
    }

    protected function getClearFilterState($searchable, $filterable, $fields, $relationships = [])
    {
        $clearState = [];
        
        // Add searchable fields
        foreach ($searchable as $field) {
            if (!empty($field)) {
                $clearState[] = "            {$field}: '',";
            }
        }
        
        // Only add filterable fields if filterable array is not empty
        if (!empty($filterable)) {
            foreach ($filterable as $field) {
                if (!empty($field) && isset($fields[$field])) {
                    $fieldData = $fields[$field];
                    if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating' || $fieldData['type'] === 'switch')) {
                        $clearState[] = "            {$field}: '',";
                    }
                } elseif (!empty($field) && isset($relationships[$field]) && $relationships[$field]['type'] === 'belongsTo') {
                    $foreignKey = $relationships[$field]['foreign_key'];
                    $clearState[] = "            {$foreignKey}: 'all',";
                }
            }
        }
        
        return implode("\n", $clearState);
    }

    protected function getSearchField($searchable)
    {
        return !empty($searchable) && !empty($searchable[0]) ? $searchable[0] : 'name';
    }

    protected function getHasFiltersCheck($searchable, $filterable, $fields, $relationships = [])
    {
        $checks = [];
        
        // Add searchable fields
        foreach ($searchable as $field) {
            if (!empty($field)) {
                $checks[] = "filters.{$field}";
            }
        }
        
        // Only add filterable fields if filterable array is not empty
        if (!empty($filterable)) {
            foreach ($filterable as $field) {
                if (!empty($field) && isset($fields[$field])) {
                    $fieldData = $fields[$field];
                    if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating' || $fieldData['type'] === 'switch')) {
                        $checks[] = "filters.{$field}";
                    }
                } elseif (!empty($field) && isset($relationships[$field]) && $relationships[$field]['type'] === 'belongsTo') {
                    $foreignKey = $relationships[$field]['foreign_key'];
                    $checks[] = "(filters.{$foreignKey} !== 'all' && filters.{$foreignKey})";
                }
            }
        }
        
        return !empty($checks) ? '!!(' . implode(' || ', $checks) . ')' : 'false';
    }

    protected function getActionsColumnPermissionCheck($view, $nameKebab)
    {
        $permissions = ["edit-{$nameKebab}", "delete-{$nameKebab}"];
        
        // Add view permission if view is enabled
        if (!empty($view)) {
            array_unshift($permissions, "view-{$nameKebab}");
        }
        
        $permissionsString = "'" . implode("', '", $permissions) . "'";
        return "auth.user?.permissions?.some((p: string) => [{$permissionsString}].includes(p))";
    }

    protected function getDependentDropdownMethods($relationships, $modelName)
    {
        $methods = [];
        
        // Only generate method if this model has dependent relationships in its form
        // AND the child model is different from current model (not self-reference)
        foreach ($relationships as $relationship) {
            // Skip self-referencing relationships
            if ($relationship['model'] === $modelName) {
                continue;
            }
            
            $dependentInfo = $this->getDependentFieldInfo($relationship, $relationships);
            if ($dependentInfo) {
                // Check if both parent and child relationships exist (excluding self-references)
                $parentExists = false;
                $childExists = false;
                
                foreach ($relationships as $rel) {
                    // Skip self-references
                    if ($rel['model'] === $modelName) {
                        continue;
                    }
                    
                    if ($rel['foreign_key'] === $dependentInfo['parent_foreign_key']) {
                        $parentExists = true;
                    }
                    if ($rel['foreign_key'] === $relationship['foreign_key']) {
                        $childExists = true;
                    }
                }
                
                if ($parentExists && $childExists) {
                    $childField = $relationship['foreign_key'];
                    $childModelName = str_replace('_id', '', $childField);
                    $childModelClass = Str::studly($childModelName);
                    $parentModel = $dependentInfo['parent_name'];
                    $methodName = 'get' . Str::plural(Str::studly($childModelName)) . 'By' . Str::studly($parentModel);
                    $paramName = Str::lower($parentModel) . 'Id';
                    $permission = 'view-' . Str::kebab(Str::plural($childModelName));
                    $variableName = Str::plural(Str::lower($childModelName));
                    
                    // Use the correct foreign key that links child to parent
                    // For subcategory -> category relationship, we need 'category_id' in subcategories table
                    $childToParentForeignKey = $dependentInfo['parent_name'] . '_id';
                    
                    $methods[] = "    public function {$methodName}(\${$paramName})\n    {\n        if(Auth::user()->can('{$permission}')){\n            \${$variableName} = {$childModelClass}::where('{$childToParentForeignKey}', \${$paramName})\n                ->where('created_by', creatorId())\n                ->select('id', 'name')\n                ->get();\n\n            return response()->json(\${$variableName});\n        }\n        else{\n            return response()->json([], 403);\n        }\n    }";
                    break; // Only one method per controller
                }
            }
        }
        
        return implode("\n\n", $methods);
    }

    protected function getDependentDropdownState($relationships, $modelName)
    {
        $states = [];
        
        // Find dependent relationships in current model
        foreach ($relationships as $relationship) {
            $dependentInfo = $this->getDependentFieldInfo($relationship, $relationships);
            if ($dependentInfo) {
                $childField = $relationship['foreign_key'];
                $childModelName = str_replace('_id', '', $childField);
                $stateName = Str::plural(Str::lower($childModelName));
                
                // Use the actual prop name from relationship model
                $propName = Str::plural(Str::lower($relationship['model']));
                
                // Use different name to avoid conflict with props
                $localStateName = 'filtered' . Str::studly($stateName);
                $localSetterName = 'set' . Str::studly($localStateName);
                
                $states[] = "    const [{$localStateName}, {$localSetterName}] = useState({$propName} || []);";
            }
        }
        
        return implode("\n", $states);
    }

    protected function getDependentDropdownEffects($relationships, $modelName, $packageData = null)
    {
        $effects = [];
        
        // Only generate effects for forms (create/edit), not for index pages
        // This method should only be used in create.tsx and edit.tsx templates
        foreach ($relationships as $relationship) {
            // Skip self-referencing relationships
            if ($relationship['model'] === $modelName) {
                continue;
            }
            
            $dependentInfo = $this->getDependentFieldInfo($relationship, $relationships);
            if ($dependentInfo) {
                // Check if both parent and child relationships exist (excluding self-references)
                $parentExists = false;
                $childExists = false;
                
                foreach ($relationships as $rel) {
                    // Skip self-references
                    if ($rel['model'] === $modelName) {
                        continue;
                    }
                    
                    if ($rel['foreign_key'] === $dependentInfo['parent_foreign_key']) {
                        $parentExists = true;
                    }
                    if ($rel['foreign_key'] === $relationship['foreign_key']) {
                        $childExists = true;
                    }
                }
                
                if ($parentExists && $childExists) {
                    $parentField = $dependentInfo['parent_foreign_key'];
                    $childField = $relationship['foreign_key'];
                    $childModelName = str_replace('_id', '', $childField);
                    $stateName = Str::plural(Str::lower($childModelName));
                    
                    // Use the actual prop name from relationship model
                    $propName = Str::plural(Str::lower($relationship['model']));
                    
                    $localStateName = 'filtered' . Str::studly($stateName);
                    $setterName = 'set' . Str::studly($localStateName);
                    $parentRoute = Str::kebab(Str::plural($dependentInfo['parent_name']));
                    $childRoute = Str::kebab(Str::plural($childModelName));
                    
                    // Use package route prefix if in package mode
                    $routePrefix = $packageData && $packageData['is_package'] 
                        ? $packageData['package_lower'] . '.'
                        : '';
                    $routeName = "{$routePrefix}{$parentRoute}.{$childRoute}";
                    $effects[] = "    useEffect(() => {\n        if (data.{$parentField}) {\n            axios.get(route('{$routeName}', data.{$parentField}))\n                .then(response => {\n                    {$setterName}(response.data);\n                })\n                .catch(() => {\n                    {$setterName}([]);\n                });\n        } else {\n            {$setterName}({$propName} || []);\n            setData('{$childField}', '');\n        }\n    }, [data.{$parentField}]);";
                    break; // Only one effect per form
                }
            }
        }
        
        return implode("\n\n", $effects);
    }

    protected function getDependentFilterEffects($relationships, $filterable, $packageData = null)
    {
        $effects = [];
        
        // Check if we have dependent relationships in filterable fields
        foreach ($filterable as $filterField) {
            if (isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                $dependentInfo = $this->getDependentFieldInfo($relationships[$filterField], $relationships);
                if ($dependentInfo) {
                    // Find parent relationship in filterable
                    $parentRelationshipName = null;
                    foreach ($relationships as $relName => $rel) {
                        if ($rel['foreign_key'] === $dependentInfo['parent_foreign_key']) {
                            $parentRelationshipName = $relName;
                            break;
                        }
                    }
                    
                    if ($parentRelationshipName && in_array($parentRelationshipName, $filterable)) {
                        $parentForeignKey = $dependentInfo['parent_foreign_key'];
                        $childForeignKey = $relationships[$filterField]['foreign_key'];
                        $childModelName = str_replace('_id', '', $childForeignKey);
                        $pluralName = Str::plural(Str::lower($relationships[$filterField]['model']));
                        $stateName = 'filtered' . Str::studly(Str::plural($childModelName));
                        $setterName = 'set' . Str::studly($stateName);
                        $parentRoute = Str::kebab(Str::plural($dependentInfo['parent_name']));
                        $childRoute = Str::kebab(Str::plural($childModelName));
                        
                        // Use package route prefix if in package mode
                        $routePrefix = $packageData && $packageData['is_package'] 
                            ? $packageData['package_lower'] . '.'
                            : '';
                        $routeName = "{$routePrefix}{$parentRoute}.{$childRoute}";
                        
                        // Only generate useEffect, not useState (state is already generated by FILTER_DEPENDENT_DROPDOWN_STATE)
                        $effects[] = "    // Handle dependent dropdown for {$filterField} filters
    useEffect(() => {
        if (filters.{$parentForeignKey} && filters.{$parentForeignKey} !== 'all') {
            // Fetch {$filterField}s for selected {$parentRelationshipName}
            fetch(route('{$routeName}', filters.{$parentForeignKey}))
                .then(response => response.json())
                .then(data => {
                    {$setterName}(data);
                    // Clear {$filterField} if it doesn't belong to selected {$parentRelationshipName}
                    if (filters.{$childForeignKey} && filters.{$childForeignKey} !== 'all') {
                        const {$filterField}Exists = data.find((sub: any) => sub.id.toString() === filters.{$childForeignKey});
                        if (!{$filterField}Exists) {
                            setFilters(prev => ({ ...prev, {$childForeignKey}: 'all' }));
                        }
                    }
                })
                .catch(() => {$setterName}([]));
        } else {
            {$setterName}({$pluralName} || []);
            setFilters(prev => ({ ...prev, {$childForeignKey}: 'all' }));
        }
    }, [filters.{$parentForeignKey}]);";
                        break;
                    }
                }
            }
        }
        
        return implode("\n\n", $effects);
    }

    protected function getDependentRelationships($relationships)
    {
        $dependentRelationships = [];
        
        // Auto-detect dependent relationships based on naming patterns
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                // Check if this relationship depends on another
                $parentRelationship = $this->findParentRelationship($relationship, $relationships);
                
                if ($parentRelationship) {
                    $dependentRelationships[] = [
                        'child_name' => $relationship['name'],
                        'child_foreign_key' => $relationship['foreign_key'],
                        'parent_name' => $parentRelationship['name'],
                        'parent_model' => $parentRelationship['model'],
                        'foreign_key' => $parentRelationship['foreign_key']
                    ];
                }
            }
        }
        
        return $dependentRelationships;
    }

    protected function findParentRelationship($childRelationship, $allRelationships)
    {
        // Auto-detect parent based on relationship order and naming
        // If there are multiple relationships, assume the first one is parent of subsequent ones
        
        $relationshipNames = array_keys($allRelationships);
        $currentIndex = array_search($childRelationship['name'], $relationshipNames);
        
        // If this is not the first relationship, check if previous one could be parent
        if ($currentIndex > 0) {
            $previousRelationshipName = $relationshipNames[$currentIndex - 1];
            $previousRelationship = $allRelationships[$previousRelationshipName];
            
            // Check if the child model name contains or relates to parent model
            if ($this->isLikelyParentChild($previousRelationship, $childRelationship)) {
                return $previousRelationship;
            }
        }
        
        return null;
    }

    protected function isLikelyParentChild($parentRelationship, $childRelationship)
    {
        $parentModel = Str::lower($parentRelationship['model']);
        $childModel = Str::lower($childRelationship['model']);
        $childName = Str::lower($childRelationship['name']);
        $parentName = Str::lower($parentRelationship['name']);
        
        // Check if child name contains parent name (e.g., subcategory contains category)
        if (str_contains($childName, $parentName)) {
            return true;
        }
        
        // Check if child model contains parent model name
        if (str_contains($childModel, $parentModel)) {
            return true;
        }
        
        // If relationships are defined in order, assume dependency
        // This allows any parent-child relationship without hardcoded patterns
        return true;
    }

    protected function getDependentFieldInfo($relationship, $allRelationships)
    {
        // Find parent relationship dynamically
        $parentRelationship = $this->findParentRelationship($relationship, $allRelationships);
        
        if ($parentRelationship) {
            return [
                'parent_name' => $parentRelationship['name'],
                'parent_foreign_key' => $parentRelationship['foreign_key'],
                'child_model' => $relationship['model']
            ];
        }
        
        return null;
    }

    protected function setCurrentModelName($modelName)
    {
        $this->currentModelName = $modelName;
        return $this;
    }

    protected function setCurrentFields($fields)
    {
        $this->currentFields = $fields;
        return $this;
    }

    protected function setCurrentRelationships($relationships)
    {
        $this->currentRelationships = $relationships;
        return $this;
    }

    protected function getSeederDynamicModelImports($fields)
    {
        $imports = [];
        $addedModels = [];
        
        foreach ($fields as $field) {
            if (!empty($field['dynamic_model'])) {
                $modelName = $field['dynamic_model'];
                if (!in_array($modelName, $addedModels)) {
                    $imports[] = "use App\\Models\\{$modelName};";
                    $addedModels[] = $modelName;
                }
            }
        }
        
        return implode("\n", $imports);
    }

    protected function getFilterButtonWithCounter($filterable, $fields, $relationships = [])
    {
        // Check if there are any valid filterable fields
        $validFilterableFields = [];
        $filterFields = [];
        
        foreach ($filterable as $field) {
            if (!empty($field) && isset($fields[$field])) {
                $fieldData = $fields[$field];
                if ($fieldData['dynamic_model'] || ($fieldData['type'] === 'select' || $fieldData['type'] === 'radiobutton' || $fieldData['type'] === 'checkboxgroup' || $fieldData['type'] === 'multiselect' || $fieldData['type'] === 'rating' || $fieldData['type'] === 'switch')) {
                    $validFilterableFields[] = $field;
                    $filterFields[] = "filters.{$field}";
                }
            } elseif (!empty($field) && isset($relationships[$field]) && $relationships[$field]['type'] === 'belongsTo') {
                $validFilterableFields[] = $field;
                $foreignKey = $relationships[$field]['foreign_key'];
                $filterFields[] = "filters.{$foreignKey} !== 'all' ? filters.{$foreignKey} : ''";
            }
        }
        
        // If no valid filterable fields, return empty string
        if (empty($validFilterableFields)) {
            return '';
        }
        
        if (!empty($filterFields)) {
            $filterFieldsString = implode(', ', $filterFields);
            return "<div className=\"relative\">\n                                <FilterButton\n                                    showFilters={showFilters}\n                                    onToggle={() => setShowFilters(!showFilters)}\n                                />\n                                {(() => {\n                                    const activeFilters = [{$filterFieldsString}].filter(f => f !== '' && f !== null && f !== undefined).length;\n                                    return activeFilters > 0 && (\n                                        <span className=\"absolute -top-2 -right-2 bg-primary text-primary-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium\">\n                                            {activeFilters}\n                                        </span>\n                                    );\n                                })()}\n                            </div>";
        }
        
        return ''; // No filter button if no filters available
    }

    protected function getFilterButton($filterable, $fields)
    {
        // Keep old method for backward compatibility
        return $this->getFilterButtonWithCounter($filterable, $fields);
    }

    protected function getViewImport($view)
    {
        if (empty($view)) {
            return '';
        }
        
        if ($view === 'modal') {
            return "import View from './View';";
        }
        
        return '';
    }

    protected function getViewState($view)
    {
        // Only generate state for modal view, not for page view
        return (!empty($view) && $view === 'modal') ? "    const [viewingItem, setViewingItem] = useState<{{NAME}} | null>(null);" : '';
    }

    protected function getViewButton($view, $context)
    {
        if (empty($view)) {
            return '';
        }

        $className = $context === 'table' ? 'h-8 w-8 p-0 text-green-600 hover:text-green-700' : 'h-9 w-9 p-0 text-green-600 hover:text-green-700 hover:bg-green-50';
        $delayDuration = $context === 'table' ? '{0}' : '{300}';
        $itemVariable = $context === 'table' ? '{{NAME_LOWER}}' : '{{NAME_LOWER}}';

        // Different onClick based on view type
        if ($view === 'page') {
            $onClick = "router.get(route('{{ROUTE_PREFIX}}.show', $itemVariable.id))";
        } else {
            $onClick = "setViewingItem($itemVariable)";
        }

        return "                        {auth.user?.permissions?.includes('view-{{NAME_KEBAB}}') && (
                            <Tooltip delayDuration=$delayDuration>
                                <TooltipTrigger asChild>
                                    <Button variant=\"ghost\" size=\"sm\" onClick={() => $onClick} className=\"{$className}\">
                                        <Eye className=\"h-4 w-4\" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}";
    }

    protected function getViewDialog($view)
    {
        // Only generate dialog for modal view, not for page view
        if (empty($view) || $view !== 'modal') {
            return '';
        }

        return "            <Dialog open={!!viewingItem} onOpenChange={() => setViewingItem(null)}>
                {viewingItem && <View {{NAME_LOWER}}={viewingItem} />}
            </Dialog>";
    }

    protected function getShowMethod($view)
    {
        if (empty($view) || $view !== 'page') {
            return '';
        }

        return "    public function show({{NAME}} \${{NAME_LOWER}})
    {
        if(Auth::user()->can('view-{{NAME_KEBAB}}')){
            return Inertia::render('{{INERTIA_PATH}}/Show', [
                '{{NAME_LOWER}}' => \${{NAME_LOWER}}
            ]);
        }
        else{
            return redirect()->route('{{ROUTE_PREFIX}}.index')->with('error', __('Permission denied'));
        }
    }";
    }

    protected function addSeederToDatabase($data)
    {
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');
        $content = File::get($databaseSeederPath);
        
        $newSeederCall = "        (new {$data['name']}Seeder())->run(\$userId);";
        
        $content = str_replace(
            "        (new DemoItemSeeder())->run(\$userId);",
            "        (new DemoItemSeeder())->run(\$userId);\n{$newSeederCall}",
            $content
        );
        
        File::put($databaseSeederPath, $content);
    }

    // Package-specific generation methods
    protected function generatePackageMigration($data)
    {
        $stub = File::get($this->stubPath . '/backend/package-migration.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $filename = date('Y_m_d_His') . '_create_' . $data['table_name'] . '_table.php';
        $packagePath = base_path("packages/local/{$data['package']}/src/Database/Migrations");
        
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/' . $filename, $content);
    }

    protected function generatePackageModel($data)
    {
        $stub = File::get($this->stubPath . '/backend/package-model.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Models");
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/' . $data['name'] . '.php', $content);
    }

    protected function generatePackageController($data)
    {
        // Use system setup controller stub for system setup entities
        $stubFile = $data['is_system_setup'] 
            ? '/backend/package-system-setup-controller.stub'
            : '/backend/package-controller.stub';
            
        $stub = File::get($this->stubPath . $stubFile);
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Http/Controllers");
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/' . $data['name'] . 'Controller.php', $content);
    }

    protected function generatePackageSeeder($data)
    {
        $stub = File::get($this->stubPath . '/backend/package-seeder.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Database/Seeders");
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/Demo' . $data['name'] . 'Seeder.php', $content);
    }

    protected function generatePackageIndexPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/package-index.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/Index.tsx', $content);
    }

    protected function generatePackageCreatePage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/create.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        File::put($packagePath . '/Create.tsx', $content);
    }

    protected function generatePackageEditPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/edit.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        File::put($packagePath . '/Edit.tsx', $content);
    }

    protected function generatePackageTypes($data)
    {
        $stub = File::get($this->stubPath . '/frontend/types.ts.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        File::put($packagePath . '/types.ts', $content);
    }

    protected function generatePackageViewPage($data)
    {
        $stub = File::get($this->stubPath . '/frontend/view.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        File::put($packagePath . '/View.tsx', $content);
    }

    protected function generatePackageViewPageComponent($data)
    {
        $stub = File::get($this->stubPath . '/frontend/view-page.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/{$data['name_plural']}");
        File::put($packagePath . '/Show.tsx', $content);
    }

    protected function generatePackagePermissions($data)
    {
        $permissionSeederPath = base_path("packages/local/{$data['package']}/src/Database/Seeders/PermissionTableSeeder.php");
        
        if (!File::exists($permissionSeederPath)) {
            // Create permission seeder if it doesn't exist
            $stub = File::get(base_path('stubs/react-package-stubs/seeders/PermissionTableSeeder.stub'));
            $content = str_replace('{{PACKAGE}}', $data['package'], $stub);
            File::put($permissionSeederPath, $content);
        }
        
        $content = File::get($permissionSeederPath);
        
        $newPermissions = "            // {$data['name']} management
            ['name' => 'manage-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage {$data['name_plural']}'],
            ['name' => 'manage-any-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage All {$data['name_plural']}'],
            ['name' => 'manage-own-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Manage Own {$data['name_plural']}'],
            ['name' => 'view-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'View {$data['name_plural']}'],
            ['name' => 'create-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Create {$data['name_plural']}'],
            ['name' => 'edit-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Edit {$data['name_plural']}'],
            ['name' => 'delete-{$data['name_kebab']}', 'module' => '{$data['name_kebab']}', 'label' => 'Delete {$data['name_plural']}'],";
        
        // Add permissions to the permissions array
        if (str_contains($content, '// Add more permissions here')) {
            $content = str_replace(
                "            // Add more permissions here",
                $newPermissions . "\n            // Add more permissions here",
                $content
            );
        } else {
            // Fallback: add before the closing bracket
            $content = str_replace(
                "        ];",
                "\n{$newPermissions}\n        ];",
                $content
            );
        }
        
        File::put($permissionSeederPath, $content);
    }

    protected function generatePackageMenuItem($data)
    {
        $menuPath = base_path("packages/local/{$data['package']}/src/Resources/js/menus/company-menu.ts");
        
        if (!File::exists($menuPath)) {
            // Create menu file if it doesn't exist
            $menuDir = dirname($menuPath);
            if (!File::exists($menuDir)) {
                File::makeDirectory($menuDir, 0755, true);
            }
            
            $stub = File::get(base_path('stubs/react-package-stubs/menus/company-menu.stub'));
            $content = str_replace(['{{PACKAGE}}', '{{PACKAGE_LOWER}}'], [$data['package'], $data['package_lower']], $stub);
            File::put($menuPath, $content);
        }
        
        $content = File::get($menuPath);
        
        // Add icon import if not already present
        $iconName = $data['icon'];
        if (!preg_match('/\b' . preg_quote($iconName, '/') . '\b/', $content)) {
            $pattern = '/import\s*{([^}]+)}\s*from\s*[\'"]lucide-react[\'"];/';
            if (preg_match($pattern, $content, $matches)) {
                $currentIcons = $matches[1];
                $newImport = "import { {$currentIcons}, {$iconName} } from 'lucide-react';";
                $content = preg_replace($pattern, $newImport, $content);
            }
        }
        
        if ($data['is_system_setup']) {
            // For system setup, add "System Setup" menu item only once
            if (!str_contains($content, "title: t('System Setup')")) {
                // Use the first system setup entity's route for the System Setup menu
                $systemSetupMenuItem = "            {
                title: t('System Setup'),
                href: route('{$data['route_prefix']}.index'),
                permission: 'manage-{$data['name_kebab']}',
            },";
                
                // Add to children array
                if (str_contains($content, 'children: [')) {
                    $content = str_replace(
                        "        children: [",
                        "        children: [\n{$systemSetupMenuItem}",
                        $content
                    );
                }
            }
            // Don't add individual menu items for system setup entities
        } else {
            // For regular CRUD, add individual menu item
            $newMenuItem = "            {
                title: t('{$data['name_plural_display']}'),
                href: route('{$data['route_prefix']}.index'),
                permission: 'manage-{$data['name_kebab']}',
            },";
            
            // Add to children array
            if (str_contains($content, 'children: [')) {
                $content = str_replace(
                    "        children: [",
                    "        children: [\n{$newMenuItem}",
                    $content
                );
            }
        }
        
        File::put($menuPath, $content);
    }

    protected function generatePackageRoutes($data)
    {
        $routesPath = base_path("packages/local/{$data['package']}/src/Routes/web.php");
        $content = File::get($routesPath);
        
        $showRoute = !empty($data['view']) && $data['view'] === 'page' ? "\n        Route::get('/{{$data['name_lower']}}', [{$data['name']}Controller::class, 'show'])->name('show');" : '';
        $newRoute = "    Route::prefix('{$data['package_kebab']}/{$data['name_kebab']}')->name('{$data['route_prefix']}.')->group(function () {
        Route::get('/', [{$data['name']}Controller::class, 'index'])->name('index');
        Route::post('/', [{$data['name']}Controller::class, 'store'])->name('store');
        Route::get('/{{$data['name_lower']}}/edit', [{$data['name']}Controller::class, 'edit'])->name('edit');
        Route::put('/{{$data['name_lower']}}', [{$data['name']}Controller::class, 'update'])->name('update');
        Route::delete('/{{$data['name_lower']}}', [{$data['name']}Controller::class, 'destroy'])->name('destroy');{$showRoute}
    });";
        
        // Add controller import
        $controllerImport = "use Zerp\\{$data['package']}\\Http\\Controllers\\{$data['name']}Controller;";
        
        // Add import at the top
        if (!str_contains($content, $controllerImport)) {
            $content = str_replace(
                "<?php",
                "<?php\n\n{$controllerImport}",
                $content
            );
        }
        
        // Handle dependent dropdown routes
        $dependentRelationships = $this->getDependentRelationships($data['relationships']);
        $hasMethod = !empty($this->getDependentDropdownMethods($data['relationships'], $data['name']));
        $dependentRoutes = '';
        
        if ($hasMethod) {
            $routesToAdd = [];
            foreach ($dependentRelationships as $dependent) {
                $parentRoute = Str::kebab(Str::plural($dependent['parent_model']));
                $childRoute = Str::kebab(Str::plural($dependent['child_name']));
                $methodName = 'get' . Str::plural(Str::studly($dependent['child_name'])) . 'By' . Str::studly($dependent['parent_name']);
                
                $routeName = "{$data['package_lower']}.{$parentRoute}.{$childRoute}";
                $dependentRoute = "    Route::get('{$data['package_kebab']}/{$parentRoute}/{{$dependent['parent_name']}}/{$childRoute}', [{$data['name']}Controller::class, '{$methodName}'])->name('{$routeName}');";
                
                // Check if route name already exists
                if (!str_contains($content, "->name('{$routeName}')")) {
                    $routesToAdd[] = $dependentRoute;
                }
            }
            
            if (!empty($routesToAdd)) {
                $dependentRoutes = "\n\n    // Dependent dropdown routes\n" . implode("\n", $routesToAdd);
            }
        }
        
        // Add route before the last closing bracket
        if (str_contains($content, '// Routes will be added here by generator')) {
            $content = str_replace(
                "    // Routes will be added here by generator",
                "    // Routes will be added here by generator\n\n{$newRoute}{$dependentRoutes}",
                $content
            );
        } else {
            // Find the last closing bracket and add before it
            $lastBracketPos = strrpos($content, '});');
            if ($lastBracketPos !== false) {
                $content = substr_replace($content, "\n{$newRoute}{$dependentRoutes}\n", $lastBracketPos, 0);
            }
        }
        
        File::put($routesPath, $content);
    }

    protected function generatePackageDependentDropdownRoutes($data)
    {
        // Skip - dependent dropdown routes are handled by generatePackageRoutes to prevent duplicates
    }

    protected function addSeederToPackageDatabase($data)
    {
        $databaseSeederPath = base_path("packages/local/{$data['package']}/src/Database/Seeders/{$data['package']}DatabaseSeeder.php");
        
        if (File::exists($databaseSeederPath)) {
            $content = File::get($databaseSeederPath);
            
            $newSeederCall = "            (new Demo{$data['name']}Seeder())->run(\$userId);";
            
            // Find the last seeder call inside the if condition and add after it
            $pattern = '/(.*\$userId = User::where\(\'email\', \'company@example\.com\'\)->first\(\)->id;\s*)(.*?)(\s*}\s*}\s*}\s*$)/s';
            
            if (preg_match($pattern, $content, $matches)) {
                $beforeSeeders = $matches[1];
                $existingSeeders = $matches[2];
                $afterSeeders = $matches[3];
                
                // Add the new seeder call
                $content = $beforeSeeders . $existingSeeders . "\n" . $newSeederCall . $afterSeeders;
            }
            
            File::put($databaseSeederPath, $content);
        }
    }

    protected function getSystemSetupSelectFields($fields)
    {
        $selectFields = [];
        foreach ($fields as $field) {
            $selectFields[] = "'{$field['name']}'";
        }
        
        // Add relationship foreign keys
        foreach ($this->currentRelationships ?? [] as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $selectFields[] = "'{$relationship['foreign_key']}'";
            }
        }
        
        return implode(', ', $selectFields);
    }

    protected function generatePackageSystemSetupPages($data)
    {
        // Only generate system setup pages for system setup entities
        if (!$data['is_system_setup']) {
            return;
        }
        
        $this->generatePackageSystemSetupIndex($data);
        $this->generatePackageSystemSetupCreate($data);
        $this->generatePackageSystemSetupEdit($data);
        $this->generatePackageSystemSetupTypes($data);
        $this->generatePackageSystemSetupSidebar($data);
    }

    protected function generatePackageSystemSetupIndex($data)
    {
        $stub = File::get($this->stubPath . '/frontend/package-system-setup-index.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/SystemSetup/{$data['name_plural']}");
        if (!File::exists($packagePath)) {
            File::makeDirectory($packagePath, 0755, true);
        }
        
        File::put($packagePath . '/Index.tsx', $content);
    }

    protected function generatePackageSystemSetupCreate($data)
    {
        $stub = File::get($this->stubPath . '/frontend/package-system-setup-create.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/SystemSetup/{$data['name_plural']}");
        File::put($packagePath . '/Create.tsx', $content);
    }

    protected function generatePackageSystemSetupEdit($data)
    {
        $stub = File::get($this->stubPath . '/frontend/package-system-setup-edit.tsx.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/SystemSetup/{$data['name_plural']}");
        File::put($packagePath . '/Edit.tsx', $content);
    }

    protected function generatePackageSystemSetupTypes($data)
    {
        $stub = File::get($this->stubPath . '/frontend/package-system-setup-types.ts.stub');
        $content = $this->replacePlaceholders($stub, $data);
        
        $packagePath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/SystemSetup/{$data['name_plural']}");
        File::put($packagePath . '/types.ts', $content);
    }

    protected function generatePackageSystemSetupSidebar($data)
    {
        // Only generate sidebar for system setup entities
        if (!$data['is_system_setup']) {
            return;
        }
        
        $sidebarPath = base_path("packages/local/{$data['package']}/src/Resources/js/Pages/SystemSetup/SystemSetupSidebar.tsx");
        
        if (!File::exists($sidebarPath)) {
            // Create new sidebar
            $stub = File::get($this->stubPath . '/frontend/package-system-setup-sidebar.tsx.stub');
            $content = $this->replacePlaceholders($stub, $data);
            File::put($sidebarPath, $content);
        } else {
            // Update existing sidebar to add new item
            $content = File::get($sidebarPath);
            
            $newSidebarItem = "        {
            key: '{$data['name_kebab']}',
            label: t('{$data['name_plural_display']}'),
            icon: {$data['system_setup_icon']},
            route: '{$data['route_prefix']}.index',
            permission: 'manage-{$data['name_kebab']}'
        },";
            
            // Add icon import if not present
            $iconName = $data['system_setup_icon'];
            if (!preg_match('/\b' . preg_quote($iconName, '/') . '\b/', $content)) {
                $pattern = '/import\s*{([^}]+)}\s*from\s*["\']lucide-react["\'];/';
                if (preg_match($pattern, $content, $matches)) {
                    $currentIcons = $matches[1];
                    $newImport = "import { {$currentIcons}, {$iconName} } from \"lucide-react\";"; 
                    $content = preg_replace($pattern, $newImport, $content);
                }
            }
            
            // Add to sidebarItems array at the end
            if (str_contains($content, 'const sidebarItems: SidebarItem[] = [')) {
                // Find the closing bracket of the array and add before it
                $pattern = '/(const sidebarItems: SidebarItem\[\] = \[.*?)(\s*\];)/s';
                if (preg_match($pattern, $content, $matches)) {
                    $beforeClosing = $matches[1];
                    $closing = $matches[2];
                    
                    // Add comma if there are existing items
                    $needsComma = !str_contains($beforeClosing, '] = [');
                    $comma = $needsComma ? ',' : '';
                    
                    $content = str_replace(
                        $matches[0],
                        $beforeClosing . $comma . "\n{$newSidebarItem}" . $closing,
                        $content
                    );
                }
            }
            
            File::put($sidebarPath, $content);
        }
    }

    protected function getFilterDependentDropdownState($relationships, $filterable)
    {
        $states = [];
        $addedStates = []; // Track added states to avoid duplicates
        
        // Check if we have dependent relationships in filterable fields
        foreach ($filterable as $filterField) {
            if (isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                $dependentInfo = $this->getDependentFieldInfo($relationships[$filterField], $relationships);
                if ($dependentInfo) {
                    $childForeignKey = $relationships[$filterField]['foreign_key'];
                    $childModelName = str_replace('_id', '', $childForeignKey);
                    $stateName = 'filtered' . Str::studly(Str::plural($childModelName));
                    $setterName = 'set' . Str::studly($stateName);
                    $pluralName = Str::plural(Str::lower($relationships[$filterField]['model']));
                    
                    // Only add if not already added
                    if (!in_array($stateName, $addedStates)) {
                        $states[] = "    const [{$stateName}, {$setterName}] = useState({$pluralName} || []);";
                        $addedStates[] = $stateName;
                    }
                }
            }
        }
        
        return implode("\n", $states);
    }

    protected function getRelationshipImportsForTypes($relationships, $filterable)
    {
        $interfaces = [];
        
        // Add interfaces for relationships used in filtering
        foreach ($filterable as $filterField) {
            if (isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                $modelName = $relationships[$filterField]['model'];
                $interfaces[] = "export interface {$modelName} {\n    id: number;\n    name: string;\n}";
            }
        }
        
        // Add interfaces for all relationships (for props)
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $modelName = $relationship['model'];
                $interface = "export interface {$modelName} {\n    id: number;\n    name: string;\n}";
                if (!in_array($interface, $interfaces)) {
                    $interfaces[] = $interface;
                }
            }
        }
        
        return implode("\n\n", $interfaces);
    }

    protected function getUseEffectImport($relationships, $filterable)
    {
        // Check if we have dependent relationships in filterable fields
        foreach ($filterable as $filterField) {
            if (isset($relationships[$filterField]) && $relationships[$filterField]['type'] === 'belongsTo') {
                $dependentInfo = $this->getDependentFieldInfo($relationships[$filterField], $relationships);
                if ($dependentInfo) {
                    return ', useEffect';
                }
            }
        }
        return '';
    }

    protected function updateExistingModelsWithReverseRelationships($data)
    {
        // Add reverse relationships to existing models
        foreach ($data['relationships'] as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $parentModelName = $relationship['model'];
                $parentModelPath = app_path('Models/' . $parentModelName . '.php');
                
                // Check if parent model exists
                if (File::exists($parentModelPath)) {
                    $content = File::get($parentModelPath);
                    
                    // Check if reverse relationship already exists
                    $reverseMethodName = Str::plural(Str::lower($data['name']));
                    if (!str_contains($content, "function {$reverseMethodName}()")) {
                        // Add import for current model if not exists
                        $currentModelImport = "use App\\Models\\{$data['name']};";
                        if (!str_contains($content, $currentModelImport)) {
                            $content = str_replace(
                                "use Illuminate\\Database\\Eloquent\\Model;",
                                "use Illuminate\\Database\\Eloquent\\Model;\n{$currentModelImport}",
                                $content
                            );
                        }
                        
                        // Add reverse relationship method before closing brace
                        $reverseMethod = "\n    public function {$reverseMethodName}()\n    {\n        return \$this->hasMany({$data['name']}::class);\n    }";
                        
                        $content = str_replace(
                            "\n}",
                            "{$reverseMethod}\n}",
                            $content
                        );
                        
                        File::put($parentModelPath, $content);
                    }
                }
            }
        }
    }
}
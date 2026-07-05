<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeCrudlyCommand extends Command
{
    protected $signature = 'make:crudly {name} {--package=} {--fields=} {--searchable=} {--filterable=} {--icon=} {--relationships=} {--table-relationships=} {--table-fields=} {--view=} {--system-setup} {--system-setup-icon=}';

    protected $description = 'Generate a complete CRUD module with custom validation';

    protected $help = '
Field format: name:type:validation
Relationship format: relation:type:Model:foreign_key
Table relationships: relation.field
Example: --fields="title:textbox:required,content:textarea:required" --relationships="user:belongsTo:User:user_id,category:belongsTo:Category:category_id" --table-relationships="user.name"';


    public function handle()
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $fields = $this->option('fields') ?? 'name:textbox,description:textarea,is_active:select';
        $searchable = $this->option('searchable') ?? 'name';
        $filterable = $this->option('filterable') ?? 'is_active';
        $icon = $this->option('icon') ?? 'Tag';
        $relationships = $this->option('relationships') ?? '';
        $tableRelationships = $this->option('table-relationships') ?? '';
        $tableFields = $this->option('table-fields') ?? '';
        $view = $this->option('view') ?? '';
        $systemSetup = $this->option('system-setup');
        $systemSetupIcon = $this->option('system-setup-icon') ?? 'Settings';

        // Validate package if provided
        if ($package) {
            $packagePath = base_path("packages/local/{$package}");
            if (!is_dir($packagePath)) {
                $availablePackages = [];
                $packagesDir = base_path('packages/local');
                if (is_dir($packagesDir)) {
                    $availablePackages = array_filter(scandir($packagesDir), function($item) use ($packagesDir) {
                        return $item !== '.' && $item !== '..' && is_dir($packagesDir . '/' . $item);
                    });
                }
                
                $this->error("Package '{$package}' not found!");
                if (!empty($availablePackages)) {
                    $this->line("Available packages: " . implode(', ', $availablePackages));
                }
                return 1;
            }
        }

        // Include the generator class
        require_once base_path('stubs/crudly/generators/CrudlyGenerator.php');
        
        $generator = new \App\Generators\CrudlyGenerator();
        
        try {
            $generator->generate($name, [
                'package' => $package,
                'fields' => $fields,
                'searchable' => $searchable,
                'filterable' => $filterable,
                'icon' => $icon,
                'relationships' => $relationships,
                'table_relationships' => $tableRelationships,
                'table_fields' => $tableFields,
                'view' => $view,
                'system_setup' => $systemSetup,
                'system_setup_icon' => $systemSetupIcon
            ]);

            if ($package) {
                $this->info("CRUD module '{$name}' generated successfully in package '{$package}'!");
                $this->line("Don't forget to:");
                $this->line("1. Run: php artisan migrate");
            } else {
                $this->info("CRUD module '{$name}' generated successfully!");
                $this->line("Next steps:");
                $this->line("1. Run: php artisan migrate:fresh --seed");
                $this->line("2. Permissions, menu, and routes have been automatically added!");
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to generate CRUD: " . $e->getMessage());
        }
    }
}
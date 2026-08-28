<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class GenerateModelPermissions extends Command
{
    protected $signature = 'permissions:generate-models';

    protected $description = 'Generate view/create/edit/delete permissions for all models + view for widgets';

    public function handle(): void
    {
        $modelPath = app_path('Models');
        $widgetPath = app_path('Filament/Widgets');

        $modelFiles = File::exists($modelPath) ? File::allFiles($modelPath) : [];
        $widgetFiles = File::exists($widgetPath) ? File::allFiles($widgetPath) : [];

        $modelActions = ['view', 'create', 'edit', 'delete'];
        $count = 0;

        // Generate model permissions
        foreach ($modelFiles as $file) {
            $className = $file->getFilenameWithoutExtension();
            $kebabName = Str::kebab($className);

            foreach ($modelActions as $action) {
                $permissionName = "{$action} {$kebabName}";

                if (! Permission::where('name', $permissionName)->exists()) {
                    Permission::create(['name' => $permissionName]);
                    $this->info("Created: {$permissionName}");
                    $count++;
                }
            }
        }

        // Generate widget permissions (only "view" action)
        foreach ($widgetFiles as $file) {
            $className = $file->getFilenameWithoutExtension();
            $kebabName = Str::kebab($className);

            $permissionName = "view {$kebabName}";

            if (! Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName]);
                $this->info("Created: {$permissionName}");
                $count++;
            }
        }

        $this->info("✅ Done. {$count} permissions created.");
    }
}

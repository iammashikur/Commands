<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Skeleton extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skeleton';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {

        $this->module = Str::studly($this->ask('Name this skeleton'));
        $this->path   = Str::studly($this->ask('Input Path'));
        $this->nameSpace = $this->path;

        if (!str_ends_with($this->path, '/')) {
            $this->path   = $this->path.'/';
        }

        $viewName       = Str::snake($this->module);
        $controllerName = Str::studly($this->module) . 'Controller';
        $modelName      = Str::studly($this->module);
        $datatableName  = Str::studly($this->module) . 'Datatable';
        $migrationName  = Str::snake(date('Y_m_d_His') . '_create' . Str::plural($this->module)) . '_table';
        $resourceName   = Str::kebab($this->module);
        $tableName      = Str::plural(Str::snake($this->module));


        // Views
        $viewIndexPath      = $this->getPath($this->path.$viewName.'/index', 'view');
        $viewCreatePath     = $this->getPath($this->path.$viewName.'/create', 'view');
        $viewEditPath       = $this->getPath($this->path.$viewName.'/edit', 'view');
        $viewShowPath       = $this->getPath($this->path.$viewName.'/show', 'view');

        // Modules
        $controllerPath     = $this->getPath($this->path.$controllerName,'controller');
        $modelPath          = $this->getPath($modelName, 'model');
        $datatablePath      = $this->getPath($datatableName, 'datatable');
        $migrationPath      = $this->getPath($migrationName, 'migration');


        // Make Dir
        $this->createDir($viewIndexPath);
        $this->createDir($controllerPath);
        $this->createDir($modelPath);
        $this->createDir($datatablePath);
        $this->createDir($migrationPath);

        // Create File
        File::put($viewIndexPath,  'Index');
        File::put($viewCreatePath, 'Create');
        File::put($viewEditPath,   'Edit');
        File::put($viewShowPath,   'Show');

        $this->createController($controllerPath,$controllerName,$this->nameSpace,$viewName,$modelName);
        $this->createModel($modelPath,$modelName);
        $this->createMigration($migrationPath,$tableName);
        $this->createRoute($resourceName,$controllerName,$this->path);



        $this->info("Skeleton {$this->module} created.");
    }
    /**
     * Get the view full path.
     *
     * @param string $view
     *
     * @return string
     */
    public function getPath($file, $type)
    {
        switch ($type) {
            case 'view':
                $file = str_replace('.', '/', $file) . '.blade.php';
                $path = "resources/views/{$file}";
                return $path;
                break;
            case 'controller':
                $file = str_replace('.', '/', $file) . '.php';
                $path = "app/Http/Controllers/{$file}";
                return $path;
                break;
            case 'model':
                $file = str_replace('.', '/', $file) . '.php';
                $path = "app/Models/{$file}";
                return $path;
                break;
            case 'migration':
                $file = str_replace('.', '/', $file) . '.php';
                $path = "database/migrations/{$file}";
                return $path;
                break;
            case 'datatable':
                $file = str_replace('.', '/', $file) . '.php';
                $path = "app/DataTables/{$file}";
                return $path;
                break;
        }
    }

    /**
     * Create view directory if not exists.
     *
     * @param $path
     */
    public function createDir($path)
    {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function createRoute($resourceName,$controllerName,$nameSpace)
    {

        $resourceName = Str::lower(Str::kebab($nameSpace.$resourceName));
        $nameSpace = "use App\\Http\\Controllers\\$nameSpace$controllerName;";
        $nameSpace = "\r\n". $nameSpace;
        $nameSpace =  str_replace("/","\\", $nameSpace);
        $nameSpace =  str_replace("\\\\","\\", $nameSpace);


        $file = File::get(base_path('routes/web.php'));
        if(Str::contains($file,$controllerName )){
            $this->error("Route already exists!");
            return false;
        }
        $new = str_replace(
            'use Illuminate\Support\Facades\Route;',
            'use Illuminate\Support\Facades\Route; '
            .$nameSpace, $file);
        File::put(base_path('routes/web.php'), $new);
        File::append(base_path('routes/web.php'), "\r\n"."Route::resource('{$resourceName}', {$controllerName}::class);");

    }

    public function createController($controllerPath,$controllerName,$nameSpace,$viewName,$modelName)
    {

        $new = File::get(base_path('app/Console/Commands/Skeletons/SkeletonController.php'));
        $new =  str_replace("namespace App\Http\Controllers" , "namespace App\Http\Controllers\\$nameSpace", $new);
        $new =  str_replace("\;",";", $new);
        $new =  str_replace("SkeletonController" , $controllerName, $new);
        $new =  str_replace('skeletonVariable' , $nameSpace, $new);
        $new =  str_replace('SkeletonModel' , $modelName, $new);
        $new =  str_replace("skeleton"  , $nameSpace ? $nameSpace.'.'.$viewName : $viewName , $new);
        File::put($controllerPath, $new);
    }

    public function createModel($modelPath,$modelName)
    {
        $new = File::get(base_path('app/Console/Commands/Skeletons/SkeletonModel.php'));
        $new =  str_replace('Skeleton' , $modelName, $new);
        File::put($modelPath, $new);
    }

    public function createMigration($migrationPath,$tableName)
    {
        $new = File::get(base_path('app/Console/Commands/Skeletons/SkeletonMigration.php'));
        $new =  str_replace('skeletons' , $tableName, $new);
        File::put($migrationPath, $new);
    }
}

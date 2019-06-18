<?php namespace Crocodicstudio\Cbmodel\Commands;

use App;
use Crocodicstudio\Cbmodel\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class MakeModel extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:cbmodel {--table=ALL : The table name, the default is all table} {--connection=mysql : The connection database, default is mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a model from table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $table = $this->option('table');
        $repoName = null;
        $connection = $this->option('connection');

        if($table == "ALL") {
            $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
            foreach($tables as $table) {
                $this->generateByTable($table, $connection, $repoName);
            }
        }else{
            $this->generateByTable($table, $connection, $repoName);
        }
    }

    private function generateByTable($table, $connection, $repoName) {
        $path = app_path('Models');

        if(!file_exists($path)) {
            @mkdir($path,0755);
        }

        $pathRepositories = app_path("Repositories");
        if(!file_exists($pathRepositories)) {
            @mkdir($pathRepositories, 0755);
        }

        $pathServices = app_path("Services");
        if(!file_exists($pathServices)) {
            @mkdir($pathServices, 0755);
        }

        $template = file_get_contents(__DIR__.'/../Stubs/template.blade.php.stub');
        $repoTemplate = file_get_contents(__DIR__.'/../Stubs/repo_template.blade.php.stub');
        $serviceTemplate = file_get_contents(__DIR__.'/../Stubs/service_template.blade.php.stub');
        $tableStudly = studly_case($table);

        //Assign Class name
        $template = str_replace('[className]',$tableStudly, $template);
        $repoTemplate = str_replace('[className]', $tableStudly, $repoTemplate);
        $serviceTemplate = str_replace('[className]', $tableStudly, $serviceTemplate);

        //Assign Table Name
        $template = str_replace('[tableName]',$table, $template);
        $repoTemplate = str_replace('[tableName]', $table, $repoTemplate);
        $serviceTemplate = str_replace('[tableName]', $table, $serviceTemplate);

        //Assign Connection
        $template = str_replace('[connection]', $connection, $template);

        //Get PK
        $pk = Helper::findPrimaryKey($table, $connection);

        //Assign Columns Properties
        $columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
        $properties = "\n";
        foreach($columns as $column)
        {
            $properties .= "\tprivate \$".$column.";\n";
        }
        $template = str_replace('[properties]', $properties, $template);

        //Assign getter setter
        $gs = "\n";
        foreach($columns as $column)
        {
            $hintClassName = null;
            if(ends_with($column,'_id')) {
                $hintClassName = studly_case(str_replace('_id','',$column));
            }elseif (starts_with($column, 'id_')) {
                $hintClassName = studly_case(str_replace('id_','',$column));
            }

            if(!class_exists("\\App\Models\\".$hintClassName)) {
                $hintClassName = null;
            }

            if($hintClassName) {
                $gs .= "\tpublic static function findAllBy".studly_case($column)."(\$value) {\n";
                $gs .= "\t\treturn static::simpleQuery()->where('".$column."',\$value)->get();\n";
                $gs .= "\t}\n\n";

                $gs .= "\t/**\n";
                $gs .= "\t* @return ".$hintClassName."\n";
                $gs .= "\t*/\n";
                $gs .= "\tpublic function get".studly_case($column)."() {\n";
                $gs .= "\t\treturn ".$hintClassName."::findById(\$this->".$column.");\n";
                $gs .= "\t}\n\n";
            }else{
                if($column != $pk) {
                    $gs .= "\tpublic static function findAllBy".studly_case($column)."(\$value) {\n";
                    $gs .= "\t\treturn static::simpleQuery()->where('".$column."',\$value)->get();\n";
                    $gs .= "\t}\n\n";

                    $gs .= "\tpublic static function findBy".studly_case($column)."(\$value) {\n";
                    $gs .= "\t\treturn static::findBy('".$column."',\$value);\n";
                    $gs .= "\t}\n\n";
                }

                $gs .= "\tpublic function get".studly_case($column)."() {\n";
                $gs .= "\t\treturn \$this->".$column.";\n";
                $gs .= "\t}\n\n";
            }

            $gs .= "\tpublic function set".studly_case($column)."(\$".$column.") {\n";
            $gs .= "\t\t\$this->".$column." = \$".$column.";\n";
            $gs .= "\t}\n\n";
        }
        $template = str_replace('[getterSetter]', $gs, $template);

        //Put the file

        if(!$repoName) {
            $repoName = $tableStudly;
        }

        file_put_contents($path.'/'.$repoName.'.php', $template);
        if(file_exists($path.'/'.$repoName.'.php')) {
            $this->info($repoName." model has been updated!");
        }else{
            $this->info($repoName." model has been created!");
        }

        //create repository
        if(!file_exists($pathRepositories.'/'.$repoName.'Repository.php')) {
            file_put_contents($pathRepositories.'/'.$repoName.'Repository.php', $repoTemplate);
            $this->info($repoName." repository has been created!");
        }


        //create service
        if(!file_exists($pathServices.'/'.$repoName.'Service.php')) {
            file_put_contents($pathServices.'/'.$repoName.'Service.php', $serviceTemplate);
            $this->info($repoName.' service has been created!');
        }
    }
}

<?php namespace Crocodicstudio\Cbmodel\Commands;

use App;
use Crocodicstudio\CbModel\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class MakeModel extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'cbmodel:make {table} {RepoName?}';

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
        $table = $this->argument('table');
        $repoName = $this->argument('RepoName');

        $path = app_path('CBModels');

        if(!file_exists($path)) {
            @mkdir($path,0755);
        }

        $template = file_get_contents(__DIR__.'/../Stubs/template.blade.php.stub');
        $tableStudly = studly_case($table);

        //Assign Class name
        $template = str_replace('[className]',$tableStudly, $template);

        //Assign Table Name
        $template = str_replace('[tableName]',$table, $template);

        //Get PK
        $pk = Helper::findPrimaryKey($table);

        //Assign Columns Properties
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        $properties = "\n";
        foreach($columns as $column)
        {
            if(ends_with($column,'_id')) {
                $column = str_replace('_id','',$column);
            }elseif (starts_with($column, 'id_')) {
                $column = str_replace('id_','',$column);
            }
            $columnCamel = camel_case($column);

            $properties .= "\tprivate \$".$columnCamel.";\n";
        }
        $template = str_replace('[properties]', $properties, $template);

        //Assign getter setter
        $gs = "\n";
        foreach($columns as $column)
        {
            $hintClassName = null;
            if(ends_with($column,'_id')) {
                $column = str_replace('_id','',$column);
                $hintClassName = studly_case($column);
            }elseif (starts_with($column, 'id_')) {
                $column = str_replace('id_','',$column);
                $hintClassName = studly_case($column);
            }

            $columnCamel = camel_case($column);

            if($hintClassName) {
                $gs .= "\t/**\n";
                $gs .= "\t* @return ".$hintClassName."\n";
                $gs .= "\t*/\n";
                $gs .= "\tpublic function get".studly_case($column)."() {\n";
                $gs .= "\t\treturn ".$hintClassName."::findById(\$this->".$columnCamel.");\n";
                $gs .= "\t}\n\n";
            }else{
                $gs .= "\tpublic function get".studly_case($column)."() {\n";
                $gs .= "\t\treturn \$this->".$columnCamel.";\n";
                $gs .= "\t}\n\n";
            }

            $gs .= "\tpublic function set".studly_case($column)."(\$".$columnCamel.") {\n";
            $gs .= "\t\t\$this->".$columnCamel." = \$".$columnCamel.";\n";
            $gs .= "\t}\n\n";
        }
        $template = str_replace('[getterSetter]', $gs, $template);

        //Put the file

        if(!$repoName) {
            $repoName = $tableStudly;
        }

        file_put_contents($path.'/'.$repoName.'.php', $template);

        $this->info($repoName." model repository has been created!");
    }
}

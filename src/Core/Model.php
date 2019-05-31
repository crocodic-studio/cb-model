<?php
namespace Crocodicstudio\Cbmodel\Core;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Crocodicstudio\Cbmodel\Helpers\Helper;
use Illuminate\Support\Str;

class Model
{
    private static $tableQuery = null;
    public static $tableName = null;
    public static $connection = null;
    private static $relations = [];
    public static $autojoin = true;
    public static $joinException = [];
    private static $id = null;
    private static $lastInsertId = null;
    private static $uniqueData = false;
    private static $rowTemp = null;

    public function __construct($row = null)
    {
        if($row) {
            foreach ($row as $key => $val) {
                if ($key) {
                    $methodName = Str::camel('set ' . $key);
                    if(method_exists($this, $methodName)) {
                        $this->$methodName($val);
                    }
                }
            }
            self::$rowTemp = $row;
        }
    }

    /**
     * @return mixed|Builder
     */
    public static function table()
    {
        if (isset(self::$tableQuery['tableName']) && self::$tableQuery['tableName'] != static::$tableName) {
            self::$relations = [];
            self::$joinException = [];
            self::$autojoin = true;
            self::$id = null;
            self::$lastInsertId = null;
        }

        self::$tableQuery['tableName'] = static::$tableName;
        self::$tableQuery['query'] = DB::connection(static::$connection)->table(static::$tableName)->select(static::$tableName . '.*');

        if (self::$autojoin) {
            self::autoJoinIt(static::$tableName);
        }


        if (count(self::$relations)) {

            foreach (self::$relations as $relate) {
                $tableFrom = (Str::contains($relate['tableFrom'], ' as ')) ? Str::after($relate['tableFrom'], ' as ') : $relate['tableFrom'];
                self::$tableQuery['query']->leftjoin($relate['tableFrom'], $tableFrom . '.' . $relate['tableFromPK'], $relate['operator'], $relate['dest']);

                $columns = DB::connection(static::$connection)->getSchemaBuilder()->getColumnListing($relate['tableFrom']);
                foreach ($columns as $column) {
                    $alias = ($relate['prefix']) ? $relate['prefix'] . '_' . $relate['tableFrom'] . '_' . $column : $relate['tableFrom'] . '_' . $column;
                    self::$tableQuery['query']->addselect($tableFrom . '.' . $column . ' as ' . $alias);
                }
            }
        }

        return self::$tableQuery['query'];
    }



    /**
     * @return Builder
     */
    public static function simpleQuery()
    {
        return DB::connection(static::$connection)->table(static::$tableName);
    }

    /**
     * @return null
     */
    public static function getTableName()
    {
        return static::$tableName;
    }


    /**
     * @return mixed
     */
    public static function query()
    {
        return self::$tableQuery['query'];
    }

    /**
     * @return null|string
     */
    public static function getPrimaryKey()
    {
        return Helper::findPrimaryKey(self::getTableName(), static::$connection);
    }

    /**
     * @return string
     */
    public static function getPrimaryField()
    {
        return static::$tableName . '.' . static::getPrimaryKey();
    }

    /**
     * @return mixed
     */
    public static function getMaxId()
    {
        return DB::connection(static::$connection)
            ->table(self::getTableName())->max(self::getPrimaryKey());
    }

    /**
     * @param $tableName
     */
    private static function autoJoinIt($tableName)
    {
        $columns = DB::connection(static::$connection)->getSchemaBuilder()->getColumnListing($tableName);
        foreach ($columns as $column) {
            if (in_array($column, static::$joinException)) continue;

            if (Str::endsWith($column, '_id')) {
                $relationTable = str_replace('_id', '', $column);
               
                if (Schema::hasTable($relationTable)) {
		    $relationTablePK = Helper::findPrimaryKey($relationTable, static::$connection);
                    self::join($relationTable, $relationTablePK, '=', $tableName.'.'.$column);
                }
            }elseif (Str::startsWith($column, 'id_')) {
                $relationTable = str_replace('id_', '', $column);
                
                if (Schema::hasTable($relationTable)) {
		    $relationTablePK = Helper::findPrimaryKey($relationTable, static::$connection);
                    self::join($relationTable, $relationTablePK, '=', $tableName.'.'.$column);
                }
            }
        }
    }

    public static function addSelectFile($field, $alias = null)
    {
        $alias = ($alias) ?: str_replace('.', '_', $field);
        self::$tableQuery['query']->addselect(DB::raw("
			CASE 
				WHEN " . $field . " IS NULL THEN ''  
				ELSE CONCAT('" . asset('/') . "'," . $field . ") 
			END AS " . $alias . "	
			"));
    }

    /**
     * @param $tableFrom
     * @param null $tableFromPK
     * @param null $operator
     * @param null $dest
     * @param null $prefix
     */
    public static function leftjoin($tableFrom, $tableFromPK = null, $operator = null, $dest = null, $prefix = null)
    {
        if ($tableFromPK == null) $tableFromPK = $tableFrom . '.id';
        if ($operator == null) $operator = '=';
        if ($dest == null) $dest = $tableFrom . '_id';

        self::$relations[$tableFrom] = ['tableFrom' => $tableFrom, 'tableFromPK' => $tableFromPK, 'operator' => $operator, 'dest' => $dest, 'prefix' => $prefix, 'leftjoin' => true];
    }

    /**
     * @param $tableFrom
     * @param null $tableFromPK
     * @param null $operator
     * @param null $dest
     * @param null $prefix
     */
    public static function join($tableFrom, $tableFromPK = null, $operator = null, $dest = null, $prefix = null)
    {
        if ($tableFromPK == null) $tableFromPK = $tableFrom . '.id';
        if ($operator == null) $operator = '=';
        if ($dest == null) $dest = $tableFrom . '_id';

        self::$relations[$tableFrom] = ['tableFrom' => $tableFrom, 'tableFromPK' => $tableFromPK, 'operator' => $operator, 'dest' => $dest, 'prefix' => $prefix];
    }


    public static function init()
    {
        if (!self::$tableQuery || self::$tableQuery['tableName'] != self::$tableName) {
            static::$connection = (static::$connection)?:config("database.default");
            self::table();
        }
    }

    public static function hookQuery($query)
    {
        self::init();
        call_user_func($query, self::$tableQuery['query']);
        return new self();
    }

    public static function limit($limit = 20)
    {
        self::hookQuery(function ($query) use ($limit) {
            $query->take($limit);
        });
        return new self();
    }


    public static function offset($offset = 0)
    {
        self::hookQuery(function ($query) use ($offset) {
            $query->skip($offset);
        });
        return new self();
    }


    public static function orderby($field, $order)
    {
        self::hookQuery(function ($query) use ($field, $order) {
            $query->orderby($field, $order);
        });
        return new self();
    }


    /**
     * @param mixed
     * @return $this|static
     */
    public static function fromQueryBuilder($row)
    {
        return new static($row);
    }

    /**
     * @param mixed
     * @return $this|static
     */
    public static function findById($id)
    {

        self::init();
        self::$id = $id;

        if($data = app("CBModelTemporary")->get(get_called_class(),"findById",$id)) {
            return new static($data);
        }else{
            $data = self::simpleQuery()->where(static::getPrimaryField(),$id)->first();
            app("CBModelTemporary")->set(get_called_class(),"findById",$id,$data);
            return new static($data);
        }
    }

    public static function findBy($field, $value)
    {
        self::init();
        $pk = static::getPrimaryKey();

        if($data = app("CBModelTemporary")->get(get_called_class(),"findBy",$value)) {
            self::$id = $data->{$pk};
            return new static($data);
        }else{

            $data = self::simpleQuery()->where($field,$value)->first();
            if($data) {
                self::$id = $data->{$pk};
                app("CBModelTemporary")->set(get_called_class(),"findBy",$value,$data);
            }
            return new static($data);
        }
    }

    public function toObject()
    {
        if(isset(self::$rowTemp)) {
            return self::$rowTemp;
        }else{
            return new \Exception("Call the method on null object");
        }
    }

    public function uniqueData() {
        self::$uniqueData = true;
        return new self();
    }

    /**
     * @throws \Exception
     */
    public function saveUnique() {
        self::uniqueData();
        try{
            self::save();
        }catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @return int|mixed|null
     * @throws \Exception
     */
    public function save()
    {
        try{
            $model = $this;
            $pk = Helper::findPrimaryKey(static::$tableName, static::$connection);
            $columns = DB::connection(static::$connection)->getSchemaBuilder()->getColumnListing(static::$tableName);

            $data = [];
            foreach($columns as $column)
            {
                $methodName = Str::camel('get '.$column);
                if(method_exists($model, $methodName)) {
                    $getAttr = $model->{$methodName}();
                    if(is_object($getAttr)) {
                        if(method_exists($getAttr, "getPrimaryKey")) {
                            $pkMethod = Str::camel('get '.$getAttr->getPrimaryKey());
                            $data[$column] = $getAttr->{$pkMethod}();
                        }else{
                            $data[$column] = $getAttr;
                        }
                    }else{
                        $data[$column] = $getAttr;
                    }
                }
            }

            if(Schema::hasColumn(static::$tableName,'updated_at')) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            if(Schema::hasColumn(static::$tableName,'created_at')) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            if(isset($data[$pk])) {
                $pkValue = $data[$pk];
                unset($data[$pk]);
                DB::connection(static::$connection)->table(static::$tableName)->where($pk,$pkValue)->update($data);
            }else{
                if(self::$uniqueData) {
                    if(DB::connection(static::$connection)->table(static::$tableName)->where($data)->exists()) {
                        throw new \Exception("The data has already exists!");
                    }
                }

                self::$lastInsertId = DB::connection(static::$connection)
                    ->table(static::$tableName)
                    ->insertGetId($data);
                $pkValue = self::$lastInsertId;
            }

            //set to setId()
            $pkSetMethod = Str::camel('set '.$pk);
            $this->{$pkSetMethod}( $pkValue );

            return $pkValue;
        }catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function delete($id = null)
    {
        if(self::$id || $id) {
            $id = ($id)?:self::$id;
            $pk = Helper::findPrimaryKey(static::$tableName, static::$connection);
            DB::connection(static::$connection)->table(self::getTableName())->where($pk,$id)->delete();
        }
    }

    public static function deleteById($id) {
        if(self::$id || $id) {
            $id = ($id)?:self::$id;
            $pk = Helper::findPrimaryKey(static::$tableName, static::$connection);
            DB::connection(static::$connection)->table(self::getTableName())->where($pk,$id)->delete();
        }
    }

    /**
     * @return Collection
     */
    public static function all()
    {
        self::init();
        return self::$tableQuery['query']->get();
    }
}

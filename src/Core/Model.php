<?php
namespace Crocodicstudio\Cbmodel\Core;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Crocodicstudio\Cbmodel\Helpers\Helper;

class Model
{
    private static $tableQuery = null;
    public static $tableName = null;
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
                    if (starts_with($key, 'id_')) {
                        $relationTable = str_replace('id_', '', $key);
                        $methodName = camel_case('set ' . $relationTable);
                        if(!method_exists($this, $methodName)) {
                            $methodName = camel_case("set ".$key);
                        }
                    } elseif (ends_with($key, '_id')) {
                        $relationTable = str_replace('_id', '', $key);
                        $methodName = camel_case('set ' . $relationTable);
                        if(!method_exists($this, $methodName)) {
                            $methodName = camel_case("set ".$key);
                        }
                    } else {
                        $methodName = camel_case('set ' . $key);
                    }

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
        self::$tableQuery['query'] = DB::table(static::$tableName)->select(static::$tableName . '.*');

        if (self::$autojoin) {
            self::autoJoinIt(static::$tableName);
        }


        if (count(self::$relations)) {

            foreach (self::$relations as $relate) {
                $tableFrom = (str_contains($relate['tableFrom'], ' as ')) ? str_after($relate['tableFrom'], ' as ') : $relate['tableFrom'];
                self::$tableQuery['query']->leftjoin($relate['tableFrom'], $tableFrom . '.' . $relate['tableFromPK'], $relate['operator'], $relate['dest']);

                $columns = DB::getSchemaBuilder()->getColumnListing($relate['tableFrom']);
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
        return DB::table(static::$tableName);
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
        return Helper::findPrimaryKey(self::getTableName());
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
        return DB::table(self::getTableName())->max(self::getPrimaryKey());
    }

    /**
     * @param $tableName
     */
    private static function autoJoinIt($tableName)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
        foreach ($columns as $column) {
            if (in_array($column, static::$joinException)) continue;

            if (ends_with($column, '_id')) {
                $relationTable = str_replace('_id', '', $column);
                $relationTablePK = Helper::findPrimaryKey($relationTable);
                if (Schema::hasTable($relationTable)) {
                    self::join($relationTable, $relationTablePK, '=', $tableName.'.'.$column);
                }
            }elseif (starts_with($column, 'id_')) {
                $relationTable = str_replace('id_', '', $column);
                $relationTablePK = Helper::findPrimaryKey($relationTable);
                if (Schema::hasTable($relationTable)) {
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
        if($data = app("CBModelTemporary")->get(get_called_class(),"findById",$id)) {
            return $data;
        }

        self::init();
        self::$id = $id;
        $row = new static(self::simpleQuery()->where(static::getPrimaryField(),$id)->first());

        app("CBModelTemporary")->set(get_called_class(),"findById",$id,$row);

        return $row;
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
            $pk = Helper::findPrimaryKey(static::$tableName);
            $columns = DB::getSchemaBuilder()->getColumnListing(static::$tableName);
            $pkColumn = camel_case('get '.$pk);

            $data = [];
            foreach($columns as $column)
            {
                if(starts_with($column, 'id_')) {
                    $relationName = str_replace('id_','',$column);
                    $methodName = camel_case('get '.$relationName);
                    if(!method_exists($model, $methodName)) {
                        $methodName = camel_case("get ".$column);
                    }
                }elseif (ends_with($column,'_id')) {
                    $relationName = str_replace('_id','',$column);
                    $methodName = camel_case('get '.$relationName);
                    if(!method_exists($model, $methodName)) {
                        $methodName = camel_case("get ".$column);
                    }
                }else{
                    $methodName = camel_case('get '.$column);
                }

                if(method_exists($model, $methodName)) {
                    $getAttr = $model->{$methodName}();
                    if(is_object($getAttr)) {
                        if(method_exists($getAttr, "getPrimaryKey")) {
                            $pkMethod = camel_case('get '.$getAttr->getPrimaryKey());
                            $data[$column] = $getAttr->{$pkMethod}();
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

            $pkValue = 0;

            if(isset($data[$pk])) {
                $pkValue = $data[$pk];
                unset($data[$pk]);
                DB::table(static::$tableName)->where($pk,$pkValue)->update($data);
            }else{
                if(self::$uniqueData) {
                    if(DB::table(static::$tableName)->where($data)->exists()) {
                        throw new \Exception("The data has already exists!");
                    }
                }

                self::$lastInsertId = DB::table(static::$tableName)->where($pk,$model->$pkColumn)->insertGetId($data);
                $pkValue = self::$lastInsertId;
            }

            //set to setId()
            $pkSetMethod = camel_case('set '.$pk);
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
            $pk = Helper::findPrimaryKey(static::$tableName);
            DB::table(self::getTableName())->where($pk,$id)->delete();
        }
    }

    public static function deleteById($id) {
        if(self::$id || $id) {
            $id = ($id)?:self::$id;
            $pk = Helper::findPrimaryKey(static::$tableName);
            DB::table(self::getTableName())->where($pk,$id)->delete();
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

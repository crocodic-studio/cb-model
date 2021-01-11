<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2/14/2020
 * Time: 1:28 PM
 */

namespace crocodicstudio\cbmodel\Core;

use Illuminate\Support\Facades\DB;

class Model extends ModelAbstract
{
    use ModelSetter;

    /**
     * Get last record id
     * @return mixed
     */
    public static function lastId() {
        return DB::table((new static())->table)->max((new static())->primary_key);
    }

    /**
     * A one-to-many relationship
     * @param string $modelName Parent model class name
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param callable|null $condition Add condition with Builder Query
     * @return mixed
     */
    public function hasMany(string $modelName, string $foreignKey = null, string $localKey = null, callable $condition = null) {
        $childModel = new $modelName();
        $parentModel = new static();
        $foreignKey = ($foreignKey) ? $foreignKey : $parentModel->table."_".$parentModel->primary_key;
        $localKey = ($localKey) ? $localKey : $parentModel->primary_key;
        $localKey = $this->$localKey;
        return $childModel::queryList(function($query) use ($foreignKey, $localKey, $condition) {
            $query = $query->where($foreignKey, $localKey);
            if(isset($condition) && is_callable($condition)) $query = call_user_func($condition, $query);
            return $query;
        });
    }

    /**
     * A one-to-one relationship
     * @param string $modelName
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return mixed
     */
    public function belongsTo(string $modelName, string $foreignKey = null, string $localKey = null) {
        $childModel = new $modelName();
        $parentModel = new static();
        $foreignKey = ($foreignKey) ? $foreignKey : $parentModel->table."_".$parentModel->primary_key;
        $localKey = ($localKey) ? $localKey : $parentModel->primary_key;
        $localKey = $this->$localKey;
        return $childModel::query(function($query) use ($foreignKey, $localKey) {
            return $query->where($foreignKey, $localKey);
        });
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table() {
        return DB::table((new static())->table);
    }

    /**
     * @param callable $query
     * @return static
     */
    public static function query(callable $query) {
        $query = call_user_func($query, static::table());
        return static::objectSetter($query->first());
    }

    /**
     * @param callable $query
     * @return static[]
     */
    public static function queryList(callable $query) {
        $query = call_user_func($query, static::table());
        return static::listSetter($query->get());
    }

    /**
     * @param array|string $column
     * @param string|null $value
     * @param string $sorting_column
     * @param string $sorting_dir
     * @return static[]
     */
    public static function findAllBy($column, $value = null, $sorting_column = "id", $sorting_dir = "desc") {
        if(is_array($column)) {
            $result = DB::table((new static())->table);
            foreach($column as $key=>$value) {
                $result->where($key, $value);
            }
            $result = $result->orderBy($sorting_column, $sorting_dir)->get();
        } else {

            $result = DB::table((new static())->table)->where($column, $value)->orderBy($sorting_column, $sorting_dir)->get();
        }

        return static::listSetter($result);
    }

    /**
     * @return integer
     */
    public static function count() {
        $total = app("CBModelTemporary")->get(static::class, "count", (new static())->table);
        if(!isset($total)) {
            $total = DB::table((new static())->table)->count();
            app("CBModelTemporary")->put(static::class, "count", (new static())->table);
        }
        return $total;
    }

    /**
     * @param array|string $column
     * @param string|null $value
     * @return integer
     */
    public static function countBy($column, $value = null) {
        if(is_array($column)) {
            $result = DB::table((new static())->table);
            foreach($column as $key=>$value) {
                $result->where($key, $value);
            }
            $result = $result->count();
        } else {

            $result = DB::table((new static())->table)
                ->where($column, $value)
                ->count();
        }
        return $result;
    }

    /**
     * @param $column
     * @return static[]
     */
    public static function findAllDesc($column = "id") {
        $result = DB::table((new static())->table)->orderBy($column,"desc")->get();
        return static::listSetter($result);
    }

    /**
     * @param $column
     * @return static[]
     */
    public static function findAllAsc($column = "id") {
        $result = DB::table((new static())->table)->orderBy($column,"asc")->get();
        return static::listSetter($result);
    }

    /**
     * @param callable|null $query Query Builder
     * @return static[]
     */
    public static function findAll(callable $query = null) {
        if(is_callable($query)) {
            $result = call_user_func($query, static::table());
            $result = $result->get();
        } else {
            $result = static::table()->get();
        }
        return static::listSetter($result);
    }

    /**
     * @return static[]
     */
    public static function latest() {
        $result = DB::table((new static())->table)->orderBy((new static())->primary_key,"desc")->get();
        return static::listSetter($result);
    }

    /**
     * @return static[]
     */
    public static function oldest() {
        $result = DB::table((new static())->table)->orderBy((new static())->primary_key,"asc")->get();
        return static::listSetter($result);
    }

    public function toArray() {
        $result = [];
        foreach($this as $key=>$val) {
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * @param $id
     * @return static
     */
    public static function findById($id) {
        $row = app("CBModelTemporary")->get(static::class, "findById", $id);
        if(!$row) {
            $row = DB::table((new static())->table)
                ->where((new static())->primary_key,$id)
                ->first();
            app("CBModelTemporary")->put(static::class, "findById", $id, $row);
        }

        return static::objectSetter($row);
    }

    /**
     * @param $id
     * @return static
     */
    public static function find($id) {
        return static::findById($id);
    }

    /**
     * @param array|string $column
     * @param string|null $value
     * @return static
     */
    public static function findBy($column, $value = null) {
        if(is_array($column)) {
            $row = DB::table((new static())->table)
                ->where($column)
                ->first();
        } else {
            $row = DB::table((new static())->table)
                ->where($column,$value)
                ->first();
        }

        return static::objectSetter($row);
    }

    /**
     * To save insert many data
     * @param Model[] $data
     */
    public static function bulkInsert(array $data) {
        $insertData = [];
        foreach($data as $row) {
            /** @var Model $row */
            $dataArray = $row->toArray();
            $insertData[] = $dataArray;
        }
        DB::table((new static())->table)->insertOrIgnore($insertData);
    }

    public function save() {
        $primary_key = (new static())->primary_key;
        $data = [];
        foreach($this as $key=>$val) {
            if(!in_array($key,[$primary_key])) {
                if(isset($this->{$key})) {
                    $data[$key] = $val;
                }
            }
        }

        unset($data['table'], $data['connection'], $data['primary_key']);

        if($this->{$primary_key}) {
            if(isset($data['created_at'])) {
                unset($data['created_at']);
            }
            DB::table((new static())->table)->where($primary_key, $this->{$primary_key})->update($data);
            $id = $this->{$primary_key};
        } else {
            if(property_exists($this,'created_at')) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $id = DB::table((new static())->table)->insertGetId($data);
        }

        $this->{$primary_key} = $id;
        return ($id)?true:false;
    }

    /**
     * @param $id
     */
    public static function deleteById($id) {
        DB::table((new static())->table)->where((new static())->primary_key,$id)->delete();
    }

    /**
     * @param string|array $column
     * @param null $value
     */
    public static function deleteBy($column, $value = null) {
        if(is_array($column)) {
            $result = DB::table((new static())->table);
            foreach($column as $key=>$value) {
                $result->where($key, $value);
            }
            $result->delete();
        } else {
            if(!$value) {
                throw new \InvalidArgumentException("Missing argument 2 value");
            }

            DB::table((new static())->table)->where($column,$value)->delete();
        }
    }

    public function delete() {
        DB::table((new static())->table)->where((new static())->primary_key, $this->{$primary_key})->delete();
    }

}
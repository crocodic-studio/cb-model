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
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table() {
        return DB::table((new static())->table);
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
        return DB::table((new static())->table)->count();
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
        $row = DB::table((new static())->table)
            ->where((new static())->primary_key,$id)
            ->first();
        return static::objectSetter($row);
    }

    /**
     * @param $id
     * @return Model
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

    public static function deleteById($id) {
        DB::table((new static())->table)->where((new static())->primary_key,$id)->delete();
    }

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
        DB::table((new static())->table)->where((new static())->primary_key, $this->id)->delete();
    }

}
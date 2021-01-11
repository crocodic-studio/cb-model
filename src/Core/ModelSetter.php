<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 3/18/2020
 * Time: 12:17 AM
 */

namespace crocodicstudio\cbmodel\Core;

trait ModelSetter
{

    /**
     * ModelSetter constructor.
     * @param null $row
     */
    public function __construct($row = null)
    {
        if($row) {
            foreach($row as $key=>$value) {
                $this->{$key} = $value;
            }
        }
    }

    public function set($column, $value) {
        $this->{$column} = $value;
    }

    /**
     * @param $result
     * @return static[]
     */
    private static function listSetter($result) {
        $final = [];
        foreach($result as $item) {
            $model = new static();
            foreach($item as $key=>$val) {
                $model->set($key,$val);
            }
            $final[] = $model;
        }
        return $final;
    }

    /**
     * @param $result
     * @return static
     */
    private static function objectSetter($result) {
        $model = new static();
        if($result) {
            foreach($result as $key=>$val) {
                $model->set($key,$val);
            }
        }
        return $model;
    }

    public function __toString()
    {
        return $this->id;
    }

}
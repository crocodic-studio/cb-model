<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2/25/2020
 * Time: 10:11 PM
 */

namespace crocodicstudio\cbmodel\Core;


abstract class ModelAbstract
{
    public $connection = "mysql";

    public $table = null;

    public $primary_key = "id";

}
<?php

namespace Crocodicstudio\Cbmodel\Helpers;

use Illuminate\Support\Facades\DB;

class Helper
{
    public static function findPrimaryKey($table, $connection = null)
    {
        $connection = $connection?:config("database.default");

        $pk = DB::connection($connection)->getDoctrineSchemaManager()->listTableDetails($table)->getPrimaryKey();
        if(!$pk) {
            return null;
        }
        return $pk->getColumns()[0];
    }

}
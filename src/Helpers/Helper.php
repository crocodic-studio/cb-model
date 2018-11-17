<?php

namespace Crocodicstudio\Cbmodel\Helpers;

use Illuminate\Support\Facades\DB;

class Helper
{
    public static function findPrimaryKey($table)
    {
        if(!$table)
        {
            return 'id';
        }

        $pk = DB::getDoctrineSchemaManager()->listTableDetails($table)->getPrimaryKey();
        if(!$pk) {
            return null;
        }
        return $pk->getColumns()[0];
    }

}
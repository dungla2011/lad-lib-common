<?php

namespace LadLib\Common\Database;

use Illuminate\Support\Str;

class DbHelper {
    /**
     * PDO version
     * @param $con
     * @param $tableName
     * Get all columns of a table , and return data type of columns
     * @return array [columns => data type]
     *
     * Example get laravel PDO : $con = \Illuminate\Support\Facades\DB::getPdo();
     */
    static function getTableColumnAndDataType($con, $tableName){
        if($con instanceof \PDO);
        $stm = $con->query("SHOW COLUMNS FROM $tableName");
        $stm->setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();
        $mm = [];
        foreach ($rows AS $one)
            $mm[$one['Field']] = $one['Type'];
        return $mm;
    }

    /**
     * PDO version
     * @param $con
     * @param $tableName
     * Get all columns of a table
     * @return array [columns]
     *
     * Example get laravel PDO : $con = \Illuminate\Support\Facades\DB::getPdo();
     */
    static function getTableColumns($con, $tableName){

        if(!$con){
            throw new \Exception("Not connection DB? May be need set DB and Table for $tableName ");
        }

        $mm = self::getTableColumnAndDataType($con, $tableName);
        $ret  = array_keys($mm);
        return $ret;
    }

    /**
     * @param $con
     * @return array
     * Example get laravel PDO : $con = \Illuminate\Support\Facades\DB::getPdo();
     */
    static function getAllTableName($con, $dbName){
        if($con instanceof \PDO);
        $stm = $con->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbName'");
        $stm->setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();
        $mm = [];
        foreach ($rows AS $one)
            $mm[] = $one['table_name'];
        return $mm;
    }

    /**
     * @param $tableName
     * @return MetaOfTableInDb
     */
    public static function getMetaObjFromTableName($tableName){
        $cls = "\\App\\Models\\" . Str::studly(Str::singular($tableName))."_Meta";
        $obj = new $cls;
        return $obj;
    }
}


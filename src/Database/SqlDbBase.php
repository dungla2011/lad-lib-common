<?php

namespace LadLib\Common\Database;

use Base\ClassRoute;
use Base\ModelBase;
use Base\modelBaseMongo;
use Base\ModelLogUser;
use Base\modelTreeMongo;
use Illuminate\Support\Facades\DB;

abstract class SqlDbBase extends BaseDb {

    public static $_tableName = '';
    public static $_dbConnection = null;

    public function getDbName(){
        return env("DB_DATABASE");
    }

    public function getTableName(){
        return static::$_tableName;
    }

    function setTableName($table){
        static::$_tableName = $table;
    }

    function insert(){
        $tbl = static::$_tableName;
        $ret = DB::table($tbl)
            ->insertGetId($this->toArray());
        return $ret;
    }

    function update($mm){
//        echo "<br/>\n static::_tableName = " . static::$_tableName;
        return DB::table(static::$_tableName)
            ->where('id', $this->id)
            ->update($mm);
    }


    /**
     * @param int $where
     * @return $this[]
     */
    static public function getArrayWhere($where = 1){
        $tbl = static::$_tableName;
        $ret = DB::select(" select * FROM $tbl WHERE $where");
        if(!$ret)
            return null;
        $cls = get_called_class();
        $mret = [];
        foreach ($ret AS $m1){
            $obj = new $cls;
            $obj->loadFromObjOrArray($m1);
            $mret[] = $obj;
        }
        return $mret;

    }

    static public function getOneId($id){
        $cls = get_called_class();
        $tbl = static::$_tableName;

        $ret = DB::select(" select * FROM $tbl WHERE id = $id");
        if(!$ret)
            return null;

        $obj = new $cls;
        $obj->loadFromObjOrArray($ret[0]);
        return $obj;
    }

    /**
     * @param $where
     * @return $this|null
     */
    static public function getOneWhereStatic($where){
        $cls = get_called_class();
        $tbl = static::$_tableName;
        $ret = DB::select(" select * FROM $tbl WHERE $where LIMIT 1");
        if(!$ret)
            return null;
        $obj = new $cls;
        $obj->loadFromObjOrArray($ret[0]);
        return $obj;
    }
}


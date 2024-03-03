<?php

namespace LadLib\Common\Database;
use Base\ClassBaseGlx;
use Base\modelBaseMongo;
use LadLib\Common\Database\MongoDbConnection;

class clsBaseGlx{

    /**
     * Gán Null
     */
    function Reset()
    {
        foreach (get_class_vars(get_class($this)) as $k => $v) {
//            $prop = new \ReflectionProperty(get_class($this), $k);
//            if ($prop->isStatic()) {
//                continue;
//            }
//            if($k != '_id' && substr($k,0,1) == '_')
//                continue;

            $this->$k = null;
        }
    }

    function toArray($ignoreField = [], $ignorePreFixField = null ,  $ignoreEmptyValue = 0)
    {
        $arr = array();
        foreach ($this as $key => $value) {
            if (is_object($value)) {
                $value = json_decode(json_encode($value), true);
            }
            if($ignoreEmptyValue && !$value && $value!==0){
                continue;
            }
            $arr[$key] = $value;
        }
        return $arr;
    }

    /*
     * Xóa hết các properties của đối tượng
     */
    function clearField()
    {
        foreach (get_class_vars(get_class($this)) as $k => $v) {

//            $prop = new \ReflectionProperty(get_class($this), $k);
//            if ($prop->isStatic()) {
//                continue;
//            }
            if($k != '_id' && substr($k,0,1) == '_')
                continue;

            unset($this->$k);
        }
    }

    function loadFromArray($row, $prefix = "", $debug = 0)
    {
        $this->clearField();

//        if(!is_array($row) || count($row) == 0)
//            return null;
        $foundField = 0;
        if ($row && is_array($row) && count($row))
            foreach ($row as $key => $value) {
                if ($prefix)
                    $key = str_replace($prefix, "", $key);
                if (property_exists(get_class($this), $key)) {
                    $foundField = 1;
                    $this->$key = $value;
                }
            }
        if ($foundField)
            return $this;
        return null;
    }

    function _ALAST(){

    }
}

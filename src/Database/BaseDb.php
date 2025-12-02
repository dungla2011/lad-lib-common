<?php

namespace LadLib\Common\Database;

define("DEF_DATA_TYPE_STATUS", 1);
define("DEF_DATA_TYPE_NUMBER", 2);
define("DEF_DATA_TYPE_TEXT_STRING", 3);
define("DEF_DATA_TYPE_TEXT_AREA", 4);
define("DEF_DATA_TYPE_RICH_TEXT", 5);
define("DEF_DATA_TYPE_OBJECT", 6);
define("DEF_DATA_TYPE_ARRAY_STRING", 7);
define("DEF_DATA_TYPE_PASSWORD", 8);
define("DEF_DATA_TYPE_MONGO_BSON_ARRAY", 9);
define("DEF_DATA_TYPE_IS_ERROR_STATUS", 10);
define("DEF_DATA_TYPE_ARRAY_JOIN_TABLE", 11); //Lay ten table o join_function,
define("DEF_DATA_TYPE_ARRAY_NUMBER", 12); //Mảng các số, ví dụ mảng các id...
define("DEF_DATA_TYPE_IS_LINK", 15);
define("DEF_DATA_TYPE_IS_COLOR_PICKER", 16);
define("DEF_DATA_TYPE_IS_SUCCESS_STATUS", 17); //Ngược với error
define("DEF_DATA_TYPE_IS_DATE", 18);
define("DEF_DATA_TYPE_IS_DATE_TIME", 19);
define("DEF_DATA_TYPE_IS_TIME", 20);
define("DEF_DATA_TYPE_IS_ONE_IMAGE_BROWSE", 21); // Cho phép browse 1 ảnh gắn ID vào đây
define("DEF_DATA_TYPE_IS_FA_FONT_ICON", 22);
define("DEF_DATA_TYPE_HTML_SELECT_OPTION", 23);
define("DEF_DATA_TYPE_BOOL_NUMBER", 24);
//Select tree, chọn kiểu Dialog select tree (folder...), radio box
define("DEF_DATA_TYPE_TREE_SELECT", 25);
//Kiểu select check box
define("DEF_DATA_TYPE_TREE_MULTI_SELECT", 26);
define("DEF_DATA_TYPE_IS_MULTI_IMAGE_BROWSE", 27); // Cho phép browse nhiều ảnh gắn ID vào đây cách nhau dấu ,
//Kiểu này thì trường sẽ là full html, ko phải input nữa
define("DEF_DATA_TYPE_FULL_HTML", 28);
define("DEF_DATA_TYPE_HTML_SELECT_OPTION_MULTI_VALUE", 29);



/**
 * This base class will be inherited in Model class, to CURD data
 * Using: Object maps properties with Fields of a row in a table of DB
 * Each property is a field in table of DB
 * Each object store data of a raw of table
 */
abstract class BaseDb implements IBaseDb
{

    /**
     * @var array
     * Store metaData: informattion of all Properties of DB object
     */
    public static $_metaData = '';

    function __construct(){
    }

    abstract function getDbName();

    abstract function getTableName();

    //https://stackoverflow.com/questions/8889521/php-force-a-class-to-declare-a-property
    //Dùng để kiểm tra class con, bắt buộc phải khai báo biến _metaData
    function __get($key)
    {
        if ($key !== '_metaData')
            return;
        if (!isset(static::$$key)) {
            throw new \Exception(' Error : ' . get_called_class() . ', need: _metaData:  public static $_metaData = \'\' !');
        }
        return static::$$key;
    }

    public static function dumpArray($mm, $n = 100)
    {
        $cc = 0;
        if (is_array($mm))
            foreach ($mm as $obj) {
                $cc++;
                if ($cc > $n)
                    break;
                echo "$cc. <pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
                print_r($obj);
                echo "</pre>";
            }
    }

    function dumpLogBr()
    {
        $this->log = trim($this->log);
        $this->log = trim($this->log, "#");
        return str_replace("#", "<br>", $this->log);
    }

    function cloneMe($ignoreField = null)
    {
        $call = get_called_class();
        $obj = new $call;
        $obj->loadFromObj($this);

        if (is_array($ignoreField)) {
            foreach ($ignoreField as $field) {
                if (isset($obj->$field)) {
                    unset($obj->$field);
                }
            }
        }

        return $obj;
    }

    function checkValidTableAndDbName(){
        $db = $this->getDbName();
        $tbl = $this->getTableName();
        if(!$tbl)
            loi("*** Need define table of ".get_class($this));
        if(!$db)
            loi("*** Need define db of ".get_class($this));
    }

    /**
     *
     * Xóa hết các giá trị của properties của đối tượng
     */
    function reset()
    {
        foreach (get_class_vars(get_class($this)) as $k => $v) {
//            $prop = new \ReflectionProperty(get_class($this), $k);
//            if ($prop->isStatic()) {
//                continue;
//            }
            if ($k != '_id' && substr($k, 0, 1) == '_')
                continue;

            $this->$k = null;
        }
    }

    function loadFromArrayAllField($row, $prefix = "", $debug = 0)
    {
        $this->clearField();

//        if(!is_array($row) || count($row) == 0)
//            return null;
        if ($row && is_array($row) && count($row))
            foreach ($row as $key => $value) {
                if ($prefix)
                    $key = str_replace($prefix, "", $key);
                $this->$key = $value;
            }
        return $this;
    }

    function loadFromArrayNotClearObj($row, $prefix = "", $debug = 0)
    {

//        if(!is_array($row) || count($row) == 0)
//            return null;
        if ($row && is_array($row) && count($row))
            foreach ($row as $key => $value) {
                if ($prefix)
                    $key = str_replace($prefix, "", $key);

                if (property_exists(get_class($this), $key)) {
                    $this->$key = $value;
                }
            }
        return $this;
    }

    public static function loadFromArrayOfArray($arrOfArr)
    {
        if (!is_array($arrOfArr) || count($arrOfArr) == 0)
            return null;
        $arrRet = array();
        foreach ($arrOfArr as $arr) {
            if (!is_array($arr))
                continue;
            $class = get_called_class();
            $obj = new $class;
            $obj->loadFromArray($arr);
            $arrRet[] = $obj;
        }
        return $arrRet;
    }

    function loadFromObjOrArray($obj, $prefix = "", $debug = 0)
    {
        $this->clearField();
        if (is_object($obj) || is_array($obj))
            foreach ($obj as $key => $value) {

                if (!property_exists($this, $key))
                    continue;

                $prop = new \ReflectionProperty(get_class($this), $key);
                if ($prop->isStatic()) {
                    continue;
                }

                if (property_exists($this, $key))
                    $this->$key = $value;
            }
        return $this;
    }

    /*
     * Load Object from string
     */
    function loadFromString($str, $seperator = ',', $asign = '=')
    {
        $mm = explode($seperator, $str);
        foreach ($mm as $elm) {
            foreach ($this as $key => $valEmpty) {
                //echo "<br/>\n $key=>$val";
                if (substr($elm, 0, strlen($key . "=")) == $key . "=") {
                    $this->$key = str_replace($key . "=", '', $elm);
                }
            }
        }
    }

    /*
    * Load Array Object from string
    */
    static function loadArrayFromFile($file, $seperator = ',', $asign = '=', $requireStringInLine = null)
    {

        $lines = file($file);
        $cls = get_called_class();
        $obj = new $cls;
        if ($obj instanceof ClassBaseGlx) ;

        $mret = [];
        foreach ($lines as $line) {
            if (!strstr($line, $requireStringInLine)) {
                continue;
            }
            $obj = new $cls;
            if ($obj instanceof ClassBaseGlx) ;
            $obj->loadFromString($line, $seperator, $asign);
            $mret[] = $obj;
        }

        return $mret;
    }

    function loadFromObj($obj, $prefix = "", $debug = 0)
    {
        $this->clearField();
        if (is_object($obj))
            foreach ($obj as $key => $value) {
//                $prop = new \ReflectionProperty(get_class($this), $key);
//                if ($prop->isStatic()) {
//                    continue;
//                }

                if ($key != '_id' && substr($key, 0, 1) == '_')
                    continue;

                if (property_exists($this, $key))
                    $this->$key = $value;
            }

        return $this;
    }

    /** Gồm cả các key ko có trong khai báo class của obj
     * @param $obj
     * @param string $prefix
     * @param int $debug
     * @return $this
     */
    function loadFromObjWithAllKey($obj, $prefix = "", $debug = 0)
    {
        $this->clearField();
        if (is_object($obj))
            foreach ($obj as $key => $value) {
//                $prop = new \ReflectionProperty(get_class($this), $key);
//                if ($prop->isStatic()) {
//                    continue;
//                }

                if ($key != '_id' && substr($key, 0, 1) == '_')
                    continue;

                //if (property_exists($this, $key))
                $this->$key = $value;
            }

        return $this;
    }

    function loadFromJsonString($str)
    {
        $obj = json_decode($str);
        if ($obj) {
            foreach ($obj as $key => $value) {
                $this->$key = $value;
            }
            return $this;
        } else
            return false;
    }

    //Return 1 nếu khác, 0 nếu = nhau
    function compare($obj, $option = "")
    {
        if ($option == 'on_my_not_null_element') {
            foreach (get_class_vars(get_class($this)) as $k => $v) {

                if ($this->$k !== null) {
                    $t1 = trim($this->$k);
                    $t2 = trim($obj->$k);
                    $t1 = str_replace("\n", "", $t1);
                    $t2 = str_replace("\n", "", $t2);
                    $t1 = str_replace("\r", "", $t1);
                    $t2 = str_replace("\r", "", $t2);
                    if (strcmp($t1, $t2) <> 0) {
                        //bl("0xx- $k <br/><br/>'$t1' <> <br/><br/>'$t2'");
                        return 1;
                    }
                }
            }
        } else {
            foreach (get_class_vars(get_class($this)) as $k => $v) {


                $t1 = trim($this->$k);
                $t2 = trim($obj->$k);
                $t1 = str_replace("\n", "", $t1);
                $t2 = str_replace("\n", "", $t2);
                $t1 = str_replace("\r", "", $t1);
                $t2 = str_replace("\r", "", $t2);
                if (strcmp($t1, $t2) <> 0) {
                    // bl("1- $k ".($this->$k)." <> <br/>".$obj->$k);
                    return 1;
                }
            }
        }
        return 0;
    }


    //Export only MY diff field to string
    function compareWithOtherAndExportDiffFields($obj, $option = "")
    {

        $diffDetail = "";
        $diffField = "";
        if ($option == 'on_my_not_null_element') {
            foreach (get_class_vars(get_class($this)) as $k => $v) {

                if ($this->$k !== null) {
                    $t1 = trim($this->$k);
                    $t2 = trim($obj->$k);
                    if (strcmp($t1, $t2) <> 0) {
                        $diffDetail .= "$k:$t1\r\n <-> \r\n$k:$t2\r\n";
                        if (strlen($t1) < 50 && strlen($t1) < 50)
                            $diffField .= " $k, '$t2'->'$t1'";
                        else
                            $diffField .= " $k, ";
                    }
                }
            }
        } else {
            foreach (get_class_vars(get_class($this)) as $k => $v) {

                $t1 = trim($this->$k);
                $t2 = trim($obj->$k);
                if (strcmp($t1, $t2) <> 0) {
                    $diffDetail .= "$k:$t1\r\n <-> \r\n$k:$t2\r\n";
                    if (strlen($t1) < 50 && strlen($t1) < 50)
                        $diffField .= " $k, '$t2'->'$t1'";
                    else
                        $diffField .= " $k, ";
                }
            }
        }
        return array($diffDetail, $diffField);
    }


    function isHaveField($field)
    {

        $mm = $this->getArrField();

        if (in_array($field, $mm))
            return 1;

        return 0;
    }

    /*
     * Xóa hết các properties của đối tượng
     */
    function clearField()
    {
        foreach (get_class_vars(get_class($this)) as $k => $v) {

            if (!property_exists($this, $k))
                continue;

            $prop = new \ReflectionProperty(get_class($this), $k);
            if ($prop->isStatic()) {
                continue;
            }
            if ($k != '_id' && substr($k, 0, 1) == '_')
                continue;

            unset($this->$k);
        }
    }

    function toArray($ignoreEmptyValue = 0)
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


}


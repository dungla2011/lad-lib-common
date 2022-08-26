<?php

namespace LadLib\Common\Database;

use Base\ModelSiteMng;
use Base\ModelUserGroup;

/**
 * Lớp metadata của từng bảng trong db
 */
class MetaOfTableInDb extends MetaTableCommon {

    //$tableName để đưa vào mMetaData[$tableName]
    //Hoặc query lấy ra các Field của table
    public $tableName;

    //DB connection, để lấy ra các field của table
    public $dbConnection;

    /**
     * Mỗi Bảng có một mảng metadata lưu static chỉ cần lấy lần đầu (single ton), key là tên bảng
     * Ví dụ bảng  product, thì $_mMetaData['product'] = <mảng meta data của product>
     * @var array
     */
    public static $_mMetaData = [];

    /**
     * Mỗi đối tượng Meta, cần có tablename, dbconnection, để lấy ra đúng thông tin mảng MetaData
     * @param $tblName
     * @param $dbConnection
     */
    public function setDbInfo($tblName, $dbConnection)
    {
        $this->tableName = $tblName;
        $this->dbConnection = $dbConnection;
    }

    /**
     * Lấy all meta info của một class, hoặc tạo, insert bảng trong db nếu chưa có
     * @param $mAllFieldObj
     * @return MetaTableCommon[]
     */
    function getOrInitMetaTableIfNotHave($mAllFieldObj){

        //Init table in DB
        foreach ($mAllFieldObj AS $fieldObj) {
            $objInsert = new MetaTableCommon();
            $objUpdate = MetaTableCommon::getOneWhereStatic(['field' => $fieldObj]);
            if (!$objUpdate) {
                $objInsert->field = $fieldObj;
                $objInsert->setDefaultMetaTypeField($fieldObj);
                if($objInsert->insert())
                    if (!$objInsert->sname || $objInsert->sname != 's'.$objInsert->_id)
                    {
                        //echo "<br/>\n EmptySname => ";
                        $objInsert->sname = "s" . $objInsert->_id;
                        $objInsert->update();
                    }
            }
            else{
                if (!$objUpdate->sname || $objUpdate->sname != 's'.$objUpdate->_id) {
                    $objUpdate->sname = "s" . $objUpdate->_id;
                    $objUpdate->update();
                }
            }
        }
        $mmAllDb = $this->getArrayWhere();

        //Sort order field, desc:
        usort($mmAllDb,function($first,$second){
            return $first->order_field < $second->order_field;
        });

        $ret = [];
        foreach ($mmAllDb AS $obj){
            if(!in_array($obj->field, $mAllFieldObj))
                continue;

            //Đôi lúc (bị lỗi) có obj không có field
            if(!isset($obj->field))
                continue;

            //Xử lý hard code, không lấy từ DB, mà lấy từ code đã fix các trường:
            foreach ($obj AS $field1=>$val1){
                if(MetaTableCommon::isHardCodeMetaField($field1)){
                    $objHardCode = DbHelper::getMetaObjFromTableName($this->tableName);
                    $objHardCode->setDbInfo($this->tableName, $this->dbConnection);
                    $obj->$field1 = $objHardCode->getHardCodeMetaObj($obj->field)?->$field1;
                    //$obj->join_func = $objHardCode->getHardCodeMetaObj('user_id')?->join_func;
                    //Nếu field1 là DataType:
                    //$obj->dataType = $objHardCode->getHardCodeMetaObj('user_id')?->dataType;
                }
            }

            //Gan tableName de khoi phai Get lai
            $obj->tableName = $this->tableName;
            $ret[$obj->field] = $obj;
        }
//        $mRet = [];
//        foreach ($mmAllDb AS $obj){
//            $mRet = (array)$obj;
//        }
//        return $mRet;
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($ret);
//        echo "</pre>";

//        die();
        return $ret;
    }


    /**
     * Lấy ra toàn bộ metainfo của 1 bảng, sort theo thứ tự order_field
     * @param $table
     * @param $con
     * @return MetaTableCommon[]
     */
    function getMetaDataApi(){
        $con = $this->dbConnection;
        $table = $this->tableName;

        //Lấy ra singleton nếu có
        if(isset(self::$_mMetaData[$table])){

            return self::$_mMetaData[$table];
        }

//        echo "<br/>\n TBL: $table";
        $mmFieldObj = \LadLib\Common\Database\DbHelper::getTableColumns($con, $table);

//
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($mmFieldObj);
//        echo "</pre>";
//        die();

        $tableNameMetaInfo = $table."_meta_info";
        $this->setTableName($tableNameMetaInfo);

        $mm = $this->getOrInitMetaTableIfNotHave($mmFieldObj);

        //Gán singleton
        self::$_mMetaData[$table] = $mm;

        return $mm;
    }

    public function callJoinFunction($funcName, $key = null, $ext = null, $ext1 = null, $ext2 = null, $ext3 = null)
    {
        if (is_callable($funcName))
            return call_user_func($funcName, $key, $ext);
        return null;

    }

    function getShortNameFromField($field)
    {
        $mm = $this->getMetaDataApi();
        if(!$mm)
            return $field;
        $field = $this->getOrginalFieldMultiLange($field);
        if(isset($mm[$field]) && $mm[$field]->sname)
            return $mm[$field]->sname;
        return $field;
    }

    function getNameDescFromField($field){
        $mm = $this->getMetaDataApi();
        $field = $this->getOrginalFieldMultiLange($field);
        if($mm[$field]->name)
            return $mm[$field]->name;
        return $field;
    }

    //Hàm này ko chính xác, nhầm field nếu 2 cái cùng shortName
//    function getFieldFromShortName($shortName)
//    {
//        $mm = $this->getMetaDataApi();
//        if(!$mm)
//            return null;
//
//        foreach ($mm as $m1)
//            foreach ($m1 as $meta => $value)
//                if ("$value" == "$shortName") {
//                    return $m1->field;
//                }
//        return null;
//    }

    //Của class
    function getArrField($getShortName = 0)
    {
        $mm = [];
        foreach (get_class_vars(get_class($this)) as $k => $v) {

            if($k != '_id' && substr($k,0,1) == '_')
                continue;

            if ($getShortName)
                $mm[] = $this->getShortNameFromField($k);
            else
                $mm[] = $k;
        }

        return $mm;
    }

    function getMetaInfoTableName(){
        return $this->getTableName().'_meta_info';
    }

    function isStatusField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if ($this->getDataType($field) == DEF_DATA_TYPE_STATUS)
            return 1;
        return 0;
    }

    public function isShow($field, $gid = null)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if($this->isEditableField($field, $gid))
            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->show_in_index == $gid)
            return 1;
        $mm = explode(',', $mMeta[$field]->show_in_index);
        if (in_array($gid, $mm))
            return 1;
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    function isShowGetOne($field, $gid){
        $field = $this->getOrginalFieldMultiLange($field);
        if($this->isEditableField($field, $gid))
            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->show_get_one == $gid)
            return 1;
        $mm = explode(',', $mMeta[$field]->show_get_one);
        if (in_array($gid, $mm))
            return 1;
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    public function isShowIndexField($field, $gid = null)
    {
        $field = $this->getOrginalFieldMultiLange($field);

//        if($this->isEditableField($field, $gid))
//            return 1;

        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->show_in_index == $gid)
            return 1;
        $mm = explode(',', $mMeta[$field]->show_in_index);
        if (in_array($gid, $mm))
            return 1;
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    /**
     * @param $field
     * @return bool
     */
    function isSelectField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        return $mMeta[$field]->is_select;
    }

    public function getCssClass($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return null;
        return $mMeta[$field]->css_class;
    }

    function getOrginalFieldMultiLange($field){
        if(class_exists('clang'))
            if(clang::$lang != 'vi')
                if($langTail = '_'.clang::$lang){
                    if(substr($field, -3) == $langTail){
                        $field = substr($field, 0,-3);
                    }
                }
        return $field;
    }


    function isEditableFieldGetOne($field, $gid = 0)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if($this->isEditableField($field, $gid))
            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->editable_get_one == $gid)
            return 1;
        $mm = explode(',', $mMeta[$field]->editable);
        if (in_array($gid, $mm))
            return 1;
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    function isEditableField($field, $gid = 0)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($field == '_id' || $field == 'id')
            return 0;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if ($mMeta[$field]->editable == $gid)
            return 1;
        $mm = explode(',', $mMeta[$field]->editable);
        if (in_array($gid, $mm))
            return 1;

        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    function isArrayStringField($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ARRAY_STRING)
            return 1;
        return 0;
    }

    function isLinkType($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_LINK)
            return 1;
        return 0;
    }

    function isHtmlSelectOption($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_HTML_SELECT_OPTION)
            return 1;
        return 0;
    }

    function isFaFontIconType($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_FA_FONT_ICON)
            return 1;
        return 0;
    }

    function isArrayNumberField($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ARRAY_NUMBER)
            return 1;
        return 0;
    }

    function isEnumNumberFieldNumberOrString($field) {
        if($this->isArrayNumberField($field) || $this->isArrayStringField($field))
            return 1;
        return 0;
    }

    function isNumberField($field)
    {
        if($field == '_id' || $field == 'id')
            return 1;

        if($field == 'order_field' || $field == 'parent' || $field == 'parent_path' || $field == 'parent_list' || $field == 'parent_all')
            return 1;

        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_NUMBER)
            return 1;
        if ($this->getDataType($field) == DEF_DATA_TYPE_BOOL_NUMBER)
            return 1;
        //Neu la status thi cung la number
        if ($this->isStatusField($field))
            return 1;

        return 0;
    }

    function getDataType($field)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return null;
        return $mMeta[$field]->dataType;
    }

    /**
     * Lấy Api search info hardcode, không lấy từ DB, vì DB không cho phép user thay đổi cái này
     * @param $field
     * @return MetaOfTableInDb
     */
    public function getHardCodeMetaObj($field){
        return null;
    }

    public function getApiUrl(){
        return $this->api_url;
    }
}

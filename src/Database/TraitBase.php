<?php

namespace LadLib\Common\Database;


trait TraitBase{


    function getShortNameFromField($field)
    {
        $class = get_called_class();
        $mm = $this->getMetaDataApi();

        $field = $this->getOrginalFieldMultiLange($field);
        if ($mm && is_array($mm))
            foreach ($mm as $m1)
                if(is_array($m1))
                    foreach ($m1 as $meta => $value)
                        if ("$value" == "$field") {
                            //echo "<br/>\n $meta=>$value == $field";
                            if (isset($m1['sname']))
                                return $m1['sname'];
                        }
        return $field;
    }

    function getFieldFromShortName($shortName)
    {

        $mm = $this->getMetaDataApi();
        if ($mm)
            foreach ($mm as $m1)
                foreach ($m1 as $meta => $value)
                    if ("$value" == "$shortName") {
                        return $m1['field'];
                    }
        return $shortName;
    }

    //Của class
    function getArrField($getShortName = 0)
    {
        $mm = [];
        foreach (get_class_vars(get_class($this)) as $k => $v) {

//            $prop = new \ReflectionProperty(get_class($this), $k);
//            if ($prop->isStatic()) {
//                continue;
//            }
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

    function createMetaTable()
    {
        //die("xxx1");

        $mf = $this->getArrField();

        //Cần tblset này, để cho API quản trị bảng meta
//        $tblSet = $this->getTableName();

//    echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//    print_r($mf);
//    echo "</pre>";
        $doneBefore = 0;
        $cc = 0;

        foreach ($mf as $field) {
            //Bo qua field ko co trong db
            if ($field[0] == '_' && $field != '_id')
                continue;

            $cc++;
            //Xóa siteid > 0
            $objMeta1 = new MetaDataTable();
            $objMeta1->setDbName($this->getDbName()); //01.09.2020 lấy luôn db name trong hàm, để có thể linh hoạt SET dinmyc, thay vì lấy DB_NAME fix cứng
//            $objMeta1->setTableName($tblSet);

            if ($objMeta1->getOneWhere(['field' => $field,'siteid'=>['$gt'=>1]])) {
                if($objMeta1->siteid > 0){
//                    echo "<br/>\n SID0 : $field, $objMeta1->_id, SID: ".$objMeta1->siteid;
//                    echo "<br/>\n DELETE SID > 0";
                    $objMeta1->delete();
                }
            }

            //continue;

            $objMeta = new MetaDataTable();
            $objMeta->setDbName($this->getDbName()); //01.09.2020 lấy luôn db name trong hàm, để có thể linh hoạt SET dinmyc, thay vì lấy DB_NAME fix cứng
//            $objMeta->setTableName($tblSet);

            if ($objMeta->getOneWhere(['field' => $field], ['_ignore_siteid'=>1])) {

                //if (!$objMeta->sname != $objMeta->_id)
                if (!$objMeta->sname || $objMeta->sname != 's'.$objMeta->_id)
                {
                    //echo "<br/>\n EmptySname => ";
                    $objMeta->sname = "s" . $objMeta->_id;
                    $objMeta->update();
                }
                continue;
            }
            else{
                //echo "<br/>\n NOT FOUND $field " .$objMeta->getTableName() . " / " . $this->getDbName();
            }

            $objMeta->field = $field;

            //////////////////////////////////////////////////////////
            //Tạo sẵn default:
            $arr1 = ['id', 'name', 'status'];
            if (in_array($field, $arr1)) {
                $objMeta->show_in_index = "1,2";
            }

            if($field == 'name'){
                //Mặc định name cho lên top
                $objMeta->order_field = 10;
            }

            $arr1 = ['parent', 'id', 'name', 'status' , 'createdAt'];
            if (in_array($field, $arr1)) {
                $objMeta->searchable = "1,2";
                $objMeta->sortable = "1,2";
            }

            $arr1 = ['name', 'status', 'summary', 'summary0', 'content'];
            if (in_array($field, $arr1)) {
                $objMeta->editable = "1,2";
                $objMeta->editable_get_one = "1,2";
                $objMeta->show_get_one = "1,2";
            }

            $arr1 = ['id', 'userid', 'orders','parent', 'parent_all', 'parent_list', 'parent_path'];
            if (in_array($field, $arr1))
                $objMeta->dataType = DEF_DATA_TYPE_NUMBER;

            $arr1 = ['content'];
            if (in_array($field, $arr1))
                $objMeta->dataType = DEF_DATA_TYPE_RICH_TEXT;

            $arr1 = ['name'];
            if (in_array($field, $arr1))
                $objMeta->dataType = DEF_DATA_TYPE_TEXT_STRING;

            $arr1 = ['summary', 'summary0', 'summary1', 'summary2'];
            if (in_array($field, $arr1))
                $objMeta->dataType = DEF_DATA_TYPE_TEXT_AREA;

            $arr1 = ['status'];
            if (in_array($field, $arr1))
                $objMeta->dataType = DEF_DATA_TYPE_STATUS;

            //Ảnh sẽ trên 1 bảng riêng, nên bỏ qua mảng trong Field
//            $arr1 = ['image_list'];
//            if(\ClassSetting::$useMongoDb)
//                if (in_array($field, $arr1))
//                    $objMeta->dataType = DEF_DATA_TYPE_MONGO_BSON_ARRAY;

            //Bỏ qua siteid với các bảng meta:
            unset($objMeta->siteid);
            unset($objMeta->_id);

            echo "<br/>\n Insert meta obj";

//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($objMeta);
//            echo "</pre>";

            $objMeta->insert(1, ['_ignore_siteid'=>1]);

            $objMeta->sname = "s" . $objMeta->_id;

            $objMeta->update();
        }
    }

    function isStatusField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_STATUS)
            return 1;

        $class = get_called_class();
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        foreach ($mMeta as $mField) {

            if (isset($mField['field']) && $mField['field'] == $field) {
                if (isset($mField['is_status']) && $mField['is_status'] > 0)
                    return 1;
                break;
            }
        }
        return 0;
    }

    /**
     * @param $field
     * @return func map name
     */
    function isSelectField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        $class = get_called_class();
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        foreach ($mMeta as $mField) {
            if (isset($mField['field']) && $mField['field'] == $field) {
                if (isset($mField['is_select']) && $mField['is_select'])
                    return $mField['is_select'];
                break;
            }
        }
        return 0;
    }


    function getOrginalFieldMultiLange($field){
        if(class_exists('clang'))
        if(clang::$lang != 'vi')
            if($langTail = '_'.clang::$lang){
                if(substr($field, -3) == $langTail){
                    return $field = substr($field, 0,-3);
                }
            }
        return $field;
    }

    function isEnumStringField($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ENUM_STRING)
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

    function isEnumNumberField($field) {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ENUM_NUMBER)
            return 1;
        return 0;
    }

    function isEnumNumberFieldNumberOrString($field) {
        if($this->isEnumNumberField($field) || $this->isEnumStringField($field))
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

        $class = get_called_class();
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        foreach ($mMeta as $mField) {
            if (isset($mField['field']) && $mField['field'] == $field) {
                if (isset($mField['isNumber']))
                    if ($mField['isNumber'])
                        return 1;
                break;
            }
        }

        return 0;
    }

    function getMetaDataApi($gid = null) {

        $class = get_called_class();
        if ($class instanceof ModelBase) ;

        //if($class::$_metaData){
        if ($this->getMetaDataStatic()) {

            $mret = $this->getMetaDataStatic();

            if ($mret['__class_name'] != get_class($this)) {
                $cl1 = $mret['__class_name'];
                $cl2 = get_class($this);
                loi("Error: not my meta api? $cl1/$cl2 , GUIDE: may be need define:  public static \$_metaData = '';");
            }

            // bỏ classname sau khi check
            unset($mret['__class_name']);

            return $mret;
            //return $class::$_metaData;
        }

        //Đối tượng meta class
        $metaObj = new MetaDataTable();
//        $metaObj = new $class();
//        if($metaObj instanceof modelMetaDataTable);

//        if(\ClassSetting::$siteId == 35)
//            $metaObj->setDbName($this->getDbName().".siteid_35");
//        else

        $metaObj->setDbName($this->getDbName());
        $metaObj->setTableName($this->getMetaInfoTableName());

        //$mm = $metaObj->getArrWhere([]);
        $mm = $metaObj->getArrayWhere([], ['sort'=>['order_field'=>-1]]);

        if (!$mm) {

            //Nếu không có thì tạo:
            $this->createMetaTable();

            //Sẽ báo lỗi 1 lần đầu, rồi lần sau ko bị báo lỗi nữa, vì đã tạo ở trên
            loi("Not found meta info/ " . $this->getDbName() . " / " . $this->getMetaInfoTableName());
        }


        $mm1 = [];
        $mpro = get_class_vars($class);

        foreach ($mm as $obj)
            foreach ($mpro as $field => $val) {

                if ($obj->field == $field) {

                    $mfieldNeedInherit = ['show_in_index', 'show_get_one',
                        'get_not_show','searchable','sortable','editable',
                        'editable_get_one','is_select'];

                    $mm1[] = (array)$obj;
                }
            }

        if (!isset($mm1['__class_name']))
            $mm1['__class_name'] = get_class($this);

        return $class::$_metaData = $mm1;

    }


    function getDataType($field)
    {
        $class = get_called_class();
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        foreach ($mMeta as $mField) {
            if (isset($mField['field']) && $mField['field'] == $field && isset($mField['dataType'])) {
                return $mField['dataType'];
            }
        }
        return null;
    }


}

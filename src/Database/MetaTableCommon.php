<?php

namespace LadLib\Common\Database;


use Doctrine\DBAL\Exception;
use Illuminate\Support\Str;
use LadLib\Common\UrlHelper1;

/**
 * Lớp của riêng đối tượng metadata
 */
class MetaTableCommon extends SqlDbBase
//class MetaTableCommon extends MsDbBase
{

    public static $_tableName = 'model_meta_infos';
    //var $tableName;

    var $id;
    var $table_name_model;
    var $field;
    var $sname;     //Name Map to hide for search, filter, sort...
    var $name;
    var $order_field;
    var $full_desc;


    var $created_at;
    var $updated_at;

    //var $gid;
    var $show_in_index;     //Lấy về và show trên list
    var $editable;

    var $get_not_show; //Get data về nhưng ko show lên, để 1 số case, ví dụ LinkToView ...

    var $searchable;
    var $sortable;

    var $show_get_one;   //Lấy về và show trên getOne, vì trên list đôi khi sẽ ko show ra, ví dụ như content bài viết
    var $editable_get_one; //Edit allow in get one, khi mà không cho edit trong Index

    //So sanh readOnly với $is_hiden_input: indden thì vẫn cho post lên, cón readonly thì disable ko cho post lên
    var $readOnly; //Edit but also allow readonly, để cho trường  hơp như tổng điểm trung bình, có joinfunc, hoặc trường hợp ko cho Input vô

    var $limit_user_edit; //Giới hạn edit theo user tạo ra, admin group thì ko bị ảnh hưởng

    var $limit_dev_edit; //Chỉ có email dev mới được edit trường này , ví dụ sname của blockUI, user khác đổi sẽ gây lỗi

    var $insertable;

   // var $api_url; //là api để CURD - query dữ liệu all, không phải cho từng trường, mà cho cả bảng

    var $join_func; //Hàm để join lấy data MAP, vd: userid cần lấy email

    var $join_relation_func; //Hàm join kiểu model laravel, pivottable, hasMany, belongToMany...

    var $join_api; //Hàm để join từ API, lấy data MAP, vd: userid cần lấy email
    var $join_api_field; //Trường mà api sẽ search, để lấy ra ID

    //- Ví dụ opt_field = 3, autocoplete khi nhập phone number vào bảng xe đi, thì tìm phone đó trong bảng user xem có chưa
    //Nếu chưa có thì vẫn cho input số phone vào db
    var $opt_field; //các option dành cho field này, để xử lý các trường hợp riêng

//    var $admin_url;
    //Hàm để join update/insert giá trị field sang bảng khác
    //Ví dụ, 1 vps có nhiều IP (trong bảng IP), khi update/insert gán (các) IP vào 1 VPS, thì bảng IP sẽ được update
    //mỗi IP vào id của VPS (mỗi ip có 1 trường VPS ID)
    // Ví dụ một Hóa đơn có nhiều ProductID, khi update các ProductID vào hóa đơn, thì bảng Product
    //var $func_foreign_key_insert_update;

    //Chuyển về dataType, bỏ trường này
//    var $is_status;
    var $is_select; //Đưa join function vào đây, trả lại mảng các giá trị cho select option [number=>string, ....]
//    var $is_textarea;
//    var $isNumber;
//    var $isText;

    var $dataType;
    var $data_type_in_db;
    var $css_class;
    var $css_cell_class;
    var $css;
    var $width_col;

    //Hàm join của model trên trường hiện tại, có thể là 1 trường fake
    var $join_func_model;

//    var $link_to_view;
//    var $link_to_edit;
//    var $primary;

    var $is_multilangg;

    //So sanh readOnly với $is_hiden_input: indden thì vẫn cho post lên, cón readonly thì disable ko cho post lên
    var $is_hiden_input; //Có lúc input sẽ cần hidden, vd khi không cho user update, mà update qua 1 joinfunction có js



    function getDbName()
    {
        return parent::getDbName(); // TODO: Change the autogenerated stub
    }

    function getTableName()
    {
        return static::$_tableName; // TODO: Change the autogenerated stub
    }


    //Các trường không tính trong changelog, ví dụ count_view của news sẽ ko đưa vào changelog, vì thế thì quá nhiều change...
    function getDisableChangeLogField(){
        return ['count_view'];
    }
    /*
     * 2021-01-05
     *  Cái này của riêng Metadata
     */
    public function getMetaDataApi()
    {
        $ret = [
            ['field' => 'isNumber', 'is_status' => 1],
            ['field' => 'isText', 'is_status' => 1],
            //['field'=>'is_select' , 'is_status'=>1],
            ['field' => 'is_textarea', 'is_status' => 1],
            ['field' => 'is_status', 'is_status' => 1],
            ['field' => 'insertable', 'is_status' => 1],
            ['field' => 'primary', 'is_status' => 1],
//            ['field' => 'get_not_show', 'is_status' => 1],
            ['field' => 'dataType', 'is_select' => 1],
            ['field' => 'limit_user_edit', 'is_status' => 1],
            ['field' => 'limit_dev_edit', 'is_status' => 1],
            ['field' => 'readOnly', 'is_status' => 1],
            ['field' => 'is_multilangg', 'is_status' => 1],
            ['field' => 'is_hiden_input', 'is_status' => 1],
        ];
        return $ret;
    }
    function getDataType($field)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        foreach ($mMeta as $mField) {
            if (isset($mField['field']) && $mField['field'] == $field && isset($mField['dataType'])) {
                return $mField['dataType'];
            }
        }
        return null;
    }

    function isArrayNumberField($field) {
        if ($this->getDataType($field) == DEF_DATA_TYPE_ARRAY_NUMBER)
            return 1;
        return 0;
    }

    function isPassword($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_PASSWORD)
            return 1;
        return 0;
    }

    //Nếu trường khác ID mã hóa
    function isRandNumberField($field)
    {
        return null;
    }

    function isNumberField($field)
    {
        $mFieldNumber = ['_id', 'id', 'parent_id'];

        if(in_array($field, $mFieldNumber))
            return 1;

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

//    public function callJoinFunction($funcName, $key = null, $ext = null, $ext1 = null, $ext2 = null, $ext3 = null)
//    {
//        if (is_callable($funcName)) {
//            return call_user_func($funcName, $key, $ext);
//        }
//    }

    public function isValidate($option = null, $param = null)
    {
        return 1;
    }

    public function getCssStr($field)
    {
        if ($field == 'css') {
            return '; min-width: 100px';
        }

        if ($field == 'name') {
            return '; min-width: 100px';
        }

        return null;

    }



    function isSelectField($field) {

        if ($field == 'dataType') {
            // Mặc định là của Meta table
            $ret = [
                DEF_DATA_TYPE_STATUS => '[Status]',
                DEF_DATA_TYPE_NUMBER => '[Number]',
                DEF_DATA_TYPE_BOOL_NUMBER => '[Bool Number]',
                DEF_DATA_TYPE_PASSWORD => '[Password]',
                DEF_DATA_TYPE_TEXT_STRING => '[String]',
                DEF_DATA_TYPE_TEXT_AREA => '[TextArea]',
                DEF_DATA_TYPE_RICH_TEXT => '[RichText]',
                DEF_DATA_TYPE_OBJECT => '[Object]',
                DEF_DATA_TYPE_ARRAY_NUMBER => '[ArrayNumber]',
                DEF_DATA_TYPE_ARRAY_STRING => '[ArrayString]',
                DEF_DATA_TYPE_ARRAY_JOIN_TABLE => '[EnumJoinTable]',
                DEF_DATA_TYPE_MONGO_BSON_ARRAY => '[MongoArray]',
                DEF_DATA_TYPE_IS_ERROR_STATUS => '[Error-Status]',
                DEF_DATA_TYPE_IS_SUCCESS_STATUS => '[Success-Status]',
                DEF_DATA_TYPE_IS_LINK => '[IsLink]',
                DEF_DATA_TYPE_IS_COLOR_PICKER => '[IsColorPicker]',
                DEF_DATA_TYPE_IS_DATE => '[DateString]',
                DEF_DATA_TYPE_IS_DATE_TIME => '[Date Time String]',
                DEF_DATA_TYPE_IS_TIME => '[Time HH:MM:SS]',
                DEF_DATA_TYPE_IS_FA_FONT_ICON => '[FA_FONT_ICON]',
                DEF_DATA_TYPE_HTML_SELECT_OPTION => '[Html Select Option]',
                DEF_DATA_TYPE_TREE_SELECT => '[Tree Select]',
                DEF_DATA_TYPE_TREE_MULTI_SELECT => '[Tree Select Multi]',
                DEF_DATA_TYPE_IS_ONE_IMAGE_BROWSE => '[SelectImage]',
                DEF_DATA_TYPE_IS_MULTI_IMAGE_BROWSE => '[MultiImage]',

            ];
            return $ret;
        }
        return 0;

    }

    function getFullDescField($field, $fieldInTable = null)
    {
        if($fieldInTable)
            $fieldInTable = "[$fieldInTable]";

        if ($field == 'func_foreign_key_insert_update') {
            return $field;
        }

        if ($field == 'dataType') {
            return $field;
        }

        if ($field == 'created_at') {
            return $field;
        }

        if ($field == 'updated_at') {
            return $field;
        }

        if ($field == 'order_field') {
            return "Thứ tự của trường $fieldInTable trong Bảng index";
        }

        if ($field == 'searchable') {
            return $field;
        }

        if ($field == 'sortable') {

        }

        if ($field == 'link_to_view') {

        }
        if ($field == 'link_to_edit') {

        }

        if ($field == 'show_in_index') {

        }
        if ($field == 'show_get_one') {

        }

        if ($field == 'name') {

        }

        if ($field == 'full_desc') {

        }

        if ($field == 'sname') {

        }
        if ($field == 'get_not_show') {

        }
        if ($field == 'editable_get_one') {

        }
        if ($field == 'editable') {

        }

        if ($field == 'insertable') {

        }

        if ($field == 'limit_user_edit') {
            return "Chỉ cho phép user tạo ra bản ghi này có thể edit Field này $fieldInTable";
        }
        if ($field == 'limit_dev_edit') {
            return "Chỉ tài khoản DEV mới có thể edit Field này $fieldInTable";
        }

        if ($field == 'readOnly') {

        }

        if ($field == 'is_multilangg') {

        }

        if ($field == 'is_select') {

        }

        if ($field == 'admin_url') {

        }

        if ($field == 'join_func') {

        }
        if ($field == 'join_api') {

        }

        if ($field == 'join_api_field') {

        }

        if ($field == 'is_hiden_input') {

        }
        if ($field == 'css_class') {

        }

        if ($field == 'css_cell_class') {

        }
        if ($field == 'css_cell') {

        }

        if ($field == 'field') {

        }

        if ($field == '_id') {

        }
        return $field;
    }

    function getDescOfField($field)
    {
        if ($field == 'func_foreign_key_insert_update') {
            return "Func foreign key";
        }

        if ($field == 'dataType') {
            return "Data Type";
        }

        if ($field == 'created_at') {
            return "Ngày tạo";
        }

        if ($field == 'updated_at') {
            return "Ngày sửa";
        }

        if ($field == 'order_field') {
            return "Order field";
        }

        if ($field == 'searchable') {
            return "Search Able";
        }

        if ($field == 'sortable') {
            return "Sort Able";
        }

        if ($field == 'link_to_view') {
            return "Link View";
        }
        if ($field == 'link_to_edit') {
            return "Link Edit";
        }

        if ($field == 'show_in_index') {
            return "Show Index";
        }
        if ($field == 'show_get_one') {
            return "Show Get One";
        }

        if ($field == 'name') {
            return "Desc Name";
        }

        if ($field == 'full_desc') {
            return "Full Desc";
        }

        if ($field == 'data_type_in_db') {
            return "Data Type DB";
        }

        if ($field == 'sname') {
            return "Short Name";
        }
        if ($field == 'get_not_show') {
            return "Get not show";
        }
        if ($field == 'editable_get_one') {
            return "Edit get one";
        }
        if ($field == 'editable') {
            return "Edit allow index";
        }

        if ($field == 'join_relation_func') {
            return "Join Relate";
        }

        if ($field == 'insertable') {
            return "Insert allow";
        }

        if ($field == 'limit_user_edit') {
            return "Limit other edit";
        }
        if ($field == 'limit_dev_edit') {
            return "Dev Edit only";
        }

        if ($field == 'readOnly') {
            return "Read only";
        }

        if ($field == 'is_multilangg') {
            return "Multi Lang";
        }

        if ($field == 'is_select') {
            return "Select Join Func";
        }

        if ($field == 'admin_url') {
            return "Admin Url";
        }

        if ($field == 'join_func') {
            return "Join Func";
        }
        if ($field == 'join_api') {
            return "Join Api Search";
        }

        if ($field == 'join_api_field') {
            return "Api Field Search";
        }

        if ($field == 'is_hiden_input') {
            return "Hiden Input";
        }
        if ($field == 'css_class') {
            return "CSS Class";
        }

        if ($field == 'css_cell_class') {
            return "CSS Cell Class";
        }
        if ($field == 'css_cell') {
            return "CSS Cell";
        }

        if ($field == 'field') {
            return "Field";
        }

        if ($field == '_id') {
            return "ID";
        }
        return $field;
    }

    public function isEditableField($field, $gid = 0)
    {
        //Với metatable, thì các trường sau ko được edit
        if($field == '_id' || $field == 'id' || $field == 'join_func' || $field == 'data_type_in_db')
            return 0;

        if ($field == 'field')
            return 0;
        if ($field == 'sname')
            return 0;
        return 1;
    }

    public function isShowIndexField($field, $gid = null)
    {
        $mFieldHide = ['meta_all_set_api', 'table_name_model', 'created_at', 'updated_at'];
        if(in_array($field, $mFieldHide)){
            return 0;
        }
        return 1;
    }

    public function isSortAbleField($field, $gid)
    {
        return 1;
    }

    public function isSearchAbleField($field, $gid)
    {
        return 1;
    }

    public function isStatusField($field)
    {
        $mMeta = $this->getMetaDataApi();
        foreach ($mMeta as $mField) {
            if ($mField['field'] == $field) {
                if (isset($mField['is_status']) && $mField['is_status'])
                    return 1;
                break;
            }
        }
        return 0;
    }

    public function getCssClass($field)
    {
        return 'not_define_class';
    }


    /** Một số tên trường có type mặc định
     * @param $field
     */
    function setDefaultMetaTypeField($field){

        //////////////////////////////////////////////////////////
        //Tạo sẵn default:
        $arr1 = ['id', 'name', 'status', 'created_at' , 'user_id',];
        if (in_array($field, $arr1)) {
            $this->show_in_index = "1,2";
        }

        if($field == 'id'){
            //Mặc định name cho lên top
            $this->order_field = 123;
        }

        if($field == 'status' || $field == 'enable'){
            //Mặc định name cho lên top
            $this->order_field = 11;
        }

        if($field == 'name'){
            //Mặc định name cho lên top
            $this->order_field = 10;
        }

        $arr1 = ['parent_id', 'id', 'name', 'status' , 'user_id', 'createdAt', 'created_at'];
        if (in_array($field, $arr1)) {
            $this->searchable = "1,2";
            $this->sortable = "1,2";
        }

        $arr1 = ['id', 'name', 'status', 'summary', 'user_id', 'content', 'created_at'];
        if (in_array($field, $arr1)) {
            $this->show_get_one = "1,2";
        }

        $arr1 = ['name', 'status', 'summary', 'user_id', 'content'];
        if (in_array($field, $arr1)) {
            $this->editable_get_one = "1,2";
        }

        $arr1 = ['name', 'status'];
        if (in_array($field, $arr1)) {
            $this->editable = "1,2";
        }


        $arr1 = ['id', 'userid', 'user_id', 'status', 'orders','price', 'price_org', 'parent_id', 'product_id', 'category_id' ];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_NUMBER;

        if ($field == 'parent_id')
            $this->dataType = DEF_DATA_TYPE_TREE_SELECT;

        $arr1 = ['content'];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_RICH_TEXT;

        //ảnh sẽ là các id, mỗi id sẽ ở bên bảng ảnh riêng
        $arr1 = ['image_list','tags_list'];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_ARRAY_NUMBER;

        $arr1 = ['name', 'name_en'];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_TEXT_STRING;

        $arr1 = ['summary', 'summary0', 'summary1', 'summary2',
            'summary_en', 'logs'];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_TEXT_AREA;

        $arr1 = ['status'];
        if (in_array($field, $arr1))
            $this->dataType = DEF_DATA_TYPE_STATUS;

        //Bỏ qua siteid với các bảng meta:
        //unset($this->siteid);
        //unset($this->_id);
    }

    /**
     * Một số field không cho phép user sửa trong db meta, sẽ fix ở code của class Meta
     * Như các class tương ứng: Users_Meta, Demo_Meta
     * @param $fieldMeta
     * @return bool
     */
    public static function isHardCodeMetaField($fieldMeta){
        $hardCode =  ['join_relation_func', 'join_func_model', 'join_func',
            'join_api',
            'opt_field' , 'join_api_field', 'dataType', 'is_select', 'table_name_model'];
        if(in_array($fieldMeta, $hardCode))
            return true;
        return false;
    }

    /**
     * Kiểm tra URL table grid, xem có filter ko, nếu có thì show Clear Button
     * @return int
     */
    public static function checkToShowClearFilter(){
        $mParam = UrlHelper1::getArrParamUrl(UrlHelper1::getFullUrl());
        foreach ($mParam AS $k=>$v)
            if(str_starts_with($k, DEF_PREFIX_SEARCH_URL_PARAM_GLX))
                return 1;
        return 0;
    }

    /**
     * @param $tableName
     * @return MetaOfTableInDb
     */
    public static function getMetaObjFromTableName($tableName){


        $modelName = self::getModelNameFromTableName($tableName);

        if($tableName[0] == '_')
            $modelName = lcfirst($modelName);

        $cls = "\\App\\Models\\" . $modelName ."_Meta";
        if(!class_exists($cls)){
            //Có cần thiết báo lỗi ở đây không?
//            loi2("Not found class: $cls (Table: $tableName)");
//            die("Not found class: $tableName/ $cls");
            return null;
        }
        $obj = new $cls;
        if($obj instanceof MetaOfTableInDb);
        $obj->table_name_model = $tableName;
        return $obj;
    }


    /**
     * @param $tableName
     * @return \App\Models\ModelGlxBase|mixed|null
    echo Str::studly(Str::singular("demo_tables"));
    echo Str::snake(Str::pluralStudly("DemoTable"));
     */
    public static function getModelFromTableName($tableName){
        $name = self::getModelNameFromTableName($tableName);
        $model =  '\\App\\Models\\' . $name;
        if(!class_exists($model))
            return null;
        $model = new $model;
        return $model;
    }

    /**
     * Get by the way laravel set tablename automatically with ModelName
     */
    public static function getModelNameFromTableName($tableName){
        $name = Str::studly(Str::singular($tableName));
        return $name;
    }

    /**
     * Get by the way laravel set tablename automatically with ModelName
     * @param $name
     * @return string
     */
    public static function getTableNameFromModelName($name){
        return Str::snake(Str::pluralStudly($name));
    }

    public static function getTableNameFromThisMetaClass(){

        $modelName = substr(basename(str_replace("\\",'/',get_called_class())),0,-5);
        //die("xxxx $modelName ..." . self::getTableNameFromModelName($modelName));
        return self::getTableNameFromModelName($modelName);
        //return Str::snake(Str::pluralStudly($name));
    }

}

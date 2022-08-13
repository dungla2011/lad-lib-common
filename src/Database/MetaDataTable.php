<?php

namespace LadLib\Common\Database;

class MetaDataTable extends MongoDbBase
{
    public static $_tableName = '';

    //var $tableName;

    var $_id;
    var $field;
    var $sname;     //Name Map to hide for search, filter, sort...
    var $name;
    var $order_field;
    var $full_desc;


    //var $gid;
    var $show_in_index;     //Lấy về và show trên list
    var $show_get_one;   //Lấy về và show trên getOne, vì trên list đôi khi sẽ ko show ra, ví dụ như content bài viết
    var $get_not_show; //Get data về nhưng ko show lên, để 1 số case, ví dụ LinkToView ...

    var $searchable;
    var $sortable;
    var $editable;
    var $editable_get_one; //Edit allow in get one, khi mà không cho edit trong Index

    //So sanh readOnly với $is_hiden_input: indden thì vẫn cho post lên, cón readonly thì disable ko cho post lên
    var $readOnly; //Edit but also allow readonly, để cho trường  hơp như tổng điểm trung bình, có joinfunc, hoặc trường hợp ko cho Input vô

    var $limit_user_edit; //Giới hạn edit theo user tạo ra, admin group thì ko bị ảnh hưởng

    var $limit_dev_edit; //Chỉ có email dev mới được edit trường này , ví dụ sname của blockUI, user khác đổi sẽ gây lỗi

    var $insertable;

    var $join_func; //Hàm để join lấy data MAP, vd: userid cần lấy email

    var $join_api; //Hàm để join từ API, lấy data MAP, vd: userid cần lấy email

    var $admin_url;
    //Hàm để join update/insert giá trị field sang bảng khác
    //Ví dụ, 1 vps có nhiều IP (trong bảng IP), khi update/insert gán (các) IP vào 1 VPS, thì bảng IP sẽ được update
    //mỗi IP vào id của VPS (mỗi ip có 1 trường VPS ID)
    var $func_foreign_key_insert_update;

//    var $is_status;
    var $is_select;
//    var $is_textarea;
//    var $isNumber;
//    var $isText;

    var $dataType;

    var $css_class;
    var $css;
    var $css_cell;


    var $link_to_view;
    var $link_to_edit;
    var $primary;

    var $is_multilangg;

    //So sanh readOnly với $is_hiden_input: indden thì vẫn cho post lên, cón readonly thì disable ko cho post lên
    var $is_hiden_input; //Có lúc input sẽ cần hidden, vd khi không cho user update, mà update qua 1 joinfunction có js


    /**
     * Lấy ra toàn bộ metainfo của 1 bảng, sort theo thứ tự order_field
     * @param $table
     * @param $con
     * @return MetaDataTable[]
     */
    static function getMetaDataOfTable($table, $con){
        $mmFieldObj = \LadLib\Common\Database\DbHelper::getTableColumns($con, $table);
        $tableNameMetaInfo = $table."_meta_info";
        $objMeta = new MetaDataTable();
        $objMeta->setTableName($tableNameMetaInfo);
        $mm = $objMeta->getOrInitMetaTableIfNotHave($mmFieldObj);
        //Sort order field, desc:
        usort($mm,function($first,$second){
            return $first->order_field < $second->order_field;
        });
        return $mm;
    }

    /*
     * 2021-01-05
     *  Cái này của riêng Metadata
     */
    public function getMetaDataApi($gid = null)
    {
        $ret = [
            ['field' => 'isNumber', 'is_status' => 1],
            ['field' => 'isText', 'is_status' => 1],
            //['field'=>'is_select' , 'is_status'=>1],
            ['field' => 'is_textarea', 'is_status' => 1],
            ['field' => 'is_status', 'is_status' => 1],
            ['field' => 'insertable', 'is_status' => 1],
            ['field' => 'primary', 'is_status' => 1],
            //['field' => 'get_not_show', 'is_status' => 1],
            ['field' => 'dataType', 'is_select' => 1],
            ['field' => 'limit_user_edit', 'is_status' => 1],
            ['field' => 'limit_dev_edit', 'is_status' => 1],
            ['field' => 'readOnly', 'is_status' => 1],
            ['field' => 'is_multilangg', 'is_status' => 1],
            ['field' => 'is_hiden_input', 'is_status' => 1],
        ];
        return $ret;
    }

    /**
     * Lấy all meta info của một class, hoặc tạo, insert bảng trong db nếu chưa có
     * @param $mAllFieldObj
     * @return MetaDataTable[]
     */
    function getOrInitMetaTableIfNotHave($mAllFieldObj){

        $mmAllDb = $this->getArrayWhere();
        if (!$mmAllDb) {
            //Init table in DB
            foreach ($mAllFieldObj AS $fieldObj) {
                $objInsert = new MetaDataTable();
                if (!MetaDataTable::getOneWhereStatic(['field' => $fieldObj])) {
                    $objInsert->field = $fieldObj;
                    if($objInsert->insert())
                    if (!$objInsert->sname || $objInsert->sname != 's'.$objInsert->_id)
                    {
                        //echo "<br/>\n EmptySname => ";
                        $objInsert->sname = "s" . $objInsert->_id;
                        $objInsert->update();
                    }
                }
            }
            $mmAllDb = $this->getArrayWhere();
        }

        return $mmAllDb;
    }


    public function callJoinFunction($funcName, $key = null, $ext = null, $ext1 = null, $ext2 = null, $ext3 = null)
    {
        if (is_callable($funcName)) {
            return call_user_func($funcName, $key, $ext);
        }

        // Mặc định là của Meta table
        return [
            DEF_DATA_TYPE_STATUS => '[Status]',
            DEF_DATA_TYPE_NUMBER => '[Number]',
            DEF_DATA_TYPE_BOOL_NUMBER => '[Bool Number]',
            DEF_DATA_TYPE_PASSWORD => '[Password]',
            DEF_DATA_TYPE_TEXT_STRING => '[String]',
            DEF_DATA_TYPE_TEXT_AREA => '[TextArea]',
            DEF_DATA_TYPE_RICH_TEXT => '[RichText]',
            DEF_DATA_TYPE_OBJECT => '[Object]',
            DEF_DATA_TYPE_ENUM_NUMBER => '[Enum Number]',
            DEF_DATA_TYPE_ENUM_STRING => '[Enum String]',
            DEF_DATA_TYPE_ENUM_JOIN_TABLE => '[EnumJoinTable]',
            DEF_DATA_TYPE_MONGO_BSON_ARRAY => '[MongoArray]',
            DEF_DATA_TYPE_IS_ERROR_STATUS => '[Error-Status]',
            DEF_DATA_TYPE_IS_SUCCESS_STATUS => '[Success-Status]',
            DEF_DATA_TYPE_IS_LINK => '[IsLink]',
            DEF_DATA_TYPE_IS_COLOR_PICKER => '[IsColorPicker]',
            DEF_DATA_TYPE_IS_IMAGE_BROWSE => '[SelectImage]',
            DEF_DATA_TYPE_IS_DATE => '[Date String]',
            DEF_DATA_TYPE_IS_DATE_TIME => '[Date Time String]',
            DEF_DATA_TYPE_IS_TIME => '[Time HH:MM:SS]',
            DEF_DATA_TYPE_IS_FA_FONT_ICON => '[FA_FONT_ICON]',
            DEF_DATA_TYPE_HTML_SELECT_OPTION => '[HtmlSelectOption]',

        ];
    }

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

        return parent::getCssStr($field);

    }

    function isSelectField($field)
    {
        if ($field == 'dataType')
            return 1;
        return 0;
    }

    function getNameDescFromField($field)
    {
        if ($field == 'func_foreign_key_insert_update') {
            return "Func foreign key";
        }

        if ($field == 'order_field') {
            return "Thứ tự";
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
            return "Edit allow";
        }

        if ($field == 'insertable') {
            return "Insert allow";
        }

        if ($field == 'limit_user_edit') {
            return "Limit edit";
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
            return "Is Select";
        }

        if ($field == 'admin_url') {
            return "Admin Url";
        }

        if ($field == 'join_func') {
            return "Join Func";
        }
        if ($field == 'join_api') {
            return "Join Api";
        }

        if ($field == 'is_hiden_input') {
            return "Hiden Input";
        }
        if ($field == 'css_class') {
            return "CSS Class";
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



        return parent::getNameDescFromField($field); // TODO: Change the autogenerated stub
    }

    public function isEditableField($field, $gid)
    {
        //Với metatable, thì các trường sau ko được edit
        if($field == '_id' || $field == 'id')
            return 0;

        if ($field == 'field')
            return 0;
        if ($field == 'sname')
            return 0;
        return 1;
    }

    public function isShowIndexField($field, $gid)
    {
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
}

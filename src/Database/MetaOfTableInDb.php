<?php

namespace LadLib\Common\Database;

use App\Components\ClassRandId2;
use App\Components\clsParamRequestEx;
use App\Components\Helper1;
use App\Models\DemoFolderTbl;
use App\Models\FolderFile;
use App\Models\GiaPha;
use App\Models\ModelGlxBase;
use App\Models\ModelMetaInfo;
use App\Models\SiteMng;
use App\Support\HTMLPurifierSupport;
use Base\ModelCloudFile;
use Base\ModelSiteMng;
use Base\ModelUserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LadLib\Common\UrlHelper1;
use LadLib\Laravel\Database\DbHelperLaravel;
use function Clue\StreamFilter\fun;
use function Clue\StreamFilter\remove;
use function GuzzleHttp\Psr7\parse_request;
use function MongoDB\select_server_for_aggregate_write_stage;

/**
 * Lớp metadata của từng bảng trong db
 *
 * Các MetaClass tương ứng với Class trong db, sẽ có thể thêm 02 loại hàm mở rộng, có gạch chân ở đầu
 * 1 là hàm có trường trong db, ví dụ user_id, thì hàm sẽ là _user_id() , sẽ trả lại thông tin mở rộng cho user_id. hoặc tag_id, có hàm là _tag_id() ...
 * 2 là hàm không có trường tương ứng trong db, ví dụ hàm _test123_() , dùng để lấy thêm thông tin mở rộng linh hoạt nào đó về bản ghi, show lên Web hoặc API...
 */
class MetaOfTableInDb extends MetaTableCommon
{

    /**
     * @var null Tương ứng với mỗi module trên URL request, sẽ có URL là admin, hay member tương ứng
     */
    protected static $api_url_admin = null;
    protected static $api_url_member = null;


    /**
     * @var null Tương ứng với mỗi module trên URL request, sẽ có URL là admin, hay member tương ứng
     */
    protected static $web_url_admin = null;
    protected static $web_url_member = null;

    public static $titleMeta;

    public static $disableAddItem = 0;
    public static $disableSaveAllButton = 0;

//    public static $enableAddMultiItem = 0;

    //Mở rộng tên cột Field
    public static $getMapColField_class;
    public static $getMapColField = [];

    // Mảng này sẽ lưu các dữ liệu Object trên các trường của các bản ghi đã lấy ra ở Index
    //Các trường đó sẽ là các trường có thể join với các bảng khác, để lấy ra thông tin mở rộng
    static public $preDataAfterIndex = [];

//    public static $useRandId = 0;

    /**
     * @var string Để có thể thay view từng Controller riêng
     * Mặc định sẽ là admin.demo-api.index, có trường hợp cần đổi như FileIndex , cần có Upload và Tree Folder trong view...
     */
    protected static $index_view_admin = 'admin.demo-api.index';
    protected static $index_view_member = 'admin.demo-api.index';

    protected static $tree_view = 'need_set_tree_view';

    //Folder class của item nếu có (kiểu folder của file)
    //VD: Khi move file, cần biết folder class, để check xem folder có thuộc user ko
    //Bắt buộc phải khai báo nếu muốn có tree, và khi đó bắt buộc phải có trường parent_id
    /**
     * @var ModelGlxBase
     */
    public static $folderParentClass;

    //
    public static $allowAdminShowTree = 0;

    static $retAllTableToExport;

    static $limitRecord = 20;

    //Bỏ qua bảng index
    public $ignoreIndexTable = 0;


    //$tableName là table của Model, query lấy ra các Field của table
    //Hoặc để đưa vào mMetaData[$tableName]
    public $table_name_model;

    public static $modelClass;

    //DB connection, để lấy ra các field của table
//    public $dbConnection;

    /**
     * Mỗi Bảng có một mảng metadata lưu static chỉ cần lấy lần đầu (single ton), key là tên bảng
     * Ví dụ bảng  product, thì $_mMetaData['product'] = <mảng meta data của product>
     * @var array
     */
    public static $_mMetaData = [];

    //Id list không được update , trừ khi la supper Admin
    //Ví dụ các bản ghi mẫu
    public static function getIdReadOnlyIfNotSupperAdmin()
    {
        return 0;
    }

    public static function enableAddMultiItem()
    {
        return 0;
    }

    /**
     * Hàm bổ xung SQL, để lấy ra các trường mở rộng, hoặc các trường trong bảng join
     * @param \Illuminate\Database\Eloquent\Builder|null $x
     * @param $getSelect
     * @return void
     */
    public function getSqlOrJoinExtraIndex(\Illuminate\Database\Eloquent\Builder &$x = null, $getSelect = 0)
    {

    }

    /**
     * Hàm bổ xung SQL, để lấy ra các trường mở rộng, hoặc các trường trong bảng join Edit
     */
    public function getSqlOrJoinExtraEdit(\Illuminate\Database\Eloquent\Builder &$x = null, $params = null)
    {

    }

    /**
     * Mảng từ alias field sang field thật của bảng join
     * các trường sẽ có _ ở đầu, để phân biệt với các trường thật của bảng
     * @return void
     */
    function getMapJoinFieldAlias()
    {

    }

    //full_search_join
    //các trường trong bảng này hoặc trong bảng join
    public function getFullSearchJoinField()
    {
        return [

        ];

    }


    /**
     * Mỗi đối tượng Meta, cần có tablename, dbconnection, để lấy ra đúng thông tin mảng MetaData
     * Nếu các tham số này không được SET, thì khi lấy Meta, sẽ báo lỗi
     * @param $tblName
     * @param $dbConnection
     */
    public function setDbInfoGetMeta($tblName, $dbConnection)
    {
        $this->table_name_model = $tblName;
        static::$_dbConnection = $dbConnection;
    }

    /**
     * Lấy tên cột mở rộng nếu có, cột này do user định nghĩa, để không cần truy cập vào vùng admin định nghĩa
     * @return array
     */
    public static function getMapColFieldEx(){
        if(static::$getMapColField)
            return static::$getMapColField;
        if(static::$getMapColField_class){
            $m1  = static::$getMapColField_class::all()->toArray();
            if($m1)
            foreach ($m1 AS $m2){
//                static::$getMapColField[$m2['field']] = $m2;
                static::$getMapColField[] = $m2;
            }
        }
        return self::$getMapColField;
    }


    /**
     * Ham chay truoc khi insert
     * Vi du: Them user vao su kien, chi nhap Email va bam Them
     * Neu email da ton tai, thi them luon user vao SuKien, ma khong can nhap lai thong tin nua
     * @param $obj
     * @param $get
     * @param $post
     * @return void
     */
    public function beforeInsertDb($get = null, $post = null){

    }

    /**
     * Hàm chạy sau khi insert obj vào db
     * Neu co loi, thi khong insert duoc, vi Commit se khong duoc goi
     */
    public function afterInsertDb($obj, $get = null, $post = null){

    }

    function getHeightTinyMce($field){
        return null;
    }


    function _parent_id_template($obj, $valIntOrStringInt, $field){
        //return " $val , $obj->id , $obj->parent ";

//        if($field == 'parent_multi' || $field == 'parent_multi2')

        if(!$valIntOrStringInt)
            return null;

        $cls = get_called_class();

        $objFolder = new static::$folderParentClass;;

        if($objFolder instanceof DemoFolderTbl);

        $edit = $objFolder::getMetaObj()->isEditableField($field);

        $mRandField = $this->getRandIdListField();
        $useRand = 0;
        if($mRandField)
        if(in_array($field, $mRandField)){
            $useRand = 1;
        }

        $ret = '';
        $retApi = [];
        //if(strstr($valIntOrStringInt, ','))
        if($valIntOrStringInt)
        {
            $valIntOrStringInt = trim(trim($valIntOrStringInt,','));
            $mVal = explode(",", $valIntOrStringInt);
            if($mm = $objFolder->whereIn("id", $mVal)->get()){
                foreach ($mm AS $obj1) {
                    $mName = $obj1->getFullPathParentObj(2);
                    $randId = $obj1->id;
                    if($useRand)
                        $randId = qqgetRandFromId_($obj1->id);
                    $retApi[$randId] = $obj1->name;
                    $retApi[$randId] = $name0 = implode("/", $mName);;
                    $ret .= "<span data-code-pos='ppp17236279527301' class='one_node_name' title='remove this: $randId' data-id='$randId' data-field='$field'> &nbsp
<i class='fa fa-times'></i> &nbsp $name0 &nbsp</span>";
                }
            }
        }

        if(Helper1::isApiCurrentRequest())
            return $retApi;
//        else
//            return "xxxxxx <span title='' class='all_node_name' data-field='$field'>$ret </span>";

        return $ret;
    }


    function isDateType($field){
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_DATE)
            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if($mMeta[$field]->data_type_in_db == 'date')
            return 1;
        return 0;
    }


    function isDateTimeType($field){
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_DATE_TIME)
            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if($mMeta[$field]->data_type_in_db == 'datetime' || $mMeta[$field]->data_type_in_db == 'timestamp')
            return 1;
        return 0;
    }

    function setDefaultValue($field){
//        if ($field == 'user_id') {
//            return getUserIdCurrentInCookie();
//        }
    }

    function getUrlTreeFolder($module = 'admin'){
        if($this::$modelClass)
            if($this::$folderParentClass != $this::$modelClass){
                $folderClass = $this::$folderParentClass;
                if($folderClass instanceof \App\Models\ModelGlxBase);
                $mtFolder = $folderClass::getMetaObj();
//                if($module == 'admin') //5.12.24 tai sao lai co dieu kien nay
                    if($tmp1 = $mtFolder->getAdminUrlWeb($module))
                        return $tmp1."/tree";
            }
        return $this->getAdminUrlWeb($module)."/tree";
    }

    function getPublicLink($idOrObj){
        return null;
        //return "not_set_public_".get_called_class();
    }
    //Một số trường sẽ cần randID, trong trường hợp dùng RandID
    //Ví dụ parent_id, married_with..., thì api lên phải dựa vào đây để rand chúng
    function getRandIdListField($field = null){

        return ['id'];
    }

    /**
     * @return null[]
     * @Ex :
     * @return ['parent_id' => FolderFile::class]; //FileUpload có pid Trỏ về lớp FolderFile
     * @return ['parent_id' => null]; //Cha sẽ Trỏ về chính Model hiện tại
     */
    //mảng các trường trỏ về id, ví dụ parent_id cũng là id trong bảng, và cần kiểm tra nếu nó thuộc về userid
    //Ví dụ: gán parent_id của 1 obj, thì parent_id đó phải thuộc user của obj, (nếu parent_id có trong mảng setting này)
    public function getAllFieldBelongUserId(){
        return ['parent_id' => null];
    }

    function extraContentIndexButton1($v1 = null, $v2 = null, $v3 = null){

    }

    function extraContentIndexButton2($v1 = null, $v2 = null, $v3 = null){

    }

    //Vị trí nội dung mở rộng index1
    function extraContentIndex1($v1 = null, $v2 = null, $v3 = null){

    }

    //Vị trí nội dung mở rộng index1
    function extraContentEdit1($v1 = null, $v2 = null, $v3 = null){

    }

    //Vị trí nội dung mở rộng index1
    function extraContentIndex2(){

    }

    function extraJsInclude(){

    }

    function extraCssInclude(){

    }

    function extraJsIncludeEdit($objData = null){

    }
    function extraCssIncludeEdit(){

    }
    function extraHtmlIncludeEdit0(){

    }

    function extraHtmlIncludeEdit1(){

    }

    function extraHtmlIncludeEditButtonZone2($obj = null){

    }

    function extraHtmlIncludeEditButtonZone1($obj = null){

    }

    function setEncodeIdRand(){
        return $this->isUseRandId();
    }
    function isUseRandId(){
        return false;
    }

    //Sử dụng ID từ 1
    public function isUseIdFromOne()
    {
        return false;
    }

    /**
     * Các trường cần index trong db:
     */
    function getNeedIndexFieldDb(){
        return  [];
    }


    function _parent_extra($obj, $valIntOrStringInt, $field)
    {
        return self::_parent_id_template($obj, $valIntOrStringInt, $field); // TODO: Change the autogenerated stub
    }

    function _parent_id($obj, $valIntOrStringInt, $field)
    {
        //return " $val , $obj->id , $obj->parent ";
        return self::_parent_id_template($obj, $valIntOrStringInt, $field); // TODO: Change the autogenerated stub
    }

    function _parent_all($obj, $valIntOrStringInt, $field)
    {
        return self::_parent_id_template($obj, $valIntOrStringInt, $field); // TODO: Change the autogenerated stub
    }
    /**
     * vì có lưu $folderParentClass, nên có thể lấy path từ đó
     */
    function getPathHtml($id0, $action = '', $seperator = '/'){

        if(!$this::$folderParentClass)
            return null;

        //- edit: nếu cùng parent class, thì lấy id luôn
        // nếu ko cùng pr class, thì lấy parentid

        //Nếu là 'index'
        //Luôn lấy id của parent Folder


        if($action == 'edit'){
            $objItem = \LadLib\Common\Database\MetaTableCommon::getModelFromTableName($this->table_name_model);

            if($objItem::class == $this::$folderParentClass){
                if(!$objItem = $objItem::find($id0))
                    return null;
            }
            else{
                //Nếu không trùng với chính class, thì mới là lớp parent khác
                if(!$objItem = $objItem::find($id0))
                    return null;
                $id0 = $objItem->parent_id;

                if(!$objItem = $this::$folderParentClass::find($id0))
                    return null;
            }
        }

        $objFolder = new $this::$folderParentClass;

        $str = '';
        if($objFolder = $objFolder->find($id0)){
            if($objFolder instanceof \App\Models\ModelGlxBase);
            $metaFold = $objFolder::getMetaObj();
            $searchKeyParent = $this->getSearchKeyField('parent_id');
            $mmP = $objFolder->getFullPathParentObj(0);
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($mmP);
//            echo "</pre>";
            $urlNotParam = $this->getAdminUrlWeb(Helper1::getModuleCurrentName(request()));
            foreach ($mmP AS $parent0){
                $idp = $parent0->id;
                if($metaFold->isUseRandId())
                    $idp = qqgetRandFromId_($idp);
                $nameP = $parent0->name;
                $str .= "  <a class='link_path' href='$urlNotParam?$searchKeyParent=$idp'>$nameP</a> $seperator ";
                //echo "\n<br> ---$idp, (Name = $nameP) ";
            }
        }
        return $str;
    }

    public function getApiUrl($module = 'admin')
    {
        $mUrl = (parse_url(request()->url()));
        $baseDomain = $mUrl['scheme'];
        $baseDomain.='://'.$mUrl['host'];
        if(isset($mUrl['port']))
            $baseDomain.=':'.$mUrl['port'];

        if($module == 'admin'){
//            if (!isset($_SERVER) || !isset($_SERVER['SERVER_NAME']))
//                return "http://localhost:9081" . static::$api_url_admin;
            //return "https://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . static::$api_url_admin;
            return $baseDomain.static::$api_url_admin;
        }

//        if (!isset($_SERVER) || !isset($_SERVER['SERVER_NAME']))
//            return "http://localhost:9081" . static::$api_url_member;
//        return "https://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . static::$api_url_member;
        return $baseDomain.static::$api_url_member;
    }

    function checkJoinFuncExistAndGetName(){
        if(method_exists($this, "_".$this->field))
            return basename(get_called_class()) . "::".$this->field;
        if($this->join_func)
            return $this->join_func;
        return 0;
    }

    function callJoinFunction($obj = null, $val = null, $field = null){

        $funcName = $this->field;
        if($this->field[0] != '_')
            $funcName = "_".$this->field;

        if(method_exists($this, $funcName)){

            $ret =  $this->$funcName($obj, $val, $this->field);


            return $ret;
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($ret);
//            echo "</pre>";
//            die ("RET = $funcName / $this->field");
        }

//        if($this->field == 'parent_multi2')
//        {
//            die(" $this->field ");
//        }

//        die($this->field . " / $this->join_func ");

        if($this->join_func) {

            if(!is_callable($this->join_func))
                loi("Can not call: $this->join_func");

            return call_user_func($this->join_func, $obj, $val, $this->field);
        }

        if($obj)
            loi("Can not call user function / $funcName / ". get_class($this));

        loi("Can not call user function / $funcName / " . get_called_class());
//        $objMeta->join_func;
    }

    /** Các trường mở rộng không có trong DB
     *  Dùng để chứa các giá trị trả lại mở rộng, ví dụ API lấy thông tin 1 sản phẩm, sẽ gồm luôn các link ảnh của sản phẩm đó
     * @return array
     */

    //Các trường mở rộng ko có trong db, dùng cho các thông tin mở rộng,
    //Ví dụ các giá trị liên kết với các bảng khác... như email với userid, ảnh với imageList...
    public function getExtraFieldDb(){
        $mm = get_class_methods($this);
        $ret = [];
        foreach ($mm AS $fname){
            if($fname[0] === '_' && $fname[1] !== '_')
                $ret[] = $fname;
        }

        return $ret;

//        return ['_img_list', '_test1'];
    }

    /**
     * các thao tác (CURD...) user chỉ được thực hiện với Bản ghi của chính user tạo ra Hay Không:
     * Nếu setBelongUserId = 1, nghĩa là phải có trường user_id. và khi đó ở WebMember /member, ApiMember /api/member... , user chỉ được thao tác trên bản ghi mình tạo ra
     * (Admin thì có quyền trên mọi bản ghi của mọi user, và thao tác trong vùng /admin..., api/admin...)
     * @return Boolean
     */

    //Hình như chưa dùng hàm này, mà chỉ dựa vào /member , api/member để xly quyền UID
    function setBelongUserId(){
        return 0;
    }

    function getIndexViewName($uri = null){
        $ret0 = $ret1 = $ret = '';
        if(str_starts_with($uri, '/member'))
            $ret0 = static::$index_view_member;
        if(str_starts_with($uri, '/admin'))
            $ret1 = static::$index_view_admin;
        $ret = $ret0 ? $ret0 : $ret1;
        return $ret;
    }

    public function getJoinRelationshipOfModel(){
        $modelName = \LadLib\Common\Database\MetaTableCommon::getModelFromTableName($this->table_name_model);
        return \LadLib\Laravel\Database\DbHelperLaravel::getRelationshipsBaseModel($modelName);
    }

    static function getMetaInfoFromCache($table){

        $sid = SiteMng::isUseOwnMetaTable();

        $tmpFile = sys_get_temp_dir()."/glx_web/$sid-glx_cache_meta_api-$table";
        @include $tmpFile;
        if(isset($valStoreInCache))
            return $valStoreInCache;
        return null;
    }

    /**
     * Dummy Function
     * Đưa vào View chung các Block bổ xung khi cần, ví dụ đưa block UploadFile vào IndexFile
     */
    function requireViewPos1(){

    }

    static function setMetaInfoToCache($table, $data){
        $val = var_export($data, true);
        $fold = sys_get_temp_dir()."/glx_web";
        $sid = SiteMng::isUseOwnMetaTable();

//        "/glx_web/$sid-glx_cache_meta_api-$table";

        $file = $fold."/$sid-glx_cache_meta_api-$table";
        if(!file_exists($fold))
            mkdir($fold, 0755,1);

        if(!$data){
            if(file_exists($file))
                unlink($file);
            return ;
        }
        file_put_contents($file, '<?php $valStoreInCache = ' . $val . ';');
    }

    public static function __set_state($an_array)
    {
        $cls = get_called_class();
        $obj = new $cls;
        foreach ($an_array as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

    function insertOneFieldToMetaTable($fieldObj, $table, $stt)
    {
        $objInsert = new MetaTableCommon();
        $objInsert->field = $fieldObj;
        $objInsert->table_name_model = $table;
        $objInsert->sname = 's' . $stt;

        $objInsert->setDefaultMetaTypeField($fieldObj);

//        $obj = new ModelMetaInfo();

        ModelMetaInfo::insert($objInsert->toArray());

//        if ($objInsert->insert()) {
//            if (!$objInsert->sname || $objInsert->sname != 's' . $stt) {
//                $objInsert->sname = "s" . $stt;
//                $objInsert->update($objInsert->toArray());
//            }
//        }
    }

    /**
     * Lấy mapfiled và class tương ứng để index chỉ query 1 lần các ID của class
     * Rồi đưa vào 1 mảng static, để lần sau chỉ cần lấy ra mảng này, tránh bị multi query
     * @return string[]
     */
    function getMapFieldAndClass()
    {

    }

    //Return 1 mặc định bình thường, nếu return 0, thì sẽ 0 show Data nào
    //Unset dataView
    function excuteBeforeIndex($param = null){
        return 1;
    }

    function excuteAfterQueryIndex($data){

        $map = $this->getMapFieldAndClass();


        if($map)
        foreach ($map AS $field=>$class){
            $mId = [];
            foreach ($data AS $obj){
                $mId[] = $obj->$field;
            }
            $mId = array_unique($mId);

//            dump($mId);
//
            if(!isset(self::$preDataAfterIndex[$class]))
                self::$preDataAfterIndex[$class] = [];

            $mObj = $class::whereIn('id', $mId)->get();
            foreach ($mObj AS $obj){
                if($obj->id)
                    self::$preDataAfterIndex[$class][$obj->id] = $obj;
            }


//            if($this->isUseRandId()){
//                self::$preDataAfterIndex['id'];
//            }
        }
        $arrId = $data->pluck('id')->toArray();
        self::$preDataAfterIndex['rand_id'] = DB::table('rand_table')->whereIn('id', $arrId)->get()->keyBy('id');

//        dump(self::$preDataAfterIndex);
    }

    /**
     * Khởi tạo bảng Meta cho Model nếu chưa có, và lấy mảng all meta
     * @param $mAllFieldObj
     * @return MetaTableCommon[]
     * @throws \Exception
     */
    function initGetMetaTable($mAllFieldObj)
    {
        if (!$this->table_name_model)
            loi("Not define table_name_model: ". get_called_class());


        //Bổ xung thêm các trường mở rộng thông tin, là các trường trong metaObj có gạch chân ở đầu
//        $mAllFieldObj[] = $key;

        $mAll = $this->getAllMetaObjOfThisTableStoredInDb();


        $haveUpdateOrInsert = 0;
        $cc = 0; //Dùng để tăng sname từ 1-> hết số field của bảng  (nếu dùng id cho sname thì id sẽ big quá)
        foreach ($mAllFieldObj as $fieldObj) {
//            echo "<br/>\n";
//            echo "\n FFF = $fieldObj / $cc";
            $cc++;
            $found = 0;
            if ($mAll){
//                echo "\n --- have mall";
                foreach ($mAll as $objUpdate) {
//                    echo "<br/>\nxxx0  $fieldObj == $objUpdate->field ";
                    if ($fieldObj == $objUpdate->field) {
//                        echo "<br/>\n FOUND $fieldObj / $objUpdate->sname";
                        $found = 1;
                        if (!$objUpdate->sname || $objUpdate->sname !== 's' . $cc) {
//                            echo "<br/>\nxxx1";

                            $objUpdate->sname = "s" . $cc;
//                            $objUpdate->update($objUpdate->toArray());
                            if($obj1 = ModelMetaInfo::find($objUpdate->id)){
                                $obj1->update($objUpdate->toArray());
                            }

                            $haveUpdateOrInsert++;
                        }
                        break;
                    }
                }
            }
            if (!$found) {
//                echo "\n<br/> Inxert $fieldObj ...";
                $haveUpdateOrInsert++;
                $this->insertOneFieldToMetaTable($fieldObj, $this->table_name_model, $cc);
            }
        }
//
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($mAll);
//        echo "</pre>";

//        die("xxxxx");
        if (!$haveUpdateOrInsert)
            return $mAll;
        return $this->getAllMetaObjOfThisTableStoredInDb();
    }

    /**
     * Get all and sort desc by order_field
     * @return $this[]
     * @throws \Exception
     */
    public function getAllMetaObjOfThisTableStoredInDb()
    {
        if (!$this->table_name_model)
            loi("Not define table_name_model: ". get_called_class());
        $cls = get_called_class();
        //x33333333333

//            $mmAllDb = $this->getArrayWhere("table_name_model = '$this->table_name_model'");

        $mmAllDb = [];
        //Dùng model mới chuẩn
        $mm = ModelMetaInfo::where("table_name_model", $this->table_name_model)->get()->toArray();


        foreach ($mm AS $m1){
            $obj = new $cls;
            $obj->loadFromObjOrArray($m1);

            //Bo qua neu trung lap:
            $found = 0;
            foreach ($mmAllDb AS $t1){
                if($t1->field == $m1['field']){
                    $found = 1;
                }
            }
            if($found)
                continue;
            $mmAllDb[] = $obj;
        }

        //Sort order field, desc:
        if($mmAllDb)
        usort($mmAllDb, function ($first, $second) {
            return $first->order_field < $second->order_field;
        });

        //Bỏ qua các trường mở rộng , có gạch dưới ở đầu field
        if($mmAllDb)
        foreach ($mmAllDb AS $obj){
            foreach ($obj AS $k=>$v){
                if($k[0] == '_')
                    unset($obj->$k);
            }
        }

        return $mmAllDb;
    }

    /**
     * Lấy all meta info của một class, hoặc tạo, insert bảng trong db nếu chưa có
     * @param $mAllFieldObj
     * @return MetaTableCommon[]
     */
    function getOrInitMetaTableIfNotHave($mFieldAndDataType)
    {
        if (!$this->table_name_model)
            loi("Not define table_name_model: ". get_called_class());

        $mAllFieldObj = array_keys($mFieldAndDataType);


        //Bổ xung thêm các trường mở rộng thông tin, là các trường trong metaObj có gạch chân ở đầu
        $mAllFieldObjAndExtra = array_merge($mAllFieldObj, $this->getExtraFieldDb());

        $mmAllDb = $this->initGetMetaTable($mAllFieldObjAndExtra);



        $ret = [];
        if($mmAllDb)
        foreach ($mmAllDb as $obj) {
            //Đôi lúc (bị lỗi) có obj không có field
            if (!in_array($obj->field, $mAllFieldObjAndExtra) || !isset($obj->field))
                continue;

            if(isset($mFieldAndDataType[$obj->field]))
                $obj->data_type_in_db = $mFieldAndDataType[$obj->field];

            //Xử lý hard code, không lấy từ DB, mà lấy từ code đã fix các field được hardcode:
            foreach ($obj as $field1 => $val1) {



                if (MetaTableCommon::isHardCodeMetaField($field1)) {
                    $objHardCode = \LadLib\Common\Database\MetaTableCommon::getMetaObjFromTableName($this->table_name_model);
                    $objHardCode->setDbInfoGetMeta($this->table_name_model, static::$_dbConnection);
                    $obj->$field1 = $objHardCode->getHardCodeMetaObj($obj->field)?->$field1;
                    //Ví dụ:
                    //$obj->join_func = $objHardCode->getHardCodeMetaObj('user_id')?->join_func;
                    //Nếu field1 là DataType:
                    //$obj->dataType = $objHardCode->getHardCodeMetaObj('user_id')?->dataType;
                }
            }
            //Gan tableName de khoi phai Get lai
            $obj->table_name_model = $this->table_name_model;
            $ret[$obj->field] = $obj;
        }

        return $ret;
    }

    /**
     * Lấy ra toàn bộ metainfo của 1 bảng, sort theo thứ tự order_field
     * @param $table
     * @param $con
     * @return MetaOfTableInDb[]
     */
    function getMetaDataApi($field = null)
    {
        if (!$this->table_name_model)
            loi("Not define table_name_model: ". get_called_class());
        $table = $this->table_name_model;

        //Lấy ra singleton nếu có
        if (isset(self::$_mMetaData) && is_array(self::$_mMetaData) && isset(self::$_mMetaData[$table]))
            return self::$_mMetaData[$table];

        //!isset($mm['_id'])
        //Todo: cần kiểm tra cache về tốc độ, nếu cho cache vào test số giây load xong trang DEMO
        if($mm = MetaOfTableInDb::getMetaInfoFromCache($table)) {
//            if($this->table_name_model[0] == '_'){
//                if(!isset($mm['_id']))
//                    die("\nNot init meta table1 ($table)???");
//            }
//            else
                if(!isset($mm['id']))
                    die("\nNot init meta table2 ($table)???");
            return $mm;
        }

//        $cacheName = "meta_data_table_cache_".$this->table_name_model;
//        if(isset($cacheName)  && Cache::has($cacheName))
//        //if(isset($cacheName)  && \apcu_exists($cacheName))
//        {
////            $ret = apcu_fetch($cacheName);
////            return $ret;
//            return unserialize(Cache::get($cacheName));
//        }

        //zzzzzzzz
//        $mmFieldObj = DbHelper::getTableColumns(static::$_dbConnection, $table);
        //$mmFieldObj = DbHelperLaravel::getTableColumns(null, $table);

        $tmp = null;
        if(static::$modelClass)
            $tmp = new static::$modelClass;

        if($this->table_name_model[0] == '_'){
            $mmFieldAndDataType = get_object_vars($tmp);
            $mmFieldAndDataType['id'] = '';
            unset($mmFieldAndDataType['_id']);
        }
        else{
            if($tmp) {
//                echo " <span style='font-size: small'> -Model: " . $tmp::class . "</span>";
                $mmFieldAndDataType = $tmp->getTableColumnAndDataType();
            }
            else {
                $mmFieldAndDataType = DbHelperLaravel::getTableColumnAndDataType(null, $table);
            }
        }
        if($mJoinF = $this->getJoinField()){
            foreach ($mJoinF AS $fieldJ => $tmp){
//                if(isset($tmp['table'])){
//                    $mmFieldAndDataType[$fieldJ] = '';
//                }
                if(isset($mmFieldAndDataType[$fieldJ])){
                    loi(static::$modelClass . "_Meta::getJoinField(): '$fieldJ' trùng tên với field trong DB");
                }
                $mmFieldAndDataType[$fieldJ] = '';
            }
        }

        $mm = $this->getOrInitMetaTableIfNotHave($mmFieldAndDataType);

//        if($this->table_name_model[0] == '_'){
//            if(!isset($mm['_id']))
//                die("\nNot init meta table11 ($table)???");
//        }
//        else
        if(!isset($mm['id'])){
//            echo " $table <pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($mm);
//            echo "</pre>";
            die("\nNot init meta table21 ($table)???");
        }

        //Gán singleton
        self::$_mMetaData[$table] = $mm;

//        if(isset($cacheName)) {
//            Cache::put($cacheName, serialize($mm));
//            apcu_add($cacheName, $mm);
//        }

        MetaOfTableInDb::setMetaInfoToCache($table, $mm);

        return $mm;
    }

    public function deleteClearCacheMetaApi_(){
        self::$_mMetaData = [];
        MetaOfTableInDb::deleteClearCacheMetaApi($this->table_name_model);
    }

    /**
     * Xóa cache file meta và static metaData
     * @param $table
     */
    public static function deleteClearCacheMetaApi($table){

        $objMeta = MetaOfTableInDb::getMetaObjFromTableName($table);
        $objMeta::$_mMetaData = [];

        $sid = SiteMng::isUseOwnMetaTable();

        $tmpFile = sys_get_temp_dir()."/glx_web/$sid-glx_cache_meta_api-$table";
        if(file_exists($tmpFile))
            unlink($tmpFile);
//        $cacheName = "meta_data_table_cache_".$table;
//        Cache::forget($cacheName);
    }

//    public function callJoinFunction($funcName, $key = null, $ext = null, $ext1 = null, $ext2 = null, $ext3 = null)
//    {
//        if (is_callable($funcName))
//            return call_user_func($funcName, $key, $ext);
//        return null;
//
//    }

    static function getSearchKeyFromField($field){
        return DEF_PREFIX_SEARCH_URL_PARAM_GLX . self::getSNameFromField_($field);
    }

    /**
     * 12.2024: có thể không dùng hàm này nữa, vì joinSQL là đủ, hàm nàu có lúc gây lỗi nhân bản 1 id ra nhiều trong index
     *
     * Các field có join với các bảng khác
     * @return null
     */
    function getJoinField(){
        return [];
    }

    static function getSNameFromField_($field){
        $cls = get_called_class();
        $obj = new $cls;
        if($obj instanceof MetaOfTableInDb);
        $obj->table_name_model = self::getTableNameFromThisMetaClass();

        //die("TBL : $obj->table_name_model  ");

        return $obj->getSNameFromField($field);
    }

    //Alias
    function getSNameFromField($field)
    {
        return $this->getShortNameFromField($field);
    }

    function getShortNameFromField($field)
    {
        $mm = $this->getMetaDataApi();
        if (!$mm)
            return $field;
        $field = $this->getOrginalFieldMultiLange($field);
        if (isset($mm[$field]) && $mm[$field]->sname)
            return $mm[$field]->sname;
        return $field;
    }

    /**
     * Lấy option search của field trên URL, nếu có
     * VD: ?seoby_s5=eq&seby_s5=1&seoby_s12=S&seby_s12=111
     * @param $param
     */
    function getOptSearchOfField($params, $field)
    {
        foreach ($params as $key => $val) {
            if (str_starts_with($key, DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX)) {
                $sname = substr($key, strlen(DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX));
                if ($field == $this->getFieldFromShortName($sname)) {
                    return $val;
                }
            }
        }
        return null;
    }

    function getFieldFromShortName($sname)
    {
        $mm = $this->getMetaDataApi();
        if (!$mm)
            return null;

        $mJoinField = $this->getJoinField();


        foreach ($mm as $m1)
            foreach ($m1 as $meta => $value)
                if ("$value" == "$sname") {
//                    if($m1->field[0] == '_')
//                        return substr($m1->field, 1);
                    if($mJoinField)
                        foreach ($mJoinField AS $alias => $mInfo){
                            if($alias == $m1->field){
//                                return $mInfo['field'];
//                                return $mInfo['table'].".".$mInfo['field'];
                            }
                        }
                    return $m1->field;
                }
        return null;
    }

    function getFullDescField($field, $fieldInTable = null)
    {
        $mm = $this->getMetaDataApi();
        $field = $this->getOrginalFieldMultiLange($field);
        if ($mm[$field]?->full_desc ?? ''){
            return strip_tags(htmlentities($mm[$field]->full_desc));
        }
        return $this->getDescOfField($field);

    }

    function getDescOfFieldEx($field, $replaceDash = 0)
    {

        $mm = self::getMapColFieldEx();

    }

    function getDescOfField($field, $replaceDash = 0)
    {

//        if($m1 = static::getMapColFieldEx())
//            if(isset($m1[$field]))
//                return $m1[$field]['name'];


        $mm = $this->getMetaDataApi();
        $field = $this->getOrginalFieldMultiLange($field);
        if ($mm[$field]?->name ?? '')
            return mb_ucfirst(($mm[$field]->name));
        if($replaceDash)
            return mb_ucfirst(str_replace("_", ' ', mb_ucfirst($field)));
        return mb_ucfirst($field);
    }


    //Của class
    function getArrField($getShortName = 0)
    {
        $mm = [];
        foreach (get_class_vars(get_class($this)) as $k => $v) {

            if ($k != 'id' && substr($k, 0, 1) == '_')
                continue;

            if ($getShortName)
                $mm[] = $this->getShortNameFromField($k);
            else
                $mm[] = $k;
        }

        return $mm;
    }

    function getMetaInfoTableName()
    {
        return $this->getTableName() . '_meta_info';
    }

    function isTextAreaField($field){
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_TEXT_AREA)
            return 1;
        return 0;
    }

    function isRichTextField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_RICH_TEXT)
            return 1;
        return 0;
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

    function isSortAbleField($field, $gid)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi()){
            return 0;
        }

//        if($mMeta[$field] ?? '')
        if ($mMeta[$field]?->sortable == $gid){
            return 1;
        }

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->sortable.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if($mMeta[$field] ?? '')
        if($mMeta[$field]->sortable){
            $mm = explode(',', $mMeta[$field]->sortable);
            if (in_array($gid, $mm))
                return 1;
        }
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...
        return 0;
    }

    function isSearchAbleField($field, $gid)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        //Bo di
//        if ($this->isEditableField($field, $gid))
//            return 1;

        if ($mMeta[$field]->searchable == $gid)
            return 1;

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->searchable.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if($mMeta[$field]->searchable) {
            $mm = explode(',', $mMeta[$field]->searchable);
            if (in_array($gid, $mm))
                return 1;
        }
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }


    function isDevAllowEdit($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if ($mMeta[$field]->limit_dev_edit)
            return 1;
        return 0;
    }

    /**
     * Ví dụ trường hợp _image_list (bổ xung cho image_list), muốn hiển thị ở api,  nhưng ko hiển thị ở AdminWeb, thì có thể set getNotShow
     * @param $field
     * @param $gid
     * @return int
     */
    function isGetNotShowField($field, $gid)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->get_not_show == $gid)
            return 1;

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->get_not_show.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if($mMeta[$field]->get_not_show){
            $mm = explode(',', $mMeta[$field]->get_not_show);
            if (in_array($gid, $mm))
                return 1;
        }
        return 0;
    }

    function isShowGetOne($field, $gid)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
//        if ($this->isEditableFieldGetOne($field, $gid))
//            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->show_get_one.",", ",".$g1.",") !== false)
                    return 1;
            }
        }


        if(strstr(",".$mMeta[$field]->show_get_one.",", ",".$gid.",") !== false){
            return 1;
        }

//        if ($mMeta[$field]->show_get_one == $gid)
//            return 1;
//        $mm = explode(',', $mMeta[$field]->show_get_one);
//        if (in_array($gid, $mm))
//            return 1;
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    public function isShowIndexField($field, $gid = null)
    {
        if(!$gid)
            return 0;

        $field = $this->getOrginalFieldMultiLange($field);

//        if($this->isEditableField($field, $gid))
//            return 1;

        if (!$mMeta = $this->getMetaDataApi())
            return 0;

        if ($mMeta[$field]->get_not_show == $gid)
            return 1;

        if ($mMeta[$field]->show_in_index == $gid)
            return 1;

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->show_in_index.",", ",".$g1.",") !== false)
                    return 1;
                if($g1 && strstr(",".$mMeta[$field]->get_not_show.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if($mMeta[$field]->show_in_index  ?? ''){
            $mm = explode(',', $mMeta[$field]->show_in_index);
            if (in_array($gid, $mm))
                return 1;
        }

        if($mMeta[$field]->get_not_show)
        if($mMeta[$field]->show_in_index  ?? '') {
            $mm = explode(',', $mMeta[$field]->get_not_show);
            if (in_array($gid, $mm))
                return 1;
        }

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
        return $this->isHtmlSelectOption($field);


        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if($mMeta[$field]->is_select)
            return 1;
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

    function getOrginalFieldMultiLange($field)
    {
        if (class_exists('clang'))
            if (clang::$lang != 'vi')
                if ($langTail = '_' . clang::$lang) {
                    if (substr($field, -3) == $langTail) {
                        $field = substr($field, 0, -3);
                    }
                }
        return $field;
    }


    function isEditableFieldGetOne($field, $gid = 0)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
//        if ($this->isEditableField($field, $gid))
//            return 1;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;

//        die("$field / " .$mMeta[$field]->editable_get_one);

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì okbất kỳ trong 1 thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->editable_get_one.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if(strstr(",".$mMeta[$field]->editable_get_one.",", ",".$gid.",") !== false){
            return 1;
        }

//        if ($mMeta[$field]->editable_get_one == $gid)
//            return 1;
//        $mm = explode(',', $mMeta[$field]->editable_get_one);
//        if (in_array($gid, $mm))
//            return 1;


        //Todo: trường hợp multi GID? trong 1 chuỗi cách nhau dấu ,
        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    function isEditableField($field, $gid = 0)
    {
        if(!$gid)
            return 0;
        $field = $this->getOrginalFieldMultiLange($field);
        if ($field == 'id')
            return 0;
        if (!$mMeta = $this->getMetaDataApi())
            return 0;
        if ($mMeta[$field]->editable == $gid)
            return 1;

        if(strstr($gid, ',') !==false){
            $m1 = explode(",", $gid);
            //Nếu có 1 gid bất kỳ đáp ứng thì ok
            foreach ($m1 AS $g1){
                if($g1 && strstr(",".$mMeta[$field]->editable.",", ",".$g1.",") !== false)
                    return 1;
            }
        }

        if($mMeta[$field]->editable){
            $mm = explode(',', $mMeta[$field]->editable);
            if (in_array($gid, $mm))
                return 1;
        }

        //Còn trường hợp kế thừa quyền... ví dụ Manager kế thừa từ Member
        //Thì khi member editable, manager cũng có quyền editable trên field
        //- Vậy cần tìm gid này kế thừa từ các gid nào...

        return 0;
    }

    function isArrayStringField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ARRAY_STRING)
            return 1;
        return 0;
    }

    function isLinkType($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_LINK)
            return 1;
        return 0;
    }

    function isHtmlDataType($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        $type = $this->getDataType($field);
        if (in_array($type, [DEF_DATA_TYPE_HTML_SELECT_OPTION, DEF_DATA_TYPE_FULL_HTML])){
            return 1;
        }
        return 0;
    }

    function isHtmlSelectOption($field = null)
    {
        if(!$field)
            if($this->dataType == DEF_DATA_TYPE_HTML_SELECT_OPTION)
                return 1;

        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_HTML_SELECT_OPTION)
            return 1;
        return 0;
    }

    function isFaFontIconType($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_FA_FONT_ICON)
            return 1;
        return 0;
    }

    function isTreeSelect($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_TREE_SELECT)
            return 1;
        return 0;
    }

    function isTreeMultiSelect($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_TREE_MULTI_SELECT)
            return 1;
        return 0;
    }

    function isMultiValueField($field){
        if ($this->isArrayStringField($field)
            || $this->isArrayNumberField($field)
            || $this->isTreeMultiSelect($field)
        ) {
            return 1;
        }
        return 0;
    }

    function isArrayNumberField($field){
        //Mặc định các trường sau sẽ là number field array
        if ($field == 'parent_list')
            return 1;
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_ARRAY_NUMBER)
            return 1;
        return 0;
    }

    function isEnumNumberFieldNumberOrString($field)
    {
        if ($this->isArrayNumberField($field) || $this->isArrayStringField($field))
            return 1;
        return 0;
    }


    function isOneImagesField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_ONE_IMAGE_BROWSE)
            return 1;
        return 0;
    }


    function isMultiImagesField($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if ($this->getDataType($field) == DEF_DATA_TYPE_IS_MULTI_IMAGE_BROWSE)
            return 1;
        return 0;
    }


    function isNumberFieldDb($field)
    {
        $dbDt = $this->getDbDataType($field);
        if(str_starts_with($dbDt, 'int')){
            return 1;
        }
    }

    function isNumberField($field)
    {
        if ($field == 'id')
            return 1;

        if ($field == 'order_field' || $field == 'parent_id' || $field == 'parent_path' || $field == 'parent_list' || $field == 'parent_all')
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

    function getDbDataType($field)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return null;
        return $mMeta[$field]->data_type_in_db;
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
    public function getHardCodeMetaObj($field) {

        $objMeta = new MetaOfTableInDb();
        if($field == 'log' || $field == 'note' ) {
            $objMeta->dataType = DEF_DATA_TYPE_TEXT_AREA;
        }
        if ($field == 'image_list')
            $objMeta->dataType = DEF_DATA_TYPE_IS_MULTI_IMAGE_BROWSE;

        if($field == 'content' ) {
            $objMeta->dataType = DEF_DATA_TYPE_RICH_TEXT;
        }
        if($field == 'status'){
            $objMeta->dataType = DEF_DATA_TYPE_STATUS;
        }
        if($field == 'parent_id'){
            $objMeta->dataType = DEF_DATA_TYPE_TREE_SELECT;
        }
        if($field == 'parent_extra' || $field == 'parent_all' ){
            $objMeta->dataType = DEF_DATA_TYPE_TREE_MULTI_SELECT;
            //lay api luon:
            if(static::$folderParentClass)
                $objMeta->join_api = static::$folderParentClass::getMetaObj()->getApiUrl();;
//            $objMeta->join_func = 'App\Models\DemoFolderTbl::joinFuncPathNameFullTree';
        }
        if(!$objMeta->dataType)
            return null;
        return $objMeta;
    }

    /**
     * Admin url on web
     * @return string|null
     */
    public function getAdminUrlWeb($module = 'admin')
    {
        if($module == 'admin'){
            if (!static::$web_url_admin)
                return "not_set_admin_url_main_of_" . basename(get_called_class());
            return static::$web_url_admin;
        }

        if (!static::$web_url_member)
            return "not_set_member_url_main_of_" . basename(get_called_class());
        return static::$web_url_member;
    }



    /**
     * Lấy URL sort cho từng field trên TableHeader, ghép vào URL hiện tại
     * @param $snameField
     * @param $param
     * @return array|string|string[]
     */
    public function getUrlSortField($snameField, $param)
    {
        if (!isset($param[DEF_PREFIX_SORTBY_URL_PARAM_GLX . $snameField]) || $param[DEF_PREFIX_SORTBY_URL_PARAM_GLX . $snameField] == 'asc')
            $urlSort = \LadLib\Common\UrlHelper1::setUrlParam(null, DEF_PREFIX_SORTBY_URL_PARAM_GLX . $snameField, 'desc');
        else
            $urlSort = \LadLib\Common\UrlHelper1::setUrlParam(null, DEF_PREFIX_SORTBY_URL_PARAM_GLX . $snameField, 'asc');
        return $urlSort;
    }

    /**
     * Lấy URL search cho từng field trên TableHeader, ghép vào URL hiện tại
     * @param $snameField
     * @param $param
     * @return array|string|string[]
     */
    public function getUrlSearchField($snameField, $searchString)
    {
        return \LadLib\Common\UrlHelper1::setUrlParam(null, DEF_PREFIX_SEARCH_URL_PARAM_GLX . $snameField, $searchString);
        //return \LadLib\Common\UrlHelper1::setUrlParam(null, "searchf_".$snameField, $searchString);
    }

    /**
     * Lấy search key của field để đưa lên URL
     * @param $field
     * @return string
     */
    public function getSearchKeyField($field){
        $sname = $this->getShortNameFromField($field);

        return DEF_PREFIX_SEARCH_URL_PARAM_GLX . $sname;
    }

    public static function getGuideTextFilter()
    {
        return "
= (Tìm chính xác bằng)
S (Tìm bắt đầu bằng)
C (Tìm có chứa)
N (Tìm bằng null),
E (Tìm bằng rỗng),
NE (Tìm khác rỗng)
!= (Tìm khác giá trị này)
>, <, >=, <=  (Tìm lớn hơn,  nhỏ hơn...)
B (Tìm nằm giữa 2 giá trị, phân cách bởi dấu cách - space)
B1 (Tìm nằm giữa 2 giá trị, phân cách bởi dấu phẩy: ,)
B2 (Tìm nằm giữa 2 giá trị, phân cách bởi dấu chấm phẩy: ;)
";
    }

    /**
     * Hiển thị form filter TOP index data grid
     * @param clsParamRequestEx $objParamEx
     * @param MetaOfTableInDb[] $mMetaAll
     * @param array $requestAllAndRemoveInputSearchField
     */
    public static function showFormFilterDataGrid(array $mMetaAll, array $params, $objParamEx = null)
    {
        if(!$mMetaAll)
            return null;
        $objMeta = end($mMetaAll);

        $mprUrl = array_keys(UrlHelper1::getArrParamUrl());

        $gid = $objParamEx->set_gid;
        $mF = $objMeta->getListFilterField($gid);
        if(!$mF)
            return;
        $requestAllAndRemoveInputSearchField = $params;

        ?>
        <div class="">

        <form method="get" action="<?php echo \LadLib\Common\UrlHelper1::setUrlParamThisUrl('page', null) ?>">
            <?php

            $mFilterOperator = MetaOfTableInDb::getArrayFilterOperator();



            foreach ($mMetaAll as $field => $objMeta) {
                if (!$objMeta->isSearchAbleField($field, $gid))
                    continue;
//                echo "<br/>\n Search OK $field/$gid";
                //Nếu ko phải thùng rác thì bỏ qua cột deleted nếu có
                if(!isset($params['in_trash']) && $field == 'deleted_at')
                    continue;

                $isStringForSearch = 0;
                if(strstr($objMeta->data_type_in_db, 'text') !==false ||  strstr($objMeta->data_type_in_db, 'varchar') !==false){
                    $isStringForSearch = 1;
                }

                $sname = $objMeta->getShortNameFromField($field);
                $sField = DEF_PREFIX_SEARCH_URL_PARAM_GLX . $objMeta->getShortNameFromField($field);
                $searchOpt = DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX . $objMeta->getShortNameFromField($field);


                $valSearch = request($sField);
                $valSearch = HTMLPurifierSupport::clean($valSearch);
//                $valSearch = addslashes(strip_tags($valSearch));

//                $valSearch = htmlspecialchars($valSearch, ENT_QUOTES, 'UTF-8');
////                $valSearch = sanitize($valSearch);
                $valSearch = str_replace(["'", '"' ,'<', '>'], '', $valSearch);


                $des = $objMeta->getDescOfField($field);
                //Bỏ các param Trong search, để đưa vào hidden phía sau, nhằm mục đích giữ nguyên url param khi post lại
                unset($requestAllAndRemoveInputSearchField[$sField]);
                unset($requestAllAndRemoveInputSearchField[$searchOpt]);
                unset($requestAllAndRemoveInputSearchField['page']); //Khi sumit search, thì page = null

                $stDisplay = '';
                if(!$valSearch && $field!= 'id' && $field != 'name')
                    $stDisplay = ';display: none;';

                //Nếu url có s opt của field:
                if(in_array( DEF_PREFIX_SEARCH_URL_PARAM_GLX. $objMeta->getShortNameFromField($field), $mprUrl)){
                    $stDisplay = '';
                }




                ?>
                <div class="div_filter_item div_filter_item1" data-field-filter="<?php echo $field ?>" style="<?php echo $stDisplay ?>">
                    <?php
                    if(!$objMeta->isStatusField($field)){
                    ?>
                    <select data-code-pos='ppp17137475816561' data-field-sl="<?php echo $field ?>"
                            title="<?php echo \LadLib\Common\Database\MetaOfTableInDb::getGuideTextFilter() ?>"
                            class="btn btn-mini"
                            style="font-size: small"
                            name="<?php echo $searchOpt ?>">
                        <?php
                        $haveSelect = 0;

                        foreach ($mFilterOperator as $k => $v) {
                            $padSelect = null;
                            if (isset($params[$searchOpt]) && $params[$searchOpt] == $k)
                                $haveSelect = $padSelect = "selected";
                            if(!$padSelect && $isStringForSearch){
                                if($v == 'C')
                                    $padSelect = 'selected';
                            }
                            echo "<option $padSelect value='$k'>$v</option>";
                        }
                        ?>
                    </select>

                    <?php
                    }

                    $multiValue = $objMeta->isMultiValueField($field);
//                    if ($objMeta->isArrayStringField($field)
//                        || $objMeta->isArrayNumberField($field)) {
//                        $multiValue = 1;
//                    }
                    $descField = $objMeta->getDescOfField($field);

                    $displayInput = null;
                    if ($joinFunc = $objMeta->isSelectField($field)) {
                        $displayInput = "; display: none; ";
                        $mm = $objMeta->callJoinFunction();
                        if($mm) {
                            echo "<select class='form-control form-control-sm search_top_grid  sl_option' data-code-pos='ppp166546695425433' data-id='' data-joinfunc='$joinFunc' data-field='$field' >";
                            $skey = $objMeta->getSearchKeyField($field);

                            foreach ($mm as $key => $val) {
                                $selected = '';
                                if (isset($params[$skey]) && $params[$skey] == $key)
                                    $selected = 'selected';
                                echo "<option value='$key' $selected> $val </option>";
                            }
                            echo "</select>";
                        }
                    }
                    elseif ($objMeta->isStatusField($field)) {
                        $displayInput = "; display: none; ";

                        $pr = $objMeta->getSearchKeyField($field);
                        $linkOn = UrlHelper1::setUrlParamThisUrl($pr, 1);
                        $linkOff = UrlHelper1::setUrlParamThisUrl($pr, 0);

                        $nameDes = $objMeta->getDescOfField($field);

                        $padOn = $padOff = null;
                        if(isset($params[$pr]) && $params[$pr] == 0)
                            $padOff = ';color: red;';
                        if(isset($params[$pr]) && $params[$pr] == 1)
                            $padOn = ';color: red;';

                        echo "<span class='search_top_grid' style=''> $nameDes:
<a class='filter_status_on_$field' style='$padOn' href='$linkOn' title='Filter all Status ON, field : $field '>  ON </a> |
<a class='filter_status_off_$field' title='Filter all Status Off, field : $field ' style='$padOff' href='$linkOff'>  OFF </a>    </span>";
                    }
                    elseif ($objMeta->join_api && !$objMeta->isTreeSelect($field) && !$objMeta->isTreeMultiSelect($field)) {
                        $padClass = 'search-auto-complete-tbl';
                        $displayInput = "; display: none; ";
                        $joinSpan = null;
                        if ($objMeta->checkJoinFuncExistAndGetName()) {

//                            if (is_callable($objMeta->join_func))
                            if(1)
                            {
                                if(isset($params[$objMeta->getSearchKeyField($field)]) && is_numeric($params[$objMeta->getSearchKeyField($field)])){
                                    //if ($getValJoin = call_user_func($objMeta->join_func, null, $params[$objMeta->getSearchKeyField($field)]))
                                    if ($getValJoin = $objMeta->callJoinFunction(null, $params[$objMeta->getSearchKeyField($field)]))
                                    {
                                        //Multivalue sẽ trả về id=>val
                                        //if($multiValue) //Chỉ lấy ra một nếu là multivalue
                                            $joinSpan = $getValJoin[$params[$objMeta->getSearchKeyField($field)]];
                                        //else
                                            //$joinSpan = $getValJoin;
                                    }
                                }
                            } else {
                                $joinSpan = "Not callable1: $objMeta->join_func()";
                            }
                        }



                        echo "<input data-code='ppp98a7s9d79867896' data-type-field='".$objMeta->getDataType($field)."' data-field-filter='$field' data-is-top-filter='1' data-multi-value='$multiValue' placeholder='Search $descField'  data-autocomplete-id='filter_field_$field'
                        class='form-control search_top_grid $padClass' " .
                            "data-api-search='$objMeta->join_api' data-api-search-field='$objMeta->join_api_field' type='text' value='$joinSpan'>";
                    }

                    $padClass = '';
                    if($objMeta->isTreeSelect($field) || $objMeta->isTreeMultiSelect($field)){
                        $padClass = 'input_open_tree_select';
                    }

                    {
                        ?>
                        <input
                            data-lpignore = 'true'
                            data-code-pos="ppp1667865513803" style="<?php echo $displayInput?> ; height: 28px; "
                               data-field-s="<?php echo $field ?>"
                            type="text" placeholder="Tìm <?php echo $des ?>"
                               title="<?php echo $descField . " / " . $field ?>"
                               class="<?php echo $padClass ?> form-control search_top_grid input_value_to_post input-sm "
                               name="<?php echo $sField ?>"
                               data-type-field="<?php echo $objMeta->getDataType($field) ?>"
                               data-field-filter='<?php echo $sField ?>'
                               data-api-search='<?php echo $objMeta->join_api ?>'
                               data-autocomplete-id='filter_field_<?php echo $field ?>'
                               value="<?php echo HTMLPurifierSupport::clean($valSearch) ?>"
                        >
                        <?php
                    }
                    ?>

                    <a href="<?php echo \LadLib\Common\UrlHelper1::clearUrlParamsEndWith(null, "_" . $sname) ?>">
                        <i title="<?php echo "clear filter $sname/$field/$des" ?>"
                           class="fa fa-times cancel_filter_item <?php if ($valSearch != '') echo "red_color $haveSelect / $valSearch"  ?>"></i>
                    </a>

                </div>

                <?php
            }

            //Các param ko có trong search, như page, sort..., sẽ đưa vào hidden, để Resubmit lên url
            foreach ($requestAllAndRemoveInputSearchField as $field1 => $val) {
//                $val = addslashes(strip_tags($val));
                $val = HTMLPurifierSupport::clean($val);
                echo "<input data-code-pos='ppp17137476507721' type='hidden' name='$field1' value='$val'>";
            }

            ?>
            <div class="div_filter_item border_transparent search_btn x1" data-field-filter='' style="">
                <button title="Thêm-Bớt cột tìm kiếm" class="btn btn-default btn-sm" type="button" id="add_field_btn_filter">
                    <i class="fa fa-plus-square mx-1"></i>
                    Mở rộng
                </button>

                <button title="Tìm kiếm" id="search_btn_top" class="btn btn-primary btn-sm search_top_grid1" style="" type="submit">
                    <i class="fas fa-search"></i>
                    Tìm
                </button>
                <i class="fa fa-times cancel_filter_item"></i>

            </div>
<!--            <div class="div_filter_item border_transparent search_btn x2" data-field-filter='' style="">-->
<!---->
<!--            </div>-->
            <div class="div_filter_item border_transparent" style="">

                <?php
                if (\LadLib\Common\Database\MetaOfTableInDb::checkToShowClearFilter()) {
                    $urlClearFilter = \LadLib\Common\UrlHelper1::clearUrlParamsStartWith(null, DEF_PREFIX_SEARCH_URL_PARAM_GLX);
                    $urlClearFilter = \LadLib\Common\UrlHelper1::clearUrlParamsStartWith($urlClearFilter, DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX);

                    echo "<a href='$urlClearFilter' class='btn btn-warning btn-sm search_top_grid btn-cancel-search'>
                                        Hủy Tìm</a>";
                }
                ?>
                <i class="fa fa-times cancel_filter_item"></i>
            </div>

        </form>
        </div>
        <?php
    }

    function deleteCachePublic($id){
        return null;
    }
    function getCacheKeyPublic($num){
        return null;
    }

    /**
     * Hiển thị form filter TOP index data grid
     * @param MetaOfTableInDb[] $mMetaAll
     */
    public static function showTableHeaderDataGrid(array $mMetaAll, $params, $gid)
    {
        $objMeta0 = array_values($mMetaAll)[0];



        if(isTestingDb())
        {
        ?>
        <style>            .divTable2Cell .icon_tool_for_field{                display: inline;            }        </style>
        <?php
        }
        ?>

        <div class="divTable2Row divTable2Heading1">
            <div class="divTable2Cell text-center div_select_all_check">
                <input class="select_all_check select_one_check" class="" type="checkbox" title="Select all rows">
            </div>
            <div data-code-pos="ppp16493381" class="divTable2Cell cellHeader">
                STT
            </div>
            <div class="divTable2Cell cellHeader"> Action</div>
            <?php
            self::$retAllTableToExport = '';
            foreach ($mMetaAll as $field=>$objMeta) {
                if ($objMeta instanceof \LadLib\Common\Database\MetaOfTableInDb) ;
                $field = $objMeta->field;
                if (!$objMeta->isShowIndexField($field, $gid) || $objMeta->isGetNotShowField($field,$gid)) {
                    continue;
                }


                if(isDebugIp()){
//                    dump($objMeta);
                }

                $sname = $objMeta->getShortNameFromField($field);
                $urlSort = $objMeta->getUrlSortField($sname, request()->toArray());

                $apiUrl = $objMeta->join_api;


                //Nếu ko phải thùng rác thì bỏ qua cột deleted nếu có
                if(!isset($params['in_trash']) && $field == 'deleted_at')
                    continue;

                ?>


                <div data-code-pos="ppp1666347493381" class="divTable2Cell resizable  cellHeader <?php echo $field ?>" title="Sort <?php
                echo $field . " - " .$objMeta->width_col;
                ?>"
                style="<?php
                if($objMeta->width_col && is_numeric($objMeta->width_col ) && $objMeta->width_col > 0 && $objMeta->width_col < 500)
                    echo ";width: ".$objMeta->width_col."px;";
                    ?>"
                >
                    <div class="resize-handle" title="Resize this column"></div>
                    <?php

                    if($field != 'id' && ($objMeta->isEditableField($field , $gid))){
                        echo "<i class='fa fa-cog icon_tool_for_field' data-search-field-if-have='$objMeta->join_api_field'
data-api-if-have='$apiUrl' data-type-field='".$objMeta->getDataType($field)."' data-field='$field' title='tool for $field'></i>";
                    }

                    $descField = $objMeta->getDescOfField($field,1);
                    self::$retAllTableToExport .= str_replace(" ", '_', $descField) . "\t";

                    if ($objMeta->isSortAbleField($field, 1)) {
                        if (isset($params[DEF_PREFIX_SORTBY_URL_PARAM_GLX . $sname])) {
                            if ($params[DEF_PREFIX_SORTBY_URL_PARAM_GLX . $sname] == 'asc')
                                echo "  <i class='fa fa-arrow-up red_color mr-1'></i> ";
                            else {
                                echo "  <i class='fa fa-arrow-down red_color mr-1'></i> ";
                            }
                        }
                        echo "<a data-code-pos='ppp1666347484461' data-tester='sort_field_$field' href='$urlSort'>";
                        echo $descField;
                        echo "</a>";




                        if (isset($params[DEF_PREFIX_SORTBY_URL_PARAM_GLX . $sname])) {
                            $urlCancelSort = \LadLib\Common\UrlHelper1::setUrlParamThisUrl(DEF_PREFIX_SORTBY_URL_PARAM_GLX . $sname, null);
                            echo "<a data-code-pos='ppp1666347502214' title='remove sort $field' class='fa fa-times red_color ml-1' href='$urlCancelSort'> </a>";
                        }
                    } else {
                        echo $objMeta->getDescOfField($field,1);
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php

    }

    /**
     * Hiển thị form filter TOP index data grid
     * @param MetaOfTableInDb[] $mMetaAll
     * @param Model[] $dataView
     * @param clsParamRequestEx $objParamEx
     */
    public static function showDataTableDataGrid($dataView, array $mMetaAll, $dataApiUrl, $params, $objParamEx)
    {
        $objMeta0 = array_values($mMetaAll)[0];
        $gid = $objParamEx->set_gid;

        $nPage = $params['page'] ?? 0;
        if($nPage <= 0 || !$nPage || !is_numeric($nPage))
            $nPage = 1;

        $mRandField = $objMeta0->getRandIdListField();


        {
            if($dataView instanceof LengthAwarePaginator)
                $dataView = $dataView->items();

            if(isset($params['last_order']) && $dataView && is_array($dataView))
                if($nPage <=1)
                    $dataView = array_reverse($dataView);
//            dump($dataView);
//            $dataView = [];
//            array_push($dataView->items, [1,2,3]);
//            $custom = collect(['my_data' => 'My custom data here']);
//            $data = $custom->merge($book);

//           die("ABC = ");
        }
//        die("GID = $gid");


        ?>
        <form id="form_data">
            <div class="divTable2 divContainer index_data" data-code-pos="ppp1665356335831"
                 id="div_container"
                 data-api-url="<?php echo $dataApiUrl ?>"
                 data-api-url-update-multi="<?php echo $dataApiUrl ?>/update-multi"
                 data-api-url-update-one="<?php echo $dataApiUrl ?>/update">
                <?php
                if(isset($params['browse_file_iframe'])){
                ?>
                <div class="divGrid2Body">
                    <?php
                    }
                    else{
                    ?>
                    <div class="divTable2Body">
                        <?php
                        }
                        \LadLib\Common\Database\MetaOfTableInDb::showTableHeaderDataGrid($mMetaAll, $params, $gid);


                        //todo: set admin/member?
                        $adminUrl = $objMeta0->getAdminUrlWeb( Helper1::getModuleCurrentName(request()));

                        if($cls0 = $objMeta0::$modelClass)
                            $tmp1 = new $cls0;
                        if(isset($params['last_order']))
                        if($nPage <=1 && isset($tmp1) && $tmp1){
                            foreach ($tmp1 AS $k=>$v)
                                $tmp1->$k = '';
                            $tmp1->id = -1;
                            array_push($dataView, $tmp1);
                            for($i = 0; $i < 10; $i++) {
                                $tmp1 = clone $tmp1;$tmp1->id--;
                                array_push($dataView, $tmp1);
                            }
                        }

                        $stt = ($nPage - 1) * ($params['limit'] ?? 0);
                        $row = 0;
                        if($dataView)
                            foreach ($dataView as $objData) {
                                if(!is_object($objData)){
                                    bl("Lỗi not object", serialize($objData));
                                }
                                self::$retAllTableToExport .= "\n";
                                $dataId = $objData->getId();
                                if($objMeta0->isUseRandId()) {
                                    $tmpId = $objData->getId();
                                    if($randX = ($objMeta0::$preDataAfterIndex['rand_id'][$tmpId]->rand ?? ''))
                                        $dataId = $randX;
                                    else {
                                        if($objData->id__)
                                            $dataId = $objData->id__;
                                        else
                                            $dataId = ClassRandId2::getRandFromId($tmpId);
                                    }
                                }


//                                dump($objData->id__);

                                $stt++;
                                //self::$retAllTableToExport .= "$dataId\t";
                                ?>
                                <div data-code-pos="ppp1665495464865" class="divTable2Row" data-id="<?php echo $dataId ?>">
                                    <div class="divTable2Cell div_select_one_check">
                                        <input title="Select this row" type="checkbox" class="select_one_check" data-id="<?php echo $dataId ?>">
                                    </div>

                                    <div class="divTable2Cell text-center stt">
                                        <?php
                                        echo $stt;
                                        ?>
                                    </div>

                                    <div class="divTable2Cell text-center action" data-code-pos="qqq1709211821646">
                                        <a href="<?php
                                        echo "$adminUrl/edit/$dataId";
                                        ?>"><i title="Edit" class="fa fa-edit " style="font-size: 20px; margin: 2px;"></i></a>

                                        <i title="Save" style="font-size: 21px; color: dodgerblue" class="fa fa-save save_one_item"  data-id="<?php echo $dataId ?>" style="color: dodgerblue"></i>
                                        <?php
//                                        $dataId = ClassRandId2::getRandFromId($objData->getId());
                                        if($linkPublic = $objMeta0->getPublicLink($objData)){
                                            echo '<a target="_blank" href="'.$linkPublic.'"><i title="Xem link public" class="fa fa-link" style="color: brown"> </i></a> ';
                                        }
                                        ?>
                                    </div>
                                    <?php
                                    $col = 0;
                                    foreach ($mMetaAll as $objMeta) {
                                        $displayInput = null;

                                        $field = $objMeta->field;
                                        $valJoin = null;
                                        //Nếu ko phải thùng rác thì bỏ qua cột deleted nếu có
                                        if(!isset($params['in_trash']) && $field == 'deleted_at')
                                            continue;

                                        //Không in ra cell nếu ko show index
                                        if (!$objMeta->isShowIndexField($field, $gid)) {
                                            continue;
                                        }
                                        if ($objMeta->isGetNotShowField($field, $gid)) {
                                            continue;
                                        }

                                        $descField = $objMeta->getDescOfField($field);

                                        $valueField0 = $valueField = $objData->$field;


                                        if($objMeta0->isUseRandId())
                                        if($mRandField && $field !== 'id') //Vi ID da duoc lay o tren
                                        if(in_array($field, $mRandField) && $valueField && is_numeric($valueField)){
                                            //không gán lại ở đây, vì sẽ làm lỗi data = 0
                                            //$objData->$field =
                                            $valueField = \App\Components\ClassRandId2::getRandFromId($valueField);

                                        }

//                                if($field[0] == '_')
//                                    $valueField = $objMeta->$field();

                                        $isEdit = $objMeta->isEditableField($field, $gid);

                                        $joinFunc = $objMeta->checkJoinFuncExistAndGetName();

//                                        if($joinFunc && isDebugIp()){
                                        if($joinFunc){
                                            $valJoin = $objMeta->callJoinFunction($objData, $valueField, $field);
                                        }

                                        if(is_array($valueField)){
                                            $valueField = implode(",", array_keys($valueField));
                                        }

                                        $bgGray = null;
                                        $disabledInputAutoCom = $readlOnlyInput = $disabledInput = $ifClsTextCenter = null;
                                        //ID luôn cần có, để có thể post,  nên ko thể disable
                                        if ($field == 'id' || $field == '_id') {
                                            $ifClsTextCenter = " text-center ";
                                            $readlOnlyInput = 'readonly';
                                            $displayInput = "; display: none; ";
//                                    echo $objData->id;
                                        } else
                                            if (!$isEdit) {
                                                $disabledInputAutoCom = " ; display: none; ";
                                                $disabledInput = 'disabled';
                                                $readlOnlyInput = 'readonly';
                                                $bgGray = ' bgSnow ';
                                            }

                                        $cssRo = '';
                                        if($objMeta->isDevAllowEdit($field))
                                        {
                                            $cssRo = ' ; background-color: lavender ; ';
                                            if(!isDevEmail())
                                                $disabledInput = ' disabled ';
                                        }

//                                $multiValue = 0;
//                                if ($objMeta->isArrayStringField($field)
//                                    || $objMeta->isArrayNumberField($field)) {
//                                    $multiValue = 1;
//                                }
                                        $multiValue = $objMeta->isMultiValueField($field);
                                        $joinSpan = null;

                                        self::$retAllTableToExport .= "$valueField\t";

                                        $strAfterInput = null;

                                        ?>
                                        <div  data-joinfunc='<?php
                                        echo $joinFunc?>' data-id="<?php echo $dataId ?>"
                                              data-selecting-keyboard=""
                                              data-code-pos="ppp1665495460297" data-multi-value="<?php echo $multiValue ?>"
                                              class="divTable2Cell divCellDataForTest <?php echo $objMeta->css_cell_class; ?> <?php echo $bgGray; ?>"
                                              data-table-field="<?php echo $field ?>"
                                              data-tablerow="<?php echo $row ?>"
                                              data-edit-able="<?php echo $isEdit ?>"
                                              data-tablecol="<?php echo $col ?>" title="">




                                            <?php
                                            if ($field == 'id' || $field == '_id'){
                                                $padSt = '';
                                                if($dataId < 0)
                                                    $padSt = 'style=";color: transparent;"';
                                                echo  "<div $padSt class='id_data'> $dataId </div>";
                                            }
                                            if($objMeta->isTreeSelect($field)){
                                                $valShow = $valueField;
                                                if(isset($valJoin))
                                                    $valShow = $valJoin;
                                                echo " <span data-code-pos='ppp343dfd1' class='tree_select'> $valShow</span> ";

                                                $displayInput = "; display: none; ";
                                            }elseif($objMeta->isTreeMultiSelect($field)){
                                                $valShow = $valueField;
                                                if(isset($valJoin))
                                                    $valShow = $valJoin;
                                                echo " <span data-code-pos='ppp3432dfd1' class='tree_select'> $valShow </span> ";
                                                $displayInput = "; display: none; ";
                                            }
                                            elseif ($joinFunc = $objMeta->isSelectField($field)){
                                                $displayInput = "; display: none; ";
                                                $mm = $objMeta->callJoinFunction();
                                                //$mm = call_user_func($joinFunc);
                                                if($mm && is_array($mm)) {
                                                    echo "<select $disabledInput data-code-pos='ppp1665411195425433' data-id='$dataId' data-joinfunc='$joinFunc' class='sl_option $objMeta->css_class' style='$objMeta->css' data-field='$field' >";

                                                    foreach ($mm as $key => $val) {
                                                        $selected = '';
                                                        if ($objData->$field == $key)
                                                            $selected = 'selected';
                                                        echo "<option value='$key' $selected> $val </option>";
                                                    }
                                                    echo "</select>";
                                                }
                                            } elseif ($objMeta->join_api) {


                                                //if ($objMeta->join_func)
                                                if($objMeta->checkJoinFuncExistAndGetName())
                                                {

                                                    //if (is_callable($objMeta->join_func))
                                                    if(1)
                                                    {
                                                        //if ($valJoin = call_user_func($objMeta->join_func, $objData, $valueField, $field))
                                                        if ($valJoin = $objMeta->callJoinFunction($objData, $valueField, $field))
                                                        {
                                                            //if ($multiValue)
                                                            {
                                                                //Bỏ đoạn array, chỉ lấy string
                                                                if (is_array($valJoin)) {
                                                                    foreach ($valJoin as $idJoin => $valJoin) {
                                                                        $joinSpan .= "<span data-autocomplete-id='$dataId-$field' class='span_auto_complete' data-item-value='$idJoin' title='Remove this item'>$valJoin [x]</span>";
                                                                    }
                                                                }else{
                                                                    $joinSpan = $valJoin;
                                                                }
                                                            }
                                                            //else
                                                            //  $joinSpan = "<span data-autocomplete-id='$dataId-$field' class='span_auto_complete' data-item-value='$valueField' title='Remove this item'>$valJoin [x]</span>";
                                                        }


                                                    } else {
                                                        $joinSpan = "Not callable2: $objMeta->join_func()";
                                                    }
                                                    //echo "<br/>\n JOIN = $objMeta->join_func ";
                                                }
                                                //Join func đã được ưu tiên ở trên
                                                elseif($objMeta->join_relation_func){
                                                    $funcRl = $objMeta->join_relation_func;
                                                    $tmpValJoin = $objData->$funcRl;
                                                    $valueField = null;

                                                    foreach ($tmpValJoin as $objJoin) {
                                                        $tmpField = $objMeta->join_api_field;
                                                        $valJoin = $objJoin->$tmpField;
                                                        $valueField .="$objJoin->id,";
                                                        $joinSpan .= "<span data-autocomplete-id='$dataId-$field' class='span_auto_complete' data-item-value='$objJoin->id' title='Remove this item'>$valJoin [x]</span>";
                                                    }
                                                }

                                                $displayInput = "; display: none; ";

                                                echo "<div data-join-val='$dataId-$field' data-code-pos='ppp1665495430328' class='search-auto-complete-tbl' style=''>$joinSpan</div>";

                                                echo "<input data-code-pos='ppp1667865466084' placeholder='Search $descField'  style='$disabledInputAutoCom' data-autocomplete-id='$dataId-$field' class='search-auto-complete-tbl' " .
                                                    "data-api-search='$objMeta->join_api' data-opt-field='$objMeta->opt_field' data-api-search-field='$objMeta->join_api_field' type='text' value=''>";
                                            } elseif ($objMeta->isStatusField($field)) {
                                                $displayInput = "; display: none; ";
                                                $cls = null;
                                                if ($isEdit) {
                                                    $cls = 'change_status_item';
                                                }
                                                else{
                                                    $cls = 'not_editable_status';
                                                }
                                                if ($objData->$field)
                                                    echo "<div title='$field' class='text-center'><i data-code-pos='ppp1681816931570' data-id='$dataId' data-field='$field' class='fa fa-toggle-on $cls'></i></div>";
                                                else
                                                    echo "<div title='$field' class='text-center'><i data-code-pos='ppp1681816931570' data-id='$dataId' data-field='$field' class='fa fa-toggle-off $cls'></i></div>";
                                            }
                                            else
                                                //if($objMeta->join_func)
                                                if($objMeta->checkJoinFuncExistAndGetName())
                                                {

                                                    $joinSpan = null;
                                                    //                                        if (is_callable($objMeta->join_func))
                                                    if(1)
                                                    {
                                                        //if ($valJoin = call_user_func($objMeta->join_func, $objData, $valueField, $field))
                                                        if ($valJoin = $objMeta->callJoinFunction($objData, $valueField, $field))
                                                        {

//                                                    echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                                                    print_r($valJoin);
//                                                    echo "</pre>";
                                                            if(is_string($valJoin) || is_numeric($valJoin))
                                                                $strAfterInput = "<div class='join_val' data-code-pos='ppp16881003038811'>$valJoin</div> " ;
                                                        }
                                                    }
                                                }

                                            //if (1)
                                            {

                                                if(isset($params['in_trash'])) {
                                                    $readlOnlyInput = 'readonly';
                                                    $disabledInput = 'disabled';
                                                }

                                                if($field[0] == '_'){
                                                    $show = $valueField0;
                                                    if(is_array($valueField0) && isset($valueField0['value_show']))
                                                        $show = $valueField0['value_show'];
                                                    if(is_array($show))
                                                        $show = implode(",", array_keys($show));

                                                    $show = ($show);

                                                    echo "<div $field class='full_html_field'>$show</div>";
                                                }
                                                else
                                                if($objMeta->isTextAreaField($field)){
                                                    ?>

                                                    <textarea
                                                        data-lpignore="true"
                                                        placeholder="<?php if($isEdit) echo $objMeta->getDescOfField($field) ?>"
                                                        data-edit-able='<?php echo $isEdit ?>'
                                                        data-code-pos="ppp166544209"
                                                        <?php echo $readlOnlyInput . " " . $disabledInput ?>
                                                        class="input_value_to_post <?php echo $readlOnlyInput . " " . $ifClsTextCenter . " $field " . $objMeta->getCssClass($field) ?>"
                                                        data-field='<?php echo $field ?>' type="text"
                                                        data-autocomplete-id="<?php echo $dataId . "-$field" ?>"
                                                        name="<?php echo $field ?>[]"
                                                        title="<?php echo htmlspecialchars($valueField) ?>"
                                                        data-id="<?php echo $dataId ?>"
                                                        style="<?php if($field == 'deleted_at') echo ';color: red;' ; echo $displayInput . '; ' . $objMeta->getCssStr($field) ?>"
                                                    ><?php echo htmlspecialchars($valueField) ?></textarea>
                                                    <?php
                                                }
                                                else
                                                {
                                                    $showInput = 1;
                                                    //Quá dài thì ko cần show input...
                                                    //và là join function thì đã show ở $valJoin ở trên
                                                    if(!$isEdit && ($valueField && strlen($valueField) > 1000) && $objMeta->checkJoinFuncExistAndGetName()){
                                                        $showInput = 0;
                                                    }

                                                    if($showInput){

                                                        $padClassEditDate = null;
                                                        if($isEdit)
                                                        if($objMeta->isDateTimeType($field)){
                                                            $padClassEditDate = "edit_date_time";
                                                            if($valueField)
                                                                $valueField = date("d/m/Y H:i:s", strtotime($valueField));
                                                        }
                                                        elseif($objMeta->isDateType($field)){
                                                            $padClassEditDate = "edit_date";
                                                            if($valueField)
                                                                $valueField = date("d/m/Y", strtotime($valueField));
                                                        }


                                                        $typeText='text';
                                                        if($objMeta->isPassword($field))
                                                            $typeText = 'password';

                                                        //đoaạn này làm  hỏng rand
                                                        if($mRandField)
                                                        if(!in_array($field, $mRandField))
                                                        if ($objMeta->isNumberField($field) || $objMeta->isNumberFieldDb($field))
                                                            $typeText = 'number';

                                                        ?>
                                                    <input
                                                        data-lpignore="true"
                                                        autocomplete="off"
                                                        placeholder="<?php if($isEdit) echo $objMeta->getDescOfField($field) ?>"
                                                        data-edit-able='<?php echo $isEdit ?>'
                                                        data-code-pos="ppp16655549509"
                                                        data-type="<?php echo $objMeta->getDbDataType($field) ?>"
                                                        <?php echo $readlOnlyInput . " " . $disabledInput ?>
                                                        class="input_value_to_post  <?php echo $padClassEditDate . " ". $readlOnlyInput . " " . $ifClsTextCenter . " $field " . $objMeta->getCssClass($field) ?>"

                                                        data-field='<?php echo $field ?>' type="<?php echo $typeText ?>"
                                                        data-autocomplete-id="<?php echo $dataId . "-$field" ?>"
                                                        value="<?php if($valueField) echo htmlspecialchars($valueField) ?>"
                                                        name="<?php echo $field ?>[]"
                                                        title="<?php if($valueField) echo htmlspecialchars($valueField)  . " | " . $objMeta->getFullDescField($field) ?>"
                                                        data-id="<?php echo $dataId ?>"
                                                        style="<?php echo $cssRo; if($field == 'deleted_at') echo ';color: red;' ; echo $displayInput . '; ' . $objMeta->getCssStr($field) ?>"
                                                    >
                                                    <?php

                                                        echo $strAfterInput;
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        $col++;
                                    }
                                    ?>
                                </div>
                                <?php
                                $row++;
                            }

                        ?>
                    </div>
                </div>
        </form>


        <?php
        echo "<form style='display: none; width: 90%; height: 20px' method='post' action='/tool1/mytree/export_excel.php'>";
        echo "<textarea ppp458974 name='data' style='display: none; width: 100%; height: 200px;   '>";
        echo self::$retAllTableToExport;
        echo "</textarea>";
        echo "<button style='display: none;' type='submit' id='export_to_ecxel'> ExportToExcel </button>";
        echo "</form>";
    }

    function getCssStr($field)
    {
        $field = $this->getOrginalFieldMultiLange($field);
        if (!$mMeta = $this->getMetaDataApi())
            return null;
        return $mMeta[$field]->css;
    }

    /**
     * DS các operator trên filter
     * @return string[]
     */
    public static function getArrayFilterOperator()
    {
        return [''=> '..', "in"=>'in', 'eq' => '=', 'S' => "S",
            'C' => "C", 'N' => "N", 'E' => "E", "NE" =>"NE",
            'gt' => ">", 'gte' => ">=", 'lt' => "<", 'lte' => "<=",
            'ne' => "!=",
            'B' => 'B',
            'B1' => 'B1',
            'B2' => 'B2',
            ];
    }

    /**
     * Lấy mảng các trường được view index, trả lại để đưa vào query lọc field
     */
    function getShowEditAllowFieldList($gid, $withExtraField = 1)
    {

        if (!$mMeta = $this->getMetaDataApi())
            return null;

        $mRet = [];
        foreach ($mMeta as $field => $obj) {
            if ($obj->isEditableFieldGetOne($field, $gid)) {
                if(!$withExtraField)
                    if($field[0] == '_')
                        continue;

                $mRet[] = $field;
            }
        }
        return $mRet;
    }


    function getEditAllowInIndexFieldList($gid, $withExtraField = 1)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return null;

     //   $fieldMetaEx = self::getMapColFieldEx();

        $mRet = [];
        foreach ($mMeta as $field => $obj) {


            if ($obj->isEditableField($field, $gid)) {
                if(!$withExtraField)
                    if($field[0] == '_')
                        continue;

                $mRet[] = $field;
            }
        }
        return $mRet;
    }

    /**
     * Lấy mảng các trường được view index, trả lại để đưa vào query lọc field
     */
    function getListFilterField($gid)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return null;
        $mRet = [];
        foreach ($mMeta as $field => $obj) {
            if ($obj->isSearchAbleField($field, $gid)) {
                $mRet[] = $field;
            }
        }
        return $mRet;
    }

    /**
     * Lấy mảng các trường được view index, trả lại để đưa vào query lọc field
     */
    function getShowIndexAllowFieldList($gid, $withExtraField = 1)
    {

        if (!$mMeta = $this->getMetaDataApi())
            return null;

        $mRet = [];
        foreach ($mMeta as $field => $obj) {

            if ($obj->isShowIndexField($field, $gid)) {
                if(!$withExtraField)
                if($field[0] == '_')
                    continue;

                $mRet[] = $field;
            }
        }
        return $mRet;
    }

    /**
     * Lấy mảng các trường được view index, trả lại để đưa vào query lọc field
     */
    function getShowGetOneAllowFieldList($gid, $withExtraField = 1)
    {
        if (!$mMeta = $this->getMetaDataApi())
            return null;

        $mJoinField = $this->getJoinField();

        $mRet = [];
        foreach ($mMeta as $field => $obj) {
            if(!$withExtraField)
                if($field[0] == '_')
                    continue;



            if ($obj->isShowGetOne($field, $gid)) {
                $mRet[] = $field;
            }
        }
        //Luôn có id:
        if(!in_array('id', $mRet))
            $mRet[] = 'id';
//        if(!in_array('_id', $mRet))
//            $mRet[] = '_id';

        return $mRet;
    }


    function setAllowGidEditSortableField($mField, $gid, $enableDisable){
        return $this->setAllowGidOnIndexField($mField, $gid, $enableDisable, 'sortable');
    }

    function setAllowGidEditGetOneField($mField, $gid, $enableDisable){
        return $this->setAllowGidOnIndexField($mField, $gid, $enableDisable, 'editable_get_one');
    }

    function setAllowGidEditIndexField($mField, $gid, $enableDisable){
        return $this->setAllowGidOnIndexField($mField, $gid, $enableDisable, 'editable');
    }

    /**
     * Đặt thêm quyền cho index cho mảng các field
     * @param $field
     * @param $enableDisable
     */
    function setAllowGidOnIndexField($mFieldOrOneField, $gid, $enableDisable, $roleField = 'show_in_index'){

        $mField = $mFieldOrOneField;
        if(!is_array($mFieldOrOneField) && is_string($mFieldOrOneField))
            $mField = [$mFieldOrOneField];

        $this->deleteClearCacheMetaApi_();

        $mObj = ModelMetaInfo::where('table_name_model', $this->table_name_model)->whereIn('field', $mField)->get();

//        dump($mObj);
//        dump("---------------");
//        dump($obj->show_in_index);

        foreach ($mObj as $obj) {
            $obj->$roleField = ",".$obj->$roleField.",";
            if($enableDisable) {
                //Thêm vào
                if (strstr($obj->$roleField, ",$gid,") === false) {
                    $obj->$roleField .= ",$gid,";
                    $obj->$roleField = str_replace(",,", ',', $obj->$roleField);
                    $obj->$roleField = trim($obj->$roleField, ',');
                    $obj->update();
                }
            }else{
                //Bỏ đi nếu có
                if (strstr($obj->$roleField, ",$gid,") !== false) {
                    $obj->$roleField = str_replace(",$gid,", ',', $obj->$roleField);
                    $obj->$roleField = str_replace(",,", ',', $obj->$roleField);
                    $obj->$roleField = trim($obj->$roleField, ',');
                    $obj->update();
                }
            }
        }

    }



}

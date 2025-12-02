<?php

namespace LadLib\Common\Database;
use App\Components\clsParamRequestEx;
use Base\ClassBaseGlx;
use Base\modelBaseMongo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LadLib\Common\Database\MongoDbConnection;


class mongoDb extends clsBaseGlx {

    public static $_tableName;
    public static $_dbName;

    function getId(){
        if(isset($this->id))
            return $this->id;
        return null;
    }

    function hasField($field)
    {
        if (isset($this->$field))
            return true;
        return false;
    }

    static function getApiMetaArray(){
        return null;
    }

    public function getDbName(){
        if(static::$_dbName)
            return static::$_dbName;
        return env("DB_DATABASE");
    }

    static function getMetaObj(){
        $cls = static::class ."_Meta";
        return new $cls;
    }

    public function getTableName(){
        if(static::$_tableName)
            return static::$_tableName;
        return '';
    }

    public function getTable(){
        return $this->getTableName();
    }

    public function queryGetOne($id, $objPr = null){
        return $this::find($id);
    }

    public function getCtrlDb(){
        $db = $this->getDbName();
        $tbl = $this->getTableName();
        return MongoDbConnection::getConnection()->$db->$tbl;
    }

    function queryDataWithParams($mFilter = [], $objParam = null, $option = []){
        $objMeta = $this->getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();
        if (isset($mMeta['user_id'])){
            $objParam->setUidIfMust();
            if ($objParam->need_set_uid > 0)
                $mFilter['user_id'] = $objParam->need_set_uid;
        }

        try{

        $clt = $this->getCtrlDb();
        if(!$clt)
            return null;

            $cursor = $clt->find($mFilter, $option);
        }
        catch (\Exception $exception){
            return null;
        }
        $cc = 0;
        $cls = $this::class;

        $mm = [];
        foreach ($cursor as $document) {
//            echo "<br/>\n -----------";
            $cc++;
            $obj = new $cls;
            foreach ($document as $key => $value) {
                $obj->$key = $value;
//                echo "<br/>\n $key=>$value ";
            }
            $mm[] = $obj;
        }
        return $mm;
    }

    static function find($id){
        $cls = static::class ;
        $obj = new $cls;
        $clt = $obj->getCtrlDb();

        try{
//            $cursor = $clt->find($mFilter, $option);
            $document = $clt->findOne(['_id'=>intval($id)]);
        }
        catch (\Exception $exception){
            return null;
        }

        if (!$document)
            return null;
        foreach ($document as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

    function delete($param, clsParamRequestEx $objParam){
        $objMeta = $this->getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();
        if (isset($mMeta['user_id']))
            $objParam->setUidIfMust();
        if(!isset($param['id']))
            loi("Not id?");
        $cls = static::class ;
        $obj = new $cls;
        $clt = $obj->getCtrlDb();
        $mFilter = [];
        if ($objParam->need_set_uid > 0)
            $mFilter['user_id'] = $objParam->need_set_uid;
        $mid = explode(",", $param['id']);
        if($mid)
        foreach ($mid AS $idf){
            if(!$idf || $idf < 0)
                continue;
            $idf = intval($idf);

            $mFilter['_id'] = $idf;


            try{
//            $cursor = $clt->find($mFilter, $option);
                $document = $clt->findOne($mFilter);
            }
            catch (\Exception $exception){
                return null;
            }

            if (!$document)
                loi("Not found $idf or not belong your acc!");
            $clt->deleteOne(['_id'=>$idf]);
        }
        return 1;
    }

    function update_multi($param, clsParamRequestEx $objParam){


        $cls = static::class ;
        $obj = new $cls;
        $clt = null;
        try{
            $clt = $this->getCtrlDb();
        }
        catch (\Exception $exception){
            return null;
        }

        $mFilter = [];

        $objMeta = $this->getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();

        if (isset($mMeta['user_id']))
            $objParam->setUidIfMust();
//        die(" $db / $tbl / $id");

        //Remap:
        $mm = [];
        foreach ($param AS $field=>$m1){
            foreach ($m1 AS $key=>$val){
                if(!isset($mm[$key])){
                    $mm[$key] = [];
                }
                $mm[$key][$field] = $val;
            }
        }
        $idInsertDone = [];
        $nDone = 0;
        foreach ($mm AS $m1){
            if(isset($m1['id'])){
//                die("  --- " . $m1['id']);
                $fid = intval($m1['id']);
                if(!is_numeric($fid))
                    $fid = qqgetIdFromRand_($fid);
                $mFilter['_id'] = $fid;
                if ($objParam->need_set_uid > 0)
                    $mFilter['user_id'] = $objParam->need_set_uid;

                //nếu FID < 0 thì sẽ là Insert
                if($fid < 0){
                    foreach ($m1 AS $k1=>$v1){
                        //Phải có 1 giá trị gì khác rỗng thì mới insert
                        if($k1 != 'id' && trim($v1)){
                            $id = $this->getIdToInsertNew_();
                            $m1['_id'] = $id;
                            $m1['id'] = $id;

                            if ($objParam->need_set_uid > 0)
                                $m1['user_id'] = $objParam->need_set_uid;
                            $insertOneResult = $clt->insertOne($m1);
                            $idInsertDone[$fid] = $id;
                            break;
                        }
                    }
                    continue;
                }

                $document = $clt->findOne($mFilter);
                try{
//            $cursor = $clt->find($mFilter, $option);
                    $document = $clt->findOne($mFilter);
                }
                catch (\Exception $exception){
                    return null;
                }

                if (!$document)
                    continue;
                if($clt->updateOne(['_id' => $fid], ['$set' => $m1]))
                    $nDone++;
            }
        }

        return rtJsonApiDone(['insert_list'=>$idInsertDone] , "update done: $nDone record!");
    }

    function getAll($mFilter = [], $option = []){
        $clt = null;
        try{
            $clt = $this->getCtrlDb();
        }
        catch (\Exception $exception){
            return null;
        }

        try{
//            $cursor = $clt->find($mFilter, $option);
            $cursor = $clt->find($mFilter, $option);
        }
        catch (\Exception $exception){
            return null;
        }

        $cc = 0;
        foreach ($cursor as $document) {
            echo "<br/>\n -----------";
            $cc++;
            foreach ($document as $key => $value) {
                echo "<br/>\n $key=>$value ";
            }
        }
    }

    function update($id, $mm = null, $objParam = null){

        if(!$mm)
            $mm = $this->toArray();

        $id = intval($id);
        $objMeta = $this->getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();
        $mFilter = [];
        if (isset($mMeta['user_id'])){
            $objParam->setUidIfMust();
            if ($objParam->need_set_uid > 0) {
                $mFilter['user_id'] = $objParam->need_set_uid;
                $mm['user_id'] = $objParam->need_set_uid;
            }
        }

        $mFilter['_id'] = $id;

        $clt = null;
        try{
            $clt = $this->getCtrlDb();
        }
        catch (\Exception $exception){
            return null;
        }


        if($id < 0){

            $idInsertDone = [];
            foreach ($mm AS $k1=>$v1){
                //Phải có 1 giá trị gì khác rỗng thì mới insert
                if($k1 != 'id' && trim($v1)){


                    $idNew = $this->getIdToInsertNew_();
                    $mm['_id'] = $idNew;
                    $mm['id'] = $idNew;
                    $insertOneResult = $clt->insertOne($mm);
                    $idInsertDone[$id] = $idNew;
                    return ['insert_list'=>$idInsertDone];
                }
            }
            return 1;
        }


        try{
//            $cursor = $clt->find($mFilter, $option);
            $document = $clt->findOne($mFilter);
        }
        catch (\Exception $exception){
            return null;
        }

        if (!$document)
            return null;

        $clt->updateOne($mFilter, ['$set' => $mm]);
        return 1;
    }

    function insert($mm = null, $objParam = null){

        $objMeta = $this->getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();
        if($objParam)
        if (isset($mMeta['user_id']))
            $objParam->setUidIfMust();
        if ($objParam && $objParam->need_set_uid)
            $mm['user_id'] = $objParam->need_set_uid;

        if(!$mm)
            $mm = $this->toArray();

        $clt = null;
        try{
            $clt = $this->getCtrlDb();
        }
        catch (\Exception $exception){
            return null;
        }
        if (!isset($mm['id'])) {
            $id = $this->getIdToInsertNew_();
            //modelBaseMongo::sampleInsertDoc();
            $mm['_id'] = $id;
            $mm['id'] = $id;
            $insertOneResult = $clt->insertOne($mm);
            return $id;
        }
        return null;
    }

    public static function getMax($db, $table, $field){
        $collection = MongoDbConnection::getConnection()->$db->$table;
        $filter = [];
        $options = ['sort' => [$field => -1]]; // -1 is for DESC
        $result = $collection->findOne($filter, $options);
        if ($result && isset($result[$field]))
            return $result[$field];
        return null;
    }


    public function getIdToInsertNew_(){
        $maxHaveBefore = mongoDb::getMax($db = $this->getDbName(), $table = $this->getTableName(), "id");
        $table = $table . "__id";
        $collection = MongoDbConnection::getConnection()->$db->$table;
        $ret = $collection->findOneAndUpdate([], ['$inc' => ['id' => 1]]);
        //Nếu chưa có bảng Id:
        if (!$ret) {
            $first = 1;
            //nếu chưa có bảng, nhưng đã có maxID bên bảng Data
            //Thì gán ID first = max + 1 đó
            if ($maxHaveBefore && $maxHaveBefore > $first)
                $first = $maxHaveBefore + 1;
            $insertOneResult = $collection->insertOne([
                'id' => intval($first)
            ]);
            return $first;
        }
        return $ret['id'] + 1;
    }

    function _ALAST(){

    }
}

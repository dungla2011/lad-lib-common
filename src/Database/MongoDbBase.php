<?php

namespace LadLib\Common\Database;

use Base\ClassRoute;
use Base\modelBaseMongo;
use Base\ModelLogUser;

abstract class MongoDbBase extends BaseDb
{
    var $_id;

    function __construct(){
//        echo "<br> OK: ".get_called_class();
    }

    /**
     * Child class can define other dbname if necessary
     * @return string
     */
    function getDbName()
    {
        return env("DB_DATABASE");
    }

    abstract function getTableName();

    function getOneId($id)
    {
        return $this->getOneWhere(['_id' => $id]);
    }

    function getCtrMongo(): \MongoDB\Collection
    {
        $db = $this->getDbName();
        $tbl = $this->getTableName();
        $this->checkValidTableAndDbName();
        $clt = MongoDbConnection::getConnection()->$db->$tbl;
        return $clt;
    }

    /**
     * @param array $w
     * @param array $opt
     * @return $this|null
     * LIKE: $arr = $obj->getArrayWhere(['name'=> new \MongoDB\BSON\Regex("^iphone", 'i')]);
     */
    function getOneWhere($w = [], $opt = [])
    {
        $mFilter = $w;
        $clt = $this->getCtrMongo();
        $document = $clt->findOne($mFilter, $opt);
        if (!$document)
            return null;
        foreach ($document as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * @param array $w
     * @param array $option
     * @return $this[]
     * LIKE sample: $arr = $obj->getArrayWhere(['name'=> new \MongoDB\BSON\Regex("^iphone", 'i')]);
     */
    function getArrayWhere($w = [], $option = [], $arrField = [], $returnRaw = null)
    {
        if ($arrField && !isset($option['projection'])) {
            $option['projection'] = [];
            foreach ($arrField as $field)
                $option['projection'][$field] = 1;
        }
        if ($w === null)
            $w = [];
        $mFilter = $w;
        $clt = $this->getCtrMongo();
        $cursor = $clt->find($mFilter, $option);
        $arr = [];
        $clName = get_called_class();
        $cc = 0;
        foreach ($cursor as $document) {
            $cc++;
            $obj = new $clName;
            foreach ($document as $key => $value) {
                $obj->$key = $value;
            }
            $arr[] = $obj;
        }
        return $arr;
    }

    function getIdIncrementalToInsert(){
        $db = $this->getDbName();
        $tbl = $this->getTableName();
        return $id = MongoDbBase::getIdToInsertNew($db, $tbl);;
    }

    function insert($convertToArray = 1){
        $clt = $this->getCtrMongo();
        if (!$this->_id)
            $this->_id = $this->getIdIncrementalToInsert();
        if (isset($this->_id) && $this->_id && is_numeric($this->_id))
            $this->_id = intval($this->_id);
        if ($convertToArray) {
            $mm = $this->toArray();
            $insertOneResult = $clt->insertOne($mm);
        } else {
            $insertOneResult = $clt->insertOne($this);
        }
        if ($insertOneResult && $insertOneResult->getInsertedCount())
            return $insertOneResult->getInsertedId();
        return 0;
    }

    /**
     * Tự động tăng _id thêm 1 cho trường _id trong các bảng mongodb
     * Không dùng _id mặc định của bảng
     * Ví dụ:
     *  {
            "_id": "cloud_file_id",
            "id": 80
        }
     * @param $db
     * @param $table
     * @return int|mixed
     * @throws \Exception
     */
    public static function getIdToInsertNew($db, $table) {
        //Bảng counter sẽ chứa các bản ghi: {_id =<'tên bảng dữ liệu'> , id: <số incremental> }
        $collection = MongoDbConnection::getConnection()->$db->counters;
        if (!$collection)
            loi("Can not get id to insert news of DB: $db ");
        $ret = $collection->findOneAndUpdate(['_id' => $table . "_id"], ['$inc' => ['id' => 1]]);
        //tìm maxid bên bảng data
        $maxHaveBefore = MongoDbBase::getMax($db, $table, "_id");
        //Nếu chưa có bản ghi Id:
        if (!$ret) {
            $first = 1;
            //nếu chưa có bảng dữ liệu, nhưng đã có maxID bên bảng data
            //Thì gán ID first = max + 1 đó
            if ($maxHaveBefore && $maxHaveBefore > $first)
                $first = $maxHaveBefore + 1;
            $collection->insertOne([
                '_id' => $table . "_id",'id' => intval($first)
            ]);
            return $first;
        } else {
            if ($ret['id'] + 1 < $maxHaveBefore) {
                $ret = $collection->findOneAndUpdate(['_id' => $table . "_id"], ['$inc' => ['id' => $maxHaveBefore - $ret['id']]]);
                loi("ErrorDB: counter not increase sometime (max = $maxHaveBefore, current = " . ($ret['id'] + 1));
            }
        }
        return $ret['id'] + 1;
    }

    public static function getMax($db, $table, $field) {
        $collection = MongoDbConnection::getConnection()->$db->$table;
        $filter = [];
        $options = ['sort' => [$field => -1]]; // -1 is for DESC
        $result = $collection->findOne($filter, $options);
        if ($result && isset($result[$field]))
            return $result[$field];
        return null;
    }

    public static function getMaxField($field){
        $cls = get_called_class();
        $obj = new $cls;
        return self::getMax($obj->getDbName(), $obj->getTableName(), $field);
    }

    function update()
    {

    }

    function delete()
    {

    }
}


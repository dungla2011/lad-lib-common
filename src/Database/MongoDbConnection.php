<?php
namespace LadLib\Common\Database;

class MongoDbConnection {

    public static $conn;
    public static $hostname;

    //Dùng trong trường hợp đặt DB name, có thể thay đổi DB trong 1 script
    public static $dbname;

    public static $lastFilter;
    public static $lastOpt;

    public static function connectDb($host = DB_HOST, $user = null, $pass = null){
        if(!MongoDbConnection::$conn)
            MongoDbConnection::$conn = new \MongoDB\Client('mongodb://'.$host.'/');
    }

    /**
     * @return \MongoDB\Client
     */
    public static function getConnection(){
        return MongoDbConnection::$conn;
    }

    public static function getListCollection(){
        return MongoDbConnection::getConnection()->listDatabases();
    }

    public static function changeDbToGlx(){
        MongoDbConnection::$dbname = "cms2017LAD";
        //ClassMongoDbConnection::$conn = new MongoDB\Client('mongodb://'.DB_HOST.'/');
    }

    public static function changeDbToMultiSite(){
        MongoDbConnection::$dbname = "cmsLADMultiSite";
        //ClassMongoDbConnection::$conn = new MongoDB\Client('mongodb://'.DB_HOST.'/');
    }

    public static function changeToSecondHostDb($host){
        MongoDbConnection::$hostname = $host;
        MongoDbConnection::$conn = new MongoDB\Client('mongodb://'.$host.'/');
    }

    public static function changeToDefaultHost(){
        MongoDbConnection::$hostname = DB_HOST;;
        MongoDbConnection::$conn = new MongoDB\Client('mongodb://'.DB_MONGO.'/');
    }

    //Backup and restore DB
    // mongorestore -d test2019 /mnt/tmp1/var/glx/weblog/test2019.dump.mongo/test2019/
    // mongodump -d test2019 -o /var/glx/weblog/test2019.dump.mongo
    public static function dumpAllDb($toFolder){

        if(!isCli())
            die("Not cli dumpAllDb");
        $mm = MongoDbConnection::getListCollection();
        if($mm)
        foreach ($mm AS $db1){
            $name = $db1->getName();
            $cmd = "mongodump -d $name -o $toFolder";
            echo "\n $cmd ";
            shell_exec($cmd);
        }
    }

    public static function restoreFromFolderDump($toFolder){
        if(!isCli())
            die("Not cli restoreFromFolderDump");

        $mm = ListDir($toFolder);
        if($mm)
        foreach ($mm AS $name){
            $cmd = "mongorestore -d $name $toFolder/$name";
            echo "\n $cmd ";
            shell_exec($cmd);
        }
    }

}

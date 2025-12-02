<?php
namespace LadLib\Common;

use App\Components\cstring;

class cstring2
{

    static function replaceByArray($string, $replaceArray) {
        foreach ($replaceArray as $key => $value) {
            $string = str_replace($key, $value, $string);
        }
        return $string;
    }

    static public function toSlugPhp5($string, $getFromCache = 0){

        if(!$string)
            return null;

        if($getFromCache == 1){
            $md5 = STH($string);
            $filesl = "/mnt/glx/cache/cache_slug/$md5";
            if(!file_exists(dirname($filesl)))
                mkdir(dirname($filesl));
            if(file_exists($filesl))
                return trim(file_get_contents($filesl));
        }

        $string = trim(strtolower($string));
        $table = array(
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
            'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
            'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
            'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
            'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
            'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
            'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'Đ'=>'d',
            'đ'=>'d', ' ' => '-'
        );

        // -- Remove duplicated spaces
        //$stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $string);

        //$stripped = preg_replace('/[^a-z0-9-\/\s]/', '', $stripped);
        // -- Returns the slug
        $ret = strtr($string, $table);
        $ret = preg_replace('/[^a-z0-9-\/\s]/', '', $ret);
        $ret = str_replace("--",'-',$ret);
        $ret = str_replace("--",'-',$ret);
        $ret = str_replace("--",'-',$ret);

        if($getFromCache == 1)
            file_put_contents($filesl, $ret);

        return $ret;
    }

    static public function toSlug($string, $getFromCache = 0){

        if(!$string)
            return null;

        if($getFromCache == 1){
            $md5 = STH($string);
            $filesl = "/mnt/glx/cache/cache_slug/$md5";
            if(!file_exists(dirname($filesl)))
                mkdir(dirname($filesl));
            if(file_exists($filesl))
                return trim(file_get_contents($filesl));
        }

        $string = trim(mb_strtolower($string));
        $table = array(
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
            'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
            'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
            'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
            'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
            'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
            'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'đ'=>'d', ' ' => '-'
        );

        // -- Remove duplicated spaces
        //$stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $string);

        //$stripped = preg_replace('/[^a-z0-9-\/\s]/', '', $stripped);
        // -- Returns the slug
        $ret = strtr($string, $table);
        $ret = preg_replace('/[^a-z0-9-\/\s]/', '', $ret);
        $ret = str_replace("--",'-',$ret);
        $ret = str_replace("--",'-',$ret);
        $ret = str_replace("--",'-',$ret);

        $ret = trim($ret, "-");

        if($getFromCache == 1)
            file_put_contents($filesl, $ret);

        return $ret;
    }

    //https://stackoverflow.com/questions/15737408/php-find-all-occurrences-of-a-substring-in-a-string
    //static::findAllStrInStr($txt, ["<pre>", '<pre ']);
    public static function findAllStrInStr($str, $needleOrArray){

        $lastPos = 0;
        $positions = array();
//        while (($lastPos = strpos($str, $needle, $lastPos))!== false) {

        $len = strlen($str);
        $cc = 0;
        while (1) {
            //đề phòng lỗi loop:
            if($cc > $len){
                break;
            }
            $cc++;

            if(is_array($needleOrArray)){
                $pos = false;
                foreach ($needleOrArray AS $needle0){
                    if(!strlen($needle0))
                        continue;

                    $needle = $needle0;
                    $pos = strpos($str, $needle0, $lastPos);
                    if($pos !== false){
                        $lastPos = $pos;
                        break;
                    }
                }
                if($pos === false)
                    $lastPos = false;

            }
            else {
                $needle = $needleOrArray;

                if(!strlen($needle))
                    return null;

                $lastPos = strpos($str, $needle, $lastPos);
            }

            if($lastPos === false)
                break;
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen($needle);
        }
        return $positions;
    }

    public  static function splitStringToTrunkNumberWord($str, $numberWordTrunk = 500, $includeDot = 0){

        if($includeDot) //not fit with domain name...
        {
            $str = str_replace(".<", ". <", $str);
            $str = str_replace(".\n", ". \n", $str);
            $str = str_replace(".\r", ". \r", $str);
        }

        $mm = explode(" ", $str);
        $m1 = [];
        $len = count($mm);

        //echo "<br/>\n TT = $len";

        $start = 0;
        $cc = 0;
        while (1){
            $cc++;
            //echo "<br/>\n $cc ....";
            $str = '';
            for($i = $start; $i < $start + $numberWordTrunk && $i < $len; $i++){
                $str .= $mm[$i]." ";
            }

            //
            if($includeDot)
                for($j = $i ;$j < $len; $j++){

                    //if(strstr($mm[$j], '.') !== false){
                    if(substr($mm[$j],-1) == '.'){
                        $start++;
                        $str .= $mm[$j]." ";
                        break;
                    }
                    $start++;
                    $str .= $mm[$j]." ";
                }

            $start += $numberWordTrunk;
            //outputNotEndLine("/share/2.txt", $str);

            //$str = str_replace("\n ", "\n", $str);

            $m1[]  = $str;
            if($start > $len)
                break;

        }
        return $m1;
    }

    static function substr_ucwords($str){
        return ucwords(mb_strtolower($str));
    }

    /**
     * Chức năng: cắt đủ đến character cuối cùng
     * @param $str
     * @param int $from : vị trí character
     * @param $n: số character
     * @param int $with3Dot : thêm 3 chấm khi cần
     * @return false|string
     */
    static function substr_fit_char_unicode($str, $from = 0, $n, $with3Dot = 0) {
        $ret = mb_substr($str, 0, $n);
        if($with3Dot)
            if(strlen($ret) < strlen($str))
                return $ret."...";
        return $ret;
    }

    /**
     * Chức năng: cắt đủ đến word cuối cùng, với unicode ok
     * @param $str
     * @param int $from : vị trí character, không phải số word
     * @param $n : chú ý , n ở đây là số character, không phải số word
     * @param int $with3Dot: thêm 3 chấm khi cần
     * @return false|mixed|string
     */
    static function substr_fit_word_unicode($str, $from = 0, $n = 0, $with3Dot = 0) {

        if($n > mb_strlen($str))
            $n = mb_strlen($str);

        $pos = mb_strpos($str, ' ', $n);
        if ($pos !== false) {
            if ($pos > $n + 10)
                $ret = mb_substr($str, 0, $n);
            $ret = mb_substr($str, 0, $pos);
        }
        else {
            if (mb_strlen($str) > $n + 10)
                $ret = mb_substr($str, 0, $n);
            $ret = $str;
        }

        if($with3Dot)
            if(strlen($ret) < strlen($str))
                return $ret."...";
        return $ret;
    }

    /**
     * Chức năng: cắt đủ đến word cuối cùng
     * @param $str
     * @param int $from : vị trí character, không phải số word
     * @param $n : chú ý , n ở đây là số character, không phải số word
     * @param int $with3Dot: thêm 3 chấm khi cần
     * @return false|mixed|string
     */
    static function substr_fit_word($str, $from = 0, $n) {
        $pos = @strpos($str, ' ', $n);
        if ($pos !== false) {
            if ($pos > $n + 10)
                return substr($str, 0, $n);
            return substr($str, 0, $pos);
        }
        else {
            if (strlen($str) > $n + 10)
                return substr($str, 0, $n);
            return $str."...";
        }
    }

    public static function getCurrentcyFormat($number){
        return number_format($number,0,",",".");
    }

    public static function convert_codau_khong_dau($str) {

        if(!$str) return false;
        $utf8 = array(
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd'=>'đ',
            'D'=>'Ð',
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i'=>'í|ì|ỉ|ĩ|ị',
            'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
            'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );


//        return strtr($str, $utf8);

        foreach($utf8 as $ascii=>$uni) $str = preg_replace("/($uni)/i",$ascii,$str);
        $str = str_replace(["Ð"], 'D', $str);
        $str = str_replace(["Đ"], 'D', $str);
        $str = str_replace(['đ'], 'd', $str);
        return $str;
    }

    /*
     * Tìm Chuỗi End xuất hiện Đầu tiên
abc12551155pbcq6655q55 =>
Array
(
    [0] => bc1255
    [1] => 12
)
     */
    public static function getStringBetween2String00($str, $start, $end, $all = 0){
        $str = str_replace("\n", "", $str);
        $str = str_replace("\r", "", $str);

        $regex = "/$start(.*?)$end/";
        if($all)
            $ret = preg_match_all($regex, $str, $matches);
        else
            $ret = preg_match($regex, $str, $matches);
        if($ret)
            return $matches[1];
        return null;
    }

    /*
     * Tìm chuỗi End xuất hiện cuối cùng (nếu có nhiều chuỗi End)
abc12551155pbcq6655q55
Array
(
    [0] => bc12551155pbcq6655q55
    [1] => 12551155pbcq6655q
)
     */
    public static function getStringBetween2StringType2($str, $start, $end, $all = 0){
        $str = str_replace("\n", "", $str);
        $str = str_replace("\r", "", $str);

        $regex = "/$start(.*)$end/";
        if($all)
            $ret = preg_match_all($regex, $str, $matches);
        else
            $ret = preg_match($regex, $str, $matches);
        if($ret)
            return $matches[1];
        return null;
    }

    public static function getStringBetween2StringType3($str, $start, $end, $all = 0, $limit = 1000){
//        echo "<br/>\n $str, $start, $end,";

        $mret = [];
        $cc = 0;
        while (1){
            $cc++;
            if($cc > $limit){
                break;
            }

            if(!$start)
                $p1 = 0;
            else
                $p1 = strpos($str, $start);

            if(!$end)
                $p2 = strlen($str);
            else {
                if($x = strpos(substr($str, $p1), $end))
                    $p2 = $p1 + $x;
                else{

                    if($all)
                        return $mret;

                    return null;
                }
            }

            $ret = substr($str, $p1 + strlen($start), $p2 - ($p1 + strlen($start)));

            $mret[] = $ret;

            if(!$all)
                break;


            $str = substr($str, $p2);

            usleep(1);
        }

        if($all)
            return $mret;

        return $ret;
    }

    static public function addOrReplaceParamUrlV2NotFriendLy($url, $param, $val = null){

        if(!strstr($url, "?")){
            return $url."?$param=$val";
        }
        $query = explode("?", $url)[1];
        $url0 = explode("?", $url)[0];

        if(!strstr($query, "&")){
            if(!strstr($query, "=")){
                return $url;
            }

            list($p1, $v1 ) = explode("=", $query);
            if($p1 == $param){
                return $url0."?$param=$val";
            }
            else
                return $url0."?$p1=$v1&$param=$val";
        }

        $ar = explode("&", $query);
        $first = "?";
        $foundParamInOlrUrl = 0;
        foreach ($ar AS $one){
            list($p1, $v1 ) = explode("=", $one);
            if($p1 == $param){
                $url0.="$first$param=$val";
                $foundParamInOlrUrl = 1;
            }
            else
                $url0.="$first$p1=$v1";
            if($first == '?')
                $first = "&";
        }

        if(!$foundParamInOlrUrl)
            $url0.="$first$param=$val";

        return $url0;
    }

    static public function addOrReplaceParamUrl($url, $param, $val = null, $friendLy = 0){
        if(!strstr($url, "/$param/")
            && !strstr($url, "?$param=")
            && !strstr($url, "&$param=")
        ){
            if($val === null)
                return $url;

            if(strstr($url, "?") === false){
                if($friendLy)
                    return $ret = str_replace("//", "/", $url . "/$param/$val");
                return $ret = $url . "?$param=$val";
            }
            else {
                if($friendLy)
                    return $ret = str_replace("//", "/", $url . "/$param/$val");
                return $ret = $url . "&$param=$val";
            }
        }
        else{
            if($val === null) {
                $url = preg_replace("/\/$param\/(\w+)/", "/", $url);
                $url = preg_replace("/\?$param\=(\w+)/", "?", $url);
                $url = preg_replace("/\&$param\=(\w+)/", "&", $url);
            }
            else {
                $url = preg_replace("/\/$param\/(\w+)/", "/$param/$val", $url);
                $url = preg_replace("/\?$param\=(\w+)/", "?$param=$val", $url);
                $url = preg_replace("/\&$param\=(\w+)/", "&$param=$val", $url);
            }
        }
        $url = str_replace("&&" , "&", $url);
        if($url[strlen($url) - 1] == "&")
            $url = substr($url, 0, -1);
        return $url;
    }

    public static function sampleGenerateFileTextSimple($file1 = "g:/6.txt"){
        function output123($filename, $string, $createFolder = 0)
        {
            if ($createFolder && !file_exists(dirname($filename))) {
                mkdir(dirname($filename));
            }

            $file = @fopen($filename, "a");
            if (!$file)
                return;
            @fputs($file, $string);
            @fclose($file);
        }

        if(file_exists($file1))
            unlink($file1);
        $str = '';
        for($i = 1; $i<= 1000000; $i++){
            $num = sprintf("%09d\n", $i);
            $str .= $num;
            if($i % 10000 == 0) {
                echo "\n $num";
                output123($file1, $str );
                $str = '';
            }
        }
    }


    public static function replaceStringSample(){

        $str = "qqq/abc/ppp/lllsdf/qqq/abc/ppp/lllsdf/";

        echo "<br/>$str";
        echo "<br/> Thay the 'abc' bang 'abc/12345'";

        echo "<br/>";
        echo preg_replace("/\/abc\/(\w+)/", "/abc/12345",$str);

        echo "<hr>";
        $string = 'April 15, 2003';
        echo "<br/>$string";
        echo "<br/>";
        $pattern = '/(\w+) (\d+), (\d+)/i';
        $replacement = '${1} 1,$3';
        echo preg_replace($pattern, $replacement, $string);
        echo "<hr>";



    }
    /*
        $linkOrg = "/abc/&sfilter=123&";
        $linkOrg = "/abc/&sfilter=123";
    */
    public static function replaceStringBetween2String($inputString, $replaceBy, $strStart, $strEnd){

        if(!$inputString)
            return $inputString;

        if(!strstr($inputString, $strStart))
            return $inputString;

        $len = strlen($inputString);
        $start = strpos($inputString, $strStart) + strlen($strStart);
        if(!$strEnd)
            $end = strlen($inputString);
        else {
            $end = strpos($inputString, $strEnd, $start);
            if(!$end)
                $end = $len;
        }

        return substr($inputString, 0, $start). $replaceBy .substr($inputString, $end, $len - $end);
    }


    /** 10.3.2020
     *  replaceStringBetween2StringV2($str, $start, $end, "$start-ABC123-$end",3);
     *  Vi du thay the hang loat IP
    user_pref("network.proxy.backup.ftp", "1111:19c0:0:fffe:17a:1:1:2");
    user_pref("network.proxy.backup.ssl", "1111:19c0:0:fffe:17a:1:1:2");
    user_pref("network.proxy.backup.ssl_port", 28888);
    user_pref("network.proxy.ftp", "1111:19c0:0:fffe:17a:785:371:436");
     */
    public static function replaceStringBetween2StringV2($stringIn, $start, $end, $by, $limit = 0){
        $mm = explode($start, $stringIn);
        $cc = -1;
        $mmRet = [];
        foreach ($mm AS $line){

            $cc++;
            //ignore first
            if($cc == 0) {
                $mmRet[] = $line;
                continue;
            }

            if($limit && $cc > $limit){
                $mmRet[] = $start.$line;
                continue;
            }

            //if($limit && $cc > $limit)
            //break;

            //echo "<br/>\n --- String = $str";
            $pos = null;
            if($end)
                $pos = strpos($line, $end);
            //echo "<br/>\n POS = $pos";
            $sub = substr($line, 0, $pos + strlen($end));

            //echo "<br/>\n SUB = $sub";

            $strReplace = $sub;

            //echo "<br/>\n LINE0 = $line";
            $line = str_replace($strReplace, $by, $line);

            $mmRet[] = $line;
            // echo "<br/>\nFULL = $strReplace";

            //echo "<br/>\n LINE1 = $line";
        }

        //echo "<br/>\n\n --- \n\n";
        return implode("", $mmRet);
    }


    public static function replaceStringBetween2StringDel($inputString, $replaceBy, $strStart, $strEnd){

        if(!$inputString)
            return $inputString;

        $str = preg_replace("/$strStart.*$strEnd/", $strStart.$replaceBy.$strEnd, $inputString);
        if($str == $inputString)
            $str = preg_replace("/$strStart.*/", $strStart.$replaceBy, $inputString);

        return $str;

    }

    public static function convertToUpperCaseAfterDash($str){
        return implode('', array_map('ucfirst', explode('-', $str)));
    }

    public static function convertToDashFromUpperCase($name){
        return strtolower(preg_replace(
            '/(?<=[a-z])([A-Z]+)/',
            '-$1',
            $name
        ));
    }

    public static function removeKeepNFirstElement($str = null, $n, $seperator = ',') {
        if(!$str)
            return null;
        $mm = explode($seperator, $str);
        $mm = array_filter($mm);
        if($n >= count($mm))
            return $str;
        $m1 = [];
        if($mm)
        for($i = 0; $i <= $n; $i++){
            if($mm[$i] ?? '')
                $m1[] = $mm[$i];
        }
        return  $seperator. implode($seperator, $m1).$seperator;
    }

    public static function addElementToStringToBegin($str = null, $elm, $seperator = ',') {
        if(!$str)
            $str = "$seperator$elm$seperator";
        else
            $str = "$seperator$elm$seperator". $str;
        $str = str_replace("$seperator$seperator",$seperator, $str);
        return $str;
    }

    public static function addElementToString($str = null, $elm, $seperator = ',') {
        if(!$str)
            $str = "$seperator$elm$seperator";
        else
            $str .= "$seperator$elm$seperator";
        $str = str_replace("$seperator$seperator",$seperator, $str);
        return $str;
    }

    public static function checkElementInString($str, $elm, $seperator = ',') {
        if(strstr($str, "$seperator$elm$seperator")!==false)
            return true;
        return false;
    }

    public static function deleteElementInString($str, $elm, $seperator = ',') {
        $str = str_replace("$seperator$elm$seperator",',', $str);
        return $str;
    }

    public static function removeElementInString($str, $elm, $seperator = ',') {
        return $str = static::deleteElementInString($str, $elm, $seperator);
    }

    static function toTienVietNamString3($amount)
    {
        if($amount <=0)
        {
            return '';
        }
        $amount = round($amount);
        $Text=array("không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín");
        $TextLuythua =array("","nghìn", "triệu", "tỷ", "ngàn tỷ", "triệu tỷ", "tỷ tỷ");
        $textnumber = "";
        $length = strlen($amount);

        for ($i = 0; $i < $length; $i++)
            $unread[$i] = 0;

        for ($i = 0; $i < $length; $i++)
        {
            $so = substr($amount, $length - $i -1 , 1);

            if ( ($so == 0) && ($i % 3 == 0) && ($unread[$i] == 0)){
                for ($j = $i+1 ; $j < $length ; $j ++)
                {
                    $so1 = substr($amount,$length - $j -1, 1);
                    if ($so1 != 0)
                        break;
                }

                if (intval(($j - $i )/3) > 0){
                    for ($k = $i ; $k <intval(($j-$i)/3)*3 + $i; $k++)
                        $unread[$k] =1;
                }
            }
        }

        for ($i = 0; $i < $length; $i++)
        {
            $so = substr($amount,$length - $i -1, 1);
            if ($unread[$i] ==1)
                continue;

            if ( ($i% 3 == 0) && ($i > 0))
                $textnumber = $TextLuythua[$i/3] ." ". $textnumber;

            if ($i % 3 == 2 )
                $textnumber = 'trăm ' . $textnumber;

            if ($i % 3 == 1)
                $textnumber = 'mươi ' . $textnumber;


            $textnumber = $Text[$so] ." ". $textnumber;
        }

        //Phai de cac ham replace theo dung thu tu nhu the nay
        $textnumber = str_replace("không mươi", "lẻ", $textnumber);
        $textnumber = str_replace("lẻ không", "", $textnumber);
        $textnumber = str_replace("mươi không", "mươi", $textnumber);
        $textnumber = str_replace("một mươi", "mười", $textnumber);
        $textnumber = str_replace("mươi năm", "mươi lăm", $textnumber);
        $textnumber = str_replace("mươi một", "mươi mốt", $textnumber);
        $textnumber = str_replace("mười năm", "mười lăm", $textnumber);

        return ucfirst($textnumber." đồng chẵn");
    }


    /*
     * Đưa vào Chuỗi mẫu vào trước và sau 1 Tag HTML, nếu trước và sau chưa có chuỗi mẫu đó:
     * Ex: $ret = insertBeforeAndAfterTagHtml($str, "img", '<div style="text-align: center;">', "</div>");
     */
    public static function insertBeforeAndAfterTagHtml($str, $tag, $insertBefore, $insertAfter){

        $len = strlen($str);

        //<p style="text-align: center;">
        //$insertBefore = '<div style="text-align: center;">';
        $lenCenter = strlen($insertBefore);

        $ret = "";
        $needInsert = 0;

        for($i = 0; $i < $len ; $i++){

            if(substr($str, $i, strlen($tag) + 1) == '<'. $tag ||
                substr($str, $i, strlen($tag) + 2) == '<'. $tag.">"
            ){
                if($tmp = $i - $lenCenter > 0){
                    if(strstr(substr($str, $tmp, $len), $insertBefore) === false){
                        $ret.= "$insertBefore";
                        $needInsert = 1;
                    }
                }
                else{
                    $ret.= "$insertBefore";
                    $needInsert = 1;
                }
            }

            $ret.= $str[$i];

            //  echo "<br/>\n $i: ".$str[$i];
            if($needInsert){
                //echo "<br/>\n OK?";
                if($str[$i] == '>'){

                    $ret.= $insertAfter;
                    $needInsert = 0;
                }
            }
        }
        return $ret;
    }

    public static function trimRemoveFromEnd($haystack, $needle)
    {
        $haystack = trim($haystack);
        $length = strlen($needle);

        if(substr($haystack, -$length) === $needle)
        {
            $haystack = substr($haystack, 0, -$length);
        }
        return $haystack;
    }

    public static function trimRemoveFromBegin($haystack, $needle)
    {
        $haystack = trim($haystack);
        $length = strlen($needle);
        if(substr($haystack, 0, $length) === $needle)
        {
            $haystack = substr($haystack, $length);
        }
        return $haystack;
    }



    /*
     * $word1 = ["<pre>", '<pre '];
    $word2 = "</pre>";
    $mm = splitStringByBlockWidthSignature($txt, $word1, $word2);

    return array of strings, and signature info
     */
    public static function splitStringByBlockWidthSignature($txt, $signBlockStart, $signBlockEnd){

        $ret0 = static::findAllStrInStr($txt, $signBlockStart);
        $ret1 = static::findAllStrInStr($txt, $signBlockEnd);

        $mm = [];
        $tt = count($ret0);
        $len2 = strlen($signBlockEnd);
        //$mm[] = substr($txt, 0, $ret0[0]);

        $j = 0;
        if($ret0[0] > 0){
            $mm[$j] = [];
            $mm[$j]['in_block_sign'] = 0;
            $mm[$j]['str'] = substr($txt, 0, $ret0[0]);
        }
        for($i = 0; $i< $tt; $i++){
            $p1 = $ret0[$i];
            $p2 = $ret1[$i];
            //echo "<br/>\n $p1 - $p2";
            //$mm[] = substr($txt, $p1, $p2 + $len2 - $p1);
            $j++;
            $mm[$j] = [];
            $mm[$j]['in_block_sign'] = 1;
            $mm[$j]['str'] = substr($txt, $p1, $p2 + $len2 - $p1);

            if(isset($ret0[$i + 1])){
                //$mm[] = substr($txt, $p2 + $len2, $ret0[$i+1] - $p2 - $len2);
                $j++;
                $mm[$j] = [];
                $mm[$j]['in_block_sign'] = 0;
                $mm[$j]['str'] = substr($txt, $p2 + $len2, $ret0[$i+1] - $p2 - $len2);

            }
        }

        //Phần tử cuối
        //$mm[] = substr($txt, end($ret1) + $len2);
        $j++;
        $mm[$j] = [];
        $mm[$j]['in_block_sign'] = 0;
        $mm[$j]['str'] = substr($txt, end($ret1) + $len2);
        return $mm;
    }

    function _LAST(){}

    /**
     * Lấy chuỗi giữa 2 chuỗi Start->End
     * Nếu ko thấy chuỗi End, thì lấy từ Start đến hết
     * @param $str
     * @param null $start
     * @param null $end
     * @return mixed|void
     */
    static function getStringBetween2String($str, $starting_word, $ending_word)
    {
        $subtring_start = strpos($str, $starting_word);
        //Adding the starting index of the starting word to
        //its length would give its ending index
        $subtring_start += strlen($starting_word);
        //Length of our required sub string

        $size = strpos($str, $ending_word, $subtring_start) - $subtring_start;
        if(!$ending_word)
            $size = strlen($str) - $subtring_start;
        // Return the substring from the index substring_start of length size
        return substr($str, $subtring_start, $size);
    }
}

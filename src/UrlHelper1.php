<?php

namespace LadLib\Common;

class UrlHelper1
{

    static public function dumpServerParam()
    {
        echo "<pre>";
        print_r($_SERVER);
        echo "</pre>";
    }

    /**
     * VD: [p1=>v1, p2=>v2] => p1=v1&p2=v2
     * @param $array
     * @param null $prefix
     */
    static public function arrayToUrlString($array, $prefix = null){
        $m1 = [];
        foreach ($array AS $k=>$v){
            if(substr($k,0, strlen($prefix)) == $prefix)
                $m1[$k] = $v;
        }
        return http_build_query($m1);
    }

    //get array param in url, after question
    static public function getArrParamUrl($url = null)
    {
        if(!$url)
            $url = static::getUrlRequestUri();
        $str = $url;
        if (strstr($url, '?')) {
            $str = explode("?", $url)[1];
        } else
            return [];

        parse_str($str, $output);
        $query = [];

        //Bo di empty value
        foreach ($output as $k => $v) {
            if (isset($v))
                $query[$k] = $v;
        }

        return $query;
    }

    static public function getUrlScriptName()
    {
        return $_SERVER['SCRIPT_NAME'];
    }

    static public function getUrlRequestUri()
    {
        if(isset($_SERVER) && isset($_SERVER['REQUEST_URI']))
            return "/" . ltrim($_SERVER['REQUEST_URI'], "/");
        return null;
    }

    static public function getUriWithoutParam($link = null)
    {
        if(!$link)
            $link = self::getUrlRequestUri();

        if ($link)
            return explode('?', ("/" . ltrim($link, "/")))[0];

        if (!isset($_SERVER['REQUEST_URI']))
            return null;

        return explode('?', ("/" . ltrim($_SERVER['REQUEST_URI'], "/")))[0];
    }

    static public function getUriFromFullUrl($link = null)
    {
        if (!$link)
            $link = self::getUrlRequestUri();
        $path = parse_url($link)['path'];
        return explode("?", $path)[0];
    }

    static public function getUrlPhpSelf()
    {
        return $_SERVER['PHP_SELF'];
    }

    static public function getUrlWithDomainOnly($s = null, $use_forwarded_host = false)
    {
        return self::getUrlOrigin();
    }

    static public function getUrlOrigin($s = null, $use_forwarded_host = false)
    {
        if (!$s)
            $s = $_SERVER;

        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
        $sp = strtolower(@$s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = @$s['SERVER_PORT'];
        $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
        $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $host = isset($host) ? $host : @$s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host;
    }

    //For Debug:
    static public function fullUrlIncludeString($str)
    {
        if (strstr($_SERVER['REQUEST_URI'], $str)) {
            return 1;
        }
        return 0;
    }

    static public function getFullUrl($s = null, $use_forwarded_host = false)
    {
        if (!$s)
            $s = $_SERVER;
        return self::getUrlOrigin($s, $use_forwarded_host) . @$s['REQUEST_URI'];
    }

    static public function getRemoteAddress()
    {
        return @$_SERVER['REMOTE_ADDR'];
    }

    static public function getDomainHostName()
    {
        if(!isWindow1() && isCli()){//
//            return gethostname();
        }
        if (isset($_SERVER['HTTP_HOST']))
            return explode(":", $_SERVER['HTTP_HOST'])[0];
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        return null;
    }

    static function getDomainFromUrl($url = '')
    {
        if (!$url)
            $url = self::getFullUrl();
        $parse = parse_url($url);
        if (isset($parse['host']))
            $ret = strtolower($parse['host']);
        if (!isset($ret)) {
            return $url;
        }
        return $ret;
    }

    static function getPortFromUrl($url = '')
    {
        $url = trim($url);
        if (!$url)
            $url = self::getFullUrl();
        $parse = parse_url($url);
        if (isset($parse['port']))
            $ret = strtolower($parse['port']);
        if (!isset($ret)) {
            if (substr($url, 0, strlen("https://")) == 'https://')
                return 443;
            return 80;
        }
        return $ret;
    }

    static public function getTimeValidateSSLCertificate($url, $port = 443)
    {
        return ctool::getTimeValidateSSLCertificate($url, $port);
    }


    static public function getParamInUrl($param, $url = null, $searchLike = 0)
    {

        if (!is_string($param) && !is_numeric($param))
            return null;

        if (!$url)
            $url = self::getFullUrl();

        $mm = self::getArrParamUrl($url);

//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($mm);
//        echo "</pre>";

        //dấu . trong tên biến bị chuyển thành _ trong tên mảng
        $param = str_replace(".", "_", $param);

        if ($searchLike) {
            foreach ($mm as $key => $value) {

                if (isAdmin_()) {
                    //echo "<br/>\n xxx $key=>$value";
                }

                if (strstr($key, $param) !== false) {
                    return $value;
                }
            }
        }

        if ($mm && isset($mm[$param]))
            return $mm[$param];
        return null;
    }

    static public function setUrlParamThisUrl($key, $value = null)
    {
        $url = self::getUrlRequestUri();
        if(is_array($key)){
            foreach ($key as $k=>$v){
                $url = self::setUrlParam($url, $k, $v);
            }
        }
        else
            return self::setUrlParam(null, $key, $value);
        return $url;
//        return self::setUrlParam(null, $key, $value);
    }

    static public function setUrlParamArray($url = null, $array = null){
        if ($url === null)
            $url = self::getUrlRequestUri();
        if(!$array)
            return $url;

        $url0 = $url;
        foreach ($array AS $k => $v){
            $url0 = self::setUrlParam($url0, $k, $v);
        }
        return $url0;
    }

    static public function clearUrlParamsEndWith($url, $endWidth){
        return self::clearUrlParamsStartWith($url, null, $endWidth);

    }
    static public function clearUrlParamsStartWith($url, $startWith, $endWidth = null){
        if ($url === null)
            $url = self::getUrlRequestUri();
        $url0 = $url;
        $url1 = null;
        if (strstr($url, "?")) {
            $url0 = explode("?", $url)[0];
            $url1 = explode("?", $url)[1];
        }

        //2022.06.22 add, cho các trường hợp zalo paste link > ra &lt
        if($url1)
        $url1 = htmlspecialchars_decode($url1);

        //echo "<br/>\nurl1 = $url1";
        $query1 = null;
        if($url1)
            parse_str($url1, $query1);
        $query = [];
        if($query1)
        foreach ($query1 as $k => $v) {
            if($endWidth){
                if (substr($k,  -1* strlen($endWidth)) != $endWidth)
                    $query[$k] = $v;
            }else
            if (substr($k,0, strlen($startWith)) != $startWith)
                $query[$k] = $v;
        }
        $qr1 = http_build_query($query);
        $ret = "$url0?$qr1";
        $ret = str_replace("?&", "?", $ret);
        return $ret;
    }

    // $value = null: unset (remove) param
    // $url = null: current url
    static public function setUrlParam($url, $key, $value)
    {
        if (!$url)
            $url = self::getUrlRequestUri();

        $url0 = $url;
        $url1 = null;
        if (strstr($url, "?")) {
            $url0 = explode("?", $url)[0];
            $url1 = explode("?", $url)[1];
        }




        //2022.06.22 add, cho các trường hợp zalo paste link > ra &lt
        if($url1)
            $url1 = htmlspecialchars_decode($url1);

        //echo "<br/>\nurl1 = $url1";
        $query1 = null;
        if($url1)
            parse_str($url1, $query1);
        $query = [];

        if($query1)
        foreach ($query1 as $k => $v) {
            //Cả trường hợp = 0 cũng được set:
            if ($v !== false)
                $query[$k] = $v;
        }

        $qr1 = http_build_query($query ? array_merge($query, array($key => $value)) : array($key => $value));

        $ret = "$url0?$qr1";
        if ($value === null)
            $ret = str_replace("$key=", "", $ret);
        $ret = str_replace("?&", "?", $ret);
        return $ret;
    }

}

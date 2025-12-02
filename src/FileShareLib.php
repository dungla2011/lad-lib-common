<?php

/*
 * FileShareLib
 *
 */

namespace LadLib\Common;

defined('_MB') || define('_MB', 1048576);
defined('_GB') || define('_GB', 1073741824);
defined('_NSECOND_DAY') || define('_NSECOND_DAY', 86400);

class FileShareLib
{
    // Class implementation


    public static function getFileFullPath($fid = 0, $location = '')
    {
        $dirRang = $fid - $fid % 1000;
        $dirRang .= "-" . ($dirRang + 1000);
        return "/mnt/" . $location . "/$dirRang/$fid";
    }

    public static function file_get_contents_timeout($url, $timeout = 0)
    {

        if ($timeout) {
            $ctx = stream_context_create(['http' =>
                [
                    'timeout' => $timeout,  //1200 Seconds is 20 Minutes
                ]
            ]);
            return file_get_contents($url, false, $ctx);
        } else
            return file_get_contents($url);
    }

    public static function pushSizeToFile($fileMark, $sizeIn, $fsize, $urlDone)
    {
        if (!is_numeric($sizeIn))
            return;
        if (!file_exists($fileMark))
            file_put_contents($fileMark, '0');

        //FileDone này chắc sẽ chuẩn hơn
        $fileMarkDone = $fileMark . '.done';

        $size = file_get_contents($fileMark);
        if (!$size)
            $size = 0;
        $size += $sizeIn;
        if ($size >= $fsize * 0.8) {

            @unlink($fileMark);
            @file_get_contents($urlDone);
            return;
        }
//    if(file_exists($fileMarkDone)){
//        @unlink($fileMark);
//        return;
//    }
        file_put_contents($fileMark, $size);
    }

    static function HTS($string){

        $str = '';
        for($i=0; $i<strlen($string); $i+=2){
            $str .= chr(hexdec(substr($string,$i,2)));
        }
        return $str;


    }
    static function loi($string){
        throw new \Exception($string);
    }


    static  function output($filename, $string, $createFolder = 0)
    {
        if ($createFolder && ! file_exists(dirname($filename))) {
            mkdir(dirname($filename));
        }

        $file = @fopen($filename, 'a');
        if (! $file) {
            return;
        }
        @fwrite($file, $string."\r\n");
        @fclose($file);
    }

    static  function outputW($filename, $string, $createFolder = 0)
    {
        if (! $filename) {
            return;
        }
        if ($createFolder && ! file_exists(dirname($filename))) {
            mkdir(dirname($filename));
        }
        $file = fopen($filename, 'w');
        if (! $file) {
            return;
        }
        fwrite($file, $string);
        fclose($file);
    }

    static function outputT($filename, $strlog, $createFolder = 0)
    {

        if ($createFolder && ! file_exists(dirname($filename))) {
            mkdir(dirname($filename));
        }

        $datetime = date('Y-m-d H:i:s');
        self::output($filename, $datetime.'#'.$strlog);
    }

    public static function getLocationInToken($tokenEnc)
    {
        $tk = self::HTS($tokenEnc);
        if (strstr($tk, "|") === false) {
            http_response_code(500);
            self::loi("Not valid tokenEnc!");
        }
        $m1 = explode("|", $tk);
        $location = $m1[1];
        if (strlen($location) != 1) {
            http_response_code(500);
            self::loi("Not valid token location!");
        }
        return "sd$location";
    }

    public static function curl_get_contents_timeout($url, $timeout = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$data, $httpcode];
    }

    public static function outputLogErrorReportSizeDone($fid, $uid, $tokenEnc, $sizeDone, $ip, $link, $mess)
    {
        self::outputT("/var/glx/logErrorReportSizeDone.log", "fid=$fid|uid=$uid|tk=$tokenEnc|size=$sizeDone|ip=$ip|link=$link|msg=$mess");
    }

    static function getDomainHome()
    {
        return '4share.vn';
    }

    /**
     *
     * @param $fid
     * @param $uid
     * @param $tokenEnc
     * @param $sizeDone : dùng để cộng dần vào site đã được tải thành công bên db
     * @param $ip
     * @param $startTime : dùng để kiểm tra user tải trùng nhau, với IP khác nhau
     * @param $endTime : dùng để kiểm tra user tải trùng nhau, với IP khác nhau
     * @return void
     */
    public static function updateSizeDownloadRemote($fid, $uid, $tokenEnc, $sizeDone, $ip, $startTime, $endTime)
    {
        if (!$sizeDone)
            return;
        $domainH = @static::getDomainHome();
        $link = "https://$domainH/tool/gw/fs.php?cmd=update_byte_downloaded&fid=$fid&tokenEnc=$tokenEnc&uid=$uid&sizeDone=$sizeDone&ip=$ip&startTime=$startTime&endTime=$endTime";
        [$ret, $code] = @static::curl_get_contents_timeout($link, 3);
        $objRet = @json_decode($ret);

        @static::outputLogErrorReportSizeDone($fid, $uid, $tokenEnc, $sizeDone, $ip, $link, $objRet->message);

        if (!$ret || !$objRet || $objRet->code != 1) {
            @static::outputLogErrorReportSizeDone($fid, $uid, $tokenEnc, $sizeDone, $ip, $link, $objRet->message);
        }
    }

    public static function dl_file_resumable3($file, $filename, $is_resume = TRUE, $fid, $tokenEnc, $uid)
    {
//    global $fid, $tokenEnc, $uid;

        $startTime0 = $startTime = time();

//    $urlDone = "https://$mainServer/api/download_done_file?fid=$fid&sidE=$tokenEnc&ip=" . $_SERVER['REMOTE_ADDR'];

        $fid = basename($file);
        if (file_exists("/share/cache/$fid"))
            $file = "/share/cache/$fid";

        //Gather relevent info about file
        $size = filesize($file);
        //This will set the Content-Type to the appropriate setting for the file
        $ctype = 'application/force-download';
        //check if http_range is sent by browser (or download manager)
        if ($is_resume && isset($_SERVER['HTTP_RANGE'])) {
            @list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if ($size_unit == 'bytes') {
                @list($range, $extra_ranges) = explode(',', $range_orig, 2);
            } else {
                $range = '';
            }
        } else {
            $range = '';
        }
        $seek_start = null;
        $seek_end = null;
        if (!empty($range))
            @list($seek_start, $seek_end) = explode('-', $range, 2);
        ob_clean();
        $seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
        $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);
        header("Pragma: public");
        header("Expires: -1");
        header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
        //add headers if resumable
        if ($is_resume) {
            if ($seek_start > 0 || $seek_end < ($size - 1))
                header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
        }
        header('Content-Type: ' . $ctype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . ($seek_end - $seek_start + 1));
        $fp = fopen($file, 'rb');
        fseek($fp, $seek_start);

        $ttByteWill = 0;
        if (isset($seek_end) && isset($seek_start))
            $ttByteWill = $seek_end - $seek_start;

        //Do IDM chia 8, nên sẽ lấy /20 làm chuẩn
        $sizeToUpdate = round($size / 20);
        if ($sizeToUpdate > 30 * _MB)
            $sizeToUpdate = 30 * _MB;

        $ttByteDone = $countByte = 0;
        while (!feof($fp)) {
            usleep(100);
            $read = fread($fp, 1024 * 64);
            print($read);
            $ttByteDone += strlen($read);
            $countByte += strlen($read);
            flush();
            ob_flush();

            //Mỗi khi size > 1/20 thì update, hoặc quá 10 giây thì update...
            if ($countByte)
                if ($countByte > $sizeToUpdate || ($ttByteWill && $ttByteDone >= $ttByteWill - 1) || time() - $startTime > 10) {

                    $tmpTime = $startTime0;
                    //Nếu chưa tải hết thì đánh dấu start=end=0, để ko bị cập nhật sai
                    if ($ttByteDone < $ttByteWill) {
                        $tmpTime = 0;
                        $endTime = 0;
                    } else
                        $endTime = time();

                    @static::updateSizeDownloadRemote($fid, $uid, $tokenEnc, $countByte, $_SERVER['REMOTE_ADDR'], $tmpTime, $endTime);
                    $startTime = time();
                    $countByte = 0;
                }
        }

        if ($countByte)
            @static::updateSizeDownloadRemote($fid, $uid, $tokenEnc, $countByte, $_SERVER['REMOTE_ADDR'], $startTime0, time());
        // exec("echo END OF FILE-`date '+%Y-%m-%d %H:%M:%S'` >>  /var/tmp/log_download_userfile");
        fclose($fp);
    }
}

<?php

namespace LadLib\Common;

class clsDateTime2{

    //week 1-52
    static function getWeekDateRange($week, $year) {
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $d1 = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $d2 = $dto->format('Y-m-d');
        return "$d1 - $d2";
    }

    //week 1-52
    static function getWeekDateRangeVn($week, $year) {
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $d1 = $dto->format('d-m-Y');
        $dto->modify('+6 days');
        $d2 = $dto->format('d-m-Y');
        return "$d1 - $d2";
    }

    static function getWeekRangeFromNowEn($nWeekFrom = -10, $nWeekTo = +10)
    {
        $ret = [];
        $monday = strtotime("last monday");
        $monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;
        $sunday = strtotime(date("d-m-Y", $monday) . " +6 days");

        for ($i = $nWeekFrom; $i < $nWeekTo; $i++) {
            $m2 = date("Y-m-d", $monday + $i * 7 * 86400);
            $s2 = date("Y-m-d", $sunday + $i * 7 * 86400);
            $ret[] = [$m2,$s2];
        }

        return $ret;
    }

    static function getWeekRangeFromNowTmp($nWeekFrom = -10, $nWeekTo = +10)
    {
        $ret = [];
        $monday = strtotime("last monday");
        $monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;
        $sunday = strtotime(date("d-m-Y", $monday) . " +6 days");

        $m1 = date("d-m-Y", $monday);
        $s1 = date("d-m-Y", $sunday);

        //echo "<br/>\n $m1 - $s1 ($monday / $sunday)";

        for ($i = $nWeekFrom; $i < $nWeekTo; $i++) {
            $m2 = date("d-m-Y", $monday + $i * 7 * 86400);
            $s2 = date("d-m-Y", $sunday + $i * 7 * 86400);
//        $m1 = date("d-m-Y",$monday);
//        $s1 = date("d-m-Y",$sunday);
            //echo "<br/>\n $m2 - $s2";
            $ret[] = [$m2,$s2];
        }

        return $ret;
    }

    /**
     * Thứ 2, ngày 1 trong tuần sẽ là ngày đầu tiên của Tuần
     * (Chủ nhật là ngày 0 của tuần)
     * @param null $date
     * @return string
     */
    static function getWeekStartDay($date = null) {
        if(!$date)
            $date = time();
        if(!is_numeric($date))
            $date = strtotime($date);
        $start = (date('w', $date) == 1) ? $date : strtotime('last monday', $date);
        return date('Y-m-d', $start);

    }

    static function getWeekRange($startDate, $endDate){
        if(!is_numeric($startDate)){
            $startDate = strtotime($startDate);
            $endDate = strtotime($endDate);
        }
        $mD = [];
        for($time = $startDate; $time <= $endDate; $time+=86400 * 7){
            $mD[] = self::getWeekStartDay($time);
        }
        return $mD;

    }

    static function getCurrentWeekRangeVn($addMoreWeek = 0) {
        $monday = strtotime("last monday");
        $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("d-m-Y",$monday)." +6 days");
        $this_week_sd = date("d-m-Y",$monday);
        $this_week_ed = date("d-m-Y",$sunday);
        if(!$addMoreWeek)
            return "$this_week_sd - $this_week_ed";

        $m2 = date("d-m-Y", $monday + $addMoreWeek * 7 * 86400);
        $s2 = date("d-m-Y", $sunday + $addMoreWeek * 7 * 86400);
        return "$m2 - $s2";
    }

    public static function convertToTimeVnFormat($inputSeconds, $full = 1){
        if($full)
            return date("d/m/Y H:i:s ", $inputSeconds);
        return date("d/m/Y", $inputSeconds);
    }

    public static function secondsToTimeVn($inputSeconds){
        return self::secondsToTime($inputSeconds, null, 'ngày', 'giờ', 'phút', "giây");
    }
    //https://stackoverflow.com/questions/8273804/convert-seconds-into-days-hours-minutes-and-seconds
    public static function secondsToTime($inputSeconds,
                                         $returnObj = null,
                                         $strDay = 'days',
                                         $strHour = 'hours',
                                         $strMin = 'minutes',
                                         $strSec = 'seconds'
    ) {

        $secondsInAMinute = 60;
        $secondsInAnHour  = 60 * $secondsInAMinute;
        $secondsInADay    = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        if(!$returnObj){
            $str = "";
            if($days){
                $str.= " $days $strDay";
            }
            if($hours){
                $str.= " $hours $strHour";
            }

            if($strMin)
                if($minutes){
                    $str.= " $minutes $strMin";
                }

            if($strSec)
                if($seconds){
                    $str.= " $seconds $strSec";
                }
            return $str;
        }
        // return the final array
        $obj = array(
            'd' => (int) $days,
            'h' => (int) $hours,
            'm' => (int) $minutes,
            's' => (int) $seconds,
        );

        return $obj;
    }

    public static function getSecondIso8610YoutubeFormat($timeStr){
        if(!$timeStr)
            return 0;

        $n = new \DateInterval($timeStr);
        return ($n->y * 365 * 24 * 60 * 60) +
            ($n->m * 30 * 24 * 60 * 60) +
            ($n->d * 24 * 60 * 60) +
            ($n->h * 60 * 60) +
            ($n->i * 60) +
            $n->s;
    }

    static function getMonDayInMonth($y, $m){

        $ret = new \DatePeriod(
            new \DateTime("first monday of $y-$m"),
            \DateInterval::createFromDateString('next monday'),
            new \DateTime("last day of $y-$m 23:59:59")
        );

        $mDay = [];
        foreach ($ret AS $obj){
//            echo "<br/>\n";
//            echo $obj->format("Y-m-d");
            $mDay[] = $obj->format("d");
        }

        return $mDay;
    }

    static function getSunDayInMonth($y, $m){

        $ret = new \DatePeriod(
            new \DateTime("first sunday of $y-$m"),
            \DateInterval::createFromDateString('next sunday'),
            new \DateTime("last day of $y-$m 23:59:59")
        );

        $mDay = [];
        foreach ($ret AS $obj){
//            echo "<br/>\n";
//            echo $obj->format("Y-m-d");
            $mDay[] = $obj->format("d");
        }

        return $mDay;
    }

    static function getSatDayInMonth($y, $m){
        $ret = new \DatePeriod(
            new \DateTime("first saturday of $y-$m"),
            \DateInterval::createFromDateString('next saturday'),
            new \DateTime("last day of $y-$m 23:59:59")
        );
        $mDay = [];
        foreach ($ret AS $obj){
//            echo "<br/>\n";
//            echo $obj->format("Y-m-d");
            $mDay[] = $obj->format("d");
        }
        return $mDay;
    }

    //Thứ ba, 28/09/2021 | 15:56 GMT+7
    public static function showDateTimeStringVNToDisplay($time, $dateOnly = null){
        return self::getDateTimeStringVNToDisplay($time,$dateOnly);
    }

    public static function getDayOfDateVN($time, $pad = "Thứ "){
        if(!is_numeric($time))
            $time = strtotime($time);
        $str = date('l' , $time);
        $str = str_replace("Monday" ,$pad."2", $str);
        $str = str_replace("Tuesday" ,$pad."3", $str);
        $str = str_replace("Wednesday" ,$pad."4", $str);
        $str = str_replace("Thursday" ,$pad."5", $str);
        $str = str_replace("Friday" ,$pad."6", $str);
        $str = str_replace("Saturday" ,$pad."7", $str);
        $str = str_replace("Sunday" ,"CN", $str);
        return $str;
    }

    //Thứ ba, 28/09/2021 | 15:56 GMT+7
    public static function getDateTimeStringVNToDisplay($time, $dateOnly = null){

        if($dateOnly)
            $str = date('l, d/m/Y' , $time);
        else
            $str = date('l, d/m/Y | H:i' , $time)." GMT+7";
        $str = str_replace("January" ,"Tháng một", $str);
        $str = str_replace("February" ,"Tháng hai", $str);
        $str = str_replace("March" ,"Tháng ba", $str);
        $str = str_replace("April" ,"Tháng tư", $str);
        $str = str_replace("May" ,"Tháng năm", $str);
        $str = str_replace("June" ,"Tháng sáu", $str);
        $str = str_replace("July" ,"Tháng bảy", $str);
        $str = str_replace("August" ,"Tháng tám", $str);
        $str = str_replace("September" ,"Tháng chín", $str);
        $str = str_replace("October" ,"Tháng mười", $str);
        $str = str_replace("November" ,"Tháng mười một", $str);
        $str = str_replace("December" ,"Tháng mười hai ", $str);

        $str = str_replace("Monday" ,"Thứ hai", $str);
        $str = str_replace("Tuesday" ,"Thứ ba", $str);
        $str = str_replace("Wednesday" ,"Thứ tư", $str);
        $str = str_replace("Thursday" ,"Thứ năm", $str);
        $str = str_replace("Friday" ,"Thứ sáu", $str);
        $str = str_replace("Saturday" ,"Thứ bảy", $str);
        $str = str_replace("Sunday" ,"Chủ Nhật", $str);

        return $str;
    }

    static public function isSunDay($date){
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 0);
    }

    static public function isSatDay($date){
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 6);
    }

    static public function getStartDayLastWeek(){
        $previous_week = strtotime("-1 week +1 day");
        $start_weekLast = strtotime("last sunday midnight",$previous_week);
        return  $start_weekLast = date("Y-m-d",$start_weekLast);
    }
    static public function getEndDayLastWeek(){
        $previous_week = strtotime("-1 week +1 day");
        $start_weekLast = strtotime("last sunday midnight",$previous_week);
        $end_weekLast = strtotime("next saturday",$start_weekLast);
        return $end_weekLast = date("Y-m-d",$end_weekLast);
    }
    static public function getStartDayLastMonth(){
        return $startDayLastMonth = date("Y-m-d", strtotime("first day of previous month"));
    }
    static public function getEndDayLastMonth(){
        return $endDayLastMonth = date("Y-m-d", strtotime("last day of previous month"));
    }

    static public function countGetNumberDayWeekInMonth($month, $year){
        $mDay = [];

        try{
            // Lấy số ngày trong tháng
            $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            // Khởi tạo biến đếm số ngày trong tuần
            $weekDayCount = 0;

                // Duyệt qua từng ngày trong tháng
            for ($day = 1; $day <= $totalDaysInMonth; $day++) {
                // Sử dụng hàm date() để xác định ngày trong tuần (0: Chủ Nhật, 1: Thứ Hai, ..., 6: Thứ Bảy)
                $dayOfWeek = date('w', strtotime("$year-$month-$day"));

                // Nếu ngày là Chủ Nhật (0), tăng biến đếm số ngày trong tuần lên 1
                if ($dayOfWeek == 0) {
                    $weekDayCount++;
                }

                if(!isset($mDay[$dayOfWeek]))
                    $mDay[$dayOfWeek] = 0;
                $mDay[$dayOfWeek]++;
            }
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error1: ($month) ".$e->getMessage();
            die();
        }
        catch (\Exception $exception){
            echo "<br/>\n Error2: ($month) ".$exception->getMessage();

            die();
        }
        return $mDay;
    }

    static public function getEndDayOfMonth($monthYmd){
        return date("t", strtotime($monthYmd));
    }


    static function ListMonthBetWeenDate($date1, $date2 = null) { //null date2 is to Now
        if (!isset($date2))
            $date2 = nowyh();

        if(is_numeric($date1))
            $date1 = nowyh($date1);
        if(is_numeric($date2))
            $date2 = nowyh($date2);


//
//        if($date2 > $date1)
//        {
//            $tmp = $date2;
//            $date2 = $date1;
//            $date1 = $tmp;
//        }


        $start = new \DateTime($date1);
        $start->modify('first day of this month');
        $end = new \DateTime($date2);
        $end->modify('first day of this month'); // $end->modify('first day of next month');
        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($start, $interval, $end);


        if(isDebugIp()){


        }

        $arrMonth = array();
        foreach ($period as $dt) {
            //echo $dt->format("Y-m") . "<br>\n";
            $arrMonth[] = $dt->format("Y-m");
        }

        return $arrMonth;
    }

    //null date2 is to Now
    static function getArrayDayBetWeenDates($fromdate, $todate = null) {
        return self::ListDayBetWeenDate($fromdate, $todate);
    }

    //null date2 is to Now
    static function getArrayMonthBetWeenDates($fromdate, $todate = null) {
        return self::ListMonthBetWeenDate($fromdate, $todate);
    }

    //null date2 is to Now
    static function ListDayBetWeenDate($fromdate, $todate = null) { //null date2 is to Now
        if (!isset($todate))
            $todate = nowy();

        $datediff = strtotime($todate) - strtotime($fromdate);
        $datediff = floor($datediff / (60 * 60 * 24));
        $arr = array();
        for ($i = 0; $i < $datediff + 1; $i++) {
            $arr[] = date("Y-m-d", strtotime($fromdate . ' + ' . $i . 'day')) . "";
        }

        return $arr;
    }

    static function getMonth($n = 0) {
        $date = date_create(nowy());
        date_add($date, date_interval_create_from_date_string("$n month"));
        return date_format($date, "Y-m");
    }


}

<?php

namespace LadLib\Common;

use App\Components\Helper1;
use LadLib\Common\cstring2;
use LadLib\Common\UrlHelper1;

class clsPaginator2
{

    static public function getPageRange($totalPage, $currentPage = 1, $range = 10)
    {

        if (!$totalPage) {
            return [0, 0];
        }

        if ($currentPage < floor($range / 2)) {
            $from = 1;
            $to = $totalPage < $range ? $totalPage : $range;
            return array($from, $to);
        }
        if ($currentPage > $totalPage - floor($range / 2)) {
            $from = $totalPage - $range;
            if ($from <= 0)
                $from = 1;
            $to = $totalPage;
            return array($from, $to);
        }

        $from = $currentPage - floor($range / 2);
        if ($from <= 0)
            $from = 1;
        $to = $currentPage + floor($range / 2);
        return array($from, $to);
    }

    static public function getNextPage($uriBase, $nPage, $cPage)
    {
        $padPage = "&page=";
        $padEnd = "&";
        if (strstr($uriBase, "/page/")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "/page/";
            $padEnd = "/";
        } elseif (strstr($uriBase, "?page=")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "?page=";
            $padEnd = "&";
        } else {
            if (strstr($uriBase, "?")) {
                return $uriBase . "&page=2";
            } else
                return $uriBase . "?page=2";
        }

        $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage + 1, $padPage, "$padEnd");
        return $uriOK;
    }

    static public function getPrevPage($uriBase, $nPage, $cPage)
    {
        $padPage = "&page=";
        $padEnd = "&";
        if (strstr($uriBase, "/page/")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "/page/";
            $padEnd = "/";
        } elseif (strstr($uriBase, "?page=")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "?page=";
            $padEnd = "&";
        }

        $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage - 1, $padPage = 0, "$padEnd");
        return $uriOK;
    }



    public static function showPaginatorBasicStyleULLI($uriBase = null, $totalItem = 0, $limitPerPage = 0, $currentPage = 0, $rangeShow = 5, $ulClass='', $liClass='', $liActive = '', $aClass='', $aClassActive = '')
    {

        if ($totalItem <= 0)
            return null;


        $mm = clsPaginator2::getArrayLinkPaginator($uriBase, $totalItem, $limitPerPage, $currentPage, $rangeShow);
        $ret = "<ul class='$ulClass'>";
        foreach ($mm as $k => $link) {

            $show = $k;
            if ($k == 'first')
                $show = " « ";
            if ($k == 'last')
                $show = " » ";
            if ($k == 'prev')
                $show = " ‹ ";
            if ($k == 'next')
                $show = " › ";
            $padClass = '';
            if ($k == 'current') {
                $padClass = $aClassActive;
                $show = " <b>$currentPage</b>";
            }

            if ($k == 'empty1' || $k == 'empty2')
                $ret .= " <li class='$liClass dotdotdot'> ... </li> ";
            else
                $ret .= " <li class='$liClass'> <a class='$aClass $padClass' style='text-decoration: none' href='$link'>$show</a> </li>";
        }

        $fromItem = $limitPerPage * ($currentPage - 1) + 1;
        $toItem = $limitPerPage * ($currentPage);
        if ($toItem > $totalItem) {
            $toItem = $totalItem;
        }

//        $ret .= " <span> Show <b>$fromItem - $toItem </b> of <b>$totalItem</b> </span>";

        $ret .= "</ul>";
        return $ret;
    }

    //2020
    public static function showPaginatorBasicStyle($uriBase = null, $totalItem = 0, $limitPerPage = 0, $currentPage = 0, $rangeShow = 5)
    {

        if ($totalItem <= 0)
            return null;


        $mm = clsPaginator2::getArrayLinkPaginator($uriBase, $totalItem, $limitPerPage, $currentPage, $rangeShow);
        $ret = "<div class='paginator_glx'>";
        foreach ($mm as $k => $link) {

            $show = $k;
            if ($k == 'first')
                $show = " << ";
            if ($k == 'last')
                $show = " >> ";
            if ($k == 'prev')
                $show = " > ";
            if ($k == 'next')
                $show = " > ";
            $padClass = '';
            if ($k == 'current') {
                $padClass = 'pg_selecting';
                $show = " <b>$currentPage</b>";
            }

            if ($k == 'empty1' || $k == 'empty2')
                $ret .= " ... ";
            else
                $ret .= " <a class='link_pg $padClass' style='text-decoration: none' href='$link'>$show</a>";
        }

        $fromItem = $limitPerPage * ($currentPage - 1) + 1;
        $toItem = $limitPerPage * ($currentPage);
        if ($toItem > $totalItem) {
            $toItem = $totalItem;
        }

        $ret .= " <span> Show <b>$fromItem - $toItem </b> of <b>$totalItem</b> </span>";

        $ret .= "</div>";
        return $ret;
    }

    /**
     * === Đưa ra một mảng limit, offset của 2 bảng, khi số trang được nhập vào
     * Mục đích: thông thường ta phân trang 1 bảng dễ dàng với limit, offset
     * Tuy nhiên, khi có 2 bảng ta cần nối nhau phân trang, bảng 1 rồi đến bảng 2
     * Vậy cần phân trang bảng 1 trước, hết trang có phần tử bảng 1 thì sẽ đến phần tử bảng 2
     * Như vậy tại mỗi trang, sẽ có 3 trường hợp tuần tự:
     * - chỉ có phần tử bảng 1 (là những trang đầu)
     * - hoặc có cả 2 phần tử thuộc bảng 1 và 2 (có 1 trang này , hoặc không có nếu số phần tử bảng 1 chia hết cho $limit)
     * - và cuối cùng là các trang thuộc bảng thứ 2
     * === Kết quả hàm này sẽ đưa ra offset, limit của 2 bảng để query db
     * Với đầu vào là $totalInTab1, $limit, và số $currentPage = trang hiện tại, chạy từ 1 đến N (N sẽ dừng khi hết bảng 2)
     * @param $totalInTab1
     * @param $limit
     * @param $currentPage
     * @return array
     */
    static function createArrayLimitOffset2TableToQueryPaginator($totalInTab1, $limit, $currentPage)
    {
        $limit1 = $limit2 = $offset1 = $offset2 = -1;
        //Tính Trang cuối có chứa phần tử của tbl1
        $lastPageHaveElmOfTbl1 = ceil($totalInTab1 / $limit);
        //Tính Trang đầu tiên xuất hiện phần tử của tbl2
        if ($totalInTab1 % $limit == 0) {
            $firstPageHaveElmOfTbl2 = $lastPageHaveElmOfTbl1 + 1;
            //Limit của trang đầu tiên có phần tử ở tbl2
            $firstLimitOfTbl2InFisrtHaveElm2 = $limit;
        } else {
            $firstPageHaveElmOfTbl2 = $lastPageHaveElmOfTbl1;
            //Limit tbl2 của trang đầu tiên xuất hiện phần tử của tbl2, là (Limit- số Dư của $totalInTab1 / $limit)
            $firstLimitOfTbl2InFisrtHaveElm2 = $limit - $totalInTab1 % $limit;
        }

        //Nếu trang hiện tại nhỏ hơn trang xuất hiện phần tử đầu tiên của tbl2, thì data sẽ ko có phần tử nào thuộc tbl2
        if ($currentPage < $firstPageHaveElmOfTbl2) {
            $limit1 = $limit;
            $offset1 = $limit1 * ($currentPage - 1);
        }
        //Nếu trang hiện tại = trang xuất hiện phần tử đầu tiên của tbl2
        //Thì có 2 trường hợp, có hoặc không còn phần tử tbl1
        elseif ($currentPage == $firstPageHaveElmOfTbl2) {
            $offset2 = 0;
            //Không còn phần tử của Tbl1
            if ($firstLimitOfTbl2InFisrtHaveElm2 == $limit) {
                //Khi này thì limit = gốc, và offset sẽ tính ra
                $limit2 = $limit;
            } //Còn phần tử của tbl1
            else {
                $limit1 = $totalInTab1 % $limit;
                $offset1 = $limit * ($currentPage - 1);
                $limit2 = $firstLimitOfTbl2InFisrtHaveElm2;
            }
        }
        //Nếu trang hiện tại lớn hơn trang đầu tiên có phần tử tbl2, thì đơn giản
        //chỉ cần tính bình thường
        elseif ($currentPage > $firstPageHaveElmOfTbl2) {
            $limit2 = $limit;
            $offset2 = $limit * ($currentPage - 1) - $totalInTab1;
        }

        return ["offset1" => $offset1, 'limit1' => $limit1, 'offset2' => $offset2, 'limit2' => $limit2];
    }

    static function createArrayLimitOffset2TableToQueryPaginatorTester(){

        //Thay đổi số phần tử bảng 1 là đủ số liệu
        for ($totalInTable1 = 1; $totalInTable1 < 30; $totalInTable1++) {
            $limit = 10;
            $mRet = [];
            $totalElmIn2Table = 0;
            for ($i = 1; $i < 10; $i++) {
                $mRet[] = clsPaginator2::createArrayLimitOffset2TableToQueryPaginator($totalInTable1, $limit, $i);
                $totalElmIn2Table += $limit;
            }
//    echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//    print_r($mRet);
//    echo "</pre>";
            $foundElmInTable1 = $foundElmInTable2 = 0;
            for ($i = 0; $i < count($mRet); $i++) {
                $ret = $mRet[$i];
//            echo "<br/>\n $i ---";
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($ret);
//        echo "</pre>";

                if ($i > 1) {
                    if ($ret['offset1'] > 0)
                        //Kiểm tra xem offset mới có bằng offset cũ + limit không
                        if ($ret['offset1'] != $offset1 + $limit1) {
                            die(" Có lỗi 1 - $i : $offset1 + $limit vs " . $ret['offset1']);
                            return 0;
                        }
                    if ($ret['offset2'] > 0)
                        //Kiểm tra xem offset mới có bằng offset cũ + limit không
                        if ($ret['offset2'] != $offset2 + $limit2) {
                            die(" Có lỗi 2 - $i : $offset2 + $limit vs " . $ret['offset2']);
                            return 0;
                        }
                }

                $limit1 = $ret['limit1'];
                $limit2 = $ret['limit2'];
                $offset1 = $ret['offset1'];
                $offset2 = $ret['offset2'];

                if ($limit2 >= 0 && $offset2 >= 0) {
                    $foundElmInTable2 += $limit2;
//                echo "<br/>\n --- $foundElmInTable2 ";
                }

                if ($limit1 >= 0 && $offset1 >= 0) {
                    $foundElmInTable1 += $limit1;
//                echo "<br/>\n --- $foundElmInTable1 ";
                }


            }

            if ($foundElmInTable1 != $totalInTable1) {
                die(" Có lỗi 3 foundElmInTable1 != totalInTable1 : $foundElmInTable1 != $totalInTable1");
                return 0;
            }

//        echo "<br/>\n TotalElm1 = $foundElmInTable1";
//        echo "<br/>\n TotalElm2 = $foundElmInTable2";

            if ($foundElmInTable1 + $foundElmInTable2 == $totalElmIn2Table) {
                echo "<br/>\n OK số phần tử 2 table: $foundElmInTable1 + $foundElmInTable2 == $totalElmIn2Table";
            } else {
                die("<br/>\n Có lỗi số phần tử 2 table cộng lại ko bằng tổng? ($foundElmInTable1 + $foundElmInTable2 == $totalElmIn2Table)");
                return 0;
            }
        }
    }

    /*
     * LAD 2020
     */
    public static function getArrayLinkPaginator($uriBase = null, $totalItem = 0, $limitPerPage = 0, &$currentPage = 0, $rangeShow = 5)
    {

        $nPage = ceil($totalItem / $limitPerPage);

        if ($currentPage > $nPage)
            $currentPage = $nPage;

        //echo "<br/>\n N PAg = $nPage, Cpage = $currentPage";

        $arrayWillShow = [];

        if ($rangeShow > $totalItem)
            $rangeShow = $totalItem;


        if ($currentPage < floor($rangeShow / 2)
            || $nPage <= $rangeShow
        )
            $page1 = 1;
        else
            $page1 = ceil($currentPage - $rangeShow / 2);


        if ($nPage - $page1 < $rangeShow)
            $page1 = $nPage - $rangeShow + 1;

        if ($page1 < 1)
            $page1 = 1;


        $pageEnd = $page1 + $rangeShow - 1;

        if ($pageEnd > $nPage)
            $pageEnd = $nPage;

        $prevPage = $currentPage - 1;
        $nextPage = $currentPage + 1;
        if ($prevPage < 1)
            $prevPage = 1;

        if ($nextPage > $nPage)
            $nextPage = $nPage;

        if ($nextPage <= 0)
            $nextPage = 1;
        if ($pageEnd <= 0)
            $pageEnd = $nPage;

        //echo "<br/>\n Page1 = $page1 -> $pageEnd";

        //echo "<br/>\n Prev / Next  = $prevPage / $nextPage";


//    $toMax = ($rangeShow + $currentPage) > $totalItem  ? $totalItem: ($rangeShow + $currentPage);
//
//    echo "<br/>\n $rangeShow + $currentPage / Max = $toMax";
//
//    for($i = $currentPage; $i<= $toMax; $i++){
//        echo "<br/>\n rang: $i ";
//    }

        $mm = [];


        $p = UrlHelper1::setUrlParam($uriBase, "page", 1);
        $mm['first'] = $p;
//
        $p = UrlHelper1::setUrlParam($uriBase, "page", $prevPage);
        $mm['prev'] = $p;

        if ($page1 > 1) {
            $mm['empty1'] = "#";
        }

        //echo "<br/>\n Page1 = $page1 / $pageEnd / $currentPage";

        //for($i = 1; $i<=$nPage; $i++){
        for ($i = $page1; $i <= $pageEnd; $i++) {
            $p = UrlHelper1::setUrlParam($uriBase, "page", $i);

            if ($currentPage == $i)
                $mm["current"] = $p;
            else
                $mm[$i] = $p;

        }

        if ($pageEnd < $nPage - 1) {
            $mm['empty2'] = "#";
        }

        if ($pageEnd < $nPage) {
            $p = UrlHelper1::setUrlParam($uriBase, "page", $nPage);
            $mm[$nPage] = $p;
        }

        $p = UrlHelper1::setUrlParam($uriBase, "page", $nextPage);
        $mm['next'] = $p;
        $p = UrlHelper1::setUrlParam($uriBase, "page", $nPage);
        $mm['last'] = $p;

        //UrlHelper1::setUrlParam();
        return $mm;
    }

    static public function getPaginatorStringUlLi($uriBase, $nPage, $cPage, $limitPerPage, $totalItem, $range = 10)
    {


        if ($totalItem <= $limitPerPage)
            return "";
        echo "\n<ul class='pagination'>";

        $padPage = "&page=";
        $padEnd = "&";
        if (strstr($uriBase, "/page/")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "/page/";
            $padEnd = "/";
        } elseif (strstr($uriBase, "?page=")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "?page=";
            $padEnd = "&";
        }

        //Xoa het dau hieu page, để điền lại:
        //$uriBase = cstring2::replaceStringBetween2String($uriBase,"/page/to/del","$padPage","&");
        //$uriBase = str_replace("/page/to/del", "", $uriBase);

        if (!strstr($uriBase, "?"))
            $uriBase .= "?";
        if (!strstr($uriBase, "$padPage"))
            $uriBase .= "$padPage";

        $strPaginator = '';

        $arrRange = clsPaginator2::getPageRange($nPage, $cPage);

        $maxPage1 = $nPage < 3 ? $nPage : 3;
        //for ($i = $arrRange[0]; $i <= $arrRange[1]; $i++) {
        for ($i = 1; $i <= $maxPage1; $i++) {
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $i, $padPage, "$padEnd");
            if ($cPage == $i) {
                //$strPaginator .= " <b>[$i]</b> ";
                $strPaginator .= "<li class='page-item active'><a title='Go to $i' class='page-link'>$i</a></li>";
            } else {
                $strPaginator .= "<li class='page-item'><a title='Go to $i' class='page-link' href='$uriOK'>$i</a></li>";
                //$strPaginator .= "<a href='$uriOK'>" . $i . "</a>";
            }
        }

//        if($nPage > 3)
//            $strPaginator.= " ... ";

        if ($cPage > 3) {
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage, $padPage, "$padEnd");
            //$strPaginator .= "<a class=\"active\" href='$uriOK'>" . $cPage . "</a>";
            $strPaginator .= "<li class='page-item active'><a title='Go to $cPage' class='page-link' href='$uriOK'>$cPage</a></li>";
        }

        if ($cPage < $nPage)
            $strPaginator .= " ... ";

        if ($nPage > $arrRange[1]) {
            //$strPaginator.= " ... <a href='$uriBase" . $nPage . "'>" . $nPage . "</a> ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $nPage, $padPage, "$padEnd");
            //$strPaginator.= "<a href='$uriOK'>" . $nPage . "</a>";
            $strPaginator .= "<li class='page-item'><a title='Go to $nPage'  class='page-link' href='$uriOK'>$nPage</a></li>";
        }

        if ($cPage >= $nPage)
            $nextButton = "        <li class='page-item'>
            <a title='Next'  class='page-link' href='#' aria-label='Next'>
                <span  aria-hidden='true'>›</span>
            </a>
        </li>";
        else {
            //$nextButton = " | <a href='$uriBase" . ($cPage + 1) . "'> Next</a> ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage + 1, $padPage, "$padEnd");
            $nextButton = "        <li class='page-item'>
            <a title='Next' class='page-link' href='$uriOK' aria-label='Next'>
                <span aria-hidden='true'>›</span>

            </a>
        </li>";
        }

        if ($cPage <= 1)
            $preButton = "         <li class='page-item'>
            <a title='Previous' class='page-link' href='#' aria-label='Previous'>
                <span title='Previos' aria-hidden='true'>‹</span>
            </a>
        </li> ";
        else {
            //$preButton = "<a href='$uriBase" . ($cPage - 1) . "'>Prev</a> | ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage - 1, $padPage, "$padEnd");
            $preButton = "        <li class='page-item'>
            <a title='Previous' class='page-link' href='$uriOK' aria-label='Previous'>
                <span title='Previos' aria-hidden='true'>‹</span>
            </a>
        </li>";
        }

        if ($arrRange[0] > 1) {
            $preButton .= "...";
        }

        $uriOK = cstring2::replaceStringBetween2String($uriBase, 1, $padPage, "$padEnd");
        //$firstButton = "<a href='$uriOK'>&#171;</a>";
        $firstButton = "<li class='page-item'><a title='First' class='page-link' href='$uriOK'> « </a></li>";

        $uriOK = cstring2::replaceStringBetween2String($uriBase, $nPage, $padPage, "$padEnd");
        //$firstButton = " | <a href='$uriOK" . 1 . "'> First</a> ";
        //$lastButton = "<a href='$uriBase" . $nPage . "'>Last</a> ";
        //$lastButton = "<a href='$uriOK'>&#187;</a>";
        $lastButton = "<li class='page-item'><a title='Last' class='page-link' href='$uriOK'> » </a></li>";

        $fromI = $limitPerPage * ($cPage - 1) + 1;
        $toI = $limitPerPage * $cPage;
        $toI = ($toI < $totalItem ? $toI : $totalItem);

//        $firstButton = $lastButton = null;

        $strPaginator = "$firstButton $preButton $strPaginator $nextButton $lastButton";

        echo $strPaginator;

        echo "\n</ul>";
    }

    /*
     * Tạo str paginator
     */
    static public function getPaginatorString($uriBase, $nPage, $cPage, $limitPerPage, $totalItem, $range = 10)
    {

        if ($totalItem <= $limitPerPage)
            return "";

        $padPage = "&page=";
        $padEnd = "&";
        if (strstr($uriBase, "/page/")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "/page/";
            $padEnd = "/";
        } elseif (strstr($uriBase, "?page=")) {
            //cstring2::replaceStringBetween2String("")
            $padPage = "?page=";
            $padEnd = "&";
        }

        //Xoa het dau hieu page, để điền lại:
        //$uriBase = cstring2::replaceStringBetween2String($uriBase,"/page/to/del","$padPage","&");
        //$uriBase = str_replace("/page/to/del", "", $uriBase);

        if (!strstr($uriBase, "?"))
            $uriBase .= "?";
        if (!strstr($uriBase, "$padPage"))
            $uriBase .= "$padPage";

        $strPaginator = '';

        $arrRange = clsPaginator2::getPageRange($nPage, $cPage);

        $maxPage1 = $nPage < 3 ? $nPage : 3;
        //for ($i = $arrRange[0]; $i <= $arrRange[1]; $i++) {
        for ($i = 1; $i <= $maxPage1; $i++) {
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $i, $padPage, "$padEnd");
            if ($cPage == $i) {
                //$strPaginator .= " <b>[$i]</b> ";
                $strPaginator .= "<a class=\"active\">$i</a>";
            } else
                $strPaginator .= "<a href='$uriOK'>" . $i . "</a>";
        }

        if ($nPage > 3)
            $strPaginator .= " ... ";

        if ($cPage > 3) {
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage, $padPage, "$padEnd");
            $strPaginator .= "<a class=\"active\" href='$uriOK'>" . $cPage . "</a>";
        }

        if ($cPage < $nPage)
            $strPaginator .= " ... ";

        if ($nPage > $arrRange[1]) {
            //$strPaginator.= " ... <a href='$uriBase" . $nPage . "'>" . $nPage . "</a> ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $nPage, $padPage, "$padEnd");
            $strPaginator .= "<a href='$uriOK'>" . $nPage . "</a>";
        }

        if ($cPage >= $nPage)
            $nextButton = "<a href='#'>&#155;</a>";
        else {
            //$nextButton = " | <a href='$uriBase" . ($cPage + 1) . "'> Next</a> ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage + 1, $padPage, "$padEnd");
            $nextButton = "<a href='$uriOK'>&#155;</i></a>";
        }

        if ($cPage <= 1)
            $preButton = " <a href='#'>&#139;</a> ";
        else {
            //$preButton = "<a href='$uriBase" . ($cPage - 1) . "'>Prev</a> | ";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $cPage - 1, $padPage, "$padEnd");
            $preButton = "<a href='$uriOK'>&#139;</a>";
        }

        if ($arrRange[0] > 1) {
            $preButton .= "...";
        }

        $uriOK = cstring2::replaceStringBetween2String($uriBase, 1, $padPage, "$padEnd");
        $firstButton = "<a href='$uriOK'>&#171;</a>";
        //$firstButton = "<a href='$uriBase" . 1 . "'>First</a> ";

        $uriOK = cstring2::replaceStringBetween2String($uriBase, $nPage, $padPage, "$padEnd");
        //$firstButton = " | <a href='$uriOK" . 1 . "'> First</a> ";
        //$lastButton = "<a href='$uriBase" . $nPage . "'>Last</a> ";
        $lastButton = "<a href='$uriOK'>&#187;</a>";

        $fromI = $limitPerPage * ($cPage - 1) + 1;
        $toI = $limitPerPage * $cPage;
        $toI = ($toI < $totalItem ? $toI : $totalItem);

        $strPaginator = "$firstButton $preButton $strPaginator $nextButton $lastButton";

        $strSelect = "<select class='select_glx' onChange=\"window.location.href=this.value\">";

        for ($i = 1; $i <= $nPage; $i++) {
            //$link = "<a href='$uriBase?&page=$i'>$uriBase?&page=$i </a>";
            $uriOK = cstring2::replaceStringBetween2String($uriBase, $i, $padPage, "$padEnd");
            //$uriOK = "<a href='$uriOK'> $uriOK </a> ";
            $pad = "";
            if ($cPage == $i)
                $pad = " selected ";
            $strSelect .= "<option $pad value='$uriOK'>Page $i</option>";
        }

        $strSelect .= "</select>";
        $strPaginator .= $strSelect;
        //$strPaginator .= " <br/> [ $fromI - $toI of $totalItem ]";

        return "" . $strPaginator . " <span style='color: ; font-size: '> ($totalItem)</span>";

    }

}

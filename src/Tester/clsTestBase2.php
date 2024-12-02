<?php

namespace LadLib\Common\Tester;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverPoint;

/**
 * Trang bị một số hàm static để code test tiện lợi hơn
 */
class clsTestBase2 {

    /**
     * @var WebDriver
     */
    static public $driver ;

    /**
     * @param $serverUrl
     * @return RemoteWebDriver
     */
    static function initDriver($server = 'localhost', $port = 9515, $setPos = 1){

        if(!$server)
            $server = 'localhost';
        if(!$port)
            $port = 9515;

        $serverUrl = "http://$server:$port";
        $socket = fsockopen($server, $port, $e1, $e2, 1);

        if(!$socket){
            $cmd = "e:/2/chromedriver.exe";
            //exec($cmd);
            die("\n Not run driver on port: $port , CMD: $cmd?");
        }

        $options = new ChromeOptions();


//        $userAgent = "lad_tester_2020";

        //Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36
        $options->addArguments(array(
//            '--user-agent=' . $userAgent,
//            "--disable-web-security"
        "--disable-notifications"
        ));

//        _chrome.AddUserProfilePreference("credentials_enable_service", false);

        //chrome_options.add_argument("")

        $options->setExperimentalOption('excludeSwitches' , ["enable-automation"]);

        ///////////////////
        //Bỏ show save pw:
        //https://stackoverflow.com/questions/43223857/save-password-for-this-website-dialog-with-chromedriver-despite-numerous-comm
        $prefs = array("credentials_enable_service" => false,
            "profile.password_manager_enabled"=>false);
        $options->setExperimentalOption('prefs', $prefs);

//        cOpt.AddUserProfilePreference("credentials_enable_service", false);
//        cOpt.AddUserProfilePreference("profile.password_manager_enabled", false);

//        options.addArguments("--start-maximized");
//        options.addArguments("--disable-web-security");
//        options.addArguments("--no-proxy-server");


//        ChromeOptions options = new ChromeOptions();
//options.setExperimentalOption("excludeSwitches", new String[]{"enable-automation"});
//WebDriver driver = new ChromeDriver(options);

        $caps = DesiredCapabilities::chrome();
        $caps->setCapability(ChromeOptions::CAPABILITY, $options);
        $caps->setCapability( 'loggingPrefs', ['browser' => 'ALL'] );

        //$driver = clsTester::$driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
        $driver = self::$driver = RemoteWebDriver::create($serverUrl, $caps);

        if($setPos){
            self::$driver->manage()->window()->setPosition(new WebDriverPoint(500,10));
            self::$driver->manage()->window()->setSize(new WebDriverDimension(1200, 980));
        }

        return $driver;
    }

    static function clickFirstAutoCompleteDown(){
        $slAutoComplete1 = "//ul[contains(@class,'ui-autocomplete')][not(contains(@style,'display: none'))]//*[@class='ui-menu-item-wrapper']";
        self::$driver->wait(3)->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath($slAutoComplete1)));
        clsTestBase2::findOneByXPath($slAutoComplete1, 0)->click();
    }

    /**
     * @param $obj WebDriverElement
     * @return WebDriverElement
     */
    static function findParent($obj){
        return $obj->findElement(WebDriverBy::xpath("./.."));
    }

    static function clickAlertDialogOK(){
        clsTestBase2::$driver->wait()->until(
            WebDriverExpectedCondition::alertIsPresent()
        );
        clsTestBase2::$driver->switchTo()->alert()->accept();
    }

    static function clickOnFirstMenuContext($menuName){
        clsTestBase2::findOneByXPath("//li[contains(@class, 'context-menu-item')]/span[contains(text(), '$menuName')]")->click();
    }

    static function findOneContainClass($tag , $class, $index = 0){
        return self::findOneByXPath("//$tag"."[contains(@class,'$class')]", $index);
    }

    static function findAllContainClass($tag , $class){
        return self::findOneByXPath("//$tag"."[contains(@class,'$class')]", -1);
    }

    static function findOneContainClassAndVisible($tag , $class){
        $mm = self::findOneByXPath("//$tag"."[contains(@class,'$class')]", -1);
        foreach ($mm AS $obj){
            if($obj->isDisplayed())
                return $obj;
        }
        return null;
    }

    /**
     * @param $path
     * @return RemoteWebElement[]|null
     */
    static function findAllByXPath($path){
        try{
            $ret = clsTestBase2::$driver->findElements(WebDriverBy::xpath($path));
            return $ret;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error1 findAllByXPath: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error2 findAllByXPath: ".$exception->getMessage();
            return  null;
        }
    }

    /**
     * @param $path
     * @param $checkDisplay
     * @return RemoteWebElement
     */
    static function findOneByXPath($str , $index = 0, $checkDisplay = 0){
        if(!clsTestBase2::$driver)
            loi2("Not init webdriver?");
        try{
            $ret = self::$driver->findElements(WebDriverBy::xpath($str));
            if($index == -1)
                return $ret;
            if(count($ret) > 0){
                return $ret[$index];
            }
            return null;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error19: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error25: ".$exception->getMessage();
            return  null;
        }
    }

    /**
     * @param $path
     * @return WebDriverElement
     */
    static function findOneByClassName($str, $index = 0, $checkDisplay = 0){
        if(!clsTestBase2::$driver)
            loi2("Not init webdriver?");
        try{

            $ret = self::$driver->findElements(WebDriverBy::className($str));

            if(count($ret) > 0){
                return $ret[$index];
            }
            return null;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error18: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error22: ".$exception->getMessage();
            return  null;
        }
    }

    /**
     * @param $path
     * @return WebDriverElement
     */
    static function findOneByCssSelector($str, $index = 0, $checkDisplay = 0){
        if(!clsTestBase2::$driver)
            loi2("Not init webdriver?");
        try{
            $ret = self::$driver->findElements(WebDriverBy::cssSelector($str));
            if(count($ret) > 0){
                return $ret[$index];
            }
            return null;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error18: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error22: ".$exception->getMessage();
            return  null;
        }
    }

    /**
     * @param $path
     * @return WebDriverElement
     */
    static function findOneById($str, $index = 0, $checkDisplay = 0){
        if(!clsTestBase2::$driver)
            loi2("Not init webdriver?");
        try{

            $ret = self::$driver->findElements(WebDriverBy::id($str));

            if(count($ret) > 0){
                return $ret[$index];
            }
            return null;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error17: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error2: ".$exception->getMessage();
            return  null;
        }
    }

    static function findOneByTextName($str, $index = 0, $checkDisplay = 0){
        if(!clsTestBase2::$driver)
            loi2("Not init webdriver?");
        try{

            $ret = self::$driver->findElements(WebDriverBy::xpath("//*[text()='$str']"));

            if(count($ret) > 0){
                return $ret[$index];
            }
            return null;
        }
        catch (\Throwable $e) { // For PHP 7
            echo "<br/>\n Error17: ".$e->getMessage();
            return  null;
        }
        catch (\Exception $exception){
            echo "<br/>\n Error2: ".$exception->getMessage();
            return  null;
        }
    }

}

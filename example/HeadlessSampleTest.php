<?php
use Cake\Datasource\ConnectionManager;
use Facebook\WebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Cake\Core\Configure;
use KentaroA\HeadlessBrowserTesting;


class HeadlessSampleTest extends HeadlessBrowserTesting {
	private $test_name = "HeadlessSampleTest";

	/*
	 *	Initialize
	 *
	 */
	public static function initialize() {
		return [
			"selenium_host" => "http://localhost:60002/wd/hub/",
			"chrome_binary_path" => "/usr/bin/google-chrome",
			"screen_shot_dir" => ROOT ."/webroot/img/",
			"ua" => "Mozilla/5.0 (Macintosh, Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.167 Safari/537.36",
			"window_size" => ["width"=>1480, "height"=>2000],
			"initialize_sql_file_path" => "/path/to/init.sql",
			"env_path" => "",
			"datasoruce_key" => "",
		];

	}


	public function test001() {
		// Clean up image directory of this case.
		$this->initOutputDir($this->test_name, __FUNCTION__);
		// Driver初期化
		$dk = "dk";
		$this->initDriver($dk);
		$this->getDriver($dk)->get("http://tamago-engineer.hateblo.jp/");
		$this->waitUntil($dk, WebDriverExpectedCondition::titleContains("TRY AND"));
		$this->screenshot($dk, $this->test_name, __FUNCTION__, "test001.png", __LINE__);
		$this->assertEquals(1,1);
	}


	public function test002() {
		// Clean up image directory of this case.
		$this->initOutputDir($this->test_name, __FUNCTION__);
		// Driver初期化
		$dk = "dk";
		$this->initDriver($dk);
		$this->getDriver($dk)->get("http://tamago-engineer.hateblo.jp/");
		$this->waitUntil($dk, WebDriverExpectedCondition::titleContains("TRY AND"));
		$this->screenshot($dk, $this->test_name, __FUNCTION__, "test002.png", __LINE__);
		$this->assertEquals(1,1);
	}


}

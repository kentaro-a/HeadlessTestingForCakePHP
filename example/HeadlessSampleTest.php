<?php
use App\Controller\Users\ProjectsController;
use Cake\TestSuite\IntegrationTestCase;
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
use Cake\Controller\ComponentRegistry;
use App\Controller\Component\CarComponent;
use App\Controller\Component\UtilComponent;
use KentaroA\HeadlessBrowserTesting;


/*
 * Place this file on tests/TestCase/
 *
 * */
class HeadlessSampleTest extends HeadlessBrowserTesting {
	private $test_name = "HeadlessSampleTest";
	/*
	 */
	public function test001() {
		// Clean up image directory of this case.
		$this->initOutputDir($this->test_name, __FUNCTION__);
		// Driver初期化
		$dk = "dk";
		$this->initDriver($dk);
		$this->getDriver($dk)->get("http://tamago-engineer.hateblo.jp/");
		$this->waitUntil($dk, WebDriverExpectedCondition::titleContains("TRY AND"));
		$this->screenshot($dk, $this->test_name, __FUNCTION__, "sample.png", __LINE__);
		$this->assertEquals(1,1);

	}


}

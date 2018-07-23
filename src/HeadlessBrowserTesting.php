<?php
namespace KentaroA;

use App\Controller\Users\ProjectsController;
use Cake\TestSuite\IntegrationTestCase;
use Cake\Datasource\ConnectionManager;
use Facebook\WebDriver;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\Remote;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\I18n\FrozenTime;


/**
 * Wrapper for Headless Browser Test With Selenium
 *
 * # Install google-chrome
 * $ curl https://intoli.com/install-google-chrome.sh | bash
 *
 * # Launch selenium standalone on localhost:specific port as daemon
 * $ vendor/se/selenium-server-standalone/bin/selenium-server-standalone -port 60002 -log "/tmp/selenium.log" > /dev/null 2>&1 &
 *
 * # Make sure processes
 * $ ps aux|grep -e Xv -e selenium -e chrome
 *
 * # Kill virtual display, chrome and selenium
 * $ pkill -f chrome ; pkill -f selenium
 *
 * # Kill relations and start selenium standalone
 * $ pkill -f chrome ; pkill -f selenium ; vendor/se/selenium-server-standalone/bin/selenium-server-standalone -port 60002 -log "/tmp/selenium.log" > /dev/null 2>&1 &
 *
 * # Testing
 * $ vendor/bin/phpunit tests/TestCase/HeadlessSampleTest.php --filter test001
 *
 * # Xpath tip
 * # Chrome Addon "ChroPath" is the easiest way to get a xpath of specific element.
 *
 */
abstract class HeadlessBrowserTesting extends IntegrationTestCase {

	/**
	 * Properties
	 *
	 */
	private $_selenium_host = "http://localhost:60002/wd/hub/";
	private $_screen_shot_dir = ROOT ."/path/to/dir/";
	private $_ua = "user agent";
	private $_window_size = ["width"=>1480, "height"=>2000];
	private $_initialize_sql_file_path = ROOT."/path/to/init.sql";
	private $_env_path = "environments/headless_testing";
	private $_datasoruce_key = "test_master";
	private $_chrome_binary_path = "/usr/bin/google-chrome";
	private $_drivers;
	private $_conn;


	/**
	 * Getter
	 *
	 */
	public function getDriver($driver_key) {
		return $this->_drivers[$driver_key] ?? false;
	}
	public function getDrivers() {
		return $this->_drivers;
	}
	public function getUA() {
		return $this->_ua;
	}



	/**
	 * Setup before testing
	 *
	 */
	public function setUp() {
		// Load config/environments/selenium_test.php instead of app.php
		if (!empty($this->_env_path)) {
			Configure::load($this->_env_path, "default", false);
		}
		$this->initDB();
		parent::setUp();
	}



	/**
	 * Initialize test DB
	 *
	 */
	private function initDB() {
		// Override config with other key because the same key isn't accepted.
		$this->_conn = ConnectionManager::get($this->_datasoruce_key);
		// Execute sql in $_initialize_sql_file_path if it's provided.
		if (file_exists($this->_initialize_sql_file_path)) {
			$sql = file_get_contents($this->_initialize_sql_file_path);
			$this->_conn->execute($sql);
		}
	}



	/**
	 * Clean up after testing
	 *
	 */
	public function tearDown() {
		$ds = $this->getDrivers();
		if (!empty($ds)) {
			foreach ($ds as &$d) {
				$d->quit();
			}
			$this->_drivers = [];
		}
		parent::tearDown();
	}




	/*
	 * Check if driver key exist.
	 * @param string driver_key: Existed driver key. // required,
	 * @return bool
	 *
	 */
	public function checkDriverExist($driver_key) {
		return !empty($this->getDriver($driver_key)) ? true : false;
	}



	/*
	 * Check if selector is Facebook\WebDriver\WebDriverBy or not.
	 * @param any selector: Object // required,
	 * @return: bool
	 *
	 */
	public function checkSelector($selector) {
		return (get_class($selector) === "Facebook\WebDriver\WebDriverBy");
	}



	/**
	 * Initialize WebDriver
	 * @param string driver_key: Existed driver key. // required,
	 *
	 */
	public function initDriver($driver_key) {
		if ($this->checkDriverExist($driver_key)) {
			return true;
		}
		$options = new ChromeOptions();
		$options->addArguments([
			'--user-agent=' .$this->_ua,
			'--headless',
			'--window-size=' ."{$this->_window_size['width']},{$this->_window_size['height']}",
			'--no-sandbox',
		]);
		$options->setBinary($this->_chrome_binary_path);
		$caps = DesiredCapabilities::chrome();
		$caps->setCapability(ChromeOptions::CAPABILITY, $options);
		$this->_drivers[$driver_key] = RemoteWebDriver::create(
			$this->_selenium_host,
			$caps,
			60 * 1000 * 10, // Connection timeout in miliseconds
			60 * 1000 * 10 // Request timeout in miliseconds
		);
		// Wait which enables to wait up to specific sec in the case of searching for an element.
		$this->_drivers[$driver_key]->manage()->timeouts()->implicitlyWait(5);
		// Wait which enables to wait up to specific sec in the case of ajaxing.
		$this->_drivers[$driver_key]->manage()->timeouts()->setScriptTimeout(120);
		// Wait which enables to wait up to specific sec in the case of loading page.
		$this->_drivers[$driver_key]->manage()->timeouts()->pageLoadTimeout(120);
	}



	/**
	 * Quit WebDriver
	 * @param string driver_key: Existed driver key. // required,
	 *
	 */
	public function quitDriver($driver_key) {
		if (!$this->checkDriverExist($driver_key)) {
			return true;
		}
		$this->_drivers[$driver_key]->quit();
		$this->_drivers[$driver_key] = null;
	}



	/**
	 * Resize window size
	 * @param string driver_key: Existed driver key. // required,
	 * @param hash size: ["width"=>int, "height"=>int], // If this is empty or its keys aren't provided, set $this->_window_size to it.
	 *
	 */
	public function resizeWindow($driver_key, $size=[]) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		$size["width"] = $size["width"] ?? $this->_window_size["width"];
		$size["height"] = $size["height"] ?? $this->_window_size["height"];
		$this->getDriver($driver_key)->manage()->window()->setSize(new WebDriverDimension($size["width"], $size["height"]));
	}



	/**
	 * Remove directory recursively
	 * @param string dir: path to dir. // required,
	 *
	 */
	private function removeDirectory($dir) {
		if (file_exists($dir)) {
			if ($handle = opendir($dir)) {
				while (false !== ($item = readdir($handle))) {
					if ($item != "." && $item != "..") {
						if (is_dir("{$dir}{$item}")) {
							$this->removeDirectory("{$dir}{$item}");
						} else if (is_file("{$dir}{$item}")) {
							if ($item !== 'empty') {
								unlink("{$dir}/{$item}");
							}
						}
					}
				}
				closedir($handle);
				@rmdir($dir);
			}
		}
	}



	/**
	 * Initialize Screenshot directory
	 * @param string test_name: Test classs name which is used as a screenshot display header name. // required,
	 * @param string case_name: Test case name which is used as a screenshot display section name in header. // required,
	 *
	 */
	public function initOutputDir($test_name, $case_name) {
		$this->removeDirectory("{$this->_screen_shot_dir}{$test_name}/{$case_name}/");
	}



	/**
	 * Take screenshot
	 * @param string driver_key: Existed driver key. // required,
	 * @param string test_name: Test classs name which is used as a screenshot display header name. // required,
	 * @param string case_name: Test case name which is used as a screenshot display section name in header. // required,
	 * @param string file_name: Name which is used a part of screenshot image file name. // required,
	 * @param string line: Executed line number which is used a part of screenshot image file name. // required,
	 *
	 */
	public function screenshot($driver_key, $test_name, $case_name, $file_name, $line) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		$dir = "{$this->_screen_shot_dir}{$test_name}";
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		$dir = "{$dir}/{$case_name}";
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		$this->getDriver($driver_key)->takeScreenshot("{$dir}/{$line}_{$file_name}");
	}



	/**
	 * Assertion of existence of elements which contains text same with expected text.
	 * @param array(Facebook\WebDriver\WebDriverElement) elements: array of Facebook\WebDriver\WebDriverElement
	 * @param string text expected text which is contained in Elements
	 * @param bool expected: existence of elements, true is that  you expect the elements exist, false is vice versa.
	 * @param bool strict: if true it use strictly string comparation like "===", else(default) then use partial matching.
	 *
	 */
	public function assertElementsHasTextExistAtLeast($elements, $text, $expected, $strict=false) {
		$ret = false;
		$reg = "/{$text}/i";
		foreach ($elements as $elem) {
			if ($strict === true) {
				if ($elem->getText() === $expected) {
					$ret = true;
					break;
				}
			} else {
				if (preg_match($reg, $elem->getText())) {
					$ret = true;
					break;
				}
			}
		}
		$this->assertSame($ret, $expected);
	}

	/**
	 * Assertion of equality between specific racord and expected hash.
	 * @param string table_name: Table Class name.
	 * @param hash expected: Hash of table colmns by which you want to fetch.
	 *
	 */
	public function assertEqualRecord($table_name, $expected) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		$this->assertTrue($table->exists($expected));
	}



	/**
	 * Assertion of equality between last racord and expected hash.
	 * @param string table_name: Table Class name.
	 * @param hash expected: Hash of table colmns by which you want to fetch.
	 *
	 */
	public function assertEqualLastRecord($table_name, $expected) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		$this->assertArraySubset($expected, $table->find('all')->all()->last()->toArray());
	}



	/**
	 * Check if specific element exists.
	 * @param string driver_key: Existed driver key. // required,
	 * @param Facebook\WebDriver\WebDriverBy selector: element selector // required,
	 * @return bool.
	 *
	 */
	public function isElementExists($driver_key, $selector) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		if (empty($selector)) {
			throw New \Exception("selector is invalid. it need to be Facebook\WebDriver\WebDriverBy.");
		}
		return (count($this->getDriver($driver_key)->findElements($selector)) > 0) ? true : false;
	}



	/**
	 * Get a number of records in table.
	 * @param string table_name: Table class name
	 * @return int The number of records in the table
	 *
	 */
	public function getRecordCount($table_name) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		return $table->find('all')->count();
	}



	/**
	 * Get All records in table.
	 * @param string table_name: Table class name
	 * @return Cake\ORM\Query: Query object of given table
	 *
	 */
	public function getAllRecords($table_name) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		return $table->find('all');
	}



	/**
	 * Get record in table by id.
	 * @param string table_name: Table class name
	 * @param int id: primary key in table
	 * @return hash: The last record, which is converted to array, of given table
	 *
	 */
	public function getRecordById($table_name, $id) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		return $table->get($id)->toArray();
	}



	/**
	 * Get last record in table.
	 * @param string table_name: Table class name
	 * @return hash: The last record, which is converted to array, of given table
	 *
	 */
	public function getLastRecord($table_name) {
		$table = TableRegistry::get($table_name, [
			"connection"=>$this->_conn,
			"cache"=>false,
		]);
		return $table->find('all')->all()->last()->toArray();
	}



	/**
	 * Convert datetime to Cake\I18n\FrozenTime to compare the datetime and database-datetime from Table class.
	 * @param string datetime: Datetime string
	 * @return Cake\I18n\FrozenTime: made from Datetime string
	 *
	 */
	public function convDatetime2FrozenTime($datetime) {
		return new FrozenTime($datetime);
	}




	/**
	 * Select file on fileinput
	 * @param string driver_key: Existed driver key. // required,
	 * @param Facebook\WebDriver\WebDriverBy selector: element selector // required,
	 * @param string file_path: file_path // required,
	 *
	 */
	public function selectFile($driver_key, $selector, $file_path) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		if (!$this->checkSelector($selector)) {
			throw New \Exception("selector is invalid. it need to be Facebook\WebDriver\WebDriverBy.");
		}
		$this->getDriver($driver_key)->findElement($selector)->setFileDetector(new LocalFileDetector());
		$this->getDriver($driver_key)->findElement($selector)->sendKeys($file_path);
	}



	/**
	 * Select body element whenever you need. Almost for the focus out.
	 * @param string driver_key: Existed driver key. // required,
	 *
	 */
	public function lostFocus($driver_key) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		$this->getDriver($driver_key)->findElement(WebDriverBy::cssSelector('body'))->click();
	}



	/**
	 * Select pulldown list item
	 * @param string driver_key: Existed driver key. // required,
	 * @param Facebook\WebDriver\WebDriverBy selector: element selector // required,
	 * @param hash item: ["by"=>"value, text or index", "expected"=>value] // required,
	 *
	 */
	public function selectPulldownItem($driver_key, $selector, $item) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		if (!$this->checkSelector($selector)) {
			throw New \Exception("selector is invalid. it need to be Facebook\WebDriver\WebDriverBy.");
		}
		if (!isset($item["by"]) || !in_array($item["by"], ["value", "text", "index"])) {
			throw New \Exception("parameter 'item' is invalid.");
		}
		$pulldown = new WebDriverSelect($this->getDriver($driver_key)->findElement($selector));
		switch ($item["by"]) {
			case "value":
				$pulldown->selectByValue($item["expected"]);
				break;
			case "text":
				$pulldown->selectByVisibleText($item["expected"]);
				break;
			case "index":
				$pulldown->selectByIndex($item["expected"]);
				break;
		}
	}



	/**
	 * Sleep php thread for waiting lazy javascript or something like that.
	 * This method is not recommended in most of the cases.
	 * @param int seconds: Sleep seconds. // required,
	 *
	 */
	public function threadSleep($seconds) {
		if (!is_int($seconds)) {
			throw New \Exception("Invalid parameter seconds.");
		}
		sleep($seconds);
	}



	/**
	 * Wait until specific elements's attribure head to expected condition
	 * @param string driver_key: Existed driver key. // required,
	 * @param hash conditions: [
	 *		[
	 *			"selector"=>Facebook\WebDriver\WebDriverBy // required,
	 *			"attribute"=>"value, data-xxx, or stuff" // required,
	 *			"compare"=>"==, ===, <=, <, >=, >, exists" // required
	 *			"expected"=>"value whatever you want" // required
	 *		],
	 *		....,
	 *	]
	 *
	 */
	public function waitUntilElementsAttribute($driver_key, $conditions) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}

		$d = $this->getDriver($driver_key);
		foreach ($conditions as $c) {
			// Validate condition
			if (!$this->checkSelector($c["selector"])
				|| empty($c["attribute"])
				|| !in_array($c["compare"], ["==", "===", "!=", "!==", "<=", "<", ">=", ">", "exists"])) {
				throw New \Exception("conditions is invalid.");
			}
			if ($c["compare"] !== "exists" && !isset($c["expected"])) {
				throw New \Exception("conditions is invalid.");
			}
			$d->wait()->until(
				function () use ($d, $c) {
					$elem = $d->findElement($c["selector"]);
					$ev;
					switch ($c["compare"]) {
						case "==":
							$ev = $elem->getAttribute($c["attribute"]) == $c["expected"];
							break;
						case "===":
							$ev = $elem->getAttribute($c["attribute"]) === $c["expected"];
							break;
						case "!=":
							$ev = $elem->getAttribute($c["attribute"]) != $c["expected"];
							break;
						case "!==":
							$ev = $elem->getAttribute($c["attribute"]) !== $c["expected"];
							break;
						case "<=":
							$ev = $elem->getAttribute($c["attribute"]) <= $c["expected"];
							break;
						case "<":
							$ev = $elem->getAttribute($c["attribute"]) < $c["expected"];
							break;
						case ">=":
							$ev = $elem->getAttribute($c["attribute"]) >= $c["expected"];
							break;
						case ">":
							$ev = $elem->getAttribute($c["attribute"]) > $c["expected"];
							break;
						case "exists":
							$ev = !is_null($elem->getAttribute($c["attribute"]));
							break;
						default:
							throw New \Exception("conditions['compare'] is invalid.");
					}
					return $ev;
				},
				'Failed to wait..'
			);
		}
	}



	/**
	 * Wait until with WebDriverExpectedCondition
	 * @param string driver_key: Existed driver key. // required,
	 * @param Facebook\WebDriver\WebDriverExpectedCondition web_driver_expected_condition: condition for waiting // required,
	 *
	 */
	public function waitUntil($driver_key, $web_driver_expected_condition) {
		if (!$this->checkDriverExist($driver_key)) {
			throw New \Exception("Driver {$driver_key} doesn't exist.");
		}
		// Wait with specified condition
		$this->getDriver($driver_key)->wait()->until($web_driver_expected_condition);
	}







}

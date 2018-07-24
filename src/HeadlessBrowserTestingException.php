<?php
namespace KentaroA;


/**
 * Exception class for HeadlessBrowserTesting
 *
 */
class HeadlessBrowserTestingException extends \Exception {

	public function __construct($msg="HeadlessBrowserTestingException has occurred."){
		parent::__construct($msg);
	}
}

<?php

/**
 * Short description for compactor.php
 *
 * Long description for compactor.php
 *
 * PHP version 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.views.helpers
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * CompactorHelper class
 *
 * @uses          AppHelper
 * @package       mi
 * @subpackage    mi.views.helpers
 */
class CompactorHelper extends AppHelper {

/**
 * name property
 *
 * @var string 'Compactor'
 * @access public
 */
	public $name = 'Compactor';

/**
 * construct method
 *
 * @param mixed $one
 * @param mixed $two
 * @param mixed $three
 * @access private
 * @return void
 */
	public function __construct($one = null, $two = null, $three = null) {
		parent::__construct($one, $two, $three);
		ob_start();
	}

/**
 * destruct method
 *
 * @access private
 * @return void
 */
	public function __destruct() {
		if (!Configure::read()) {
			$buffer = ob_get_clean();
			//$original = strlen($buffer);
			$out = preg_replace(
				array("@\r@", "@\n@", '@>\s+<@', '@\s+@', '@\s?{\s?@', '@\s?}\s?@'),
				array('', '', '><', ' ', '{', '}'), $buffer);
			//$after = strlen($out);
			echo $out;
			return;
			//echo "\r\n" . '<!-- ' . round((1 - ($after / $original)) * 100, 2) . '% compacted -->';
		}
	}
}
?>
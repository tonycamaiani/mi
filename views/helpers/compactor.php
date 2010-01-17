<?php
/* SVN FILE: $Id: compactor.php 1358 2009-07-28 09:41:06Z AD7six $ */

/**
 * Short description for compactor.php
 *
 * Long description for compactor.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       base
 * @subpackage    base.views.helpers
 * @since         v 1.0
 * @version       $Revision: 1358 $
 * @modifiedby    $LastChangedBy: AD7six $
 * @lastmodified  $Date: 2009-07-28 11:41:06 +0200 (Tue, 28 Jul 2009) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * CompactorHelper class
 *
 * @uses          AppHelper
 * @package       base
 * @subpackage    base.views.helpers
 */
class CompactorHelper extends AppHelper {

/**
 * name property
 *
 * @var string 'Compactor'
 * @access public
 */
	var $name = 'Compactor';

/**
 * construct method
 *
 * @param mixed $one
 * @param mixed $two
 * @param mixed $three
 * @access private
 * @return void
 */
	function __construct($one = null, $two = null, $three = null) {
		parent::__construct($one, $two, $three);
		ob_start();
	}

/**
 * destruct method
 *
 * @access private
 * @return void
 */
	function __destruct() {
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
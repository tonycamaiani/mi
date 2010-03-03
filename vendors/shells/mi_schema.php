<?php
/**
 * Short description for mi_schema.php
 *
 * Long description for mi_schema.php
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
 * @subpackage    mi.vendors.shells
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
require_once (CAKE_CORE_INCLUDE_PATH . '/cake/console/libs/schema.php');

/**
 * MiSchemaShell class
 *
 * @uses          SchemaShell
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class MiSchemaShell extends SchemaShell {

/**
 * in method
 *
 * The sole purpose of this shell, is to allow none-interactive schema updates
 *
 * @return void
 * @access public
 */
	public function in() {
		return 'y';
	}
}
?>
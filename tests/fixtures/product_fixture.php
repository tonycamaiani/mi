<?php
/**
 * Short description for product_fixture.php
 *
 * Long description for product_fixture.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright            Copyright (c) 2008, Andy Dawson
 * @link                 www.ad7six.com
 * @package              base
 * @subpackage           base.tests.fixtures
 * @since                v 1.0
 * @modifiedBy           $LastChangedBy$
 * @lastModified         $Date$
 * @license              http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * ProductFixture class
 *
 * @uses                 CakeTestFixture
 * @package              base
 * @subpackage           base.tests.fixtures
 */
class ProductFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Product'
 * @access public
 */
	var $name = 'Product';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'slug' => array('type' => 'string', 'null' => true),
		'filename' => array('type' => 'string', 'null' => true),
	);
}
?>
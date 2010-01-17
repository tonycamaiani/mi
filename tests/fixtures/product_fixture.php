<?php
/* SVN FILE: $Id: product_fixture.php 1358 2009-07-28 09:41:06Z AD7six $ */

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
 * @filesource
 * @copyright            Copyright (c) 2008, Andy Dawson
 * @link                 www.ad7six.com
 * @package              base
 * @subpackage           base.tests.fixtures
 * @since                v 1.0
 * @version              $Revision: 1358 $
 * @modifiedBy           $LastChangedBy: AD7six $
 * @lastModified         $Date: 2009-07-28 11:41:06 +0200 (Tue, 28 Jul 2009) $
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
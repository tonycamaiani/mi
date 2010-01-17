<?php
/**
 * Menu Helper test case
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       base
 * @subpackage    base.tests.cases.helpers
 * @since         v 1.0 (28-Mar-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', array('Helper', 'AppHelper', 'Html'));
App::import('Helper', array('Mi.Menu'));

/**
 * MenuHelperTest class
 *
 * @uses          CakeTestCase
 * @package       base
 * @subpackage    base.tests.cases.helpers
 */
class MenuHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Menu = new MenuHelper();
		$this->Menu->Html = new HtmlHelper();
	}

/**
 * testEmpty method
 *
 * @return void
 * @access public
 */
	function testEmpty() {
		$result = $this->Menu->display(null, null, false);
		$this->assertEqual($result, null);

		$result = $this->Menu->display();
		$expected = array(
			array('ul' => array('class' => 'menu')),
			'/ul'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testAddSimple method
 *
 * @return void
 * @access public
 */
	function testAddSimple() {
		$expected = array(
			array('ul' => array('class' => 'menu')),
			array('li' => array()),
			array('a' => array('href' => '/url')), 'Title', '/a',
			'/li',
			'/ul'
		);
		$this->Menu->add('main', 'Title', '/url');
		$this->assertEqual(array_values($this->Menu->sections()), array('main'));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected);

		$this->Menu->add('Title', '/url');
		$this->assertEqual(array_values($this->Menu->sections()), array('main'));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected);

		$this->Menu->add(array(
			'title' => 'Title',
			'url' => '/url'
		));
		$this->assertEqual(array_values($this->Menu->sections()), array('main'));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected);

		$this->Menu->add(array(
			array(
				'title' => 'Title',
				'url' => '/url'
			)
		));
		$this->assertEqual(array_values($this->Menu->sections()), array('main'));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected);
	}

/**
 * testNesting method
 *
 * @return void
 * @access public
 */
	function testNesting() {
		$expected = array(
			array('ul' => array('class' => 'menu')),
			array('li' => array()),
			array('a' => array('href' => '/url')), 'Title', '/a',
			array('ul' => array()),
			array('li' => array()),
			array('a' => array('href' => '/url2')), 'Title2', '/a',
			'/li',
			'/ul',
			'/li',
			'/ul'
		);

		$this->Menu->add('Title', '/url');
		$this->Menu->add('menu', 'Title2', '/url2', 'Title');
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->add(array(
			'title' => 'Title',
			'url' => '/url'
		));
		$this->Menu->add(array(
			'title' => 'Title2',
			'url' => '/url2',
			'under' => 'Title'
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->add(array(
			array(
				'title' => 'Title',
				'url' => '/url'
			),
			array(
				'title' => 'Title2',
				'url' => '/url2',
				'under' => 'Title'
			),
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->add(array(
			array(
				'title' => 'Title',
				'url' => '/url',
				'children' => array(
					array(
						'title' => 'Title2',
						'url' => '/url2',
					),
				)
			),
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);
	}

/**
 * testNestingUrl method
 *
 * @return void
 * @access public
 */
	function testNestingUrl() {
		$expected = array(
			array('ul' => array('class' => 'menu')),
			array('li' => array()),
			array('a' => array('href' => '/url')), 'Title', '/a',
			array('ul' => array()),
			array('li' => array()),
			array('a' => array('href' => '/url2')), 'Title2', '/a',
			'/li',
			'/ul',
			'/li',
			'/ul'
		);

		$this->Menu->settings('main', array('uniqueKey' => 'url'));
		$this->Menu->add('Title', '/url');
		$this->Menu->add('main', 'Title2', '/url2', '/url');
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->settings('main', array('uniqueKey' => 'url'));
		$this->Menu->add(array(
			'title' => 'Title',
			'url' => '/url'
		));
		$this->Menu->add(array(
			'title' => 'Title2',
			'url' => '/url2',
			'under' => '/url'
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->settings('main', array('uniqueKey' => 'url'));
		$this->Menu->add(array(
			array(
				'title' => 'Title',
				'url' => '/url'
			),
			array(
				'title' => 'Title2',
				'url' => '/url2',
				'under' => '/url'
			),
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);

		$this->Menu->settings('main', array('uniqueKey' => 'url'));
		$this->Menu->add(array(
			array(
				'title' => 'Title',
				'url' => '/url',
				'children' => array(
					array(
						'title' => 'Title2',
						'url' => '/url2',
					),
				)
			),
		));
		$result = $this->Menu->display();
		$result = str_replace("\t", '', $result);
		$this->assertTags($result, $expected, true);
	}

/**
 * testMultipleMenus method
 *
 * @return void
 * @access public
 */
	function testMultipleMenus() {
		$this->Menu->settings('main');
		$this->Menu->add(array(
			'title' => 'Main Title',
			'url' => '/mainurl'
		));

		$this->Menu->settings('other');
		$this->Menu->add(array(
			'title' => 'Other Title',
			'url' => '/otherurl'
		));

		$result = $this->Menu->display('main');
		$result = str_replace("\t", '', $result);
		$expected = array(
			array('ul' => array('class' => 'menu')),
			array('li' => array()),
			array('a' => array('href' => '/mainurl')), 'Main Title', '/a',
			'/li',
			'/ul'
		);
		$this->assertTags($result, $expected, true);

		$result = $this->Menu->display('other');
		$result = str_replace("\t", '', $result);
		$expected = array(
			array('ul' => array('class' => 'menu')),
			array('li' => array()),
			array('a' => array('href' => '/otherurl')), 'Other Title', '/a',
			'/li',
			'/ul'
		);
		$this->assertTags($result, $expected, true);
	}
}
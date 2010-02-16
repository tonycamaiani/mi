<?php
/**
 * Short description for list.test.php
 *
 * Long description for list.test.php
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
 * @package              mi
 * @subpackage           mi.tests.cases.behaviors
 * @since                v 1.0
 * @modifiedBy           $LastChangedBy$
 * @lastModified         $Date$
 * @license              http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * MessageList class
 *
 * @uses                 CakeTestModel
 * @package              mi
 * @subpackage           mi.tests.cases.behaviors
 */
class MessageList extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'messages'
 * @access public
 */
	var $useTable = 'messages';

/**
 * order property
 *
 * @var string 'order'
 * @access public
 */
	var $order = 'random';

/**
 * actsAs property
 *
 * Use the random field for the order/sequence of the list behavior
 *
 * @var array
 * @access public
 */
	var $actsAs = array('List' => array('sequence' => 'random'));
}

/**
 * ListTestCase class
 *
 * @uses                 CakeTestCase
 * @package              mi
 * @subpackage           mi.tests.cases.behaviors
 */
class ListTestCase extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('message');

/**
 * start method
 *
 * @return void
 * @access public
 */
	function start() {
		parent::start();
		$this->Message = new MessageList(null, 'messages');
	}

/**
 * testFind method
 *
 * @return void
 * @access public
 */
	function testFind() {
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			7 => 'Third',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);
	}

/**
 * testVerify method
 *
 * @return void
 * @access public
 */
	function testVerify() {
		$result = $this->Message->verify();
		if ($result !== true) {
			debug ($result);
		die;
		}
		$this->assertIdentical($result, true);
	}

/**
 * testRecover method
 *
 * @return void
 * @access public
 */
	function testRecover() {
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);

		$this->Message->updateAll(array('random' => 1));
		$result = $this->Message->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Message->recover();
		$this->assertIdentical($result, true);
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);

		$this->Message->deleteAll(array('random' => 5));
		$result = $this->Message->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Message->recover();
		$this->assertIdentical($result, true);
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);

	}

/**
 * testMoveUp method
 *
 * @return void
 * @access public
 */
	function testMoveUp() {
		$this->Message->id = 9;
		$this->Message->moveUp();
		$results = $this->Message->find('list');
		$expected = array (
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->Message->moveUp(null, 2);
		$results = $this->Message->find('list');
		$expected = array (
			9 => 'Second',
			1 => 'First',
			5 => 'Fifth',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->Message->moveUp(null, 2);
		$results = $this->Message->find('list');
		$expected = array (
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 2;
		$this->assertIdentical(true, $this->Message->moveUp(null, true));
		$results = $this->Message->find('list');
		$expected = array (
			2 => 'Tenth',
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->assertIdentical(false, $this->Message->moveUp(null, 2));
		$results = $this->Message->find('list');
		$expected = array (
			2 => 'Tenth',
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
		);
		$this->assertIdentical($results, $expected);
	}

/**
 * testMoveDown method
 *
 * @return void
 * @access public
 */
	function testMoveDown() {
		$this->Message->id = 9;
		$this->assertIdentical(true, $this->Message->moveDown());
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			7 => 'Third',
			9 => 'Second',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 7;
		$this->assertIdentical(true, $this->Message->moveDown(null, 2));
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			3 => 'Fourth',
			7 => 'Third',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 7;
		$this->assertIdentical(true, $this->Message->moveDown(null, 3));
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			7 => 'Third',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 7;
		$this->assertIdentical(true, $this->Message->moveDown(null, true));
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth',
			7 => 'Third',
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 7;
		$this->assertIdentical(false, $this->Message->moveDown(null, true));
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth',
			7 => 'Third',
		);
		$this->assertIdentical($results, $expected);
	}

/**
 * testAddNew method
 *
 * @return void
 * @access public
 */
	function testAddNew() {
		$this->Message->create();
		$this->Message->save(array('name' => 'New'));
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			7 => 'Third',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth',
			$this->Message->id => 'New'
		);
		$this->assertIdentical($results, $expected);
	}

/**
 * testDelete method
 *
 * @return void
 * @access public
 */
	function testDelete() {
		$this->Message->id = 5;
		$this->Message->delete();
		$results = $this->Message->find('list');
		$expected = array (
			1 => 'First',
			9 => 'Second',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth',
		);
		$this->assertIdentical($results, $expected);
	}
}

/**
 * MultiListTestCase class
 *
 * @uses          ListTestCase
 * @package       mi
 * @subpackage    mi.tests.cases.behaviors
 */
class MultiListTestCase extends ListTestCase {

/**
 * before method
 *
 * @param mixed $method
 * @return void
 * @access public
 */
	function startTest($method) {
		if ($method != 'start') {
			$this->Message->Behaviors->attach('List', array('sequence' => 'random', 'scope' => 'section'));
			$this->Message->updateAll(array('section' => 1));
			$data = $this->Message->find('all', array('fields' => array('random', 'name', 'section')));
			//$sections = array(2,3,4,5,6,7,8,9,10,20,30,40,50,60,80,90,100);
			$sections = array(2);
			foreach ($sections as $section) {
				foreach ($data as $i => $row) {
					$row = current($row);
					$row['section'] = $section;
					$this->Message->create($row);
					$this->Message->save();
				}
			}
		}
	}

/**
 * testAddNew method
 *
 * @return void
 * @access public
 */
	function testAddNew() {
		$this->Message->Behaviors->attach('List', array('sequence' => 'random', 'scope' => 'section'));
		$this->Message->create();
		$before = $this->Message->find('all', array('order' => 'id'));
		$return = $this->Message->save(array('name' => 'New'));
		$after = $this->Message->find('all', array('order' => 'id'));
		$this->assertIdentical($before, $after);
		$this->assertFalse($return);

		$return = $this->Message->save(array('name' => 'New', 'section' => 1));
		$one = $this->Message->listFind(1, 'list', array('fields' => array('random', 'name')));
		$expected = array (
			1 => 'First',
			2 => 'Second',
			3 => 'Third',
			4 => 'Fourth',
			5 => 'Fifth',
			6 => 'Sixth',
			7 => 'Seventh',
			8 => 'Eigth',
			9 => 'Ninth',
			10 => 'Tenth',
			11 => 'New'
		);
		$this->assertIdentical(array_values($one), array_values($expected));
		$this->assertTrue($return);

		$return = $this->Message->save(array('name' => 'New', 'section' => 2));
		$one = $this->Message->listFind(1, 'list', array('fields' => array('random', 'name')));
		$two = $this->Message->listFind(2, 'list', array('fields' => array('random', 'name')));
		$expected = array (
			1 => 'First',
			2 => 'Second',
			3 => 'Third',
			4 => 'Fourth',
			5 => 'Fifth',
			6 => 'Sixth',
			7 => 'Seventh',
			8 => 'Eigth',
			9 => 'Ninth',
			10 => 'Tenth',
			11 => 'New'
		);
		$this->assertIdentical(array_values($two), array_values($expected));
		$this->assertTrue($return);
	}
	function testFind() {
		$results = $this->Message->listFind(1, 'list', array('fields' => array('random', 'name')));
		$expected = array (
			1 => 'First',
			2 => 'Second',
			3 => 'Third',
			4 => 'Fourth',
			5 => 'Fifth',
			6 => 'Sixth',
			7 => 'Seventh',
			8 => 'Eigth',
			9 => 'Ninth',
			10 => 'Tenth',
		);
		$this->assertIdentical($results, $expected);
	}

/**
 * testRecover method
 *
 * @return void
 * @access public
 */
	function testRecover() {
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);

		$this->Message->updateAll(array('random' => 'random * 2'));
		$result = $this->Message->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Message->recover();
		$this->assertIdentical($result, true);
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);

		$this->Message->deleteAll(array('random' => 5));
		$result = $this->Message->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Message->recover();
		$this->assertIdentical($result, true);
		$result = $this->Message->verify();
		$this->assertIdentical($result, true);
	}
	function testMoveUp() {
		$this->Message->id = 9;
		$this->Message->moveUp();
		$results = $this->Message->find('list');
		$expected = array (
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			5 => 'Fifth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->Message->moveUp(null, 2);
		$results = $this->Message->find('list');
		$expected = array (
			9 => 'Second',
			1 => 'First',
			5 => 'Fifth',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->Message->moveUp(null, 2);
		$results = $this->Message->find('list');
		$expected = array (
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth'
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 2;
		$this->assertIdentical(true, $this->Message->moveUp(null, true));
		$results = $this->Message->find('list');
		$expected = array (
			2 => 'Tenth',
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = 5;
		$this->assertIdentical(false, $this->Message->moveUp(null, 2));
		$results = $this->Message->find('list');
		$expected = array (
			2 => 'Tenth',
			5 => 'Fifth',
			9 => 'Second',
			1 => 'First',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
		);
		$this->assertIdentical($results, $expected);
	}
	function testDelete() {
		$results = $this->Message->find('list', array('fields' => array('random', 'name'), 'conditions' => array('section' => 2)));
		$expected = array (
			1 => 'First',
			2 => 'Second',
			3 => 'Third',
			4 => 'Fourth',
			5 => 'Fifth',
			6 => 'Sixth',
			7 => 'Seventh',
			8 => 'Eigth',
			9 => 'Ninth',
			10 => 'Tenth',
		);
		$this->assertIdentical($results, $expected);

		$this->Message->id = null;
		$before = $this->Message->find('count');
		$this->Message->id = 5;
		$this->Message->delete();
		$after = $this->Message->find('count');
		$this->assertIdentical($before, $after + 1);
		$results = $this->Message->find('list', array('conditions' => array('section' => 1)));
		$expected = array (
			1 => 'First',
			9 => 'Second',
			7 => 'Third',
			3 => 'Fourth',
			10 => 'Sixth',
			6 => 'Seventh',
			4 => 'Eigth',
			8 => 'Ninth',
			2 => 'Tenth',
		);
		$results = $this->Message->find('list', array('fields' => array('random', 'name'), 'conditions' => array('section' => 2)));
		$expected = array (
			1 => 'First',
			2 => 'Second',
			3 => 'Third',
			4 => 'Fourth',
			5 => 'Fifth',
			6 => 'Sixth',
			7 => 'Seventh',
			8 => 'Eigth',
			9 => 'Ninth',
			10 => 'Tenth',
		);
		$this->assertIdentical($results, $expected);
	}
}
?>
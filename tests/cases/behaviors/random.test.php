<?php
/**
 * Short description for random.test.php
 *
 * Long description for random.test.php
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
 * Message class
 *
 * @uses                 CakeTestModel
 * @package              mi
 * @subpackage           mi.tests.cases.behaviors
 */
class Message extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Message'
 * @access public
 */
	var $name = 'Message';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Mi.Random' => array('autoRandom' => false));
}

/**
 * RandomTestCase class
 *
 * @uses                 CakeTestCase
 * @package              mi
 * @subpackage           mi.tests.cases.behaviors
 */
class RandomTestCase extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('message');

/**
 * testSetup method
 *
 * @return void
 * @access public
 */
	function testSetup() {
		$this->Message = new Message();
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 3);

		$order = 'id';
		$ordered = $this->Message->find('all', compact('conditions', 'fields', 'order'));
		$expected = array (
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First')),
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth')),
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh')),
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third')),
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth')),
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second')),
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth'))
		);
		$this->assertIdentical($ordered, $expected);
		$order = 'random';
		$correctOrder = $this->Message->find('all', compact('conditions', 'fields', 'order'));
		$expected = array (
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First')),
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second')),
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third')),
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth')),
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh')),
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth')),
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth'))
		);
		$this->assertIdentical($correctOrder, $expected);
	}

/**
 * testFindFirst method
 *
 * @return void
 * @access public
 */
	function testFindFirst() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 3);
		$randomOrder = $this->Message->find('first', compact('conditions', 'fields', 'order'));
		$expected = array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third'));
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testFirstPageFromOtherDirection method
 *
 * @return void
 * @access public
 */
	function testFirstPageFromOtherDirection() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 3);

		$limit = 2;
		$page = 1;

		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third')),
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 2;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 3;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh')),
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 4;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth')),
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 5;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First')),
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second'))
		);
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testMostPageFromOtherDirection method
 *
 * @return void
 * @access public
 */
	function testMostPageFromOtherDirection() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 9);

		$limit = 2;
		$page = 1;

		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth')),
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 2;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First')),
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 3;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third')),
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 4;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 5;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh')),
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
		);
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testPageInMiddle method
 *
 * @return void
 * @access public
 */
	function testPageInMiddle() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 4);

		$limit = 2;
		$page = 1;

		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth')),
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 2;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 3;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 4;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 5;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second')),
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third'))
		);
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testPageInMiddleMostOther method
 *
 * @return void
 * @access public
 */
	function testPageInMiddleMostOther() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 10);

		$limit = 2;
		$page = 1;

		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 2;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second')),
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 3;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth')),
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 4;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh'))
		);
		$this->assertIdentical($randomOrder, $expected);

		$page = 5;
		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order', 'limit', 'page'));
		$expected = array (
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth'))
		);
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testFindNoLimit method
 *
 * @return void
 * @access public
 */
	function testFindNoLimit() {
		$this->Message = new Message();
		$order = 'RAND()';
		$conditions = array('1 = 1');
		$fields = array('id', 'random', 'name');
		$this->Message->randomCache($conditions, 3);

		$randomOrder = $this->Message->find('all', compact('conditions', 'fields', 'order'));
		$expected = array (
			array ('Message' => array ('id' => '7', 'random' => '3', 'name' => 'Third')),
			array ('Message' => array ('id' => '3', 'random' => '4', 'name' => 'Fourth')),
			array ('Message' => array ('id' => '5', 'random' => '5', 'name' => 'Fifth')),
			array ('Message' => array ('id' => '10', 'random' => '6', 'name' => 'Sixth')),
			array ('Message' => array ('id' => '6', 'random' => '7', 'name' => 'Seventh')),
			array ('Message' => array ('id' => '4', 'random' => '8', 'name' => 'Eigth')),
			array ('Message' => array ('id' => '8', 'random' => '9', 'name' => 'Ninth')),
			array ('Message' => array ('id' => '2', 'random' => '10', 'name' => 'Tenth')),
			array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First')),
			array ('Message' => array ('id' => '9', 'random' => '2', 'name' => 'Second'))
		);
		$this->assertIdentical($randomOrder, $expected);
	}

/**
 * testRead method
 *
 * @return void
 * @access public
 */
	function testRead() {
		$this->Message = new Message();
		$result = $this->Message->read(array('id', 'random', 'name'), 1);
		$expected = array ('Message' => array ('id' => '1', 'random' => '1', 'name' => 'First'));
		$this->assertIdentical($result, $expected);
	}

/**
 * testSave method
 *
 * @return void
 * @access public
 */
	function testSave() {
		$this->Message = new Message();
		$this->Message->save(array('name' => 'Eleventh'));
		$randomField = $this->Message->field('random');
		$this->assertNotIdentical($randomField, 0);
	}

/**
 * testOverride method
 *
 * @return void
 * @access public
 */
	function testOverride() {
		$this->Message = new Message();

		$one = $this->Message->find('all', array('order' => 'RAND()'));
		$two = $this->Message->find('all', array('order' => 'RAND()'));
		$three = $this->Message->find('all', array('order' => 'RAND()'));
		$same = false;
		if ($one == $two && $one == $three) {
			$same = true;
		}
		$this->assertIdentical($same, true);

		$one = $different = $this->Message->find('all', array('randomCache' => false, 'order' => 'RAND()'));
		while ($one == $different) {
			$different = $this->Message->find('all', array('randomCache' => false, 'order' => 'RAND()'));
		}
		$this->assertNotIdentical($one, $different);
	}

/**
 * testMaxRand method
 *
 * @return void
 * @access public
 */
	function testMaxRand() {
		$this->Message = new Message();
		$result = $this->Message->maxRand();
		$this->assertIdentical($result, 20);
	}

/**
 * testFindDifferentResults method
 *
 * Create some test messages, check that before and after running randomize the random values differ
 * Run ~200 finds with the same conditions (on the test table of ~100 rows) and check that at least
 * ~50 different results are returned.
 *
 * @return void
 * @access public
 */
	function testFindDifferentResults() {
		$this->Message = new Message();
		for ($i = 1; $i <= 100; $i++) {
			$this->Message->create();
			$this->Message->saveField('name', 'Test ' . str_pad($i, 3, '0', STR_PAD_LEFT));
		}
		$this->Message->create();
		$before = $this->Message->find('list', array('fields' => array('name', 'random'), 'order' => 'random'));
		$this->Message->randomize();
		$random = $this->Message->find('list', array('fields' => array('name', 'random'), 'order' => 'random'));
		$this->assertNotIdentical($random, $before);

		$count = $this->Message->find('count');
		$queries = $count * 2;
		$results = array();
		for ($i = 1; $i <= $queries; $i++) {
			$row = $this->Message->find('first', array('randomCache' => false, 'order' => 'RAND()'));
			$id = $row['Message']['id'];
			if (isset($results[$id])) {
				$results[$id]++;
				continue;
			}
			$results[$row['Message']['id']] = 1;
		}
		if (!$this->assertTrue(count($results) > $count * 0.5)) {
			debug("More than half of the result set of $count rows was not selected during $queries 'random' queries.\r\n" .
				"Due to the nature of random number generation this test may fail - it should not fail consistently.");
		}
		sort($results);
		$mostReturned = array_pop($results);
		if (!$this->assertTrue($mostReturned < $queries * 0.1)) {
			debug("The same row was returned $mostReturned different times during $queries 'random' queries.\r\n" .
				"Due to the nature of random number generation this test may fail - it should not fail consistently.");
		}
	}

/**
 * testRandomValuesAreVaried method
 *
 * Create some test messages, and explicitly run randomize. Count the number of distinct random values - check that
 * there is sufficient variety (e.g. for 100 rows, check there are 70 distinct random values). Check that there are
 * less than 5 rows which have the same random value.
 *
 * @return void
 * @access public
 */
	function testRandomValuesAreVaried() {
		$this->Message = new Message();
		for ($i = 1; $i <= 100; $i++) {
			$this->Message->create();
			$this->Message->saveField('name', 'Test ' . str_pad($i, 3, '0', STR_PAD_LEFT));
		}
		$count = $this->Message->find('count');
		$this->Message->randomize();
		$randomVals = $this->Message->find('all', array(
			'fields' => array('random', 'count(random) as counter'),
			'group' => 'random',
			'order' => 'count(random) DESC'
		));
		$randomVals = Set::combine($randomVals, '/Message/random', '/0/counter');
		$distinctValues = count($randomVals);
		$maxDuplicates = current($randomVals);
		if (!$this->assertTrue($distinctValues > $count * 0.7)) {
			debug("30% overlap detected for random values - Many rows have the same random value.\r\n" .
				"Due to the nature of random number generation this test may fail - it should not fail consistently.");
		}
		if (!$this->assertTrue($maxDuplicates <= 5)) {
			debug("The most common random number is used for $maxDuplicates different rows.\r\n" .
				"Due to the nature of random number generation this test may fail - it should not fail consistently.");
		}
	}

/**
 * testAutoRandom method
 *
 * @return void
 * @access public
 */
	function testAutoRandom() {
		$this->Message = new Message();
		$before = $this->Message->find('all', array('order' => 'id'));
		$this->Message->Behaviors->Random->__destruct();

		$this->Message->Behaviors->detach('Random');
		$random = $this->Message->find('all', array('order' => 'RAND()'));
		$this->assertNotIdentical($random, $before);

		$this->Message->Behaviors->attach('Random', array('autoRandom' => true));
		//$after = $this->Message->find('all', array('order' => 'id'));
		//$this->assertNotIdentical($before, $after);
	}
}
?>
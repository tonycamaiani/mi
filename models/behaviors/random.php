<?php
/* SVN FILE: $Id$ */

/**
 * Short description for random.php
 *
 * Long description for random.php
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
 * @subpackage    base.models.behaviors
 * @since         v 1.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * RandomBehavior class
 *
 * @uses          ModelBehavior
 * @package       base
 * @subpackage    base.models.behaviors
 */
class RandomBehavior extends ModelBehavior {

/**
 * cache property
 *
 * Array of cached values. False if not yet initialized
 *
 * @var array
 * @access private
 */
	var $__cache = false;

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'cache' => true,
		'max' => null,
		'autoRandom' => '+1 day'
	);

/**
 * destruct method
 *
 * Write the randome cache to the session
 *
 * @return void
 * @access private
 */
	function __destruct() {
		if (!$this->__cache) {
			return;
		}
		$cache = isset($_SESSION['Random']['__cache'])?$_SESSION['Random']['__cache']:array();
		foreach ($this->__cache as $alias => $values) {
			foreach ($values as $key => $value) {
				$cache[$alias][$key] = $value;
			}
		}
		$_SESSION['Random']['__cache'] = $cache;
	}

/**
 * setup method
 *
 * @param mixed $Model
 * @param array $config
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = am ($this->_defaultSettings, $config);
		if (!$Model->hasField('random')) {
			trigger_error('RandomBehavior::setup() model ' . $Model->alias . ' doesn\'t have the field random');
			$Model->Behaviors->disable('Random');
			return;
		}
		if ($this->settings[$Model->alias]['autoRandom']) {
			$valid = cache('Random.valid' . $Model->alias);
			if (!$valid) {
				$this->randomize($Model);
				cache('Random.valid' . $Model->alias, date(DATE_RFC822), $this->settings[$Model->alias]['autoRandom']);
			}
		}
	}

/**
 * randomCache method
 *
 * Set or retrieve the random value for a set of conditions
 *
 * @param mixed $Model
 * @param mixed $conditions
 * @param mixed $value
 * @return void
 * @access public
 */
	function randomCache(&$Model, $conditions, $value = null) {
		if ($this->__cache === false) {
			$this->__cache = isset($_SESSION['Random']['__cache'])?$_SESSION['Random']['__cache']:array();
		}
		$key = md5(serialize((array)$conditions));
		if ($value) {
			$this->__cache[$Model->alias][$key] = $value;
			return $value;
		}
		if (isset($this->__cache[$Model->alias]) && array_key_exists($key, $this->__cache[$Model->alias])) {
			return $this->__cache[$Model->alias][$key];
		}
		return false;
	}

/**
 * beforeFind method
 *
 * Do nothing if:
 * 	no order is passed
 * 	an order is passed, and it doesn't contain RAND()
 * 	it's a count
 * 	the random field is in the conditions
 * 	the primary key of the model is in the conditions
 * Otherwise return the results randomly sorted. Using or not the random cache. Using the cache can be overridden
 * per query passing randomCache = true/false in the queryData
 *
 * If no results are found for the derived random value - sort ASC on random
 * If more results exist than required - sort ASC on random, and modify conditions to start from a random result
 * If less results exist than required - use an 'intelligent' sort to start from a random result and start from
 * 	the beginning again.
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @return void
 * @access public
 */
	function beforeFind(&$Model, $queryData) {
		if ($Model->findQueryType == 'count') {
			return true;
		}
		$randomOrder = false;
		$randomKey = 0;
		if (isset($queryData['order'][0]) && is_array($queryData['order'][0]) && count($queryData['order']) == 1) {
			$queryData['order'] = $queryData['order'][0];
		}
		foreach ($queryData['order'] as $key => $value) {
			if (trim($value) == '') {
				unset($queryData['order'][$key]);
			} elseif (trim($value) == 'RAND()') {
				$randomKey = $key;
				$randomOrder = true;
			}
		}
		if (
			(!$randomOrder)
			||
			(isset($queryData['conditions'][$Model->alias . '.random']))
			||
			(isset($queryData['conditions'][$Model->alias . '.' . $Model->primaryKey]) &&
			is_int($queryData['conditions'][$Model->alias . '.' . $Model->primaryKey]))
		) {
			return true;
		}
		extract($this->settings[$Model->alias]);
		if (isset($queryData['randomCache'])) {
			$cache = $queryData['randomCache'];
		}
		$random = $this->__randomNumber($Model, $queryData, $cache);

		$countQueryData['conditions'] = $queryData['conditions'];
		$countQueryData['conditions'][$Model->alias . '.random >='] = $random;
		$count = $Model->find('count', $countQueryData);
		$required = 'all';
		if (isset($queryData['offset'])) {
			$required = $queryData['offset'] + $queryData['limit'];
		} elseif (isset($queryData['limit'])) {
			$required = $queryData['page'] * $queryData['limit'];
		}
		$order = '';
		if ($count > 0) {
			if ($required == 'all' || $count < $required) {
				$order = '(' . $Model->escapeField('random') . ' >= ' . $random . ') DESC, ';
			} else {
				$queryData['conditions'][$Model->alias . '.random >='] = $random;
			}
		}
		$order .= $Model->alias . '.random ASC';
		$queryData['order'][$randomKey] = $order;
		return $queryData;
	}

/**
 * beforeSave method
 *
 * Add a random value to the random field
 * If the number of rows exceeds the maximum rand value - regenerate the max value. this would indicate the table had
 * doubled in size since the maxRand value was calculated
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function beforeSave(&$Model) {
		extract ($this->settings[$Model->alias]);
		$max = $this->maxRand($Model);
		if ($Model->find('count') > $max) {
			$max = $this->randomize($Model);
		}
		$Model->data[$Model->alias]['random'] = rand(0,  $max);
		return true;
	}

/**
 * randomize method
 *
 * Change the random values for all rows - thereby changing the order of all results
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function randomize(&$Model, $conditions = array()) {
		$max = $this->maxRand($Model, true);
		$Model->updateAll(array('random' => 'FLOOR(RAND() * ' . $max . ')'), $conditions);
		return $max;
	}

/**
 * maxRand method
 *
 * Get the maximum random number value for this model - ten times the total rows in the table
 * Limiting the possible range of random values limits the risk of always finding the first/last row
 * if the range of random values is too large on a small dataset
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function maxRand(&$Model, $reset = false) {
		extract ($this->settings[$Model->alias]);
		if (!$reset && $max !== null) {
			return $max;
		}
		$return = $Model->find('count', array('recursive' => -1)) * 2;
		$this->settings[$Model->alias]['max'] = $return;
		return $return;
	}

/**
 * randomNumber method
 *
 * Get the random number to be used for this query.
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @param bool $useCache
 * @return void
 * @access private
 */
	function __randomNumber(&$Model, $queryData, $useCache = true) {
		extract ($this->settings[$Model->alias]);
		if ($useCache) {
			$cachedVal = $this->randomCache($Model, $queryData['conditions']);
			if ($cachedVal === false) {
				$cachedVal = rand(0, $this->maxRand($Model));
				$this->randomCache($Model, $queryData['conditions'], $cachedVal);
			}
			return $cachedVal;
		}
		return rand(0, $this->maxRand($Model));
	}
}
?>
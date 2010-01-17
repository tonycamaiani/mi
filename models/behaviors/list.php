<?php
/* SVN FILE: $Id: list.php 2076 2010-01-08 14:00:14Z AD7six $ */

/**
 * Short description for list.php
 *
 * Long description for list.php
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
 * @version       $Revision: 2076 $
 * @modifiedby    $LastChangedBy: AD7six $
 * @lastmodified  $Date: 2010-01-08 15:00:14 +0100 (Fri, 08 Jan 2010) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * A behavior for storing ordered lists.
 *
 * If using a field as scope - Be careful that the model id is not set when performing a find if you
 * actually want to query table-wide.
 *
 * @uses          ModelBehavior
 * @package       base
 * @subpackage    base.models.behaviors
 */

/**
 * ListBehavior class
 *
 * @uses          ModelBehavior
 * @package       base
 * @subpackage    base.models.behaviors
 */
class ListBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'List'
 * @access public
 */
	var $name = 'List';

/**
 * errors property
 *
 * @var array
 * @access public
 */
	var $errors = array();

/**
 * defaults property
 *
 * sequence - the name of the field used for storing the order. defaults to a field named 'order'
 * scope - defaults to table wide, or set to the name of a field, an array of fields, or set to any condition
 * recursive - defaults to -1. change if the scope would require it.
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'sequence' => 'order', 'scope' => '1 = 1',
		'previousSequence' => null, 'recursive' => -1,
		'strict' => false
	);

/**
 * setup method
 *
 * Sets a default model sort order
 *
 * @param mixed $Model
 * @param array $config
 * @access public
 * @return void
 */
	function setup(&$Model, $config = array()) {
		if (!is_array($config)) {
			$config = array('type' => $config);
		}
		$this->settings[$Model->alias] = am ($this->_defaultSettings, $config);
		//$Model->order = $Model->alias . '.' . $this->settings[$Model->alias]['sequence'];
	}

/**
 * beforeDelete method
 *
 * @param mixed $Model
 * @access public
 * @return void
 */
	function beforeDelete(&$Model) {
		extract($this->settings[$Model->alias]);
		$data = $Model->read();
		if (!$data[$Model->alias][$sequence]) {
			return true;
		}
		$scope = $this->_scope($Model, $scope);
		if ($scope === false) {
			return false;
		}
		$conditions = array_merge($scope, array($Model->alias . '.' . $sequence . ' >' => $data[$Model->alias][$sequence]));
		$Model->updateAll(array($sequence => $Model->escapeField($sequence) . ' - 1'), $conditions);
		return true;
	}

/**
 * beforeFind method
 *
 * Set default conditions taking account of the defined scope.
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @return void
 * @access public
 */
	function beforeFind(&$Model, $queryData) {
		$scope = $this->_scope($Model, $this->settings[$Model->alias]['scope'], null, $queryData['conditions']);
		if ($scope === false) {
			if ($this->settings[$Model->alias]['strict']) {
				return false;
			}
			return true;
		} elseif ($scope) {
			$queryData['conditions'] = array_merge($scope, (array)$queryData['conditions']);
			return $queryData;
		}
		return true;
	}

/**
 * beforeSave method
 *
 * If a field has been specified as a scope and it isn't set - return false
 *
 * @param mixed $Model
 * @access public
 * @return void
 */
	function beforeSave(&$Model) {
		extract($this->settings[$Model->alias]);
		$scope = $this->_scope($Model, $scope);
		if ($scope === false) {
			return false;
		}
		if (!$Model->id && empty($Model->data[$Model->alias][$Model->primaryKey]) ||
			array_key_exists($sequence, $Model->data[$Model->alias]) && !$Model->data[$Model->alias][$sequence]) {
			$edge = $this->__getMax($Model, $scope);
			$Model->data[$Model->alias][$sequence] = $edge + 1;
		}
		return true;
	}

/**
 * listValue method
 *
 * @param mixed $Model
 * @param mixed $value
 * @return void
 * @access public
 */
	function listValue(&$Model, $value) {
		$this->settings[$Model->alias]['scope_value'] = $value;
	}

/**
 * movedown method
 *
 * @param mixed $Model
 * @param mixed $id
 * @param int $number
 * @access public
 * @return void
 */
	function movedown(&$Model, $id = null, $number = 1) {
		if (empty ($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		$scope = $this->_scope($Model, $scope);
		if ($scope === false) {
			return false;
		}
		$conditions = array_merge($scope, array($Model->alias . '.' . $Model->primaryKey => $id));
		$order = $sequence;
		list($node) = array_values($Model->find('first', compact('conditions', 'order', 'recursive')));
		$max = $this->__getMax($Model, $scope);
		if ($number === true) {
			$newSequence = $max;
		} else {
			$newSequence = $node[$sequence] + $number;
		}
		if ($newSequence <= $max && $newSequence != $node[$sequence]) {
			$Model->id = $id;
			if ($Model->saveField($sequence, $newSequence)) {
				$conditions = array_merge($scope,
					array('NOT' => array($Model->alias . '.' . $Model->primaryKey => $id),
					array($Model->alias . '.' . $sequence . ' BETWEEN ? AND ?' => array($node[$sequence], $newSequence))));
				$Model->updateAll(array($sequence => $Model->escapeField($sequence) . ' - 1'), $conditions);
				return true;
			}
		}
		return false;
	}

/**
 * moveup method
 *
 * @param mixed $Model
 * @param mixed $id
 * @param int $number
 * @access public
 * @return void
 */
	function moveup(&$Model, $id = null, $number = 1) {
		if (empty ($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		$scope = $this->_scope($Model, $scope);
		if ($scope === false) {
			return false;
		}
		$conditions = array_merge($scope, array($Model->alias . '.' . $Model->primaryKey => $id));
		$order = $sequence;
		list($node) = array_values($Model->find('first', compact('conditions', 'order', 'recursive')));
		if ($number === true) {
			$newSequence = 1;
		} else {
			$newSequence = $node[$sequence] - $number;
		}
		if ($newSequence && $newSequence != $node[$sequence]) {
			$Model->id = $id;
			if ($Model->saveField($sequence, $newSequence)) {
				$conditions = array_merge($scope,
					array('NOT' => array($Model->alias . '.' . $Model->primaryKey => $id),
					array($Model->alias . '.' . $sequence . ' BETWEEN ? AND ?' => array($newSequence, $node[$sequence]))));
				$Model->updateAll(array($sequence => $Model->escapeField($sequence) . ' + 1'), $conditions);
				return true;
			}
		}
		return false;
	}

/**
 * recover method
 *
 * Recovers the list(s). If $scopeVal is not passed, it is run for the whole table
 * taking account of multiple lists if so defined

 *
 * @param mixed $Model
 * @param mixed $sort
 * @param mixed $__scope
 * @return void
 * @access public
 */
	function recover(&$Model, $sort = null, $__scope = null) {
		if (!$sort) {
			$sort = $Model->displayField;
		}
		$Model->recursive = -1;
		extract($this->settings[$Model->alias]);
		if ($__scope !== null && !is_string($__scope) && !$Model->hasField($__scope)) {
			$scope = $__scope;
		} else {
			$derivedScope = $this->_scope($Model, $scope);
			if ($derivedScope === false) {
				$scope = (array)$scope;
				$scope[0] = 'DISTINCT ' . $scope[0];
				$permutations = $Model->find('all', array(
					'fields' => $scope,
					'recursive' => -1
				));
				$false = false;
				foreach ($permutations as $row) {
					$result = $this->recover($Model, $sort, $row[$Model->alias]);
					if ($result !== true) {
						$false = true;
					}
					$key = current(current($row));
					$return[$key] = $result;
				}
				if ($false) {
					return $return;
				}
				return true;
			} else {
				$scope = $derivedScope;
			}
		}
		$count = 1;
		$conditions =& $scope;
		$fields = $Model->primaryKey;
		$order =& $sort;
		foreach ($Model->find('all', compact('conditions', 'order', 'recursive')) as $row) {
			$Model->{$Model->primaryKey} = $row[$Model->alias][$Model->primaryKey];
			$Model->updateAll(array($sequence => $count), array($Model->alias . '.' . $Model->primaryKey => $Model->id));
			$count++;
		}
		return true;
	}

/**
 * verify method
 *
 * Verifies that the list(s) are correct. if $scopeVal is not passed, it is run for the whole table
 * taking account of multiple lists if so defined
 *
 * @param mixed $Model
 * @param mixed $__scope
 * @return void
 * @access public
 */
	function verify(&$Model, $__scope = null) {
		extract($this->settings[$Model->alias]);
		if ($__scope !== null) {
			$scope = $__scope;
		} else {
			$derivedScope = $this->_scope($Model, $scope);
			if ($derivedScope === false) {
				$scope[0] = 'DISTINCT ' . $scope[0];
				$permutations = $Model->find('all', array(
					'fields' => $scope,
					'recursive' => -1
				));
				$false = false;
				foreach ($permutations as $row) {
					$result = $this->verify($Model, $row[$Model->alias]);
					if ($result !== true) {
						$false = true;
					}
					$return[current($row)] = $result;
				}
				if ($false) {
					return $return;
				}
				return true;
			} else {
				$scope = $derivedScope;
			}
		}
		if (!$Model->find('count', array('conditions' => $scope))) {
			return true;
		}
		$min = max($this->__getMin($Model, $scope), 1);
		$edge = $this->__getMax($Model, $scope);
		$errors =  array();
		for ($i = $min; $i <= $edge; $i++) {
			$count = $Model->find('count', array('conditions' => array_merge($scope, array($Model->alias . '.' . $sequence => $i))));
			if ($count != 1) {
				if ($count == 0) {
					$errors[] = array('index', $i, 'missing');
				} else {
					$errors[] = array('index', $i, 'duplicate');
				}
			}
		}
		if ($errors) {
			return $errors;
		} else {
			return true;
		}
	}

/**
 * getMax method
 *
 * @param mixed $Model
 * @param mixed $scope
 * @access private
 * @return void
 */
	function __getMax(&$Model, $scope) {
		$recursive = $this->settings[$Model->alias]['recursive'];
		$sequence = $this->settings[$Model->alias]['sequence'];
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		list($edge) = array_values($Model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($Model, 'max', array($Model->alias . '.' . $sequence, 'edge')),
			'recursive' => $recursive,
			'order' => false
		)));
		return ife(empty($edge['edge']), 0, $edge['edge']);
	}

/**
 * getMin method
 *
 * @param mixed $Model
 * @param mixed $scope
 * @access private
 * @return void
 */
	function __getMin($Model, $scope) {
		$recursive = $this->settings[$Model->alias]['recursive'];
		$sequence = $this->settings[$Model->alias]['sequence'];
		extract($this->settings[$Model->alias]);
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		list($edge) = array_values($Model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($Model, 'min', array($sequence)),
			'recursive' => $recursive
		)));
		return ife(empty($edge[$sequence]), 0, $edge[$sequence]);
	}

/**
 * scope method. Translate the defined scope to a condition array.
 *
 * Used to translate a scope of:
 * 	'user_id' or array('user_id')
 * Into
 * 	array('user_id' => 42)
 *
 * Can handle none trivial scopes such as converting
 * 	array('some' => 'condition', 'user_id', 'type', 'direct = sqlcomparison')
 * Into
 * 	array('some' => 'condition', 'user_id' => 42, 'type' => foo, 'direct = sqlcomparison')
 *
 * Does not recursively search for fields in defined scopes, therefore something like:
 * 	array('foo' => 'bar, array('field'))
 * Will not be translated
 *
 * @param mixed $Model
 * @param mixed $scope
 * @param mixed $id
 * @param mixed $conditions null
 * @return mixed array of conditions to use as scope, or false if it wasn't possible to determine the scope to use
 * @access protected
 */
	function _scope(&$Model, $scope, $id = null, $conditions = null) {
		if ($scope === '1 = 1') {
			return array();
		}
		if (is_string($scope)) {
			if (strpos('.', $scope)) {
				list($_alias, $scope) = explode($scope);
			}
			if ($Model->hasField($scope)) {
				$value = $this->_scopeField($Model, $scope, $id, $conditions);
				if ($value === false) {
					return false;
				}
				list($value) = $value;
				return array($Model->alias . '.' . $scope => $value);
			}
			return (array)$scope;
		} elseif (!is_array($scope)) {
			return (array)$scope;
		}
		foreach ($scope as $i => $field) {
			if (!is_numeric($i)) {
				continue;
			}
			if (is_string($field) && $Model->hasField($field)) {
				unset($scope[$i]);
				$value = $this->_scopeField($Model, $field, $id, $conditions);
				if ($value === false) {
					return false;
				}
				list($value) = $value;
				$scope[$Model->alias . '.' . $field] = $value;
			}
		}
		return $scope;
	}

/**
 * scopeField method
 *
 * For the speicfic field that is part of the behavior scope, determine the value to use as a condition
 * A result is returned using the following logic
 * 	If an id is passed, read the db field value directly
 * 	If the Model->id is set, read the db field value directly
 * 	If the Model->data[Model->alias][$field] is set, read from the model data
 * 	If the $conditions[$field] is set, read from the conditions (only applicable for beforeFind
 * 	If the $conditions[Model->alias . $field] is set, read from the conditions (only applicable for beforeFind
 * 	Else - trigger an error and return false
 *
 * The field value is returned wrapped in an array to allow diferenciating 'returning false' from 'error found'
 *
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $id null
 * @param mixed $conditions null
 * @return false or array(thevalue)
 * @access protected
 */
	function _scopeField(&$Model, $field, $id = null, $conditions = null) {
		$test = $field;
		if (strpos('.', $field)) {
			$test = $Model->alias . '.' . $field;
		}
		if ($id) {
			$Model->Behaviors->disable($this->name);
			$value = $Model->field($field, array($Model->primaryKey = $id));
			$Model->Behaviors->enable($this->name);
		} elseif ($Model->id) {
			$Model->Behaviors->disable($this->name);
			$value = $Model->field($field);
			$Model->Behaviors->enable($this->name);
		} elseif (!empty($Model->data[$Model->alias]) && array_key_exists($field, $Model->data[$Model->alias])) {
			$value = $Model->data[$Model->alias][$field];
		} elseif (!empty($conditions) && array_key_exists($field, $conditions)) {
			$value = $conditions[$field];
		} elseif (!empty($conditions) && array_key_exists($test, $conditions)) {
			$value = $conditions[$field];
		} elseif (!empty($conditions) && array_key_exists($Model->alias . '.' . $field, $conditions)) {
			$value = $conditions[$Model->alias . '.' . $field];
		} else {
			if ($this->settings[$Model->alias]['strict']) {
				trigger_error("ListBehavior::_scopeField() - the value for the field '$field' cannot be determined.
					Set the model id to a valid value before calling", E_USER_WARNING);
			}
			return false;
		}
		return array($value);
	}
}
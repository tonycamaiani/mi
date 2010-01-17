<?php
/* SVN FILE: $Id: swiss_army.php 2033 2009-12-16 01:24:56Z ad7six $ */

/**
 * Short description for swiss_army.php
 *
 * Long description for swiss_army.php
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
 * @version       $Revision: 2033 $
 * @modifiedby    $LastChangedBy: ad7six $
 * @lastmodified  $Date: 2009-12-16 02:24:56 +0100 (Wed, 16 Dec 2009) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * SwissArmyBehavior class
 *
 * @uses          ModelBehavior
 * @package       base
 * @subpackage    base.models.behaviors
 */
class SwissArmyBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'SwissArmy'
 * @access public
 */
	var $name = 'SwissArmy';

/**
 * setup method
 *
 * Add displayList to the findMethod propert
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function setup(&$Model) {
		$Model->_findMethods['displayList'] = $this->name;
	}

/**
 * beforeSave logic to apply to any and all models
 *
 * Add http to any url fields that don't have a scheme
 *
 * @return void
 * @access public
 */
	function beforeSave(&$Model) {
		foreach (array('url', 'website', 'web') as $urlField) {
			if (empty($Model->data[$Model->alias][$urlField]) || is_array($Model->data[$Model->alias][$urlField])) {
				continue;
			} if (in_array($Model->data[$Model->alias][$urlField][0], array('/', '#'))) {
				continue;
			} if (!strpos($Model->data[$Model->alias][$urlField], '://')) {
				$Model->data[$Model->alias][$urlField] = 'http://' . $Model->data[$Model->alias][$urlField];
			}
		}
		return true;
	}

/**
 * Set messages to always (without exception) be the rule name that failed
 * allows easier i18n of error messages
 *
 * @see validates, MiI18nShell
 * @return void
 * @access public
 */
	function beforeValidate($Model) {
		foreach ($Model->validate as $field => $ruleSet) {
			if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
				$Model->validate[$field] = array($ruleSet);
			}
			foreach ($Model->validate[$field] as $index => $validator) {
				if (!is_array($validator)) {
					$Model->validate[$field][$index] = array('rule' => $validator);
					$message = $validator;
				} elseif (is_array($validator['rule'])) {
					foreach ($validator['rule'] as $i => $j) {
						if (is_array($j)) {
							$validator['rule'][$i] = implode($j, ', ');
						}
					}
					$message = $validator['rule'][0];
				} else {
					$message = $validator['rule'];
				}
				if (is_string($index)) {
					$message = $index;
				}

/*
if (is_array($validator['rule'])) {
	array_shift($validator['rule']);
	if ($validator['rule']) {
		$message .= ' %' . implode($validator['rule'], ' %');
	}
}
 */
				$Model->validate[$field][$index]['message'] = $message;
			}
		}
		return true;
	}

/**
 * begin method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function begin(&$Model) {
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		return $db->begin($Model);
	}

/**
 * commit method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function commit(&$Model) {
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		return $db->commit($Model);
	}

/**
 * display method
 *
 * @param mixed $id
 * @return void
 * @access public
 */
	function display(&$Model, $id = null) {
		if (!$id) {
			if (!$Model->id) {
				return false;
			}
			$id = $Model->id;
		}
		return current($Model->find('list', array('conditions' => array(
			$Model->alias . '.' . $Model->primaryKey => $id))));
	}

/**
 * findDisplayList method
 *
 * @param mixed $Model
 * @param mixed $state
 * @param mixed $query
 * @param array $results array()
 * @return void
 * @access public
 */
	function findDisplayList(&$Model, $state, $query, $results = array()) {
		if ($state == 'before') {
			$query = am(array(
				'conditions' => array(),
				'fields' => array(
					'{0} ({1})',
					$Model->alias . '.' . $Model->displayField,
					$Model->alias . '.' . $Model->primaryKey
				),
				'joins' => array(),
				'limit' => null,
				'offset' => null,
				'order' => array(
					$Model->alias . '.' . $Model->displayField,
					$Model->alias . '.' . $Model->primaryKey
				),
				'page' => null,
				'group' => null,
				'callbacks' => 1,
				'recursive' => -1,
				'seperator' => ' ',
				'pattern' => null
			), array_filter($query));
			if (!$query['pattern']) {
				if (strpos($query['fields'][0], '{') !== false) {
					$query['pattern'] = $query['fields'][0];
					unset($query['fields'][0]);
				} else {
					$offset = 0;
					$pattern = array();
					foreach($query['fields'] as $i => $field) {
						if (is_array($field)) {
							$offset++;
							$subpattern = array();
							$j = count($query['fields']) - $offset;
							foreach($field as $subfield) {
								$subpattern[] = '{' . $j . '}';
								$j++;
								$query['fields'][] = $subfield;
							}
							unset($query['fields'][$i]);
							$pattern[] = implode($subpattern, ' ');
							continue;
						}
						$pattern[] = '{' . ($i - $offset) . '}';
					}
					$query['pattern'] = implode($pattern, $query['seperator']);
				}
			}
			if (!in_array($Model->primaryKey, $query['fields']) &&
				!in_array($Model->alias . '.' . $Model->primaryKey, $query['fields'])) {
				$query['fields'][] = $Model->alias . '.' . $Model->primaryKey;
			}
			return $query;
		}
		if (empty($results)) {
			return array();
		}
		$keyPath = "{n}.{$Model->alias}.{$Model->primaryKey}";
		$valuePath[] = $query['pattern'];
		foreach ($query['fields'] as $field) {
			if (strpos($field, '.')) {
				$valuePath[] = '{n}.' . $field;
			} else {
				$valuePath[] = '{n}.' . $Model->alias . '.' . $field;
			}
		}
		return Set::combine($results, $keyPath, $valuePath);
	}

/**
 * invalidate method
 *
 * Translate error messages from codes to localized strings
 * Skip the error message if it doesn't look like a code (i.e. it's already been converted to text)
 *
 * @param mixed $field
 * @param bool $value
 * @return void
 * @access public
 */
	function invalidate(&$Model, $field, $value = true) {
		if (!is_array($Model->validationErrors)) {
			$Model->validationErrors = array();
		}
		if (!preg_match('/[^\\dA-Z]/i', $value)) {
			$_field = $field;
			if (substr($_field, -3) == '_id') {
				$_field = substr($_field, 0, strlen($_field) - 3);
			}
			$rule = Inflector::humanize(Inflector::underscore($Model->alias . '_' . $_field . '_' . $value));
			$pluginDomain = str_replace('AppModel', '', get_parent_class($Model));
			if ($pluginDomain) {
				$pluginDomain = Inflector::underscore($pluginDomain) . '_';
			}
			$_value = __d($pluginDomain . 'error_messages', $rule, true);
			if (strpos($_value, '%') !== false && isset($Model->validate[$field][$value]['rule'][1]) &&
				is_array($Model->validate[$field][$value]['rule'])) {
				$params = $Model->validate[$field][$value]['rule'];
				$params[0] = $_value;
				$_value = call_user_func_array('sprintf', $params);
			}
			$value = $_value;
		}
		$Model->validationErrors[$field] = $value;
	}

/**
 * noHtml method
 *
 * @param mixed $vals
 * @return void
 * @access public
 */
	function noHtml(&$Model, $vals) {
		foreach ($vals as $val) {
			$noHtml = strip_tags($val);
			if ($noHtml != $val) {
				return false;
			}
		}
		return true;
	}

/**
 * rollback method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function rollback(&$Model) {
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		return $db->rollback($Model);
	}

/**
 * searchConditions method
 *
 * Get generic search conditions - searching in all fields of the model, and checking associated models if
 * appropriate. Override to make more specific
 *
 * @TODO implement extended search - automatically bind hasMany, habtm join table associations to get results
 * @param string $term
 * @param bool $extended
 * @return void
 * @access public
 */
	function searchConditions(&$Model, $term = '', $extended = false) {
		if (strpos($term, '%') === false) {
			$term = '%' . $term . '%';
		}
		$Models = array($Model);
		if ($extended) {
			$Model->recursive = 0;
			foreach ($Model->hasMany as $alias => $ModelArray) {
				if (is_string($ModelArray)) {
					$alias = $ModelArray;
				}
				if ($Model->$alias->useDbConfig != $Model->useDbConfig) {
					continue;
				}
				/* TODO */
			}
		}
		if ($Model->recursive != -1) {
			foreach (array('belongsTo', 'hasOne') as $association) {
				foreach ($Model->$association as $alias => $ModelArray) {
					if (is_string($ModelArray)) {
						$alias = $ModelArray;
					}
					if ($Model->$alias->useDbConfig != $Model->useDbConfig) {
						continue;
					}
					$Models[] = $Model->$alias;
				}
			}
		}
		$conditions = array();
		foreach ($Models as $Model) {
			foreach ($Model->schema() as $key => $details) {
				if (in_array($details['type'], array('string', 'text'))) {
					$conditions['OR'][$Model->alias . '.' . $key . ' LIKE'] = $term;
				}
			}
		}
		return $conditions;
	}

/**
 * searchFilterFields method
 *
 * @return void
 * @access public
 */
	function searchFilterFields(&$Model) {
		$return = array();
		foreach ($Model->schema() as $field => $details) {
			if ($field == $Model->primaryKey || ($field == 'password')) {
				continue;
			}
			$options = array();
			if (strpos('_id', $field)) {
				$options['filterOptions'] = false;
			}
			if (!empty($details['type'])) {
				if ($details['type'] == 'boolean') {
					$options['filterOptions'] = false;
				} elseif ($details['type'] == 'text') {
					$options['type'] = 'string';
				}
			} else {
				$options['type'] = 'string';
			}
			$return[$field] = $options;
		}
		return $return;
	}

/**
 * Allow possibility to truncate tables
 *
 * @return void
 * @access public
 */
	function truncate(&$Model) {
		$db =& ConnectionManager::getDataSource($Model->useDbConfig);
		return $db->truncate($Model);
	}

/**
 * updateCounterCache method
 *
 * @TODO override disabled, wip
 * @param array $keys
 * @param bool $created
 * @return void
 * @access public
 */
	function updateCounterCache(&$Model, $keys = array(), $created = false) {
		return false;
		if (empty($keys)) {
			$keys = $Model->data[$Model->alias];
		}
		foreach ($Model->belongsTo as $parent => $assoc) {
			if (!empty($assoc['counterCache'])) {
				extract($assoc);
			}
		}
	}

/**
 * updateCounter method
 *
 * @param array $keys
 * @param bool $created
 * @return void
 * @access protected
 */
	function _updateCounter($keys = array(), $created = false) {
		if ($assoc['counterCache'] === true) {
			$assoc['counterCache'] = Inflector::underscore($Model->alias) . '_count';
		}
		if (!isset($keys[$assoc['foreignKey']]) || empty($keys[$assoc['foreignKey']])) {
			$keys[$assoc['foreignKey']] = $Model->field($assoc['foreignKey']);
		}
		if ($Model->{$parent}->hasField($assoc['counterCache'])) {
			$conditions = array($Model->escapeField($assoc['foreignKey']) => $keys[$assoc['foreignKey']]);
			$recursive = -1;
			if (isset($assoc['counterScope'])) {
				$conditions = array_merge($conditions, (array)$assoc['counterScope']);
				$recursive = 1;
			}
			$Model->{$parent}->updateAll(
				array($assoc['counterCache'] => intval($Model->find('count', compact('conditions', 'recursive')))),
				array($Model->{$parent}->escapeField() => $keys[$assoc['foreignKey']])
			);
		}
	}

/**
 * updateCounterTree method
 *
 * @param array $keys
 * @param bool $created
 * @return void
 * @access protected
 */
	function _updateCounterTree($keys = array(), $created = false) {
	}
}
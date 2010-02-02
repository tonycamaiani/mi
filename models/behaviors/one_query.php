<?php
/**
 * OneQuery - A behavior for generating one query
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.models.behaviors
 * @since         v 1.0 (30-Mar-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * OneQueryBehavior class
 *
 * This behavior accepts a parameter array similar to containable, but generates results by using joins
 * This also means that this behavior is INAPPROPRIATE if you are expecting multiple rows of dependent
 * data to be returned.
 *
 * It will also detect and remove unnecesary joins (caution, no RIGHT JOIN detection just now)
 *
 * Unlike similar behaviors/functionality it can (by default enabled) reformat returned results to be the
 * same format as if this behavior had not been used, even if the relevant joined model has been removed
 * by the optimize settings
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class OneQueryBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'OneQuery'
 * @access public
 */
	var $name = 'OneQuery';

/**
 * settings property
 *
 * Runtime settings
 *
 * @var array
 * @access public
 */
	var $settings = array();

/**
 * defaultSettings property
 *
 * postFormat   - Nest results to simulate the same format as not using OneQuery?
 * optimize     - Detect joins that aren't needed and don't include them in the query
 * defaultFields- The fields to use for an association if none are defined false, *, a field, or an array
 * paramName    - The Parameter name to look for in find $params. defaults to oneQuery, but can be anything
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'postFormat' => true,
		'optimize' => true,
		'defaultFields' => false,
		'paramName' => 'oneQuery',
	);

/**
 * current property
 *
 * The parameters for the current request(s) data
 *
 * @var array
 * @access private
 */
	var $__current = array();

/**
 * requestIndex property
 *
 * $the array index to use in $__current, stub solution to allow for the possibility of successive/nested
 * calls to the same model
 *
 * @var int 0
 * @access private
 */
	var $__requestIndex = 0;

/**
 * reset property
 *
 * The bound Associations that need removing and the unbound associations that need putting back
 * (if requested)
 *
 * @see OneQueryBehavior::reset()
 * @var array
 * @access private
 */
	var $__reset = array();

/**
 * sKeys property
 *
 * array keys to ignore when looking at settings/parameters
 *
 * @var array
 * @access private
 */
	var $__sKeys = array();

/**
 * setup method
 *
 * Populate $settings, set $this->__sKeys which is used as an array filter
 *
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = Set::merge($this->_defaultSettings, $config);
		if (empty($this->__sKeys)) {
			$this->__sKeys = array_merge(
				array_keys($this->settings[$Model->alias]), array('fields', 'alias', 'conditions'));
		}
	}

/**
 * afterFind method
 *
 * If postFormat is true, move things around to be the same as if OneQuery had not been used
 *
 * @param mixed $Model
 * @param mixed $results
 * @param bool $primary false
 * @return void
 * @access public
 */
	function afterFind(&$Model, $results, $primary = false) {
		if (!empty($this->__current[$this->__requestIndex][$Model->alias]['params']['postFormat'])) {
			if ($results) {
				if (isset($results[0][$Model->alias])) {
					foreach ($results as $key => &$row) {
						$row = $this->_reformatRow($Model,
							$this->__current[$this->__requestIndex][$Model->alias]['params'], $row);
					}
				}
			}
			if ($this->__requestIndex > 0) {
				$this->__requestIndex--;
			}
			return $results;
		}
		if ($this->__requestIndex > 0) {
			$this->__requestIndex--;
		}
		return true;
	}

/**
 * beforeFind method
 *
 * If oneQuery (or the configured paramName) is present in the queryData - use it
 * Elseif Model->oneQuery has been called previously, call optimize to remove unnecesary joins and return
 * Otherwise call oneQuery to generate the associations required and set the fields appropriatly
 *
 * If the find is a count, ensure that the fields param isn't hijacked and remove limit and order
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @return void
 * @access public
 */
	function beforeFind(&$Model, $queryData) {
		if (array_key_exists($this->settings[$Model->alias]['paramName'], $queryData)) {
			$queryData[$this->settings[$Model->alias]['paramName']] = array_merge(
				$this->settings[$Model->alias], $queryData[$this->settings[$Model->alias]['paramName']]);
			$oq =& $queryData[$this->settings[$Model->alias]['paramName']];
		} elseif (empty($this->__current[$this->__requestIndex][$Model->alias]['params'])) {
			return true;
		} else {
			$oq =& $this->__current[$this->__requestIndex][$Model->alias]['params'];
		}
		if ($Model->findQueryType === 'count') {
			if ($oq !== true && $oq['optimize'] && $Model->findQueryType === 'count') {
				$this->_optimize($Model, $queryData, $Model->findQueryType);
				unset($queryData['limit']);
				unset($queryData['order']);
				return $queryData;
			}
			unset($queryData['limit']);
			unset($queryData['order']);
			return true;
		}
		$queryData = $this->oneQuery($Model, null, $queryData, $Model->findQueryType);
		return $queryData;
	}

/**
 * autoParams method
 *
 * Based on the recursive param - dynamically bind every belongsTo/hasOne in scope
 *
 * @param mixed $Model
 * @param mixed $recursive
 * @param array $conditions array()
 * @param mixed $findType null
 * @return void
 * @access public
 */
	function autoParams(&$Model, &$queryData = array(), $findType = null) {
		if ($findType === null) {
			$queryData = array_merge(array(
				$this->settings[$Model->alias]['paramName'] => array_merge(
					$this->settings[$Model->alias], $params),
				'recursive' => $Model->recursive, 'order' => $Model->order,
				'conditions' => array(), 'fields' => array(), 'group' => array()),
			(array)$queryData);
		}
		return $this->_autoParams($Model, $Model, $queryData, $findType);
	}

/**
 * oneQuery method
 *
 * Generate one query for your result set
 *
 * @param mixed $Model
 * @param mixed $params
 * @return array - the fields array
 * @access public
 */
	function oneQuery(&$Model, $params, &$queryData = array(), $findType = null) {
		if ($params === true) {
			$params = $this->autoParams($Model, $queryData, $findType);
			$this->__current[$this->__requestIndex][$Model->alias]['params'] = $params;
		} elseif ($params) {
			$params = $this->__current[$this->__requestIndex][$Model->alias]['params'] =
				array_merge($this->settings[$Model->alias], (array)$params);
		} elseif (!empty($this->__current[$this->__requestIndex][$Model->alias])) {
			$params = $this->__current[$this->__requestIndex][$Model->alias]['params'];
		} elseif (!empty($queryData[$this->settings[$Model->alias]['paramName']])) {
			$params = $queryData[$this->settings[$Model->alias]['paramName']];
		} else {
			trigger_error('No Params');
			return $queryData;
		}
		$queryData = array_merge(array(
			$this->settings[$Model->alias]['paramName'] => array_merge(
				$this->settings[$Model->alias], $params),
			'recursive' => $Model->recursive, 'order' => $Model->order,
			'conditions' => array(), 'fields' => array(), 'group' => array()), (array)$queryData);
		$oq =& $queryData[$this->settings[$Model->alias]['paramName']];

		if ($findType === 'count') {
			if ($oq['optimize']) {
				$this->_optimize($Model, $queryData);
			}
			return $queryData;
		}
		if (!$queryData['fields'] && $oq['defaultFields']) {
			foreach((array)$oq['defaultFields'] as $field) {
				$queryData['fields'][] = $Model->alias . '.' . $field;
			}
		} elseif (!empty($oq['fields'])) {
			$oq['fields'] = (array)$oq['fields'];
			foreach($oq['fields'] as &$field) {
				if (!strpos($field, '.')) {
					$field = $Model->alias . '.' . $field;
				}
			}
			$queryData['fields'] = am($queryData['fields'], $oq['fields']);
		}
		$queryData = $this->_joinModels($Model, $params, $Model, $queryData);
		if ($oq['optimize'] && $findType !== 'count') {
			$this->_optimize($Model, $queryData);
			return $queryData;
		}
		return $queryData;
	}

/**
 * reset method
 *
 * Reset the model back to it's original state before calling OneQuery
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function reset(&$Model) {
		if (!empty($this->__reset[$Model->alias]['add'])) {
			$Model->bindModel($this->__reset[$Model->alias]['add'], false);
		}
		if (!empty($this->__reset[$Model->alias]['remove'])) {
			$Model->unbindModel($this->__reset[$Model->alias]['remove'], false);
		}
		unset($this->__reset[$Model->alias]);
	}

/**
 * Recursive workhorse for autoParms
 *
 * Loop over associations decrementing the internal $recursive parameter until the edge of the find "scope" is reached
 * Return the equivalent OneQuery parameter array
 *
 * @param mixed $Model
 * @param mixed $Obj Model being processed
 * @param mixed $queryData
 * @param mixed $findType
 * @param mixed $recursive null
 * @return void
 * @access protected
 */
	function _autoParams(&$Model, &$Obj, &$queryData, $findType, $recursive = null) {
		if ($queryData['recursive'] <= -1) {
			return array();
		} elseif ($recursive === null) {
			$recursive = (int)$queryData['recursive'];
			if($findType === 'count') {
				$oq =& $queryData[$this->settings[$Model->alias]['paramName']];
				if ($oq['optimize']) {
					$this->_optimize($Model, $queryData, $findType);
					if ($queryData['recursive'] == -1) {
						return array();
					}
				}
			}
		} else {
			$oq =& $queryData[$this->settings[$Model->alias]['paramName']];
		}
		$params = array();
		if ($oq['defaultFields']) {
			$fields = array();
			foreach((array)$oq['defaultFields'] as $field) {
				$fields[] = $Obj->alias . '.' . $field;
			}
			if ($fields) {
				$params['fields'] = $fields;
			}
		}

		foreach (array('belongsTo', 'hasOne') as $type) {
			foreach ($Obj->$type as $alias => $settings) {
				$params[$alias] = $this->_autoParams($Model, $Obj->$alias, $queryData, $findType, $recursive -1);
			}
		}
		return $params;
	}

/**
 * joinModels method
 *
 * Loop over the parameters recursively and attach a belongsTo to the main model for any distant association
 * that are desired in the result set.
 *
 * @param mixed $Model null
 * @param mixed $params the OneQuery params for $Obj
 * @param mixed $Obj the (associated) Model object being inspected
 * @param array $queryData array()
 * @param bool $primary true indicates first or recursivly called
 * @return void
 * @access protected
 */
	function _joinModels(&$Model, $params, &$Obj = null, &$queryData = array(), $primary = true) {
		$oq =& $queryData[$this->settings[$Model->alias]['paramName']];
		foreach ($params as $alias => $config) {
			if (is_numeric($alias)) {
				$alias = $config;
				$config = array();
			}
			if (!isset($Obj->$alias)) {
				continue;
			}
			if (isset($Model->belongsTo[$alias]) || in_array($alias, $Model->belongsTo) ||
				isset($Model->hasOne[$alias]) || in_array($alias, $Model->hasOne)) {
				$bAlias = $alias;
			} else {
				$triggerError = true;
				$binds = array();
				foreach ($Model->__associations as $type) {
					if (isset($Obj->{$type}[$alias])) {
						$triggerError = false;
						$bind = $Obj->{$type}[$alias];
						if (empty($config['alias'])) {
							$bAlias = $alias;
						} else {
							if (empty($bind['className'])) {
								$bind['className'] = $alias;
							}
							$bAlias = $config['alias'];
						}
						if ($Model !== $Obj && isset($Model->$bAlias)) {
							foreach ($Model->__associations as $_type) {
								if (isset($Model->{$_type}[$bAlias])) {
									$this->__reset[$Model->alias]['add'][$_type][$bAlias] = $Model->{$_type}[$bAlias];
									$Model->unbindModel(array($_type => array($bAlias)), false);
									break;
								}
							}
						}

						if ($primary) {
							if ($type === 'hasOne') {
								$this->__reset[$Model->alias]['add']['hasOne'][$alias] = $bind;
								$Model->unbindModel(array('hasOne' => array($alias)), false);
							} else {
								$this->__reset[$Model->alias]['remove'][$type][] = $bAlias;
							}
						}
						if ($type === 'hasAndBelongsToMany') {
							$with = $bind['with'];
							$join = array(
								'className' => $with,
								'foreignKey' => false,
								'conditions' => array(
									$with . '.' . $bind['foreignKey'] . ' = ' . $Obj->alias . '.' . $Obj->primaryKey
								)
							);
							$conditions = $with . '.' . $bind['associationForeignKey'] . ' = ' .
								$bAlias . '.' . $Obj->{$alias}->primaryKey;
							$binds[$with] = $join;
							$bind['foreignKey'] = false;
							$bind['conditions'] = array_merge((array)$bind['conditions'], (array)$conditions);
						} elseif ($bind['foreignKey']) {
							if (in_array($type, array('hasOne', 'hasMany'))) {
								$conditions = $bAlias . '.' . $bind['foreignKey'] . ' = ' .
									$Obj->alias . '.' . $Obj->primaryKey;
							} elseif ($type === 'belongsTo') {
								$conditions = $Obj->alias . '.' . $bind['foreignKey'] . ' = ' .
									$bAlias . '.' . $Obj->{$alias}->primaryKey;
							}
							$bind['foreignKey'] = false;
							if ($bAlias !== $alias) {
								foreach($bind['conditions'] as $key => $val) {
									if (strpos($alias . '.', $key) !== 0) {
										unset ($bind['conditions'][$key]);
										$bind['conditions'][str_replace($alias . '.', $bAlias . '.', $key)] = $val;
									}
								}
							}
							$bind['conditions'] = array_merge((array)$bind['conditions'], (array)$conditions);
						}
						if (!empty($config['conditions'])) {
							$bind['conditions'] = array_merge((array)$bind['conditions'], (array)$config['conditions']);
						}
						$bind['conditions'] = array_filter($bind['conditions']);
						$binds[$bAlias] = $bind;
						break;
					}
				}
				if ($binds && (!isset($queryData['recursive']) || $queryData['recursive'] < 0)) {
					$queryData['recursive'] = 0;
				}

				$Model->bindModel(array('belongsTo' => $binds), false);
				if ($triggerError) {
					trigger_error('joinModels method - ' . $Obj->name .  ' isn\'t bound to ' . $alias);
					continue;
				}
			}
			if (isset($config['fields'])) {
				if ($config['fields'] !== false) {
					if ($config['fields']) {
						if (!is_array($config['fields'])) {
							$config['fields'] = array($config['fields']);
						}
						foreach ($config['fields'] as $i => $field) {
							if (!strpos($field, '.')) {
								$config['fields'][$i] = $bAlias . '.' . $field;
							}
						}
					}
					$queryData['fields'] = array_filter(
						array_merge((array)$queryData['fields'], (array)$config['fields']));
				}
			} elseif ($this->settings[$Model->alias]['defaultFields']) {
				if (is_array($this->settings[$Model->alias]['defaultFields'])) {
					foreach ($this->settings[$Model->alias]['defaultFields'] as $field) {
						$queryData['fields'] = $bAlias . '.' . $field;
					}
				} else {
					$queryData['fields'] = $bAlias . '.' . $this->settings[$Model->alias]['defaultFields'];
				}
			}
			if (array_diff(array_keys($config), $this->__sKeys)) {
				unset($config['fields']);
				unset($config['alias']);
				unset($config['conditions']);
				$this->_joinModels($Model, $config, $Model->$alias, $queryData, false);
			}
		}
		return $queryData;
	}

/**
 * checkJoinChain method
 *
 * The input to this method is an array of associations that are thought not to be necessary for the current request
 *
 * If the request is of the form:
 * 	'Blog',
 * 	'Author'
 * 		'UserProfile'
 * 			'UserProfilePic'
 *
 * It would be required to keep the UserProfile join, to be able to retrieve data from UserProfilePic
 *
 * @param mixed $Model
 * @param array $needed array() The directly required associations
 * @return array the association array to be passed to unbind
 * @access protected
 */
	function _checkJoinChain(&$Model, &$needed = array(), $check = array()) {
		if ($check !== $needed) {
			$direct = true;
			$check = $needed;
		}
		foreach($check as $alias) {
			$settings = array();
			if (isset($Model->belongsTo[$alias])) {
				if ($Model->useDbConfig !== $Model->$alias->useDbConfig) {
					trigger_error(sprintf('Model %s and %s are correctly associated, but use different DB configurations - cannot join', $Model->alias, $alias));
				}
				$settings = $Model->belongsTo[$alias];
			} elseif (isset($Model->hasOne[$alias])) {
				if ($Model->useDbConfig !== $Model->$alias->useDbConfig) {
					trigger_error(sprintf('Model %s and %s are correctly associated, but use different DB configurations - cannot join', $Model->alias, $alias));
				}
				$settings = $Model->hasOne[$alias];
			} else {
				trigger_error(sprintf('Model %s and %s are not associated, cannot join', $Model->alias, $alias));
				continue;
			}
			if ($settings['foreignKey']) {
				continue;
			}
			$dependencies = $this->_determineAssocs($Model, $settings['conditions']);
			$missingDependencies = array_diff($dependencies, $needed);
			if ($missingDependencies) {
				$needed = array_merge($needed, $missingDependencies);
				$this->_checkJoinChain($Model, $needed, $missingDependencies);
			}
		}
		if (!empty($direct)) {
			$all = $unbind = array();
			foreach(array('belongsTo', 'hasOne') as $type) {
				foreach ($Model->$type as $k => $v) {
					if (is_numeric($k)) {
						if (!in_array($v, $needed)) {
							$all[] = $v;
							$unbind[$type][] = $v;
						}
						continue;
					}
					if (!in_array($k, $needed)) {
						$all[] = $k;
						$unbind[$type][] = $k;
					}
				}
			}
			return $unbind;
		}
		return false;
	}

/**
 * optimize method
 *
 * Determine if there are any joins that can be removed
 * Run a shortcut for counts using only the conditions
 * Otherwise inspect the fields, conditionds, order and group used for the query and trim off any joins that aren't
 * necessary
 * After determining the joins that are required, check for dependencies (intermediary joins) and unbind any associations
 * that aren't desired
 *
 * @TODO account for RIGHT JOINS
 * @param mixed $Model
 * @param mixed $queryData null
 * @param mixed $findType null
 * @return array optimized queryData
 * @access protected
 */
	function _optimize(&$Model, &$queryData = null, $findType = null) {
		if (isset($queryData['recursive']) && $queryData['recursive'] !== null && $queryData['recursive'] <= -1) {
			return true;
		} elseif ($findType === 'count') {
			$neededAssocs = $this->_determineAssocs($Model, $queryData['conditions']);
			if (!$neededAssocs) {
				$queryData['recursive'] = -1;
				return $queryData;
			}
		}
		$check = am($queryData['fields'], $queryData['conditions'], $queryData['order'], $queryData['group']);
		$candidates = $this->_determineAssocs($Model, $check);

		if (!$candidates) {
			$queryData['recursive'] = -1;
		} else {
			$unnecessary = $this->_checkJoinChain($Model, $candidates);
			if ($unnecessary) {
				$Model->unbindModel($unnecessary);
			}
		}
		return $queryData;
	}

/**
 * determineAssocs method
 *
 * From the passed array (conditions, order, fields, group, or other) determine which joins are directly required
 *
 * @param mixed $Model
 * @param mixed $params
 * @return void
 * @access protected
 */
	function _determineAssocs(&$Model, $params) {
		if (!$params) {
			return array();
		}
		$aliases = array();
		foreach ((array)$params as $k => $v) {
			if (is_array($v)) {
				$aliases = array_merge($aliases, $this->_determineAssocs($Model, $v));
				continue;
			}
			if (is_numeric($k)) {
				$k = $v;
			}
			if (!strpos($k, '.')) {
				continue;
			}
			preg_match_all('@(\w+)\.([\w\*]+)@', $k, $matches);
			foreach ($matches[1] as $alias) {
				if (!in_array($alias, $aliases)) {
					$aliases[] = $alias;
				}
			}
		}
		if (!$aliases || $aliases === array($Model->alias)) {
			return array();
		}
		return array_diff($aliases, array($Model->alias));
	}

/**
 * reformatRow method
 *
 * Based upon the parameters used for the request, reformat (recursively)
 *
 * Will change a data array of the format
 * 		'Blog',
 * 		'Author',
 * 		'UserProfile',
 * 		'UserProfilePic'
 * Into
 * 		'Blog',
 * 		'Author'
 * 			'UserProfile'
 * 				'UserProfilePic'
 *
 * Should accuratly simulate the format of an equivalent find call without using this behavior
 *
 * @param mixed $Model
 * @param mixed $params the (partial) parameters for the current row
 * @param mixed $row the (partial) row of the result set
 * @param array $result array() intermediary result reference
 * @return reformatted results array
 * @access protected
 */
	function _reformatRow(&$Model, $params, $row, &$result = array()) {
		if (!$result && isset($row[$Model->alias])) {
			$result[$Model->alias] = $row[$Model->alias];
			unset($row[$Model->alias]);
		}
		$keys = array_diff(array_keys($params), $this->__sKeys);
		foreach ($keys as $key) {
			$sub = $params[$key];
			if (is_numeric($key) && is_string($sub)) {
				$key = $sub;
				$sub = array();
			}
			if (isset($row[$key])) {
				$empty = true;
				foreach ($row[$key] as $k => $v) {
					if ($v !== null) {
						$empty = false;
						break;
					}
				}
				if ($empty) {
					continue;
				}
				$result[$key] = $row[$key];
				unset($row[$key]);
			} else {
				$result[$key] = array();
			}
			if ($keys = array_diff(array_keys($sub), $this->__sKeys)) {
				$this->_reformatRow($Model, $params[$key], $row, $result[$key]);
			}
		}
		return $result;
	}
}
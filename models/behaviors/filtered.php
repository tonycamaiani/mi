<?php
/**
 * Short description for filtered.php
 *
 * Long description for filtered.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.models.behaviors
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * FilteredBehavior class
 *
 * Filter (in reality restrict) this model's data and actions to a specific subset of data
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class FilteredBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'Filtered'
 * @access public
 */
	var $name = 'Filtered';

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array('scope' => false);

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
		extract($this->settings[$Model->alias]);
		if ($Model->Behaviors->attached('Tree')) {
			$Model->Behaviors->attach('Tree', array('scope' => $scope));
		}
		if ($Model->Behaviors->attached('List')) {
			$Model->Behaviors->attach('List', array('scope' => $scope));
		}
	}

/**
 * beforeDelete method
 *
 * If the row does not fall within the filtered dataset prevent updates.
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function beforeDelete(&$Model) {
		extract ($this->settings[$Model->alias]);
		return $Model->find('count', array('conditions' => Set::merge($scope, array($Model->alias . '.id' => $Model->id))));
	}

/**
 * beforeFind method
 *
 * Add the filter scope to all finds
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @return void
 * @access public
 */
	function beforeFind(&$Model, $queryData) {
		extract ($this->settings[$Model->alias]);
		$queryData['conditions'] = Set::merge($queryData['conditions'], $scope);
		return $queryData;
	}

/**
 * beforeSave method
 *
 * If the model's data does not fall within the filtered dataset prevent updates.
 * If an insert set the scope such that this new row is within the filtered dataset
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function beforeSave(&$Model) {
		extract ($this->settings[$Model->alias]);
		if ($Model->id) {
			return $Model->find('count', array('conditions' =>
				Set::merge($scope, array($Model->alias . '.id' => $Model->id))));
		}
		foreach ($scope as $field => $value) {
			$list = explode('.', $field);
			$alias = $Model->alias;
			if (count($list) > 1) {
				list($alias, $field) = $list;
			}
			if ($alias == $Model->alias) {
				$this->_addToWhitelist($Model, array($field));
				$Model->data[$Model->alias][$field] = $value;
			}
		}
		return true;
	}
}
?>
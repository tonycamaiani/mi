<?php
/**
 * Serialized is a behavior for automatically converting complex datatypes (arrays, objects)
 * into strings on save, and the reverse on read.
 *
 * Amongst other things this allows storing structured arbritary data in a SQL table
 *
 * PHP versions 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.models.behaviors
 * @since         v 1.0 (10-Feb-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * SerializedBehavior class
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class SerializedBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'Serialized'
 * @access public
 */
	var $name = 'Serialized';

/**
 * Runtime settings
 *
 * @var array
 * @access public
 */
	var $settings = array();

/**
 * defaultSettings property
 * 	_initialized - whether the _fields method has been called for this model
 * 	fields - can be various values
 * 		'auto' -> look for fields in the db ending with _serialized and use default serialize functions
 * 		array('this', 'that') -> for the fields 'this' and 'that' use default options
 * 		array('this' => array(
 * 			'compress' => 'serialize',
 * 			'decompress' => 'unserialize'
 * 			)
 * 		) -> for the field 'this' use specificily the named functions (can be anything)
 * 		'defaultSeralizeFunction' -> what it says on the tin
 * 		'defaultUnSeralizeFunction' -> what it says on the tin
 *
 * 	The serialize functions can be any function you like
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'__initialized' => false,
		'fields' => 'auto',
		'defaultSerializeFunction' => 'json_encode',
		'defaultUnSerializeFunction' => array('json_decode', true),
	);

/**
 * Do as little as possible
 *
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		$this->settings[$Model->name] = am ($this->_defaultSettings, $config);
	}

/**
 * For any results that contain serialized fields - unserialize them
 *
 * @param mixed $results
 * @return mixed true if nothing to do, modified model data if there is
 * @access public
 */
	function afterFind(&$Model, $results) {
		$this->_fields($Model);
		if (!isset($results[0][$Model->alias])) {
			return;
		}
		$fields = array_keys(array_intersect_key($results[0][$Model->alias], $this->settings[$Model->name]['fields']));
		if (!$fields) {
			return true;
		}
		foreach($results as &$row) {
			$row = $this->handleArrays($Model, $row, 'decompress', $fields);
		}
		return $results;
	}

/**
 * Check if there's anything to do at all, and bail early if not
 *
 * @param mixed $Model
 * @return bool
 * @access public
 */
	function beforeValidate(&$Model) {
		$this->_fields($Model);
		$fields = array_keys(array_intersect_key($Model->data[$Model->alias], $this->settings[$Model->name]['_fields']));
		if (!$fields) {
			return true;
		}
		$this->handleArrays($Model, $Model->data, 'compress');
		return true;
	}

/**
 * Check if there's anything to do at all, and bail early if not
 *
 * After calling handleArrays - check if any fields were added and add them to the whitelist if so
 *
 * @param mixed $Model
 * @return mixed true if nothing to do, modified model data if there is
 * @access public
 */
	function beforeSave(&$Model) {
		$this->_fields($Model);
		$fields = array_keys(array_intersect_key($Model->data[$Model->alias], $this->settings[$Model->name]['_fields']));
		if (!$fields) {
			return true;
		}
		$fields = array_keys($Model->data[$Model->alias]);
		$return = $this->handleArrays($Model, $Model->data, 'compress');
		$fields = array_diff(array_keys($Model->data[$Model->alias]), $fields);
		$this->_addToWhitelist($Model, $fields);
		return $Model->data;
	}

/**
 * Unserialize serialized data, and serialize arrays, returns the processed data
 *
 * @param mixed $Model
 * @param mixed $mode 'compress', 'decompress' or null
 * @return array
 * @access public
 */
	function handleArrays($Model, &$data, $mode = null) {
		extract($this->settings[$Model->name]);
		if (!$data) {
			$data =& $Model->data[$Model->alias];
		}
		if ($mode === null) {
			$this->handleArrays($Model, $data, 'compress');
			$this->handleArrays($Model, $data, 'decompress');
			return $data;
		}
		foreach($fields as $field => $functions) {
			$_field  = str_replace('_serialized', '', $field);
			if ($mode === 'decompress') {
				if (!empty($data[$Model->alias][$field])) {
					$data[$Model->alias][$_field] = $this->_convert($Model, $data[$Model->alias][$field], $functions[$mode]);
				}
				continue;
			}
			if (!empty($data[$Model->alias][$_field]) && is_array($data[$Model->alias][$_field])) {
				$data[$Model->alias][$field] = $this->_convert($Model, $data[$Model->alias][$_field], $functions[$mode]);
				unset($data[$Model->alias][$_field]);
			}
		}
		return $data;
	}

/**
 * for the passed variable ($in) call the function and return the result
 *
 * @param mixed $Model
 * @param mixed $in
 * @param mixed $function null
 * @return mixed
 * @access protected
 */
	function _convert(&$Model, $in, $function = null) {
		if (is_array($function)) {
			$params = $function;
			$function = $function[0];
			$params[0] = $in;
			if (function_exists($function)) {
				return call_user_func_array($function, $params);
			}
		}
		if (function_exists($function)) {
			return $function($in);
		}
		trigger_error('SerializedBehavior::_convert the function ' . $function . ' doesn\'t exist');
	}

/**
 * Initialize the fields for this model
 *
 * Runs only once, the first time it's needed
 *
 * @param mixed $Model
 * @return void
 * @access protected
 */
	function _fields(&$Model) {
		if ($this->settings[$Model->name]['__initialized']) {
			return;
		}
		if ($this->settings[$Model->name]['fields'] === 'auto') {
			$fields = array_keys($Model->schema());
			foreach($fields as $i => $field) {
				if (!strpos($field, '_serialized')) {
					unset($fields[$i]);
				}
			}
			$this->settings[$Model->name]['fields'] = $fields;
		}
		if (!$this->settings[$Model->name]['fields']) {
			trigger_error('SerializedBehavior::_fields is attached with no fields to process');
			$Model->Behaviors->disable('Serialized');
		}
		$this->settings[$Model->name]['fields'] = (array)$this->settings[$Model->name]['fields'];
		foreach($this->settings[$Model->name]['fields'] as $field => $functions) {
			if (is_numeric($field)) {
				$_field = $field;
				$field = $functions;
				unset($this->settings[$Model->name]['fields'][$_field]);
				$functions = array(
					'compress' => $this->settings[$Model->name]['defaultSerializeFunction'],
					'decompress' => $this->settings[$Model->name]['defaultUnSerializeFunction']
				);
				$this->settings[$Model->name]['fields'][$field] = $functions;
			}
		}
		$fields = array_keys($this->settings[$Model->name]['fields']);
		$fields = array_combine($fields, $fields);
		foreach($fields as &$field) {
			$field = str_replace('_serialized', '', $field);
		}
		$fields = array_flip($fields);
		$this->settings[$Model->name]['_fields'] = $fields;
		$this->settings[$Model->name]['__initialized'] = true;
	}
}
<?php
/**
 * Short description for routered.php
 *
 * Long description for routered.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under tbd
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.models.behaviors
 * @since         v 1.0 (25-May-2009)
 * @license       tbd
 */

/**
 * RouteredBehavior class
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class RouteredBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'Routered'
 * @access public
 */
	var $name = 'Routered';

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array();

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'urlField' => 'url',
		'serializedField' => 'url_serialized',
		//'serializedFunction' => 'serialize',
		'serializedFunction' => 'json', // easier to debug
	);

/**
 * setup method
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
 * afterFind method
 *
 * @param mixed $results
 * @return void
 * @access public
 */
	function afterFind(&$Model, $results) {
		extract($this->settings[$Model->name]);
		if (isset($results[0][$Model->alias]) && array_key_exists($serializedField, $results[0][$Model->alias])) {
			foreach($results as &$row) {
				if ($row[$Model->alias][$serializedField] &&
					$array = $this->_convert($Model, $row[$Model->alias][$serializedField], 'expand')) {
					$row[$Model->alias][$serializedField] = $array;
				}
			}
		}
		return $results;
	}

/**
 * beforeValidate method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function beforeValidate(&$Model) {
		$return = $this->handleArrayUrls($Model);
		return $return;
	}

/**
 * beforeSave method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function beforeSave(&$Model) {
		$this->handleArrayUrls($Model);
		return true;
	}

/**
 * regenerate method
 *
 * @param mixed $id null
 * @return void
 * @access public
 */
	function regenerate(&$Model, $id = null) {
		extract($this->settings[$Model->name]);
		if (!$id) {
			$rows = $this->find('list', array(
				'conditions' => array($serializedField . ' >' => ''),
				'fields' => array('id', $serializedField),
			));
			foreach($rows as $id => $serialized) {
				$this->id = $id;
				$this->saveField($urlField, $this->_url(unserialize($serialized)));
			}
			return;
		}
		$this->id = $id;
		$serialized = $this->field($serializedField);
		if ($serialized) {
			$this->saveField($urlField, $this->_url(unserialize($serialized)));
		}
	}

/**
 * handleArrays method
 *
 * @param mixed $Model
 * @return boolean
 * @access protected
 */
	function handleArrayUrls($Model) {
		extract($this->settings[$Model->name]);
		$data =& $Model->data[$Model->alias];
		if (!empty($data[$urlField]) && is_array($data[$urlField])) {
			$data[$serializedField] = $this->_convert($Model, $data[$urlField]);
			$data[$urlField] = $this->_url($data[$urlField]);
		} elseif (!empty($data[$serializedField]) && is_array($data[$serializedField])) {
			$data[$urlField] = $this->_url($data[$serializedField]);
			$data[$serializedField] = $this->_convert($Model, $data[$serializedField]);
		}
		return true;
	}

/**
 * convert method
 *
 * @param mixed $Model
 * @param mixed $in
 * @param string $mode 'compact'
 * @return void
 * @access protected
 */
	function _convert(&$Model, $in, $mode = 'compact') {
		if ($mode === 'compact') {
			if ($this->settings[$Model->name]['serializedFunction'] === 'json') {
				return json_encode($in);
			}
			return serialize($in);
		}
		if ($this->settings[$Model->name]['serializedFunction'] === 'json') {
			return json_decode($in, true);
		}
		return unserialize($in);
	}
/**
 * url method
 *
 * @param mixed $url
 * @param array $merge
 * @return void
 * @access protected
 */
	function _url($url, $merge = array('admin' => null, 'prefix' => null, 'plugin' => null)) {
		if (class_exists('SeoComponent')) {
			return SeoComponent::url(am($merge, $url));
		}
		App::import('Core', 'Router');
		return Router::url(am($merge, $url));
	}
}
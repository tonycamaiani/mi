<?php
/**
 * A behavior for tracking who did what
 *
 * Initially inspired by Matt Pseudocoder Curry's understatedly named
 * 	Super Awesome Advanced CakePHP tips PDF
 *
 * Main difference is that this behavior doesn't have any dependencies; can be configured to save
 * more/less fields; and be used for custom events
 *
 * PHP versions 4 and 5
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
 * @since         v 1.0 (15-Jun-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * TrackedBehavior class
 *
 * A behavior which can be used to automatically know who created/modified/deleted what.
 *
 * If you want to know who is deleting your data, it's assumed by this behavior that you're using
 * a soft-delete behavior to take care of that, which will trigger a save with deleted => date()
 * upon delete.
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class TrackedBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'Tracked'
 * @access public
 */
	var $name = 'Tracked';

/**
 * currentEvent property
 *
 * Used internally, stores:
 * array(
 * 	'Alias' => 'created',
 * 	'OtherAlias' => 'published',
 * );
 * To allow, if so configured, custom events to take presidence and prevent the modified event from
 * being triggered
 *
 * @var array
 * @access protected
 */
	var $_currentEvent = array();

/**
 * currentUser property
 *
 * Populated automatically from the session, or by calling setUser
 *
 * @var array
 * @access protected
 */
	var $_currentUser = array();

/**
 * defaultSettings property
 * event => array(field_to_popuplate => with_this_value)
 * Warnings: boolean to trigger warnings for missing fields
 * mulitpleEvents: boolean whether to effectively loop on events. Only relevant for custom events
 * 	if true triggering "published" will also trigger "modified"
 * 	else triggering "plublished" will not be considered a modification in terms of whether or not to
 * 	populate the fields for the modified event
 * LogModel: @TODO use a dedicated log model instead of fields embedded in the tables if set?
 * 	Someone else already wrote a robust audit behavior?
 *
 * Default events are created, deleted, modified - you can define your own events @see populateEvent
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'created' => array(
			'created_by' => 'id',
			'created_by_ip' => 'ip',
		),
		'deleted' => array(
			'deleted_by' => 'id',
			'deleted_by_ip' => 'ip',
		),
		'modified' => array(
			'modified_by' => 'id',
			'modified_by_ip' => 'ip',
		),
		'multipleEvents' => true,
		'warnings' => true,
		'LogModel' => false
	);

/**
 * setup method
 *
 * For the configured events, check for missing fields and trim them off. If warnings is true
 * trigger an error for any missing fields.
 *
 * There's an exception for the deleted event - if the field 'deleted' doesn't exist the config for
 * it is removed with no warning
 *
 * @param mixed $Model
 * @param array $config array()
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = am ($this->_defaultSettings, $config);
		foreach ($this->settings[$Model->alias] as $event => &$params) {
			if (!is_array($params)) {
				continue;
			} elseif ($event = 'deleted' && !$Model->hasField('deleted')) {
				unset($this->settings[$Model->alias]['deleted']);
				continue;
			}

			foreach ($params as $field => $val) {
				if (!$Model->hasField($field)) {
					if ($this->settings[$Model->alias]['warnings']) {
						trigger_error("Tracked Behavior:: $Model->alias doesn't have the field $field");
					}
					unset($params[$field]);
				}
			}
		}
	}

/**
 * setUser method
 *
 * There's no need to call this method explicitly unless for whatever reason the current user's data
 * Isn't what you want to use, or it isn't where it's set to look ($_SESSION['Auth']['User']). You
 * might call this method during a shell to set the user id to an admin (for example)
 *
 * If called with data, it will set the current user data.
 * If called with true, it will reset the current user data (to whatever's in the session)
 * If called with no data, it will read from the session (if set) or use a fallback of userid = 0
 *
 * Also sets the ip to the current request if it isn't in the passed data array.
 *
 * @param mixed $Model
 * @param array $data array()
 * @return array current user data
 * @access public
 */
	function setUser(&$Model, $data = array()) {
		if (!$data) {
			if ($this->_currentUser) {
				return $this->_currentUser;
			}
			if (isset($_SESSION['Auth']['User'])) {
				$data = $_SESSION['Auth']['User'];
			} else {
				$data['id'] = 0;
			}
			if (empty($data['ip'])) {
				App::import('Component', 'RequestHandler');
				$data['ip'] = ip2long(str_replace('::ffff:', '', RequestHandlerComponent::getClientIp()));
			}
		} elseif ($data === true) {
			$this->_currentUser = array();
			return $this->setUser($Model);
		}
		$this->_currentUser = $data;
		return $this->_currentUser;
	}

/**
 * user method
 *
 * return the current user's <field>
 *
 * @param mixed $Model
 * @param string $field 'id'
 * @return mixed
 * @access public
 */
	function user($Model, $field = 'id') {
		$data = $this->setUser($Model);
		return $data[$field];
	}

/**
 * afterSave method
 *
 * Reset the current event
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function afterSave(&$Model) {
		$this->_currentEvent[$Model->alias] = null;
	}

/**
 * beforeSave method
 *
 * Set created by/(last) modified by/deleted by automatically
 *
 * @param mixed $Model
 * @return boolean
 * @access public
 */
	function beforeSave(&$Model) {
		if ($Model->id) {
			if (!empty($Model->data[$Model->alias]['deleted'])) {
				$this->populateEvent($Model, 'deleted');
			} else {
				$this->populateEvent($Model, 'modified');
			}
		} else {
			$this->populateEvent($Model, 'created');
		}
		return true;
	}

/**
 * populateEvent method
 *
 * For the given event, populate the model data and add the fields to the whitelist
 *
 * Called automatically for created/modified/delete, call explicitly (in your model/behavior beforeSave)
 * if you've got your own events defined - such as 'published_by', 'approved_by', 'paid_by',
 * 'last_commented_by' etc.
 *
 * If you don't want your custom event to overwrite the modified by data - set mutlipleEvents to false
 * in the config
 *
 * @param string $event the name of the event
 * @return void
 * @access public
 */
	function populateEvent(&$Model, $event = null) {
		if (!$event || empty($this->settings[$Model->alias][$event])) {
			return;
		}
		if (!$this->settings[$Model->alias]['multipleEvents'] && !empty($Model->_currentEvent[$Model->alias])) {
			return;
		}
		$this->_currentEvent[$Model->alias] = $event;
		foreach($this->settings[$Model->alias][$event] as $field => $value) {
			$Model->data[$Model->alias][$field] = $this->user($Model, $value);
		}
		$this->_addToWhitelist($Model, array_keys($this->settings[$Model->alias][$event]));
	}
}
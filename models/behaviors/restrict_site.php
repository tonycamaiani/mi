<?php
/* SVN FILE: $Id$ */

/**
 * Short description for restrict_site.php
 *
 * Long description for restrict_site.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.models.behaviors
 * @since         v 1.0 (12-Oct-2009)
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * RestrictSiteBehavior class
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class RestrictSiteBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'RestrictSite'
 * @access public
 */
	var $name = 'RestrictSite';

/**
 * settings property
 *
 * Settings for this behavior are global, not per-model as is usually the case,
 * 	writeIfMissing - the name of the config file to write the site_id if it's missing
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'writeIfMissing' => false,
		'model' => 'Site'
	);

	var $__write = false;

/**
 * setup method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		if (!$Model->hasField('site_id')) {
			return $Model->Behaviors->disable('RestrictSite');
		}
		if ($config) {
			$this->settings = array_merge($this->settings, $config);
		}
	}

/**
 * beforeFind method
 *
 * @param mixed $Model
 * @param mixed $queryData
 * @return void
 * @access public
 */
	function beforeFind(&$Model, $queryData) {
		if (!$Model->hasField('site_id')) {
			return $queryData;
		}
		$siteId = $this->_site();
		if (!$siteId) {
			return $queryData;
		}
		$queryData['conditions'][$Model->alias . '.site_id'] = $siteId;
		return $queryData;
	}

/**
 * beforeSave method
 *
 * @param mixed $Model
 * @param array $options array()
 * @return void
 * @access public
 */
	function beforeSave(&$Model, $options = array()) {
		if (!$Model->hasField('site_id')) {
			return true;
		}
		$siteId = $this->_site();
		if (!$siteId) {
			return true;
		}
		if (!$Model->id || !$Model->findById($Model->id)) {
			$this->_addToWhitelist($Model, array('site_id'));
			$Model->data[$Model->alias]['site_id'] = $siteId;
		}
		return true;
	}

/**
 * restrictSite method
 *
 * @param mixed $id null
 * @return void
 * @access public
 */
	function restrictSite($Model, $id = null) {
		Configure::write('Site.id', $id);
		$this->_site(true);
	}

/**
 * site method
 *
 * @return void
 * @access protected
 */
	function _site($reset = false) {
		static $return;
		if (!$reset && $return !== null) {
			return $return;
		}

		$return = Configure::read('Site.id');
		if ($return) {
			if ($return !== 'false') {
				return $return;
			}
			return false;
		}
		$this->__write = true;

		$host = env('HTTP_HOST');
		if (!$host) {
			Configure::write('Site.id', false);
			return false;
		}
		$Site = ClassRegistry::init(array(
			'class' => $this->settings['model'],
			'table' => false
		));
		$db =& ConnectionManager::getDataSource($Site->useDbConfig);
		$sources = $db->listSources();
		if (in_array('sites', $sources)) {
			$Site->setSource('sites');
			$return = $Site->field('id', array('domain' => $host));
			if (!$return) {
				$Site->create();
				if (defined('DEFAULT_LANGUAGE')) {
					$language = DEFAULT_LANGUAGE;
				} else {
					$language = 'eng';
				}
				if ($Site->save(array(
					'name' => $host,
					'domain' => $host,
					'language' => $language
				))) {
					$return = $Site->id;
				}
			}
		} else {
			$return = false;
		}
		Configure::write('Site.id', $return);
		return $return;
	}

/**
 * destruct method
 *
 * @return void
 * @access private
 */
	function __destruct() {
		if ($this->__write && $this->settings['writeIfMissing']) {
			$this->__write();
		}
	}

/**
 * write method
 *
 * @return void
 * @access private
 */
	function __write() {
		$File = new File(CONFIGS . $this->settings['writeIfMissing'] . '.php');
		$return = $this->_site();
		if (!$return) {
			$return = 'false';
		}
		$File->append("
/**
 * The site id - used to allow storing data for more than one site in the same table
 */
	Configure::write('Site.id', '$return');");
	}
}
<?php


/**
 * A class for caching and speeding up router usage
 *
*
 * PHP versions 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Originally proposed by Matt Curry (www.pseudocoder.com)
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.vendors
 * @since         v 1.0 (05-Jun-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Vendor', 'Mi.MiCache');

/**
 * UrlCache class
 *
 * A singleton class for caching and speeding up router usage
 *
 * @uses
 * @package       mi
 * @subpackage    mi.vendors
 */
class UrlCache {

/**
 * site property
 *
 * @var string 'main'
 * @access public
 */
	public $site = 'main';

/**
 * webroot property
 *
 * @var string '/'
 * @access public
 */
	var $webroot = '/';

/**
 * cache property
 *
 * @var array
 * @access protected
 */
	var $_cache = array();

/**
 * cacheGlobal property
 *
 * @var array
 * @access protected
 */
	var $_cacheGlobal = array();

/**
 * change property
 *
 * Has the cache changed this request
 *
 * @var bool false
 * @access protected
 */
	var $_change = false;

/**
 * changeGlobal property
 *
 * Has the global cache changed this request
 *
 * @var bool false
 * @access protected
 */
	var $_changeGlobal = false;

/**
 * extraParams property
 *
 * Parameters to use as a default/merge with array urls
 *
 * @var array
 * @access protected
 */
	var $_extraParams = array();

/**
 * The cache key, derived from the current url
 *
 * @var array
 * @access protected
 */
	var $_key = array();

/**
 * getInstance method
 *
 * Only ever want one instance of this class
 *
 * @return void
 * @access public
 */
	function getInstance($path = null, $params = null) {
		static $instance = array();
		if (!$instance) {
			$instance[0] = new UrlCache();
			$instance[0]->init($path, $params);
		}
		return $instance[0];
	}

/**
 * init method
 *
 * Setup the cache, and write a few settings
 *
 * @return void
 * @access public
 */
	function init($path = null, $params = array()) {
		if (!$path) {
			$view =& ClassRegistry::getObject('view');
			if ($view) {
				$path = $view->here;
				if (!empty($view->webroot)) {
					$this->webroot = $view->webroot;
				}
				if ($view->here == '/') {
					$path = 'home';
				}
				$params = $view->params;
			}
		}
		if (!empty($params['site'])) {
			$this->site = $params['site'];
		}

		$this->_key = $this->site . '_' . strtolower(Inflector::slug($path));

		$params = array_merge(array(
			'cacheConfig' => array(
				'name' => 'Url',
				'engine' => 'MiFile',
				'duration' => '+1 year',
				'prefix' => '',
				'path' => 'url/',
				'serialize' => true
			)
		), (array)$params);
		MiCache::config($params['cacheConfig']);
		$this->_cache = MiCache::read($this->_key, 'Url');
		$this->_cacheGlobal = MiCache::read('_global', 'Url');
		if ($params) {
			$this->_extraParams = array_intersect_key(
				(array)$params, array('controller' => '', 'plugin' => '', 'action' => '', 'prefix' => '')
			);
		} else {
			$this->_extraParams = array('controller' => '', 'plugin' => '', 'action' => '', 'prefix' => '');
		}
	}

/**
 * url method
 *
 * Bail early if it's a string url and doesn't start with a / unless it's an asset
 * This means a relative url (shouldn't ever be any) an # or http:...
 *
 * Otherwise, check the cache and return the previously calculated url or calculate the url,
 * cache it and return it.
 *
 * For assets, if they exist in the webroot the url is automatically timestamped
 *
 * @param mixed $url
 * @param mixed $full
 * @return void
 * @access public
 */
	function url($url, $full) {
		$isAsset = false;
		if (is_string($url)) {
			if (strpos($url, '://')) {
				return $url;
			}
			if (!$url || ($url[0] !== '/' || !preg_match('@/(^|/)(aud|doc|gen|ico|img|txt|vid|js|css|files)/@', $url))) {
				return $this->webroot . ltrim($url, '/');
			}
			$isAsset = true;
			$hash = $url;
		} else {
			$url += $this->_extraParams;
			$hash = md5(serialize($url));
		}
		if (isset($this->_cache[$hash])) {
			return $this->_cache[$hash];
		} elseif (isset($this->_cacheGlobal[$hash])) {
			return $this->_cacheGlobal[$hash];
		}

		if (class_exists('SeoComponent')) {
			$url = SeoComponent::url($url, $full);
		} else {
			$url = Router::url($url, $full);
		}
		if ($isAsset) {
			if (preg_match('@/(^|/)(aud|doc|gen|ico|img|txt|vid|js|css|files)/@', $url) && !file_exists(WWW_ROOT . ltrim($url, '/'))) {
				$url = $url . '?token=' . Security::hash($url, null, true);
			}
			$this->_cacheGlobal[$hash] = $url;
			$this->_changeGlobal = true;
		} else {
			$this->_cache[$hash] = $url;
			$this->_change = true;
		}
		return $url;
	}

/**
 * delete method
 *
 * Delete directly from the hash map. Intended usage would be to clear timestamps (which would
 * be populated on the next request for the same asset/url).
 *
 * If there is no match for the hash (and a url has been passed) it'll look for matching values
 * and unset the hashes.
 *
 * @param string $hash md5(serialize($array)) or the string url
 * @return void
 * @access public
 */
	function delete($hash) {
		if ($this->_cacheGlobal) {
			if (isset($this->_cacheGlobal[$hash])) {
				unset($this->_cacheGlobal[$hash]);
				$this->_changeGlobal = true;
				return true;
			} elseif ($hash = array_search($hash, $this->_cacheGlobal)) {
				unset($this->_cacheGlobal[$hash]);
				$this->_changeGlobal = true;
				return true;
			}
		}
		if ($this->_cache) {
			if (isset($this->_cache[$hash])) {
				unset($this->_cache[$hash]);
				$this->_change = true;
			} elseif ($hash = array_search($hash, $this->_cache)) {
				unset($this->_cache[$hash]);
				$this->_change = true;
				return true;
			}
		}
		return false;
	}

/**
 * read method
 *
 * @param mixed $hash
 * @return void
 * @access public
 */
	function read($hash) {
		if (isset($this->_cache[$hash])) {
			return $this->_cache[$hash];
		} elseif(isset($this->_cacheGlobal[$hash])) {
			return $this->_cacheGlobal[$hash];
		}
		return false;
	}

/**
 * Directly write a $hash -> $url pair
 *
 * This can be used to re-map one url to another, intended usage is with the MiAsset helper
 * to avoid the need to even refer to the MiCompressor class at run time
 *
 * @param string $hash md5(serialize($array)) or the string url
 * @param string $url
 * @param bool $overwrite false
 * @return void
 * @access public
 */
	function store($hash, $url, $overwrite = false) {
		$isAsset = false;
		if (preg_match('@/(^|/)(aud|doc|gen|ico|img|txt|vid|js|css|files)/@', $url)) {
			$isAsset = true;
		}
		if ($isAsset) {
			if (!$overwrite && isset($this->_cacheGlobal[$hash])) {
				return false;
			}
			$this->_cacheGlobal[$hash] = $url;
			$this->_changeGlobal = true;
		} else {
			if (!$overwrite && isset($this->_cache[$hash])) {
				return false;
			}
			$this->_cache[$hash] = $url;
			$this->_change = true;
		}
		return true;
	}

/**
 * Write the cache only if it changed
 *
 * @return void
 * @access private
 */
	function __destruct() {
		if ($this->_change) {
			MiCache::write($this->_key, $this->_cache, 'Url');
		}
		if ($this->_changeGlobal) {
			MiCache::write('_global', $this->_cacheGlobal, 'Url');
		}
	}
}
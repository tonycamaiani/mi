<?php
/* SVN FILE: $Id: mi.php 2068 2010-01-07 17:21:53Z AD7six $ */

/**
 * Short description for mi.php
 *
 * Long description for mi.php
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
 * @subpackage    base.views
 * @since         v 1.0
 * @version       $Revision: 2068 $
 * @modifiedby    $LastChangedBy: AD7six $
 * @lastmodified  $Date: 2010-01-07 18:21:53 +0100 (Thu, 07 Jan 2010) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * MiView class
 *
 * @uses          View
 * @package       base
 * @subpackage    base.views
 */
class MiView extends View {

/**
 * construct method
 *
 * @param mixed $controller
 * @return void
 * @access private
 */
	function __construct(&$controller) {
		parent::__construct($controller);
		$this->theme =& $controller->theme;
	}

/**
 * Overriden to permit multiple row same-model forms (admin_multi_edit) to work
 * Slightly none-DRY to prevent any changes to the cake method from affecting
 * normal form elements
 *
 * @return array An array containing the identity elements of an entity
 */
	function entity() {
		$assoc = ($this->association) ? $this->association : $this->model;
		if (!empty($this->entityPath)) {
			$path = explode('.', $this->entityPath);
			$count = count($path);
			if  ($count !== 3) {
				return parent::entity();
			}
			return Set::filter($path);
		}
		return parent::entity();
	}

/**
 * Overriden to prevent view processing from being forced to the View class
 *
 * @param string $action Name of action to render for
 * @param string $layout Layout to use
 * @param string $file Custom filename for view
 * @return string Rendered Element
 */
	function render($action = null, $layout = null, $file = null) {
		if ($this->hasRendered) {
			return true;
		}
		$out = null;

		if ($file != null) {
			$action = $file;
		}

		if ($action !== false && $viewFileName = $this->_getViewFileName($action)) {
			$out = $this->_render($viewFileName, $this->viewVars);
		}

		if ($layout === null) {
			$layout = $this->layout;
		}

		if ($out !== false) {
			if ($layout && $this->autoLayout) {
				$out = $this->renderLayout($out, $layout);

/* AD7six start
if (isset($this->loaded['cache']) && (($this->cacheAction != false)) && (Configure::read('Cache.check') === true)) {
 */
				if (isset($this->loaded['cache'])) {

/* AD7six end */
					$replace = array('<cake:nocache>', '</cake:nocache>');
					$out = str_replace($replace, '', $out);
				}
			}
			$this->hasRendered = true;
		} else {
			$out = $this->_render($viewFileName, $this->viewVars);
			trigger_error(sprintf(__d('mi', "Error in view %1$s, got: <blockquote>%2$s</blockquote>", true), $viewFileName, $out), E_USER_ERROR);
		}
		return $out;
	}

/**
 * loadHelpers method
 *
 * For any MiHelper (except miCache) make it available in the views as $helper
 * Reference: http://bin.cakephp.org/saved/40115 Thankye ADmad
 *
 * @param mixed $loaded
 * @param mixed $helpers
 * @param mixed $parent null
 * @return void
 * @access protected
 */
	function &_loadHelpers(&$loaded, $helpers, $parent = null) {
		if (!$parent) {
			if (in_array('Paginator', $helpers)) {
				$helpers[] = 'Mi.MiPaginator';
			}
		}
		$loaded = parent::_loadHelpers($loaded, $helpers, $parent);
		if (!$parent) {
			foreach(array_keys($loaded) as $helper) {
				if ($helper === 'Mi.MiCache') {
					continue;
				}
				if (preg_match('/^Mi([A-Z].*)/', $helper, $match)) {
					$loaded[$match[1]] = $loaded[$helper];
					unset($loaded[$helper]);
				}
			}
		}
		return $loaded;
	}

/**
 * Return all possible paths to find view files in order
 *
 * Check for plugin/locale/<locale>/views/.../foo.ctp if Config.language has been set
 *
 * @param string $plugin ''
 * @param bool $cached true
 * @return array paths
 * @access protected
 */
	function _paths($plugin = '', $cached = true) {
		if (!class_exists('MiCache')) {
			App::import('Vendor', 'Mi.MiCache');
		}
		$plugin = (string)$plugin;
		$theme = (string)$this->theme;
		return MiCache::mi('paths', 'view', compact('plugin', 'theme'));
	}

/**
 * render method
 *
 * Wrap the file contents in a comment with the filename (debug mode, and not the layout, only)
 *
 * @param mixed $___viewFn
 * @param mixed $___dataForView
 * @param bool $loadHelpers true
 * @param bool $cached false
 * @return void
 * @access protected
 */
	function _render($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
		$return = parent::_render($___viewFn, $___dataForView, $loadHelpers, $cached);
		if (!empty($return[0]) && $return[0] === '<' && Configure::read() && !strpos($___viewFn, 'layout')) {
			$return = "\n" . '<!-- ' . $___viewFn . ' START -->' . "\n" . $return . "\n" . '<!-- ' . $___viewFn . ' END -->' . "\n";
		}
		return $return;
	}
}
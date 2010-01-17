<?php
/* SVN FILE: $Id: mi_paginator.php 1752 2009-10-23 11:11:34Z AD7six $ */

/**
 * Short description for mi_paginator.php
 *
 * Long description for mi_paginator.php
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
 * @package       app
 * @subpackage    app.home.andy.www.apps.base.views.helpers
 * @since         v 1.0 (13-May-2009)
 * @version       $Revision: 1752 $
 * @modifiedby    $LastChangedBy: AD7six $
 * @lastmodified  $Date: 2009-10-23 13:11:34 +0200 (Fri, 23 Oct 2009) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', 'Paginator');

/**
 * MiPaginatorHelper class
 *
 * @uses          PaginatorHelper
 * @package       base
 * @subpackage    base.views.helpers
 */
class MiPaginatorHelper extends PaginatorHelper {

/**
 * Generates a sorting link with an automatically translated name taken from field_names.po
 * Also force it to be page 1 of the results
 *
 * @param mixed $title
 * @param mixed $key null
 * @param array $options array()
 * @return void
 * @access public
 */
	function sort($title, $key = null, $options = array()) {
		if (!$key) {
			$key = $title;
			if (strpos($title, '.')) {
				$title = str_replace('.', ' ', $title);
			} else {
				$view = ClassRegistry::getObject('view');
				$alias = ($view->association) ? $view->association : $view->model;
				$title = $alias . ' ' . $title;
			}
			if (substr($title, -3) == '_id') {
				$title = substr($title, 0, strlen($title) - 3);
			}
			$title = Inflector::humanize(Inflector::underscore($title));
			if (!empty($this->params['plugin'])) {
				$pluginDomain = Inflector::underscore($this->params['plugin']) . '_';
			} else {
				$pluginDomain = '';
			}
			$title = __d($pluginDomain . 'field_names', $title, true);
		}
		$options['url']['page'] = 1;
		return parent::sort($title, $key, $options);
	}

/**
 * url method
 *
 * Change applied to "if (is_array($url['order'])) {" block
 * It trips the alias if sorting by OtherModel.field
 * I.e.
 * /index/page:1/sort:Supplier.name/direction:asc
 * links to
 * I.e. /index/page:2/sort:name/direction:asc
 *
 * @TODO ticket, test case, patch, delete
 * @param array $options array()
 * @param bool $asArray false
 * @param mixed $model null
 * @return void
 * @access public
 */
	function url($options = array(), $asArray = false, $model = null) {
		$paging = $this->params($model);
		$url = array_merge(array_filter(Set::diff(array_merge($paging['defaults'], $paging['options']), $paging['defaults'])), $options);

		if (isset($url['order'])) {
			$sort = $direction = null;
			if (is_array($url['order'])) {
				// @TODO list($sort, $direction) = array($this->sortKey($model, $url), current($url['order']));
				$direction = current($url['order']);
				$sort = current(array_keys($url['order']));
			}
			unset($url['order']);
			$url = array_merge($url, compact('sort', 'direction'));
		}

		if ($asArray) {
			return $url;
		}
		return parent::url($url);
	}
}
<?php
/* SVN FILE: $Id$ */

/**
 * Short description for mi_form.php
 *
 * Long description for mi_form.php
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
 * @subpackage    base.views.helpers
 * @since         v 1.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', 'Form');

/**
 * MiFormHelper class
 *
 * @uses          FormHelper
 * @package       base
 * @subpackage    base.views.helpers
 */
class MiFormHelper extends FormHelper {
	var $helpers = array(
		'Session',
		'Html',
	);

/**
 * name property
 *
 * @var string 'Form'
 * @access public
 */
	var $name = 'MiForm';

/**
 * construct method
 *
 * @param array $options array()
 * @return void
 * @access private
 */
	function __construct($options = array()) {
		if (App::import('Helper', 'MiAsset.Asset')) {
			$this->helpers[] = 'MiAsset.Asset';
		}
		parent::__construct();
	}

/**
 * create method
 *
 * Default the form to the current url. add a hidden field for the referer
 *
 * @param mixed $model
 * @param array $options
 * @return void
 * @access public
 */
	function create($model = null, $options = array()) {
		if (!isset($options['url']) && !isset($options['action'])) {
			$options['url'] = '/' . ltrim($this->params['url']['url'], '/');
		}
		if (!empty($options['url'])) {
			$getParams = array_diff_key($this->params['url'], array('ext' => true, 'url' => true));
			if ($getParams) {
				if (is_string($options['url'])) {
					$options['url'] .= '?' . http_build_query($getParams);
				} else {
					$options['url']['?'] = $getParams;
				}
			}
		}

		$return = parent::create($model, $options);
		$referer = $this->Session->read('referer');
		if (!$referer) {
			$referer = AppController::referer('/', true);
			if (Router::normalize($referer) == Router::normalize(array('admin' => false, 'controller' => 'users', 'action' => 'login'))) {
				$referer = '/';
			}
		}
		$referer = $this->hidden('App.referer', array('default' => $referer));
		return preg_replace('#</fieldset>#', $referer . '</fieldset>', $return);
	}

/**
 * dateTime method
 *
 * Use an input and the jquery ui datepicker instead
 *
 * Pass $options['selects'] = true to bypass this override
 *
 * @param mixed $fieldName
 * @param string $dateFormat 'DMY'
 * @param string $timeFormat '12'
 * @param mixed $selected null
 * @param array $options array()
 * @param bool $showEmpty true
 * @return void
 * @access public
 */
	function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $options = array(), $showEmpty = true) {
		if (!empty($options['selects'])) {
			unset($options['selects']);
			return parent::dateTime($fieldName, $dateFormat, $timeFormat, $selected, $options, $showEmpty);
		}
		$options = $this->_initInputField($fieldName, array_merge(
			array('type' => 'text'), $options
		));
		$id = $options['id'];
		if (isset($this->Asset)) {
			$this->Asset->css('/js/theme/ui.datepicker', null, null, $this->name);
			$this->Asset->js('jquery-ui', $this->name);
			$timeSuffix = '';
			if ($timeFormat) {
				$time = array_pop(explode(' ', $options['value']));
				if ($time) {
					preg_match('@[1-9]@', $time, $matches);
					if ($matches) {
						$timeSuffix = ' ' . $time;
					}
				}
			}
			if (!empty($options['value'])) {
				if ($timeSuffix) {
					$options['value'] = str_replace('-', '/', $options['value']);
				} else {
					list($options['value']) = str_replace('-', '/', explode(' ', $options['value']));
				}
			}
			$dateFormat = 'yy/mm/dd' . $timeSuffix;

			$this->Asset->codeBlock(
				'$(document).ready(function() {
					$("#' . $id . '")
						.datepicker({
							showOn: \'button\',
							buttonImage: \'' . $this->Html->url('/img/calendar.gif') . '\',
							buttonImageOnly: true,
							dateFormat: \'' . $dateFormat . '\'
						});
				});',
				array('inline' => false)
			);
		}
		return $this->text($fieldName, am($options, array('class' =>  'datepicker')));
	}

/**
 * Returns a formatted LABEL element for HTML FORMs.
 *
 * Overriden to refer to field_names.po unless the label is explicitly passed
 *
 * @param string $fieldName This should be "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param string $text Text that will appear in the label field.
 * @return string The formatted LABEL element
 */
	function label($fieldName = null, $text = null, $attributes = array()) {
		if (empty($fieldName)) {
			$view = ClassRegistry::getObject('view');
			$fieldName = implode('.', $view->entity());
		}
		if ($text === null) {
			if (strpos($fieldName, '.')) {
				$alias = explode('.', $fieldName);
				if (count($alias) == 3) {
					$text = str_replace($alias[1] . '.', '', $fieldName);
				} else {
					$text = $fieldName;
				}
				$alias = $alias[0];
				$text = str_replace('.', ' ', $fieldName);
			} else {
				$view = ClassRegistry::getObject('view');
				$alias = ($view->association) ? $view->association : $view->model;
				$text = $alias . ' ' . $fieldName;
			}
			if (substr($text, -3) == '_id') {
				$text = substr($text, 0, strlen($text) - 3);
			}
			$_text = Inflector::humanize(Inflector::underscore($text));
			$pluginDomain = '';
			if (!empty($this->params['plugin'])) {
				$models = MiCache::mi('models', $this->params['plugin']);
				if (in_array($alias, $models)) {
					$pluginDomain = Inflector::underscore($this->params['plugin']) . '_';
				}
			}
			$text = __d($pluginDomain . 'field_names', $_text, true);
			if ($_text === $text) {
				$text = str_replace(Inflector::humanize(Inflector::underscore($alias)) . ' ', '', $_text);
			}
		}
		return parent::label($fieldName, $text, $attributes);
	}
}
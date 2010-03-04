<?php
/**
 * Short description for mi_form.php
 *
 * Long description for mi_form.php
 *
 * PHP version 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.views.helpers
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', 'Form');

/**
 * MiFormHelper class
 *
 * @uses          FormHelper
 * @package       mi
 * @subpackage    mi.views.helpers
 */
class MiFormHelper extends FormHelper {
	public $helpers = array(
		'Session',
		'Html',
	);

/**
 * name property
 *
 * @var string 'Form'
 * @access public
 */
	public $name = 'MiForm';

/**
 * construct method
 *
 * @param array $options array()
 * @return void
 * @access private
 */
	public function __construct($options = array()) {
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
	public function create($model = null, $options = array()) {
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
		if (!empty($this->data['App']['referer'])) {
			$referer = $this->data['App']['referer'];
		} else {
			$referer = $this->Session->read('referer');
			if (!$referer) {
				$referer = AppController::referer('/', true);
				if (Router::normalize($referer) == Router::normalize(array('admin' => false, 'controller' => 'users', 'action' => 'login'))) {
					$referer = '/';
				}
			}
		}
		$referer = $this->hidden('App.referer', array('default' => $referer));
		if (strpos('fieldset', $return)) {
			return preg_replace('#</fieldset>#', $referer . '</fieldset>', $return);
		}
		return $return . '<div style="display:none;">' . $referer . '</div>';
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
	public function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $options = array(), $showEmpty = true) {
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
	public function label($fieldName = null, $text = null, $attributes = array()) {
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

/**
 * select - or autocomplete
 *
 * @param mixed $fieldName
 * @param array $options array()
 * @param mixed $selected null
 * @param array $attributes array()
 * @return void
 * @access public
 */
	function select($fieldName, $options = array(), $selected = null, $attributes = array()) {
		$ac = array();
		if (!empty($options['--autocomplete--'])) {
			$ac = $options['--autocomplete--'];
			unset($options['--autocomplete--']);
		}
		if (count($options) > 1 || $ac === false) {
			return parent::select($fieldName, $options, $selected, $attributes);
		}
		$ac = array_merge(array(
			'class' => 'autocomplete',
			'source' => null,
			'writeJs' => true
		), (array)$ac);

		$hiddenOptions = $this->_initInputField($fieldName, array('secure' => false));
		$hidden = $this->hidden($fieldName, $hiddenOptions);

		if ($ac['class']) {
			if (!empty($attributes['class'])) {
				$attributes['class'] .= ' ' . $ac['class'];
			} else {
				$attributes['class'] = $ac['class'];
			}
		}
		$attributes = $this->_initInputField($fieldName . '_auto', array_merge(
			array('type' => 'text'), $attributes
		));

		if (array_key_exists('label', $ac)) {
			$attributes['value'] = $ac['label'];
		} elseif ($selected && array_key_exists($selected, $options)) {
			$attributes['value'] = $options[$selected];
		} elseif (!empty($hiddenOptions['value']) && array_key_exists($hiddenOptions['value'], $options)) {
			$attributes['value'] = $options[$hiddenOptions['value']];
		} elseif ($hiddenOptions['value']) {
			$attributes['value'] = '(' . $hiddenOptions['value'] . ')';
		}

		$input = sprintf(
			$this->Html->tags['input'],
			$attributes['name'],
			$this->_parseAttributes($attributes, array('name'), null, ' ')
		);
		if ($ac['writeJs']) {
			if (!empty($ac['source'])) {
				$source = $ac['source'];
				if (is_array($source) && !isset($source['action'])) {
					$source = json_encode($source, true);
				} else {
					$source = '"' . $this->url($source) . '"';
				}
			} else {
				if (substr($fieldName, -3) == '_id') {
					$fieldName = substr($fieldName, 0, strlen($fieldName) - 3);
				}
				if (strpos($fieldName, '.') && $fieldName[0] = strtoupper($fieldName[0])) {
					$bits = explode('.', $fieldName);
					array_shift($bits);
					$fieldName = array_shift($bits);
					if ($bits && is_numeric($fieldName)) {
						$fieldName = array_shift($bits);
					}
				}

				$controller = Inflector::pluralize($fieldName);
				$source = '"' . $this->url(array('controller' => $controller, 'action' => 'lookup')) . '"';
			}

			if (isset($this->Asset)) {
				$this->Asset->js('jquery-ui', $this->name);
				$this->Asset->codeBlock(
					'$(document).ready(function() {
						$("#' . $attributes['id'] . '").autocomplete({
								minLength: 3,
								source: ' . $source . ',
								change: function(event, ui) {
									if ($("#' . $attributes['id'] . '").text() == "") {
										$("#' . $hiddenOptions['id'] . '").val("");
									}
								},
								select: function(event, ui) {
									$("#' . $hiddenOptions['id'] . '").val(ui.item.id);
									$("#' . $attributes['id'] . '").text(ui.item.label);
								}
							});
					});'
				);
			}
		}
		return $hidden . $input;
	}
}
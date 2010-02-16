<?php
/**
 * Short description for auto_format.php
 *
 * Long description for auto_format.php
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
 * AutoFormatBehavior class
 *
 * A collection of functions for handling user input, aimed at making editing code content easier
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class AutoFormatBehavior extends ModelBehavior {

/**
 * defaultSettings property
 *
 * Fields - the fields that should be processed
 * To use the same methods for all fields specify as:
 * 		array('this', 'that')
 * 	OR to have different configurations per field specify as:
 * 		array('this' => array('formatPlainText', 'htmlTidy' => array(...)))
 *
 * Methods - the methods to run for each field as method => params
 * 	If params is false, the method is disabled
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'fields' => array(),
		'methods' => array(
			'formatPlainText' => array('checkForPhp' => true),
			'stripComments' => true,
			'autoCaption' => false,
			'escapePreContent' => false,
			'htmlTidy' => array(
				'args' => array(
					'-asxhtml',
					'-raw',
					'-modify',
					//'-access 1',
					'--break-before-br y',
					'--clean y',
					//'--bare y',
					'--drop-empty-paras y',
					'--drop-font-tags y',
					//'--drop-proprietary-attributes y',
					//'--indent y',
					'-i',
					//'--indent-spaces 4',
					'--quiet y',
					//'--show-body-only y',
					'--tab-size 4',
					'--wrap 100',
				),
				'prefix' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title></title></head><body>',
				'suffix' => '</body></html>',
				'bodyOnly' => true,
				'return' => 'result'
			)
		)
	);

/**
 * setup method
 *
 * @param mixed $Model
 * @param array $config
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		if (!isset($config['fields'])) {
			$_fields = Set::filter($Model->schema());
			$config['fields'] = array();
			foreach ($_fields as $field => $data) {
				if (isset($data['type']) && $data['type'] == 'text') {
					$config['fields'][] = $field;
				}
			}
		}
		$this->settings[$Model->alias] = Set::merge($this->_defaultSettings, $config);
		if (isset($config['methods'])) {
			foreach ($config['methods'] as $method => $params) {
				if ($params === true) {
					$this->settings[$Model->alias]['methods'][$method] = $this->_defaultSettings['methods'][$method];
				} else {
					$this->settings[$Model->alias]['methods'][$method] = am(
						(array)$this->_defaultSettings['methods'][$method], $params);
				}
			}
		}
	}

/**
 * autoCaption method
 *
 * Look for text that should be a specific class. When combined with formatPlainText, this allows
 * Users to type "Warning: Don't do xyz!" and get '<p class="warning">Don't do xyz!</p>'
 *
 * @param mixed $Model
 * @param string $string
 * @return void
 * @access public
 */
	function autoCaption(&$Model, $string = '') {
		if (!$string) {
			return $string;
		}
		$string = preg_replace('/<p>\W*Note:?\s*/', '<p class="note">', $string);
		$string = preg_replace('/<p>\W*Warning:?\s*/', '<p class="warning">', $string);
		$string = preg_replace('/<p>\W*Figure:?\s*/', '<p class="caption">Figure: ', $string);
		$string = preg_replace('/<p>\W*Table:?\s*/', '<p class="caption">Table: ', $string);
		return $string;
	}

/**
 * beforeValidate method
 *
 * Run each configured field through the methods to process
 *
 * @param mixed $Model
 * @return boolean
 * @access public
 */
	function beforeValidate(&$Model) {
		$data =& $Model->data[$Model->alias];
		foreach ($this->settings[$Model->alias]['fields'] as $field => $methods) {
			if (is_numeric($field)) {
				$field = $methods;
				$methods = $this->settings[$Model->alias]['methods'];
			}
			if ($data && array_key_exists($field, $data)) {
				foreach ($methods as $method => $params) {
					if (is_numeric($method)) {
						$method = $params;
						$params = array();
					}
					if ($params === false) {
						continue;
					}
					$data[$field] = $this->$method($Model, $data[$field], (array)$params);
				}
			}
		}
		return true;
	}

/**
 * escapePreContent method
 *
 * Look for pre tags, and html escape their contents. After that, check for any floating <?php
 * tags and escape, wrapped in a  pre tag.
 * Intended to be used in beforeSave/beforeValidate
 *
 * @param mixed $Model
 * @param string $string
 * @return string
 * @access public
 */
	function escapePreContent(&$Model, $string = '') {
		preg_match_all('@<pre[^>]*>([\\s\\S]*?)</pre>@i',  $string, $preSegments, PREG_PATTERN_ORDER);
		if (!$preSegments[1] && strpos('<?php', $string) === false) {
			return $string;
		}
		foreach ($preSegments[1] as $id => $text) {
			$string = str_replace($text, htmlspecialchars($text), $string);
		}
		if (strpos('<?php', $string) === false) {
			return $string;
		}
		preg_match_all('@<\?php([\\s\\S]*?)\?>@i',  $string, $phpSegments, PREG_PATTERN_ORDER);
		foreach ($phpSegments[1] as $id => $text) {
			$string = str_replace($text, '<pre>' . htmlspecialchars($text) . '</pre>', $string);
		}
		return $string;
	}

/**
 * formatPlainText method
 *
 * If the passed string does not start with a html tag, process the string returning valid html escaping any html
 * entities it may contain. This will cause partially formatted submissions to be double escaped.
 *
 * @param mixed $Model
 * @param string $string ''
 * @param array $params array()
 * @return string
 * @access public
 */
	function formatPlainText(&$Model, $string = '', $params = array()) {
		extract(am($this->settings[$Model->alias]['methods']['formatPlainText'], $params));
		if (!$string || strpos(trim($string), '<') === 0 || strpos($string, '<p>')) {
			return $string;
		}
		if ($checkForPhp) {
			preg_match_all('@<\?php([\\s\\S]*?)\?>@i',  $string, $codeSegments, PREG_PATTERN_ORDER);
			if ($codeSegments[1]) {
				foreach ($codeSegments[1] as $id => $text) {
					$string = str_replace($text, '{{{segment' . $id . '}}}', $string);
				}
			}
		}
		$string = htmlspecialchars($string);
		$string = explode("\r\n", $string);
		foreach ($string as $i => $para) {
			$para = preg_replace("/^[\r\t\n ]*|[\r\t\n ]*$/", '', $para);
			$para = trim($para);
			if (!$para) {
				unset ($string[$i]);
			}
		}
		if (!$string) {
			return '';
		}
		$string = '<p>' . implode($string, "</p>\n<p>") . '</p>';
		if ($checkForPhp && $codeSegments[1]) {
			foreach ($codeSegments[1] as $id => $text) {
				$string = str_replace('{{{segment' . $id . '}}}', '<pre>' . htmlspecialchars($text) . '</pre>', $string);
			}
		}
		return $string;
	}

/**
 * htmlTidy method
 *
 * Disabled for windows
 *
 * @param mixed $Model
 * @param string $string ''
 * @param array $params array()
 * @return string
 * @access public
 */
	function htmlTidy(&$Model, $string = '', $params = array()) {
		if (DS === '\\') {
			return true;
		}
		extract(am($this->settings[$Model->alias]['methods']['htmlTidy'], $params));
		$string =  $prefix . $string . $suffix;
		$File = new File(TMP . 'tidy'. DS . rand() . '.html', true);
		$File->write($string);
		$path = $File->pwd();
		$errors = $path . '.err';
		$args = implode($args, ' ');
		exec("tidy $args -f $errors $path", $out);
		$result = $File->read();
		$File->delete();

		if ($result && ($bodyOnly)) {
			preg_match("@<body[^>]*>(.*)</body>@s", $result, $matches);
			if ($matches) {
				$result = trim($matches[1]);
			}
		}
		if (file_exists($errors)) {
			$Error = new File($errors);
			$errors = $Error->read();
			$Error->delete();
		} else {
			$errors = false;
		}
		if ($return === 'result') {
			return $result;
		} elseif ($return === 'errors') {
			return $errors;
		}
		return $string === $result;
	}

/**
 * stripComments method
 *
 * @param mixed $Model
 * @param string $string ''
 * @return string
 * @access public
 */
	function stripComments(&$Model, $string = '') {
		return preg_replace('/<!--.*-->/Us', '', $string);
	}

/**
 * process method
 *
 * @param string $type
 * @param array $params
 * @return void
 * @access public
 */
	function process(&$Model, $type = 'all', $params = array('recursive' => -1)) {
		$results = $Model->find($type, $params);
		foreach ($results as $row) {
			$Model->create($row);
			$Model->save();
		}
	}
	function validateTidy(&$Model, $fieldData) {
		extract ($this->settings[$Model->alias]);
	}

/**
 * unescapePreContent method
 *
 * To be used when editing content
 *
 * @param mixed $Model
 * @param string $string
 * @return string
 * @access public
 */
	function unescapePreContent(&$Model, $string = '') {
		preg_match_all('@<pre[^>]*>([\\s\\S]*?)</pre>@i', $string, $result, PREG_PATTERN_ORDER);
		foreach ($result[1] as $id => $text) {
			$string = str_replace($text, html_entity_decode($text), $string);
		}
		return $string;
	}
}
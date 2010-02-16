<?php
/**
 * Short description for suspect.php
 *
 * Based upon the blog_spam component by Jonathan Snook (snook.ca)
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
 * SuspectBehavior class
 *
 * The Suspect behavior is used for input that is suspected to be spam.
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class SuspectBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'Suspect'
 * @access public
 */
	var $name = 'Suspect';

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	var $_defaultSettings = array(
		'autoStatus' => false,
		'fields' => array(),
		'scoreSpam' => 20,
		'scoreSuspect' => 10,
		'statusHam' => 'active',
		'statusSpam' => 'spam',
		'statusSuspect' => 'pending',
		'allowedProtocols' => array(
			'http',	'https', 'ftp'
		),
		'allowedTags' => array(
			'p', 'b', 'strong', 'i', 'em', 'br', 'address', 'code', 'pre', 'ol', 'ul', 'li', 'dl', 'dt',
			'dd', 'blockquote', 'strike', 'q', 'ins', 'del', 'tt', 'sub', 'sup', 'var', 'cite',
			'acronym' => array('lang' => 1, 'title' => 1),
			'abbr' => array('lang' => 1, 'title' => 1),
			'a' => array('href' => 1, 'hreflang' => 1, 'rel' => 1),
		),
		'rules' => array(
			'ip' => array(
				'existingSame' => array(1, 0),
			),
			'user_id' => array(
				'existingSame' => array(1, -1),
			),
			'email' => array(
				'existingSame' => array(1, -1),
				'spamWords' => array(5, 0),
			),
			'link' => array(
				'allCaps' => array(2, 0),
				'spamWords' => array(5, 0),
				'suspectUrls' => array(2, 0),
			),
			'string' => array(
				'allCaps' => array(2, 0),
				'nonsenseText' => array(1, 0),
				'linkFrequency' => array(10, 0, 0),
				'shortContent' => array(0.4, 0, 10),
				'spamWords' => array(1, 0, 0),
				'suspectPatterns' => array(5, 0),
				'existingSame' => array(2, 0),
			),
			'textarea' => array(
				'allCaps' => array(10, 0),
				'dirtyHtml' => array(1, 0),
				'linkFrequency' => array(1, 0, 2),
				'nonsenseText' => array(1, 0),
				'shortContent' => array(0.2, 0, 20),
				'spamWords' => array(1, 0),
				'suspectPatterns' => array(10, 0),
				'suspectUrls' => array(2, 0),
				'existingSame' => array(5, 0),
			),
		),
		'spamWords' => array(
			'levitra', 'viagra', 'casino', 'plavix', 'cialis', 'ativan', 'fioricet', 'rape', 'acyclovir', 'penis',
			'phentermine', 'porn', 'porno', 'pharm', 'ringtone', 'pharmacy', 'url>'
		),
		'suspectUrls' => array(
			'.html', '.info', '?', '&', 'free',
			'/\.(de|pl|cn)(\/|$)/' => 2,
			'/-.*-.*htm$/' => 2,
			'/^.{30,}$/'
		),
		'suspectPatterns' => array(
			'/^(computer|onewordtitle)(!|\.)+$/i',
			'/^(Interesting|Nice|Cool)(!|\.)+/i',
			'/^Sorry \:\(\r\n$/i',
			'/\${3,}/i', // Money
			'/([^\s])\1{5,}/i' => 5, // anything that looks like a seperator
		)
	);

/**
 * testStrings property
 *
 * @var array
 * @access protected
 */
	var $_testStrings = array();

/**
 * setup method
 *
 * @param mixed $Model
 * @param array $config
 * @return void
 * @access public
 */
	function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = Set::merge($this->_defaultSettings, $config);
	}

/**
 * beforeSave method
 *
 * @param mixed $Model
 * @return bool true (always)
 * @access public
 */
	function beforeSave(&$Model) {
		if (!$Model->id) {
			App::import('Component', 'RequestHandler');
			if (!isset($Model->data[$Model->alias]['ip'])) {
				$Model->data[$Model->alias]['ip'] = ip2long(RequestHandlerComponent::getClientIp());
			}
			$Model->data[$Model->alias]['junk_score'] = $this->score($Model, $Model->data);
			$log = $this->suspectLog($Model);
			$matches = $log['matchingRules'];
			$string = array();
			foreach ($matches as $rule => $score) {
				$string[] = $rule . ':' . $score;
			}
			$string = implode($string, ';');
			$Model->data[$Model->alias]['rule_matches'] = $string;
			$this->_addToWhitelist($Model, array('ip', 'junk_score', 'rule_matches'));
			if ($this->settings[$Model->alias]['autoStatus']) {
				$this->_addToWhitelist($Model, array('status'));
				$score = $this->score($Model, $Model->data);
				if ($score >= $this->settings[$Model->alias]['scoreSpam']) {
					$Model->data[$Model->alias]['status'] = $this->settings[$Model->alias]['statusSpam'];
				} elseif ($score >= $this->settings[$Model->alias]['scoreSuspect']) {
					$Model->data[$Model->alias]['status'] = $this->settings[$Model->alias]['statusSuspect'];
				} else {
					$Model->data[$Model->alias]['status'] = $this->settings[$Model->alias]['statusHam'];
				}
			}
			$Model->data[$Model->alias]['junk_score'] = (int)$Model->data[$Model->alias]['junk_score'];
		}
		return true;
	}

/**
 * allowedProtocols method
 *
 * @param mixed $Model
 * @return array the allowed protocols for kses
 * @access public
 */
	function allowedProtocols(&$Model) {
		extract ($this->settings[$Model->alias]);
		return $allowedProtocols;
	}

/**
 * allowedTags method
 *
 * @param mixed $Model
 * @return array the allowed tags for kses
 * @access public
 */
	function allowedTags(&$Model) {
		extract ($this->settings[$Model->alias]);
		return array_keys($allowedTags);
	}

/**
 * clean method
 *
 * Load the kses vendor and clean the passed string.
 * If the kses vendor can't be found - use $html->clean (which should still in any event be used in the view).
 *
 * @param mixed $Model
 * @param string $string
 * @return string the cleaned string
 * @access public
 */
	function clean(&$Model, $string = '') {
		if (!$this->checkKses($Model)) {
			App::import('Helper', 'Html');
			$html = new HtmlHelper();
			return $html->clean($string);
		}
		$string = preg_replace('/<([^a-zA-Z\/])/', '&lt;$1', $string);
		return $this->kses->Parse($string);
	}

/**
 * isHam method
 *
 * @param mixed $Model
 * @param array $data array()
 * @return bool true for ham, false for spam or suspect
 * @access public
 */
	function isHam(&$Model, $data = array()) {
		extract ($this->settings[$Model->alias]);
		$score = $this->score($Model, $data);
		return $score < $scoreSuspect?1:0;
	}

/**
 * isSpam method
 *
 * @param mixed $Model
 * @param array $data
 * @return bool true for spam, false for ham (or suspect)
 * @access public
 */
	function isSpam(&$Model, $data = array()) {
		extract ($this->settings[$Model->alias]);
		$score = $this->score($Model, $data);
		return $score >= $scoreSpam?1:0;
	}

/**
 * isSuspect method
 *
 * @param mixed $Model
 * @param array $data array()
 * @return bool true for suspect or spam, false for ham
 * @access public
 */
	function isSuspect(&$Model, $data = array()) {
		extract ($this->settings[$Model->alias]);
		$score = $this->score($Model, $data);
		return $score >= $scoreSuspect?1:0;
	}

/**
 * rulesByType method
 *
 * Called internally, public for testing. For the passed type of field, return the rules to test for
 *
 * @param mixed $Model
 * @param mixed $type
 * @param mixed $field
 * @return array rules for the passed field/type
 * @access public
 */
	function rulesByType(&$Model, $type, $field) {
		extract ($this->settings[$Model->alias]);
		if (!$type) {
			$type = $fields[$field];
		}
		return $rules[$type];
	}

/**
 * score method
 *
 * @param mixed $Model
 * @param string $field
 * @param string $string
 * @param mixed $type
 * @return int score for the passed field/data
 * @access public
 */
	function score(&$Model, $field = '', $string = '', $type = null, $debug = false) {
		extract ($this->settings[$Model->alias]);
		$Model->recursive = -1;
		if (!is_string($field)) {
			$data = false;
			if (is_array($field) && $field) {
				$data = $field;
				$debug = $string;
			} elseif ($Model->data) {
				$data = $Model->data;
				$debug = $field;
			}
			if (!$data) {
				return false;
			}
			if (!empty($data[$Model->alias])) {
				$data = $data[$Model->alias];
			}
			foreach($fields as $field => $type) {
				if (empty($data[$field])) {
					continue;
				}
				$this->_testStrings[$field] = array(low($data[$field]), $type);
			}
			$score = 0;
			foreach ($this->_testStrings as $key => $params) {
				$score += $this->score($Model, $key, $params[0], $params[1], $debug);
			}
			$this->_testStrings = array();
			$data['junk_score'] = $score;
			$log = $this->suspectLog($Model, 'all');
			$matches = array();
			foreach ($log as $field => $stuff) {
				foreach ($stuff['matchingRules'] as $rule => $s) {
					$matches[] = $rule . ':' . $s;
				}
			}
			$matches = implode($matches, ';');
			$data['junk_rule_matches'] = $matches;
			$data['spam'] = $score >= $scoreSpam?1:0;
			$Model->data[$Model->alias] = $data;
			return $score;
		}
		$rules = $this->rulesByType($Model, $type, $field);
		$matchingRules = array();
		$score = 0;
		foreach ($rules as $rule => $params) {
			if (is_string($params)) {
				$rule = $params;
				$params = array(1);
			}
			$rule = 'check' . Inflector::camelize($rule);
			$params = Set::merge(array($field, $string), $params);
			$_score = call_user_func_array(array($Model, $rule), $params);
			if ($_score || $debug) {
				$matchingRules[$rule . Inflector::camelize($field)] = $_score;
				$score += $_score;
			}
		}
		$this->_log[$Model->alias][$field] = compact('body', 'name', 'score', 'matchingRules');
		return $score;
	}

/**
 * suspectFind method
 *
 * To allow for the possibility to search for duplicates on a different model from the model being tested (contact
 * model tests in the emails table).
 * Override in the model if required.
 *
 * @param mixed $Model
 * @param mixed $type
 * @param mixed $params
 * @return mixed
 * @access public
 */
	function suspectFind($Model, $type, $params) {
		return $Model->find($type, $params);
	}

/**
 * suspectLog method
 *
 * @param mixed $Model
 * @param string $which
 * @return array the log messages for the requested test
 * @access public
 */
	function suspectLog(&$Model, $which = 'last') {
		if ($which == 'last') {
			return array_pop($this->_log[$Model->alias]);
		}
		return $this->_log[$Model->alias];
	}

/**
 * allCaps method
 *
 * Test if the passed string is all caps. Don't shout, we're not deaf.
 *
 * @TODO Consolidate logic with suspectPatterns?
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $string
 * @param int $weight
 * @param int $counterWeight
 * @return int score for this rule
 * @access protected
 */
	function checkAllCaps(&$Model, $field = null, $string = null, $weight = 2, $counterWeight = 0) {
		if (up($string) == $string) {
			return $weight;
		}
		return $counterWeight;
	}

/**
 * existingSame method
 *
 * Has it been used before? Checks if an entry exists with the same content in the field to be tested.
 * Same string field should be given a low weight, same text area contents should receive a higher score.
 *
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $string
 * @param int $weight
 * @param mixed $counterWeight
 * @return int score for this rule
 * @access protected
 */
	function checkExistingSame(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = -1) {
		extract ($this->settings[$Model->alias]);
		if (!$string) {
			return;
		}
		$conditions = array($field => $string);
		if ($Model->id) {
			$conditions['NOT'][$Model->primaryKey] = $Model->id;
		}
		$count = $Model->suspectFind('count', compact('conditions'));
		if ($count) {
			return $weight * $count;
		}
		return $counterWeight;
	}

/**
 * kses method
 *
 * Setup the kses instance
 *
 * @param mixed $Model
 * @return bool whether it was possible to load the kses5 vendor
 * @access protected
 */
	function checkKses(&$Model) {
		if (!isset($this->_kses)) {
			extract ($this->settings[$Model->alias]);
			if (!App::import('Vendor', 'kses5')) {
				return false;
			}
			$this->_kses = new kses5();
			foreach($allowedProtocols as $protocol) {
				$this->_kses->AddProtocol($protocol);
			}
			foreach($allowedTags as $tag => $allowedAttributes) {
				if (is_string($allowedAttributes)) {
					$tag = $allowedAttributes;
					$allowedAttributes = array();
				}
				$this->_kses->AddHTML($tag, $allowedAttributes);
			}
		}
		return isset($this->_kses);
	}

/**
 * ksesCleans method
 *
 * Does it contain html, and if so does the html helper/kses clean it?
 *
 * @param mixed $Model
 * @param int $weight
 * @param int $counterWeight
 * @return int score for this rule
 * @access public
 */
	function checkDirtyHtml(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = 0) {
		extract ($this->settings[$Model->alias]);
		if (strpos($string, '<') !== false && $this->clean($Model, $string) !== $string) {
			return $weight;
		}
		return $counterWeight;
	}

/**
 * linkFrequency method
 *
 * Count the links that are present. simply checks for the presence of http:// and www. Does not matter if
 * the link is in plain text or in an <a> tag.
 * If the field is a text field that should not contain links - modify the parameters to give a high
 * score for any links found
 *
 * @param mixed $Model
 * @param int $weight
 * @param mixed $counterWeight
 * @param int $max
 * @return int score for this rule
 * @access public
 */
	function checkLinkFrequency(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = -1, $max = 2) {
		$count = substr_count($string, 'http://');
		$count += substr_count($string, 'www.');
		if ($count <= $max) {
			return ($max - $count) * $counterWeight;
		}
		return $count * $weight;
	}

/**
 * nonsenseText method
 *
 * Check for 'words' with no vowels, or other random nonsense strings
 *
 * @TODO Consolidate logic with suspectPatterns?
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $string
 * @param int $weight
 * @param int $counterWeight
 * @return int score for this rule
 * @access public
 */
	function checkNonsenseText(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = 0) {
		extract ($this->settings[$Model->alias]);
		preg_match_all('/[bcdfghjklmnpqrstvwxz]{5}/', $string, $matches);
		$count = count($matches[0]);
		preg_match_all('/asdf/', $string, $matches);
		$count += count($matches[0]);
		if ($count) {
			return $weight * $count;
		}
		return $counterWeight;
	}

/**
 * shortContent method
 *
 * Is it too short?
 *
 * @TODO Consolidate logic with suspectPatterns?
 * @param mixed $Model
 * @param int $weight
 * @param int $counterWeight
 * @param int $length
 * @return int score for this rule
 * @access public
 */
	function checkShortContent(&$Model, $field = null, $string = null, $weight = 0.1, $counterWeight = 0, $length = 20) {
		$shortyness = $length - strlen($string);
		if ($shortyness > 0) {
			return $weight * $shortyness;
		}
		return $counterWeight;
	}

/**
 * spamWords method
 *
 * Check for the presence of specific spam words
 *
 * @TODO Consolidate logic with suspectPatterns?
 * @param mixed $Model
 * @param int $weight
 * @param int $counterWeight
 * @param int $min
 * @return int score for this rule
 * @access public
 */
	function checkSpamWords(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = 0, $min = 1) {
		extract ($this->settings[$Model->alias]);
		$count = 0;
		foreach ($spamWords as $word) {
			if (strpos($string, $word) !== false) {
				$count++;
			}
		}
		if ($count >= $min) {
			return $count * $weight;
		}
		return $counterWeight;
	}

/**
 * suspectPatterns method
 *
 * Check for the presence of suspicious patterns
 *
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $string
 * @param int $weight
 * @param mixed $counterWeight
 * @param int $min
 * @return int score for this rule
 * @access public
 */
	function checkSuspectPatterns(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = -1, $min = 1) {
		extract ($this->settings[$Model->alias]);
		$count = 0;
		foreach ($suspectPatterns as $pattern => $patternFactor) {
			if (is_numeric($pattern)) {
				$pattern = $patternFactor;
				$patternFactor = 1;
			}
			if ($pattern[0] != '/') {
				$pattern = '/' . $pattern . '/';
			}
			if (preg_match($pattern, $string)) {
				$count += $patternFactor;
			}
		}
		if ($count >= $min) {
			return $count * $weight;
		}
		return $counterWeight;
	}

/**
 * suspectUrls method
 *
 * Scan the text for http:// and www. . for each found link run a test to find out if they match
 * any of the suspicous patterns. If the same link is included twice - that in itself will increase
 * the score.
 *
 * @param mixed $Model
 * @param mixed $field
 * @param mixed $string
 * @param int $weight
 * @param mixed $counterWeight
 * @param int $min
 * @return int score for this rule
 * @access public
 */
	function checkSuspectUrls(&$Model, $field = null, $string = null, $weight = 1, $counterWeight = -1, $min = 1) {
		extract ($this->settings[$Model->alias]);
		$count = 0;
		$links = array();
		if ($fields[$field] == 'link') {
			$links[] = $string;
		} else {
			preg_match_all('@http://([^\'"\s]+)@i',  $string, $results, PREG_PATTERN_ORDER);
			foreach ($results[1] as $link) {
				if (isset($links[$link])) {
					$count++;
				}
				$links[$link] = $link;
			}
			preg_match_all('@www.([^\'"\s]+)@i',  $string, $results, PREG_PATTERN_ORDER);
			foreach ($results[1] as $link) {
				if (isset($links['www.' . $link])) {
					$count++;
				}
				$links['www.' . $link] = 'www.' . $link;
			}
		}
		foreach ($suspectUrls as $pattern => $patternFactor) {
			if (is_numeric($pattern)) {
				$pattern = $patternFactor;
				$patternFactor = 1;
			}
			if ($pattern[0] != '/') {
				$pattern = '/' . preg_quote($pattern) . '/';
			}
			foreach ($links as $string) {
				if (preg_match($pattern, $string)) {
					$count += $patternFactor;
				}
			}
		}
		if ($count >= $min) {
			return $count * $weight;
		}
		return $counterWeight;
	}
}
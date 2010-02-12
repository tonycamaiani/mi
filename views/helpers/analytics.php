<?php
/**
 * A simple helper to automatically inject google analytics code into any
 * (full) html page that includes the helper.
 *
 * PHP versions 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.views.helpers
 * @since         v 1.0 (11-Feb-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * AnalyticsHelper class
 *
 * @uses          AppHelper
 * @package       mi
 * @subpackage    mi.views.helpers
 */
class AnalyticsHelper extends AppHelper {

/**
 * name property
 *
 * @var string 'Analytics'
 * @access public
 */
	public $name = 'Analytics';

/**
 * runtime settings
 *
 * @var array
 * @access public
 */
	public $settings = array();

/**
 * defaultSettings property
 * 	productionCheck - automatically check we're in production?
 * 	element - the name of an element to render instead of the inbuilt template
 * 	template - the template to use - see _template()
 * 	code - the code to use - if isn't set, will look in MiCache::setting('Site.analyticsCode')
 * 	domain - the domain to use - if isn't set, will look in MiCache::setting('Site.analyticsDomain')
 *
 * @var array
 * @access protected
 */
	protected $_defaultSettings = array(
		'productionCheck' => true,
		'element' => false,
		'template' => 'traditional',
		'code' => null,
		'domain' => null
	);

/**
 * A string template for the code to inject
 *
 * @see _template()
 * @var string ''
 * @access protected
 */
	protected $_template = '';

/**
 * Merge settings
 *
 * @param array $options array()
 * @return void
 * @access public
 */
	public function __construct($options = array()) {
		$this->settings = array_merge($this->_defaultSettings, $options);
		parent::__construct($options);
	}

/**
 * if productionCheck is true - check if we're currently on the production site.
 * 	if it is, and we're not, don't do anything
 * Initialize the view object reference, generate the code block and inject it
 *
 * @return void
 * @access public
 */
	public function afterLayout() {
		if ($this->settings['productionCheck']) {
			if (!function_exists('isProduction')) {
				trigger_error('AnalyticsHelper afterLayout: This helper is configured to check production mode, but the function isProduction() doens\'t exist');
				return;
			}
			if (!isProduction()) {
				return;
			}
		}
		$this->View =& ClassRegistry::getObject('View');
		$code = $this->_codeBlock($this->settings['code'], $this->settings['domain']);
		if ($code) {
			$this->View->output = preg_replace('@</body>@', $code . '</body>', $this->View->output);
		}
	}

/**
 * If code and domain are not passed explicitly - use the MiCache setting class
 * to determine which code and domain to use
 *
 * @param mixed $code null
 * @param mixed $domain null
 * @return string the code to inject
 * @access protected
 */
	protected function _codeBlock($code = null, $domain = null) {
		if ($this->settings['element']) {
			$element = $this->settings['element'] === true?'analytics':$this->settings['element'];
			return $this->View->element($element, compact('code', 'domain'));
		}
		if (is_null($code)) {
			$code = MiCache::setting('Site.analyticsCode');
			if (!$code) {
				return;
			}
		}
		if (is_null($domain)) {
			$domain = MiCache::setting('Site.analyticsDomain');
		}

		$this->_template();

		$return = String::insert($this->_template, compact('code', 'domain'));
		if ($domain) {
			$return = preg_replace('#(<<<domainStart|domainEnd>>>)\s*#s', '', $return);
		} else {
			$return = preg_replace('#<<<domainStart.*domainEnd>>>\s*#s', '', $return);
		}
		return trim($return);
	}

/**
 * populate the template variable with the appropriate contents
 *
 * @return void
 * @access protected
 */
	protected function _template() {
		switch ($this->settings['template']) {
			default;
				$this->_template = <<<CODE
<script type="text/javascript">
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
	try {
		var pageTracker = _gat._getTracker(":code");
		<<<domainStart
		pageTracker._setDomainName(":domain");
		domainEnd>>>
		pageTracker._trackPageview();
	} catch(err) {}
</script>
CODE;
				return;
		}
	}
}
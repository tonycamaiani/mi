<?php
/**
 * Short description for mi_html.php
 *
 * Long description for mi_html.php
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
App::import('Helper', 'Html');

/**
 * MiHtmlHelper class
 *
 * @uses          HtmlHelper
 * @package       mi
 * @subpackage    mi.views.helpers
 */
class MiHtmlHelper extends HtmlHelper {

/**
 * name property
 *
 * @var string 'MiHtml'
 * @access public
 */
	public $name = 'MiHtml';

	public $settings = array(
		'warnings' => true
	);

/**
 * construct method
 *
 * @param array $settings array()
 * @return void
 * @access private
 */
	public function __construct($settings = array()) {
		$this->settings = array_merge($this->settings, $settings);
		parent::__construct($settings);
	}

/**
 * Creates a formatted IMG element.
 * Allow calling directly with media data
 *
 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
 * @param array	$options Array of HTML attributes.
 * @return string
 */
	public function image($path, $options = array()) {
		if (is_array($path)) {
			$data = $path;
			$path = $this->imageUrl($data, $options);
			if (is_string($options)) {
				$args = func_get_args();
				if (!empty($args[2])) {
					$options = $args[2];
				} else {
					$options = array();
				}
			} else {
				unset ($options['size']);
			}
			$options = $this->imageAttributes($data, $options);
		}
		return parent::image($path, $options);
	}

/**
 * imageAttributes method
 *
 * @param mixed $data
 * @param array $options array()
 * @return array
 * @access public
 */
	public function imageAttributes($data, $options = array()) {
		if (!$data) {
			return $options;
		}
		if (isset($data['Media'])) {
			$data = $data['Media'];
		}
		if (!isset($options['alt']) && isset($data['description'])) {
			$options['alt'] = $data['description'];
		}
		if (!isset($options['title']) && isset($data['description'])) {
			$options['title'] = $data['description'];
		}
		return $options;
	}

/**
 * imageLink method
 *
 * @param array $data array() the media's data
 * @param string $size a valid media size
 * @param mixed $url an array or string url, false (no link) or a valid media size
 * @param array $params array()
 * @return string an image link, or just an image if there is no link
 * @access public
 */
	public function imageLink($data = array(), $size = null, $url = null, $params = array()) {
		if ($url === false) {
			return $this->image($data, $size, $params);
		}
		if (isset($data['Media'])) {
			$data = $data['Media'];
		}
		if (!$url) {
			$url = $this->imageUrl($data);
		} elseif (is_string($url) && strpos('/', $url) === false) {
			$url = $this->imageUrl($data, $url);
		}
		$image = $this->image($data, $size, $params);
		$params = $this->imageAttributes($data, $params);
		return $this->link($image, $url, $params, null, false);
	}

/**
 * imageUrl method
 *
 * Get the url for serving up a (cached) image. For size options see config/media.php
 *
 * @param array $data Media data
 * @param string $size null
 * @return string
 * @access public
 */
	public function imageUrl($data = array(), $size = null) {
		if (isset($data['Media'])) {
			$data = $data['Media'];
		}
		if (!isset($data['filename']) || !isset($data['mimetype']) || !isset($data['id'])) {
			return false;
		}
		if ($data['mimetype'] === 'application/pdf' || strpos('.pdf', $data['filename'])) {
			$data['filename'] = str_replace('.pdf', '.png', $data['filename']);
		}
		return $this->mediaUrl($data, $size);
	}

/**
 * link method
 *
 * For any link to an action that only works by post - add the class confirm.
 * In addition, add the url itself as a token such that only if the user is able to access the link
 * will the have the possiblity to arrive at a confirmation form.
 *
 * @see SwissArmyComponent blackHole
 * @param mixed $title
 * @param mixed $url
 * @param array $htmlAttributes
 * @param bool $confirmMessage
 * @param bool $escapeTitle
 * @return void
 * @access public
 */
	public function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true) {
		if (!$escapeTitle) {
			if ($this->settings['warnings']) {
				trigger_error('escapeTitle has been removed - use htmlAttributes[\'escape\'] instead');
			}
			$htmlAttributes['escape'] = false;
		}
		if (Configure::read() && $escapeTitle && strpos($title, '<span ') === 0) {
			$escapeTitle = false;
		}
		if (!isset($this->__view)) {
			$this->__view =& ClassRegistry::getObject('view');
		}
		if (isset($this->__view->viewVars['postActions']) && is_array($url)) {
			$controller = $this->__view->name;
			if (isset($url['controller'])) {
				$controller = $url['controller'];
			}
			$controller = Inflector::underscore($controller);
			if (isset($this->__view->viewVars['postActions'][$controller])) {
				$postActions = $this->__view->viewVars['postActions'][$controller];
				if (isset($url['admin']) || isset($this->__view->params['admin'])) {
					$prefix = 'admin_';
				} else {
					$prefix = '';
				}
				if (isset($url['action'])) {
					$action = $url['action'];
				} else {
					$action = $this->__view->action;
				}
				$action = $prefix . $action;
				if (in_array($action, $postActions)) {
					if (isset($htmlAttributes['class'])) {
						$htmlAttributes['class'] .= ' confirm';
					} else {
						$htmlAttributes['class'] = 'confirm';
					}
					$url = $this->url($url);
					if ($this->webroot !== '/') {
						$url = preg_replace("@^{$this->webroot}@", '/', $url);
					}
					$url = $url . '?token=' . Security::hash($url, null, true);
				}
			}
		}
		return parent::link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle);
	}

/**
 * mediaUrl method
 *
 * @param mixed $data
 * @param mixed $size null
 * @return void
 * @access public
 */
	public function mediaUrl($data, $size = null) {
		if (is_array($size)) {
			if (isset($size['size'])) {
				$size = $size['size'];
			} else {
				$size = null;
			}
		}
		if ($size) {
			$data['filename'] = preg_replace('@(,[^\.]*)?\.@', ',' . $size . '.', $data['filename']);
		}
		$url = array(
			'admin' => false, 'prefix' => null, 'plugin' => null,
			'controller' => 'media', 'action' => 'serve',
			'id' => $data['id'], 'filename' => $data['filename']
		);
		return str_replace($this->webroot, '/', $this->url($url));
	}

/**
 * meta method
 *
 * Overriden only to add canonical link option
 *
 * @param mixed $type
 * @param mixed $url null
 * @param array $attributes array()
 * @param bool $inline true
 * @return void
 * @access public
 */
	public function meta($type, $url = null, $attributes = array(), $inline = true) {
		if ($type === 'canonical') {
			return '<link rel="canonical" href="' . $this->url($url, true) . '"/>';
		}
		return parent::meta($type, $url, $attributes, $inline);
	}

/**
 * shortHash method
 *
 * For a hash used for display (uuids, git commit references) return the short version
 *
 * @param string $hash ''
 * @return void
 * @access public
 */
	public function shortHash($hash = '') {
		return preg_replace('/^(.{4}).*(.{4})$/', '\1...\2', $hash);
	}
}
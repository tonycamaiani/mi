<?php
/* SVN FILE: $Id: mi_session.php 1766 2009-11-02 18:07:55Z AD7six $ */

/**
 * Short description for mi_session.php
 *
 * Long description for mi_session.php
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
 * @subpackage    base.controllers.components
 * @since         v 1.0
 * @version       $Revision: 1766 $
 * @modifiedby    $LastChangedBy: AD7six $
 * @lastmodified  $Date: 2009-11-02 19:07:55 +0100 (Mon, 02 Nov 2009) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * MiSessionComponent class
 *
 * @uses          Object
 * @package       base
 * @subpackage    base.controllers.components
 */
class MiSessionComponent extends Overloadable {

/**
 * name property
 *
 * Cheating
 *
 * @var string 'Session'
 * @access public
 */
	var $name = 'Session';

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Session');

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array();

/**
 * initialize method
 *
 * Replace pointer for Session component to this component
 *
 * @param mixed $Controller
 * @param array $config
 * @access public
 * @return void
 */
	function initialize(&$Controller, $config = array()) {
		$this->settings = array_merge($this->settings, $config);
		$this->Controller =& $Controller;
		$Controller->Session =& $this;
	}

/**
 * beforeRedirect method
 *
 * Write to the session that we are redirecting.
 *
 * @param mixed $Controller
 * @param mixed $url
 * @param mixed $status
 * @param mixed $exit
 * @return void
 * @access public
 */
	function beforeRedirect(&$Controller, $url, $status, $exit) {
		$count = (int)$this->Session->read('MiSession.redirecting');
		$this->log($count);
		if ($count > 10) {
			$this->Session->delete('MiSession.redirecting');
			if (Configure::read()) {
				$this->Session->setFlash('A redirect loop was detected');
			}
			return '/';
		}
		$this->Session->write('MiSession.redirecting',  $count + 1);
		return $url;
	}

/**
 * beforeRender method
 *
 * Delete the session redirect marker, so that flash messages are re-enabled
 *
 * @return void
 * @access public
 */
	function beforeRender() {
		$this->Session->delete('MiSession.redirecting');
	}

/**
 * setFlash method
 *
 * Allow multiple flash messages, by default using the message as a key to avoid duplicates
 *
 * Check for the MiSession.redirecting var to know if we are in the middle of a chain of redirects
 * and supress any generated flash messages from the intermediary pages. For example
 * /posts/index/page:2 (rendered)
 * /posts/view/1 (rendered)
 * /posts/delete/1 (user clicked, redirects)
 * /posts/view/1 (redirect)*
 * /posts/index/page:2 (rendered)
 *
 * The posts view call will (usually) generate a message "Post 2 could not be displayed" - if the session
 * variable is set this message is not displayed, the user only sees "Post 2 deleted"
 *
 * @param mixed $message
 * @param string $element 'default'
 * @param array $params array()
 * @param mixed $key null
 * @param bool $force false
 * @return void
 * @access public
 */
	function setFlash($message, $element = 'default', $params = array(), $key = null, $force = false) {
		if (!$force && $this->Session->read('MiSession.redirecting')) {
			if (Configure::read()) {
				AppController::log('MiSession flash message supressed: ' . $message, 'redirect');
			}
			return;
		}
		if ($key == null) {
			$key = md5($message);
			if (is_numeric($key[0])) {
				$key[0] = 'x';
			}
		}
		$this->Session->setFlash($message, $element, $params, $key);
	}

/**
 * call__ method
 *
 * Pass any undefined method calls directly to the real Session component
 *
 * @param mixed $method
 * @param mixed $params
 * @access public
 * @return void
 */
	function call__($method, $params) {
		return call_user_func_array(array(&$this->Session, $method), $params);
	}
}
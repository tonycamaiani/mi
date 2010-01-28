<?php


/**
 * Short description for clear.php
 *
 * Long description for clear.php
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
 * @package       base
 * @subpackage    base.vendors.shells
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('Folder','File');

/**
 * ClearShell class
 *
 * @uses          Shell
 * @package       base
 * @subpackage    base.vendors.shells
 */
class ClearShell extends Shell {

/**
 * excludePattern property
 *
 * Files and or folders (it matches against the full path) to ignore
 *
 * @var string '/^iffullpathmatchesthisdon'tdelete$/i'
 * @access public
 */
	var $excludePattern = '/^.*\.svn.*$|^.*\.git.*$|.*empty$/i';

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	function initialize() {
		$this->settings = array(
			'CACHE' => array(CACHE, true),
			'css' => array(CSS, false),
			'files' => array(WWW_ROOT . DS . 'files' . DS . 'c', '.*', true),
			'img' => array(IMAGES . 'c', '.*', true),
			'LOGS' => array(LOGS, true),
			'js' => array(JS, false),
			'TMP' => array(TMP, true),
		);
		return true;
	}

/**
 * help method
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('cake clear - A shell for deleting temporary or cached files');
		$this->out('Usage examples:');
		$this->hr();
		$this->out('cake clear TMP');
		$this->out('	- delete all tmp files');
		$this->out('cake clear CACHE');
		$this->out('	- delete all cache files (included in TMP)');
		$this->out('cake clear LOGS');
		$this->out('	- delete all log files (included in TMP)');
		$this->out('cake clear css');
		$this->out('	- delete all webroot cached css files');
		$this->out('cake clear files');
		$this->out('	- delete all webroot cached generic files');
		$this->out('cake clear img');
		$this->out('	- delete all webroot cached img files');
		$this->out('cake clear js');
		$this->out('	- delete all webroot cached js files');
		$this->out('cake clear "*"');
		$this->out('	- run all options');
		$this->out('cake clear css img ...');
		$this->out('	- run specified options in sequence');
		$this->hr();
	}

/**
 * main method
 *
 * @return void
 * @access public
 */
	function main() {
		if (!$this->args) {
			return $this->help();
		}
		foreach ($this->args as $method) {
			if ($method === '*') {
				foreach($this->settings as $method => $settings) {
					$this->out('looking at ' . $method);
					list($where, $recursive) = $settings;
					if ($recursive) {
						$this->_clearRecursive($where);
						continue;
					}
					$this->_clear($where);
				}
				return;
			}

			if (!isset($this->settings[$method])) {
				return $this->help();
			}
			list($where, $recursive) = $this->settings[$method];
			$this->out('Looking at ' . $method);
			if ($recursive) {
				$this->_clearRecursive($where);
			}
			$this->_clear($where);
		}
	}

/**
 * clear method
 *
 * Delete all files direclty in the folder. Intended to clear MiCompressor files
 *
 * @param mixed $folder null
 * @param string $pattern 'mi-.*\.(css|js)'
 * @return void
 * @access protected
 */
	function _clear($folder = null, $pattern = '.*\.(css|js)') {
		$pattern = Configure::read('MiCompressor.prefix') . $pattern;
		if (!$folder) {
			return;
		}
		$Folder = new Folder($folder);
		$files = $Folder->find($pattern);
		foreach ($files as $id => $file) {
			if (!preg_match($this->excludePattern, $file)) {
				$File = new File($folder . DS . $file);
				if ($File->delete()) {
					$this->out("\tSuccess Deleting: $file");
				} else {
					$this->out("\tError Deleting: $file");
				}
			}
		}
	}

/**
 * clearRecursive method
 *
 * Delete all files (except the few matching the exclude pattern) in or below the passed path
 *
 * @param mixed $folder TMP
 * @return void
 * @access protected
 */
	function _clearRecursive($folder = TMP) {
		$Folder = new Folder($folder);
		$files = $Folder->findRecursive();
		foreach ($files as $id => $file) {
			if (!preg_match($this->excludePattern, $file)) {
				$File = new File($file);
				$file = Debugger::trimPath($file);

				if ($File->delete()) {
					$this->out("\tSuccess Deleting: $file");
				} else {
					$this->out("\tError Deleting  : $file");
				}
			}
		}
	}
}
?>
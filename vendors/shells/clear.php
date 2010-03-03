<?php
/**
 * A shell and static vendor class for deleting files
 *
 * PHP versions 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.vendors.shells
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * ClearShell class
 *
 * @uses          Shell
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class ClearShell extends Shell {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public $settings = array(
	);

/**
 * excludePattern property
 *
 * Files and or folders (it matches against the full path) to ignore
 *
 * @var string '/^iffullpathmatchesthisdon'tdelete$/i'
 * @access public
 */
	public $excludePattern = '/^.*\.svn.*$|^.*\.git.*$|.*empty$/i';

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	public function initialize() {
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
	public function help() {
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
	public function main() {
		if (!$this->args) {
			return $this->help();
		}
		foreach ($this->args as $method) {
			if ($method === '*') {
				foreach($this->settings as $method => $settings) {
					$this->out('Looking at ' . $method);
					list($where, $recursive) = $settings;
					if ($recursive) {
						Clear::recursive($where);
						continue;
					}
					Clear::direct($where);
				}
				return;
			}

			if (!isset($this->settings[$method])) {
				return $this->help();
			}
			list($where, $recursive) = $this->settings[$method];
			$this->out('Looking at ' . $method);
			if ($recursive) {
				Clear::recursive($where);
				continue;
			}
			Clear::direct($where);
		}
		$this->out(Clear::messages());
	}
}

/**
 * Clear class
 *
 * @uses
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class Clear {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public static $settings = array(
		'useExec' => true,
		'excludePattern' => '/^.*\.svn.*$|^.*\.git.*$|.*empty$/i'
	);

/**
 * message stack
 *
 * @var array
 * @access protected
 */
	protected static $_messages = array();

/**
 * Return the list of messages
 *
 * @return void
 * @access public
 */
	public static function messages() {
		$return = Clear::$_messages;
		Clear::$_messages = array();
		return $return;
	}

/**
 * Delete all files (no pattern except not .svn and not named empty) under a folder
 *
 * @param mixed $path
 * @return void
 * @access public
 */
	public static function recursive($path) {
		if (!$path) {
			return false;
		}
		if (!is_dir($path)) {
			Clear::$_messages[] = 'Path doesn\'t exist: ' . $path;
			return false;
		}
		if (!is_writable($path)) {
			Clear::$_messages[] = 'Path isn\'t writable: ' . $path;
			return false;
		}

		if (Clear::$settings['useExec']) {
			$cmd = "find $path -type f ! -iwholename \"*.svn*\" ! -name \"empty\" -exec rm -f {} \;";
			if (Clear::exec($cmd)) {
				Clear::$_messages[] = 'Successfully deleted all files in and under ' . $path;
				return true;
			}
		}

		uses('Folder','File');
		$Folder = new Folder($path);
		$files = $Folder->findRecursive();
		return Clear::deleteFiles($files);
	}

/**
 * Delete files in a folder matching a pattern
 *
 * @param mixed $path null
 * @param string $pattern '.*\.(css|js)'
 * @return void
 * @access public
 */
	public static function direct($path = null, $pattern = '.*\.(css|js)') {
		if (!$path) {
			return false;
		}
		if (!is_dir($path)) {
			Clear::$_messages[] = 'Path doesn\'t exist: ' . $path;
			return false;
		}
		if (!is_writable($path)) {
			Clear::$_messages[] = 'Path isn\'t writable: ' . $path;
			return false;
		}

		if (Clear::$settings['useExec']) {
			preg_match('#(.*)\((.*)\)#', $pattern, $matches);
			list($_, $prefix, $extensions) = $matches;
			$prefix = str_replace(array('.*', '\\'), array('*', ''), $prefix);
			$parterns = array();
			foreach(explode('|', $extensions) as $ext) {
				$parterns[] = $prefix . $ext;
			}
			$return = null;
			foreach($parterns as $partern) {
				$cmd = "ls -f1 $path{$partern} | xargs rm";
				if (!Clear::exec($cmd)) {
					Clear::$_messages[] = 'Successfully deleted all files in ' . $path;
					$return = false;
					continue;
				}
				Clear::$_messages[] = 'Command succeeded ' . $cmd;
				if ($return === null) {
					$return = true;
				}
			}
			if ($return) {
				return true;
			}
		}

		uses('Folder','File');
		$Folder = new Folder($path);
		$files = $Folder->find($pattern);
		return Clear::deleteFiles($files);
	}

/**
 * Delete files one by one in a loop (Windows)
 *
 * @param mixed $files
 * @return bool
 * @access protected
 */
	protected static function deleteFiles($files) {
		$return = null;
		foreach ($files as $id => $file) {
			if (!preg_match(Clear::$settings['excludePattern'], $file)) {
				$File = new File($path . DS . $file);
				if ($File->delete()) {
					Clear::$_messages[] = 'Success Deleting: ' . $file;
				} else {
					Clear::$_messages[] = 'Error Deleting  : ' . $file;
					$return = false;
				}
			}
		}
		if ($return !== false) {
			return true;
		}
		return false;
	}

/**
 * exec method
 *
 * @param mixed $cmd
 * @return bool
 * @access protected
 */
	protected static function exec($cmd) {
		exec($cmd, $_, $returnVar);
		if (!$returnVar) {
			return true;
		}
		Clear::$_messages[] = "Command failed: $cmd with return code $returnVar";
		if ($_) {
			Clear::$_messages += $_;
		}
		return false;
	}
}
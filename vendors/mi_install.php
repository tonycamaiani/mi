<?php
/**
 * Short description for mi_install.php
 *
 * Reference: http://www.debian.org/doc/debian-policy/
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi_plugin
 * @subpackage    mi_plugin.vendors
 * @since         v 1.0 (20-Aug-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * MiInstall class
 *
 * @uses          Object
 * @package       mi_plugin
 * @subpackage    mi_plugin.vendors
 */
class MiInstall extends Object {

/**
 * version property
 *
 * @var string '0.1'
 * @access public
 */
	public static $version = '0.1-alpha';

/**
 * settings property
 *
 * @var mixed null
 * @access public
 */
	public static $settings = null;

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	protected static $_defaultSettings = array(
		'quiet' => false,
		'git' => array(
			'upgrade' => 'git pull',
			),
		'git-svn' => array(
			'upgrade' => 'git svn fetch && git svn rebase',
			),
		'svn' => array(
			'upgrade' => 'svn cleanup && svn up',
			#'upgrade' => 'svn cleanup && svn up --user :username --password :password',
			'username' => '""',
			'password' => '""',
		),
	);

/**
 * return property
 *
 * @var array
 * @access protected
 */
	protected static $_return = array();

/**
 * object property
 *
 * @var mixed null
 * @access private
 */
	private static $__Object = null;

/**
 * plugins method
 *
 * @return void
 * @access public
 */
	public function plugins($reset = false) {
		static $return = array();
		if ($return && !$reset) {
			return $return;
		}
		return $return = MiInstall::_subfolders('plugins');
	}

/**
 * vendors method
 *
 * @param bool $reset false
 * @return void
 * @access public
 */
	public function vendors($reset = false) {
		static $return = array();
		if ($return && !$reset) {
			return $return;
		}
		return $return = MiInstall::_subfolders('vendors', array('shells', 'css', 'js'));
	}

/**
 * autoRemove method
 *
 * @param string $what 'plugin'
 * @param mixed $name null
 * @return void
 * @access public
 */
	public function autoRemove($what = 'plugin', $name = null) {
		trigger_error('MiInstall Vendor: autoRemove not yet implemented');
	}

/**
 * checks and installs dependencies
 *
 * @return void
 * @access public
 */
	public function check($checks = array(), $plugins = null, $vendors = null) {
		if (!$checks) {
			MiInstall::_settings();
			$checks['app'][APP_DIR] = APP;
			$checks['plugins'] = $plugins = MiInstall::plugins();
			$vendorDirs = App::path('vendors');
			$checks['vendors'] = $vendors = MiInstall::vendors();
			;
		} else {
			$checks = array('secondary' => (array)$checks);
			$plugins = MiInstall::plugins();
			$vendors = MiInstall::vendors();
		}
		foreach($checks as $type => $whats) {
			foreach($whats as $what => $dir) {
				if (is_numeric($what)) {
					$what = $dir;
					$dir = null;
				}
				if (array_key_exists($what, MiInstall::$_return)) {
					continue;
				}
				if ($what !== APP_DIR) {
					if (in_array($what, $plugins)) {
						MiInstall::$_return[$what] = false;
					} elseif (in_array($what, $vendors)) {
						MiInstall::$_return[$what] = false;
					} else {
						MiInstall::$_return[$what] = 'missing';
					}
				}
				$details = MiInstall::details($what);
				if (!$details || empty($details['Depends'])) {
					continue;
				}
				MiInstall::check(array_map('trim', explode(',', $details['Depends'])), $plugins, $vendors);
			}
		}
		$missing = 0;
		$return = array();
		foreach(MiInstall::$_return as $val) {
			if ($val == 'missing') {
				$missing++;
			}
		}
		if (!$missing) {
			return true;
		}
		return MiInstall::$_return;
	}

/**
 * details method
 *
 * @param mixed $params
 * @return void
 * @access public
 */
	public function details($params = array()) {
		if (!file_exists(CAKE . 'config' . DS . 'packages.php')) {
			MiInstall::update();
		}
		$config = array();
		include(CAKE . 'config' . DS . 'packages.php');
		if (is_string($params)) {
			if (isset($config[$params])) {
				return $config[$params];
			}
			$plugins = MiInstall::plugins();
			$vendors = MiInstall::vendors();
			if (isset($plugins[$params])) {
				if (!file_exists($plugins[$params] . DS . 'config' . DS . 'control.php')) {
					return false;
				}
				include($plugins[$params] . DS . 'config' . DS . 'control.php');
			} elseif (isset($vendors[$params])) {
				if (!file_exists($vendors[$params] . 'control.php')) {
					return false;
				}
				include($vendors[$params] . 'control.php');
			} else {
				return false;
			}
			if (isset($config[$params])) {
				return $config[$params];
			}
			return false;
		}
		foreach($config as $id => &$row) {
			extract($row);
			if (isset($row['Description'])) {
				preg_match('@^([^\n\r]*)@', $Description, $matches);
				$Description = $matches[1];
			} else {
				$Description = $id;
			}
			$row = "[$Type] $Description";
		}
		return $config;
	}

/**
 * install method
 *
 * @param mixed $id null
 * @param array $params array()
 * @return void
 * @access public
 */
	public function install($id = null, $params = array()) {
		$defaultParams = array(
			'base' => dirname(array_pop(App::path('vendors')))
		);
		$params = array_merge($defaultParams, $params);
		if (!$id) {
			return false;
		}
		if (is_array($id)) {
			$id = $id['Package'];
			$details = $id;
		} else {
			$details = MiInstall::details($id);
		}
		$return = array(true, array());
		if ($details['Type'] !== 'virtual') {
			$target = $params['base'] . DS . Inflector::pluralize($details['Type']) . DS . $details['Package'];
			if (strpos($details['Source'], 'svn') !== false) {
				$cmd = "svn co {$details['Source']} $target";
			} elseif (strpos($details['Source'], 'git') !== false) {
				$cmd = "git clone {$details['Source']} $target";
			}
			if (empty($cmd)) {
				return false;
			}
			$return = MiInstall::_system($cmd);
			if ($return[0]) {
				return false;
			}
			if (!empty($details['PostInstallScript'])) {
				$return = am($return, MiInstall::_system('cd ' . $target . ' && ' . $details['PostInstallScript']));
			}
		}
		if (empty($details['Depends'])) {
			return $return;
		}
		$plugins = MiInstall::plugins();
		$vendors = MiInstall::vendors();
		$dependencies = array_map('trim', explode(',', $details['Depends']));
		foreach($dependencies as $dep) {
			if (isset($plugins[$dep]) || isset($vendors[$dep])) {
				continue;
			}
			$details = MiInstall::details($dep);
			$target = $params['base'] . DS . Inflector::pluralize($details['Type']) . DS . $details['Package'];
			if (!is_dir($target)) {
				$_return = MiInstall::install($dep, $params);
				if ($_return) {
					$return[1] = array_merge($return[1], $_return[1]);
				}
			}
		}
		return $return;
	}

/**
 * remove method
 *
 * @param mixed $id null
 * @param array $params array()
 * @return void
 * @access public
 */
	public function remove($id = null, $params = array()) {
		if (!$id) {
			return false;
		}
		if (is_array($id)) {
			$details = $id;
			$id = $id['Package'];
		} else {
			$details = MiInstall::details($id);
		}
		if (!$details) {
			return false;
		}
		$method = Inflector::pluralize($details['Type']);
		$all = array_flip(MiInstall::$method());
		if (!isset($all[$id])) {
			return false;
		}
		$Folder = new Folder($all[$id]);
		$Folder->delete();
		return $Folder->messages();
	}

/**
 * settings method
 *
 * @param array $config array()
 * @return void
 * @access public
 */
	function settings($config = array()) {
		MiInstall::_settings();
		if ($config) {
			MiInstall::$settings = array_merge(MiInstall::$settings, $config);
		}
		return MiInstall::$settings;
	}

/**
 * update method
 *
 * @return void
 * @access public
 */
	public function update() {
		if (!file_exists(CAKE . 'config' . DS . 'sources.txt')) {
			$File = new File(CAKE . 'config' . DS . 'sources.txt', true);
			$File->write('file://' . dirname(__FILE__) . DS . 'packages.txt');
		}
		$sources = file(CAKE . 'config' . DS . 'sources.txt');
		$packages = array();
		App::import('Core', 'HttpSocket');
		$Socket = new HttpSocket();
		foreach($sources as &$source) {
			$source = trim($source);
			if (!$source || $source[0] === '#') {
				$source = false;
				continue;
			}
			if (strpos($source, 'file://') !== false) {
				$data = file($source);
			} else {
				$data = $Socket->get($source);
				if (!$data) {
					$source .= ' (no response)';
					continue;
				}
				$data = explode("\n", $data);
			}
			$packages = array_merge($packages, MiInstall::_parsePackages($data));
		}
		$File = new File(CAKE . 'config' . DS . 'packages.php', true);
		$File->write("<?php\n\$config = " . var_export($packages, true) . ';');
		$return[] = Debugger::trimPath($File->pwd()) . ' updated';
		$return['sources checked:'] = array_filter($sources);
		return $return;
	}

/**
 * upgrade any or all of your app, cake, plugins and vendors
 *
 * @param mixed $what
 * @param mixed $name null
 * @return void
 * @access public
 */
	public function upgrade($what = 'app', $id = null) {
		return MiInstall::_loop('_upgrade', $what, $id);
	}

/**
 * loop method
 *
 * @param mixed $function
 * @param mixed $what
 * @param mixed $id
 * @return void
 * @access protected
 */
	function _loop($function, $what, $id = null) {
		if ($function === '_upgrade') {
			$options = array('app', 'cake', 'plugin', 'vendor');
		} else {
			$options = array('cake', 'plugin', 'vendor');
		}
		$what = Inflector::singularize(Inflector::underscore($what));
		if ($what === '*') {
			$what = $options;
		} elseif (!in_array($what, ($options))) {
			return false;
		}
		MiInstall::_settings();

		$return = array();
		$plugins = $vendors = array();
		foreach((array)$what as $dir) {
			if ($dir === 'app') {
				$return['app'] = MiInstall::$function(APP, 'app');
				continue;
			}
			if ($dir === 'cake') {
				$dir = CAKE_CORE_INCLUDE_PATH . DS . 'cake';
				$return['cake'] = MiInstall::$function($dir, 'cake');
				continue;
			}
			if ($dir === 'plugin') {
				$plugins = MiInstall::plugins();
				foreach($plugins as $dir => $name) {
					if ($name[0] === '.' || ($id && $name && $name !== $id)) {
						continue;
					}
					$return["$name (plugin)"] = MiInstall::$function($dir, 'plugin');
				}
				continue;
			}
			if ($dir === 'vendor') {
				$vendorDirs = App::path('vendors');
				foreach($vendorDirs as $dir) {
					$Folder = new Folder($dir);
					$vendors = $Folder->read();
					if (!empty($vendors)) {
						$vendors = $vendors[0];
					}
					foreach($vendors as $name) {
						if ($name[0] === '.' || ($id && $name && $name !== $id)) {
							continue;
						}
						$return["$name (vendor)"] = MiInstall::$function($dir . DS . $name, 'vendor');
					}
				}
			}
		}
		return $return;
	}

/**
 * neatOut method
 *
 * @param mixed $out
 * @param string $indent ''
 * @return void
 * @access protected
 */
	function _neatOut($out, $indent = '') {
		if (is_numeric($out)) {
			return;
		}
		if (is_array($out)) {
			$count = count($out);
			if ($count > 5) {
				$out = array_merge(array_slice($out, 0, 2), array('...'), array_slice($out, -2));
			}
			foreach($out as $key => $rows) {
				if (is_numeric($key)) {
					MiInstall::_neatOut($rows, $indent);
					continue;
				}
				$this->out($indent . $key);
				$this->_neatOut($rows, $indent . "  ");
			}
			return;
		}
		echo $indent . $out . "\n";
	}

/**
 * parsePackages method
 *
 * @param array $data array()
 * @return void
 * @access protected
 */
	protected function _parsePackages($data = array()) {
		$return = array();
		$Package = null;
		foreach($data as $row) {
			$row = rtrim($row);
			if (!$row) {
				$Package = null;
				continue;
			}
			if ($row[0] === '#') {
				continue;
			}
			if (preg_match('@^([^ ]*): (.*)$@', $row, $matches)) {
				$key = $matches[1];
				$value = trim($matches[2]);
				if ($key === 'Package') {
					$Package = $value;
				}
				$return[$Package][$key] = $value;
			} else {
				$return[$Package][$key] .= "\n" . rtrim($row);
			}
		}
		ksort($return);
		return $return;
	}

/**
 * progress method
 *
 * @param mixed $message
 * @return void
 * @access protected
 */
	protected function _progress($message, $prefix = '') {
		if (!empty(MiInstall::$settings['quiet'])) {
			return;
		}
		MiInstall::_neatOut($message, $prefix);
	}

/**
 * settings method
 *
 * @return void
 * @access protected
 */
	protected function _settings($reset = false) {
		if (!$reset && MiInstall::$settings) {
			return;
		}
		MiInstall::$settings = MiInstall::$_defaultSettings;
		$settings = $config = array();
		if (file_exists(CAKE . 'config' . DS . 'install.php')) {
			include(CAKE . 'config' . DS . 'install.php');
			$settings = Set::merge($settings, $config);
		}
		if (file_exists(CONFIGS . 'install.php')) {
			include(CONFIGS . 'install.php');
			$settings = Set::merge($settings, $config);
		}

		if ($settings) {
			MiInstall::$settings = Set::merge(MiInstall::$settings, $settings);
		}
		foreach (MiInstall::$settings as $type => &$params) {
			if (!is_array($params)) {
				continue;
			}
			foreach ($params as &$param) {
				$param = String::insert($param, $params);
			}
		}
		MiInstall::$settings['version'] = MiInstall::$version;
	}

/**
 * subfolders method
 *
 * @param mixed $type null
 * @param array $exclude array()
 * @return void
 * @access protected
 */
	function _subfolders($type = null, $exclude = array()) {
		$dirs = App::path($type);
		$return = array();
		foreach($dirs as $dir) {
			$Folder = new Folder($dir);
			$contents = $Folder->read();
			if (empty($contents)) {
				continue;
			}
			foreach($contents[0] as $name) {
				if ($name[0] === '.' || in_array($name, $return)|| in_array($name, $exclude)) {
					continue;
				}
				$return[rtrim($dir, DS) . DS . $name] = $name;
			}
		}
		return $return;
	}

/**
 * system method
 *
 * Perform and record a system call
 *
 * @param mixed $command
 * @param mixed $output
 * @return void
 * @access private
 */
	protected function _system($command, &$output = null) {
		if (empty(MiInstall::$__Object)) {
			MiInstall::$__Object = new Object();
		}
		MiInstall::$__Object->log($command, 'system_calls');
		MiInstall::_progress($command, '  ');
		if (defined('CAKE_SHELL') && CAKE_SHELL) {
			system($command, $return);
			return array($return, array());
		}
		exec($command, $output, $return);
		MiInstall::_progress($output, '    ');
		return array($return, $output);
	}

/**
 * upgrade method
 *
 * @param mixed $path
 * @param bool $type = 'app'
 * @return void
 * @access protected
 */
	protected function _upgrade($path, $type = 'app') {
		$_path = realpath($path);
		if ($_path) {
			$path = $_path;
		}
		if (!$path || is_array(MiInstall::$_return) && array_key_exists($path, MiInstall::$_return)) {
			return null;
		}
		if (is_dir(rtrim($path, DS) . DS . '.git')) {
			if (is_dir(rtrim($path, DS) . DS . '.git' . DS . 'svn')) {
				return MiInstall::_upgradeGit($path, 'git-svn', $type);
			}
			return MiInstall::_upgradeGit($path, 'git', $type);
		} elseif (is_dir(rtrim($path . DS) . DS . '.svn')) {
			return MiInstall::_upgradeGeneral($path, 'svn', $type);
		}
		$_check = $path;
		while ($_check) {
			$_prev = $_check;
			$_check = dirname($_check);
			if ($_check === $_prev) {
				break;
			}
			if (is_dir(rtrim($_check, DS) . DS . '.git')) {
				return MiInstall::_upgrade($_check, $type);
			}
		}
		return null;
	}

/**
 * upgradeGeneral method
 *
 * @param mixed $path
 * @param mixed $type null
 * @param mixed $name null
 * @return void
 * @access protected
 */
	protected function _upgradeGeneral($path, $type = null, $name = null) {
		if (DS === '\\') {
			set_time_limit(60);
		}
		if (isset(MiInstall::$settings[$type][$name]['upgrade'])) {
			$cmd = MiInstall::$settings[$type][$name]['upgrade'];
		} else {
			$cmd = MiInstall::$settings[$type]['upgrade'];
		}
		$return = MiInstall::_system('cd ' . escapeshellarg($path) . ' && ' . $cmd);
		$return['cmd'] = $cmd;
		return MiInstall::$_return[$path] = $return;
	}

/**
 * upgradeGit method
 *
 * @param mixed $path
 * @param mixed $type null
 * @param mixed $name null
 * @return void
 * @access private
 */
	protected function _upgradeGit($path, $type = null, $name = null) {
		if (DS === '\\') {
			set_time_limit(60);
		}
		$stash = false;
		$return = MiInstall::_system('cd ' . escapeshellarg($path) . ' && git status');
		if (preg_match('@# (Changed but not updated|Changes to be committed):@', implode($return[1]))) {
			MiInstall::_system('cd ' . escapeshellarg($path) . ' && git stash save "automatic stash"');
			$stash = true;
		}

		if (isset(MiInstall::$settings[$type][$name]['upgrade'])) {
			$cmd = MiInstall::$settings[$type][$name]['upgrade'];
		} else {
			$cmd = MiInstall::$settings[$type]['upgrade'];
		}

		$return = MiInstall::_system('cd ' . escapeshellarg($path) . ' && git branch');
		if ($cmd === 'git pull' && preg_match('@\* (no branch)@', implode($return[1])) !== false) {
			$cmd = 'git checkout master && git pull origin master';
		}
		$return = MiInstall::_system('cd ' . escapeshellarg($path) . ' && ' . $cmd);
		if ($stash) {
			MiInstall::_system('cd ' . escapeshellarg($path) . ' && git stash pop');
		}
		if ($type === 'git') {
			if (!$return[1]) {
				$return = array(
					0,
					array('Assumed (no return message from issuing git pull')
				);
			}
		}
		$return['cmd'] = $cmd;
		return MiInstall::$_return[$path] = $return;
	}
}
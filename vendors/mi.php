<?php
/**
 * Short description for mi.php
 *
 * Long description for mi.php
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
 * @subpackage    mi.vendors
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Mi class
 *
 * A utility class
 *
 * @package       mi
 * @subpackage    mi.vendors
 */
class Mi {
	public static $cakeVersion = null;

/**
 * extraExcludes property
 *
 * @var array
 * @access public
 */
	public static $extraExcludes = array(
		'Controller' => array('Components'),
		'Model' => array('Behavior', 'Datasource'),
		'View' => array('Helper'),
	);

/**
 * actions method
 *
 * @param mixed $controller
 * @param string $diffTo
 * @param array $excludePatterns
 * @return void
 * @access public
 */
	public function actions($controller = '', $type = 'public', $plugin = false, $diffTo = 'Controller') {
		if (is_array($type)) {
			extract(am(array('type' => 'public'), $controller));
		}
		if ($type == 'admin') {
			$excludePatterns = array('/^(?!(admin)).*/');
		} else {
			$excludePatterns = array('/admin_.*/');
		}
		if (!$controller) {
			return false;
		}
		if (is_string($controller)) {
			App::import('Core', 'Controller');
			App::import('Controller', ($plugin?$plugin . '.':'') . $controller);
			$controller .= 'Controller';
		}
		$return = array();
		$parent = get_parent_class($controller);
		while ($controller !== $diffTo) {
			$methods = array_diff(get_class_methods($controller), get_class_methods($parent));
			$system = array('hashPasswords', 'password');
			$methods = array_diff($methods, $system);
			foreach ($methods as $i => $name) {
				$skip = false;
				if ($name[0] == '_') {
					unset ($methods[$i]);
					$skip = true;
					continue;
				}
				foreach ($excludePatterns as $pattern) {
					if (preg_match($pattern, $name)) {
						unset ($methods[$i]);
						$skip = true;
						continue;
					}
				}
				if (isset($return[$name])) {
					$skip = true;
					continue;
				}
				if (!$skip) {
					$return[$name] = $controller . '::' . $name;
				}
			}
			$controller = get_parent_class($controller);
			$parent = get_parent_class($controller);
		}
		ksort($return);
		return array_flip($return);
	}

/**
 * all method
 *
 * @param bool $includeCore false
 * @param array $exclude array()
 * @return void
 * @access public
 */
	public function all($types = null, $params = array()) {
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit (30);
		}
		extract(am(array(
			'includeCore' => false,
			'plugin' => false,
			'extension' => 'php',
			'excludeFolders' => array('tests'),
			'excludePattern'=> '@jquery[\\\/]|jquery-ui[\\\/]|simpletest[\\\/]|[\\\/]index.php\|[\\\/]test.php@'
		), $params));
		if ($plugin && $plugin === basename(APP) && !is_dir(APP . DS . 'plugins')) {
			$plugin = false;
		}
		if (!$types) {
			$types = array(
				'Component', 'Controller',
				'Behavior', 'Datasource', 'Model',
				'Helper',
				'Shell', 'Vendor',
				'Plugin',
			);
		} elseif (!is_array($types)) {
			$types = array($types);
		}
		$allFiles = array();
		$files = array();
		$return = array();

		$pattern = '.*\.(' . implode('|', (array)$extension) . ')';
		foreach ($types as $type) {
			if (isset(Mi::$extraExcludes[$type])) {
				$excludeFolders = array_unique(am($excludeFolders, Mi::$extraExcludes[$type]));
			}
			$files[$type] = array();
			$paths = Mi::paths($type, compact('plugin'));
			if ($type === 'Shell') {
				$vPaths = Mi::paths('Vendor');
				foreach ($vPaths as &$vPath) {
					$vPath = $vPath . 'shells' . DS;
				}
				$paths = am($vPaths, $paths);
			}
			$core = CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'basics.php';
			if ($_core = realpath($core)) {
				$core = $_core;
			}
			$core = dirname($core);
			foreach ($paths as $i => $path) {
				if (strpos(realpath($path), $core) === 0 && !$includeCore) {
					continue;
				}
				if (rtrim($path, DS) == rtrim(APP, DS)) {
					$folder = new Folder(APP);
					$tFiles = $folder->find(low($type) . '.*php');
				} elseif ($type === 'View') {
					$tFiles = Mi::files($path, $excludeFolders, '.*ctp');
 				} else {
					$tFiles = Mi::files($path, $excludeFolders, $pattern);
				}
				if ($allFiles) {
					$tFiles = array_diff($tFiles, $allFiles);
				}
				foreach ($tFiles as $i => $file) {
					if ($excludePattern && preg_match($excludePattern, $file)) {
						unset($tFiles[$i]);
					}
				}
				$allFiles = am($allFiles, $tFiles);
				$files[$type] =  am($files[$type], $tFiles);
			}
			$folder = new Folder(APP);
			$tFiles = $folder->find($pattern);
			$tFiles = array_diff($tFiles, $allFiles);
			foreach ($tFiles as $i => $file) {
				if ($excludePattern && preg_match($excludePattern, $file)) {
					unset($tFiles[$i]);
				}
			}
			$allFiles = am($allFiles, $tFiles);
			if ((array)$extension != array('php')) {
				foreach($files[$type] as $file) {
					$name = preg_replace('@.*vendors[\\\/]@', '', $file);
					if (isset($return[$name])) {
						continue;
					}
					$return[$name] = $file;
				}
				return $return;
			}
			foreach($files[$type] as $file) {
				if (strpos( $file, '.ctp')) {
					$name = str_replace('.ctp', '', $file);
					$name = preg_replace('@.*views[\\\/]@', '', $name);
				} else {
					if ($type === 'Plugin') {
						foreach($types as $_type) {
							if (strpos($file, low($_type))) {
								$suffix = $_type;
								break;
							}
						}
						if ($suffix === 'Model') {
							$suffix = '';
						}
					} else {
						$suffix = '';
					}
					$name = preg_replace('@.(' . implode('|', (array)$extension) . ')$@', '', basename($file));
					$name = str_replace('_controller.', '.', $name);
					$name = str_replace('_model.', '.', $name);
					$name = str_replace('_helper.', '.', $name);
					if ($type === 'Model') {
						$type = '';
					}
					$name = Inflector::Camelize($name) . $type . $suffix;
				}
				if (isset($return[$name])) {
					continue;
				}
				$return[$name] = $file;
			}
		}
		if ($includeCore) {
				$tFiles = Mi::files(CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS, $excludeFolders);
				$tFiles = array_diff($tFiles, $allFiles);
				foreach ($tFiles as $i => $file) {
					if ($excludePattern && preg_match($excludePattern, $file)) {
						unset($tFiles[$i]);
					}
				}
				$allFiles = am($allFiles, $tFiles);
				foreach($tFiles as $file) {
					$name = str_replace('.php', '', basename($file));
					$name = str_replace('_controller', '', $name);
					$name = str_replace('_model', '', $name);
					$name = str_replace('_helper', '', $name);
					if ($type === 'Model') {
						$type = '';
					}
					$name = Inflector::Camelize($name);
					if (isset($return[$name])) {
						continue;
					}
					$return[$name] = $file;
				}
		}
		ksort($return);
		return array_flip($return);
	}

/**
 * components method
 *
 * @param bool $plugin
 * @param array $exclude
 * @return void
 * @access public
 */
	public function components($plugin = false, $excludeFolders = array('abstract', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = array_merge($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Component', $params);
	}

/**
 * bakeTemplates method
 *
 * call as bakeTemplates(array('type' => 'x')) to restrict what's returned
 *
 * @param bool $plugin false
 * @param array $exclude array()
 * @return void
 * @access public
 */
	public function bakeTemplates($plugin = false, $excludeFolders = array(), $params = array()) {
		$extension = 'ctp';
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders', 'extension'));
		}
		$return = array();
		$_return = Mi::all('Shell', $params);
		if (empty($type)) {
			$replace = 'shells' . DS . 'templates' . DS;
		} else {
			$replace = 'shells' . DS . 'templates' . DS . $type . DS;
		}
		foreach ($_return as $_k => $v) {
			$k = str_replace($replace, '', $_k);
			if ($_k !== $k) {
				$k = str_replace('.ctp', '', $k);
				$return[$k] = $v;
			}
		}
		return $return;
	}

/**
 * behaviors method
 *
 * @param bool $plugin
 * @param array $exclude
 * @return void
 * @access public
 */
	public function behaviors($plugin = false, $excludeFolders = array('abstract', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Behavior', $params);
	}

/**
 * controllers method
 *
 * @param bool $plugin
 * @return void
 * @access public
 */
	public function controllers($plugin = false, $excludeFolders = array('abstract', 'components', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		$return = Mi::all('Controller', $params);
		foreach ($return as $path => &$controller) {
			$controller = str_replace('Controller', '', $controller);
		}
		return $return;
	}

/**
 * datasources method
 *
 * @param bool $plugin
 * @param array $exclude
 * @param 'sunset') $'sunset')
 * @return void
 * @access public
 */
	public function datasources($plugin = false, $excludeFolders = array('abstract', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Datasource', $params);
	}

/**
 * files method
 *
 * @param mixed $path
 * @param array $excludePaths
 * @param string $pattern
 * @return void
 * @access public
 */
	public function files($path = null, $excludePaths = array(), $pattern = '.*php') {
		if (is_array($path)) {
			extract(am(array('path' => null), $path));
		}
		$folder = new Folder($path);
		$return = $folder->findRecursive($pattern);
		foreach ((array)$excludePaths as $excludePath) {
			if (!is_dir($path . $excludePath)) {
				continue;
			}
			$folder = new Folder($path . $excludePath);
			$return = array_diff($return, $folder->findRecursive($pattern));
		}
		return $return;
	}

/**
 * helpers method
 *
 * @param bool $plugin
 * @param array $exclude
 * @param 'sunset') $'sunset')
 * @return void
 * @access public
 */
	public function helpers($plugin = false, $excludeFolders = array('abstract', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Helper', $params);
	}

/**
 * models method
 *
 * @param bool $plugin
 * @return void
 * @access public
 */
	public function models($plugin = false, $excludeFolders = array('abstract', 'behaviors', 'datasources', 'sunset'), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Model', $params);
	}

/**
 * objects method
 *
 * @param mixed $type
 * @static
 * @return void
 * @access public
 */
	static function objects($type) {
		$params = func_get_args();
		unset($params[0]);
		$function = Inflector::pluralize(Inflector::Classify($type));
		$function[0] = low($function[0]);
		return call_user_func_array(array('Mi', $function), $params);
	}

/**
 * paths method
 *
 * Return all paths - taking about of any 'bespoke' logic
 *
 * @param mixed $type null
 * @param array $params array()
 * @return void
 * @access public
 */
	public function paths($type = null, $params = array()) {
		$type = low($type);
		$plugin = $locale = null;
		if (!empty($params['plugin'])) {
			$plugin = $params['plugin'];
		}
		if (!empty($params['locale'])) {
			$locale = $params['locale'];
		}

		if ($type === 'view') {
			return Mi::_viewPaths($plugin, $locale);
		}
		if ($plugin) {
			$pluginPaths = Mi::paths('plugin');
			$partials = array(
				'controller' => 'controllers',
				'component' => 'controllers' . DS . 'components',
				'model' => 'models',
				'behavior' => 'models' . DS . 'behaviors',
				'datasource' => 'models' . DS . 'datasource',
				'helper' => 'views' . DS . 'helpers',
				'vendors' => 'vendors',
				'shells' => 'vendors' . DS . 'shells',
				'tests' => 'tests' . DS . 'cases',
			);
			if (isset($partials[$type])) {
				$partial = $partials[$type];
			} else {
				$partial = $type;
			}
			$plugin = Inflector::underscore($plugin);
			foreach($pluginPaths as &$path) {
				$path .= $plugin . DS . $partial . DS;
			}
			return $pluginPaths;
		}
		if (Mi::cakeVersion() === '1.2') {
			$paths = Configure::read(low($type) . 'Paths');
		} else {
			$paths = App::path(low($type) . 's');
		}
		if ($type === 'shell') {
			if (Mi::cakeVersion() === '1.2') {
				$vPaths = Configure::read('vendorPaths');
			} else {
				$vPaths = App::path('vendors');
			}
			foreach ($vPaths as $path) {
				array_unshift($paths, $path . 'shells' . DS);
			}
		} elseif ($type === 'test') {
			$paths = array(APP . 'tests' . DS . 'cases');
		}
		if (!$paths) {
			$paths = array(APP . low($type));
		}
		return $paths;
	}

/**
 * plugins method
 *
 * @return void
 * @access public
 */
	public function plugins() {
		if (Mi::cakeVersion() === '1.2') {
			$paths = Configure::read('pluginPaths');
		} else {
			$paths = App::path('plugins');
		}
		$return = array();
		foreach ($paths as $path) {
			$folder = new Folder($path);
			list($folders) = $folder->read();
			foreach ($folders as $name) {
				if ($name[0] === '.' || isset($return[$name])) {
					continue;
				}
				$return[$name] = $path . $name;
			}
		}
		ksort($return);
		return array_flip($return);
	}

/**
 * tables method
 *
 * @param string $useDbConfig
 * @return void
 * @access public
 */
	public function tables($useDbConfig = 'default') {
		if ($useDbConfig == '*') {
			require_once(CONFIGS. 'database.php');
			$connections = get_class_vars('DATABASE_CONFIG');
			$return = array();
			foreach ($connections as $useDbConfig => $_) {
				$return = array_merge($return, Mi::tables($useDbConfig));
			}
			$return = array_flip($return);
			ksort($return);
			$return = array_flip($return);
			return $return;
		}
		if (!$useDbConfig) {
			return array();
		}
		require_once(CONFIGS. 'database.php');
		$connections = get_class_vars('DATABASE_CONFIG');
		if (!isset($connections[$useDbConfig])) {
			return array();
		}
		App::import('Core', 'ConnectionManager');
		$db =& ConnectionManager::getDataSource($useDbConfig);
		if (!$db) {
			return array();
		}
		$usePrefix = empty($db->config['prefix']) ? '': $db->config['prefix'];
		$tables = array();
		if ($usePrefix) {
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[$useDbConfig . '::' . $table] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$_tables = $db->listSources();
			foreach ($_tables as $table) {
				$tables[$useDbConfig . '::' . $table] = $table;
			}
		}
		return $tables;
	}

/**
 * views method
 *
 * @param mixed $controllerName
 * @param mixed $plugin
 * @param array $excludePatterns
 * @return void
 * @access public
 */
	public function views($controllerName, $plugin = null, $excludePatterns = array('/admin.*/'), $nameOnly = true) {
		if (is_array($controllerName)) {
			extract(am(array('controllerName' => null), $controllerName));
		}
		$paths = Mi::_viewPaths($plugin);
		$folder = Inflector::underscore($controllerName);
		foreach ($paths as &$path) {
			$path .= $folder;
		}
		$files = array();
		foreach ($paths as $path) {
			if (!strpos($path, DS . 'view')) {
				continue;
			}
			$files = Set::merge($files, Mi::files($path, null, '.*ctp'));
		}
		$return = array();
		foreach ($files as $file) {
			if ($nameOnly) {
				$name = str_replace('.ctp', '', basename($file));
			} else {
				$name = preg_replace('@^.*[\\\/]views[\\\/]' . $folder . '[\\\/]@', '', $file);
				$name = str_replace('.ctp', '', $name);
			}
			if (isset($return[$name])) {
				continue;
			}
			$return[$name] = $file;
		}
		ksort($return);
		return array_flip($return);
	}

/**
 * shells method
 *
 * @param bool $plugin false
 * @param array $exclude array('shells'
 * @param array $params array()
 * @return void
 * @access public
 */
	public function shells($plugin = false, $excludeFolders = array(), $params = array()) {
		if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = compact('plugin', 'excludeFolders');
		}
		return Mi::all('Shell', $params);
	}

	public function tests($plugin = false, $params = array()) {
	if (is_array($plugin)) {
			$params = $plugin;
		} else {
			$params = am($params, compact('plugin'));
		}
		$paths = Mi::paths('Test');
		$files = array();
		foreach ($paths as $path) {
			$files = Set::merge($files, Mi::files($path, null, '.*test.php'));
		}
		$return = array();
		foreach ($files as $file) {
			$name = preg_replace('@^.*[\\\/]cases[\\\/]@', '', $file);
			$name = str_replace('.test.php', '', $name);
			if (isset($return[$name])) {
				continue;
			}
			$return[$name] = $file;
		}
		ksort($return);
		return array_flip($return);
	}

/**
 * vendors method
 *
 * @param bool $plugin false
 * @param array $exclude array('shells'
 * @param array $params array()
 * @return void
 * @access public
 */
	public function vendors($plugin = false, $excludeFolders = array('shells', 'css', 'js'), $params = array()) {
		if (!empty($params['folders'])) {
			$dirs = App::path('vendors');
			$vendors = array();
			foreach($dirs as $dir) {
				$Folder = new Folder($dir);
				$contents = $Folder->read();
				if (empty($contents)) {
					continue;
				}
				foreach($contents[0] as $name) {
					if ($name[0] === '.' || in_array($name, $exclude)) {
						continue;
					}
					$vendors[$name] = rtrim($dir, DS) . DS . $name;
				}
			}
			return array_flip($vendors);
		}
		if (is_array($plugin)) {
			$params = am(array('plugin' => false), $plugin);
		} else {
			$params = am($params, compact('plugin', 'excludeFolders'));
		}
		return Mi::all('Vendor', $params);
	}

/**
 * viewPaths method
 *
 * @param mixed $locale null
 * @param mixed $plugin null
 * @return void
 * @access protected
 */
	protected function _viewPaths($plugin = null, $locale = null) {
		if (!$locale) {
			App::import('Vendor', 'Mi.MiCache');
			$locale = MiCache::setting('Site.lang');
			if (!$locale) {
				$locale = Configure::read('Config.language');
				if (!$locale && defined('DEFAULT_LANGUAGE')) {
					$locale = 'DEFAULT_LANGUAGE';
				}
			}
			if ($locale) {
				Configure::write('Config.language', $locale);
				App::import('Core', 'I18n');
				$I18n =& I18n::getInstance();
				$I18n->domain = 'default_' . $locale;
				$I18n->__lang = $locale;
				$I18n->l10n->get($locale);
			}
		}
		if (Mi::cakeVersion() === '1.2') {
			$viewPaths = Configure::read('viewPaths');
			$localePaths = Configure::read('localePaths');
		} else {
			$viewPaths = App::path('views');
			$localePaths = App::path('locales');
		}
		if (!class_exists('I18n')) {
			App::import('Core', array('I18n'));
		}
		$languagePaths = array_filter(array_unique(I18n::getInstance()->l10n->languagePath));
		$paths = array();
		if ($plugin) {
			$count = count($viewPaths);
			for ($i = 0; $i < $count; $i++) {
				$base = $viewPaths[$i] . 'plugins' . DS . $plugin . DS;
				if ($locale) {
					foreach($localePaths as $path) {
						foreach($localePaths as $_locale) {
							$paths[] = $_locale . $locale . DS . 'views' . DS  . 'plugins' . DS . $plugin . DS;
						}
					}
				}
				$paths[] = $base;
			}

			if (Mi::cakeVersion() === '1.2') {
				$pluginPaths = Configure::read('pluginPaths');
			} else {
				$pluginPaths = App::path('plugins');
			}
			$count = count($pluginPaths);

			for ($i = 0; $i < $count; $i++) {
				$base = $pluginPaths[$i] . $plugin . DS;
				if ($locale) {
					foreach($localePaths as $path) {
						foreach($languagePaths as $_locale) {
							$paths[] = $pluginPaths[$i] . $plugin . DS . 'locale' . DS . $_locale . DS . 'views' . DS;
						}
					}
				}
				$paths[] = $base . 'views' . DS;
			}
		}
		if ($locale) {
			foreach($localePaths as $path) {
				foreach($languagePaths as $_locale) {
					$paths[] = $path . $_locale . DS . 'views' . DS;
				}
			}
		}
		if ($paths) {
			$viewPaths = array_merge($paths, $viewPaths);
		}
		if (!empty($theme)) {
			$themePaths = array();

			$count = count($paths);
			for ($i = 0; $i < $count; $i++) {
				if (strpos($paths[$i], DS . 'plugins' . DS) === false
					&& strpos($paths[$i], DS . 'libs' . DS . 'view') === false) {
						if ($plugin) {
							$themePaths[] = $paths[$i] . 'themed'. DS . $theme . DS . 'plugins' . DS . $plugin . DS;
						}
						$themePaths[] = $paths[$i] . 'themed'. DS . $theme . DS;
					}
			}
			$viewPaths = array_merge($themePaths, $viewPaths);
		}
		return array_unique($viewPaths);
	}

/**
 * cakeVersion method
 *
 * @return void
 * @access protected
 */
	protected static function cakeVersion() {
		if (Mi::$cakeVersion) {
			return Mi::$cakeVersion;
		}
		return Mi::$cakeVersion = substr(Configure::version(), 0, 3);
	}
}
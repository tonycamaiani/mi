<?php
/**
 * Short description for mi_db.php
 *
 * Long description for mi_db.php
 *
 * PHP version 5
 *
 * Copyright (c) 2009, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi
 * @subpackage    mi.vendors.shells
 * @since         v 1.0 (30-Sep-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Model', 'ConnectionManager');

/**
 * MiDbShell class
 *
 * @uses          Shell
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class MiDbShell extends Shell {

/**
 * name property
 *
 * @var string 'MiDb'
 * @access public
 */
	public $name = 'MiDb';

/**
 * version property *
 * @var string '0.1'
 * @access protected
 */
	protected $version = '0.1';

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public $settings = array(
		'extraOptions' => '',
		'connection' => 'default',
		'table' => '',
		'quiet' => false,
		'commands' => array(),
	);

/**
 * commands property
 *
 * @var array
 * @access protected
 */
	protected $commands = array(
		'mysql' => array(
			'connection' => '--host=:host --port=:port --user=:login --password=:password --default-character-set=:encoding',
			'standardOptions' => '--set-charset -e',
			'dump' => 'mysqldump :connection -d -R :standardOptions :extraOptions :database :table',
			'dumpComplete' => 'mysqldump :connection -R :standardOptions :extraOptions :database :table',
			'dumpCreate' => 'mysqldump :connection -d -R --add-drop-table :standardOptions :extraOptions :database :table',
			'dumpData' => 'mysqldump :connection -t :standardOptions :extraOptions :database :table',
			'dumpRoutines' => 'mysqldump :connection -d -t -R :standardOptions :extraOptions :database :table',
			'import' => 'mysql :connection :extraOptions --database=:database :table < :file',
			'diff' => 'diff -u -w :from :to',
			'stripAutoIncrement' => 'sed -i "s/ AUTO_INCREMENT=[0-9]\+//" :file',
			'stripComments' => 'sed -i -e "/^--/d" -e "/^$/d" :file',
		)
	);

/**
 * help method
 *
 * @return void
 * @access public
 */
	public function help()  {
		$exclude = array('main');
		$shell = get_class_methods('Shell');
		$methods = get_class_methods($this);
		$methods = array_diff($methods, $shell);
		$methods = array_diff($methods, $exclude);
		foreach ($methods as $method) {
			if (!isset($help[$method]) && $method[0] !== '_') {
				$help[$method] = $method;
			}
		}
		$this->out($this->name . '. Version ' . $this->version);
		$this->out('Usage: cake ' . $this->name . ' command');
		$this->out('');
		$this->out($this->name . ' is a shell for manipulating database structures and data');
		$this->out('');
		$this->out('Commands:');
		foreach($help as $message) {
			$this->out("\t" . $message);
		}
		$this->hr();

	}

/**
 * startup method
 *
 * @return void
 * @access public
 */
	public function startup() {
		$this->_welcome();
		$this->db =& ConnectionManager::getDataSource($this->settings['connection']);
		$name = $this->db->config['driver'];
		if (!isset($this->settings['commands'][$name])) {
			$this->settings['commands'][$name] = $this->commands[$name];
		} else {
			$this->settings['commands'][$name] = array_merge($this->commands[$name], $this->settings['commands'][$name]);
		}
	}

/**
 * initialize method
 *
 * @return void
 * @access public
 */
	public function initialize() {
		if (!empty($this->params['q']) || !empty($this->params['quiet']) || !empty($this->params['-quiet'])) {
			$this->settings['quiet'] = true;
		}
		if (!empty($this->params['output'])) {
			$this->settings['toFile'] = $this->params['output'];
		}
		if (!empty($this->params['o'])) {
			$this->params['output'] = $this->settings['toFile'] = $this->params['o'];
			unset($this->params['o']);
		}

		if (!empty($this->params['input'])) {
			$this->settings['file'] = $this->params['input'];
		}
		if (!empty($this->params['file'])) {
			$this->settings['file'] = $this->params['file'];
		}
		if (!empty($this->params['f'])) {
			$this->params['file'] = $this->settings['file'] = $this->params['f'];
			unset($this->params['f']);
		}
		if (!empty($this->params['v'])) {
			$this->params['debug'] = true;
			unset($this->params['v']);
		}

		if (!empty($this->params['models'])) {
			$models = explode(',', $this->params['models']);
			$connecitons = $tables = array();
			foreach($models as $model) {
				$Model = ClassRegistry::init($model);
				$connections[$Model->useDbConfig] =& ConnectionManager::getDataSource($Model->useDbConfig);
				$tables[] = $connections[$Model->useDbConfig]->fullTableName($Model, false);
			}
			if (count($connections) !== 1) {
				return trigger_error('MiDbShell:: mixed connections are not supported when dumping tables');
			}
			$this->settings['connection'] = key($connections);
			$this->settings['table'] = implode(' ', array_unique($tables));
		}

		$extraParams = array();
		foreach($this->params as $k => $v) {
			if ($k[0] !== '-') {
				continue;
			}
			$k = '-' . $k;
			if ($v != 1) {
				$k .= '=' . $v;
			}
			$extraParams[] = $k;
		}
		if ($extraParams) {
			$this->settings['extraOptions'] = implode($extraParams, ' ');
		}
		$this->settings = array_merge($this->settings, $this->params);
		if (empty($this->commands['mysqli'])) {
			$this->commands['mysqli'] = $this->commands['mysql'];
		}
	}

/**
 * main method
 *
 * @return void
 * @access public
 */
	public function main() {
		return $this->help();
	}

/**
 * backup method
 *
 * @return void
 * @access public
 */
	public function backup() {
		$settings = array();
		if (empty($this->settings['toFile'])) {
			$settings['toFile'] = $this->_backupName(CONFIGS . 'schema' . DS . 'backups' . DS . $this->settings['connection']);
			if (isset($this->args[0])) {
				$settings['toFile'] .= '_' . Inflector::underscore($this->args[0]);
			}
			$settings['toFile'] .= '.sql';
		}
		$this->_run('backup', 'dump', null, $settings);
	}

/**
 * save method
 *
 * @return void
 * @access public
 */
	public function save() {
		$settings = array();
		if (empty($settings['toFile'])) {
			$settings['toFile'] = CONFIGS . 'schema' . DS . $this->settings['connection'];
			if (isset($this->args[0])) {
				$settings['toFile'] .= '_' . Inflector::underscore($this->args[0]);
			}
			$settings['toFile'] .= '.sql';
		}
		$this->_run('save', 'dump', null, $settings);
		$this->stripAutoIncrement($settings);
		$this->stripComments($settings);
	}

/**
 * stripAutoIncrement method
 *
 * @param array $settings array()
 * @return void
 * @access public
 */
	public function stripAutoIncrement($settings = array()) {
		if (!empty($settings['toFile'])) {
			$file = $settings['toFile'];
		} else {
			if (isset($this->params['file'])) {
				$file = $this->params['file'];
			} elseif (!empty($this->args[0])) {
				$file = $this->args[0];
			} else {
				$file = CONFIGS . 'schema' . DS . $this->settings['connection'];
				if (isset($this->args[0])) {
					$file .= '_' . Inflector::underscore($this->args[0]);
				}
				$file .= '.sql';
			}
		}
		$settings['file'] = $file;
		$settings['toFile'] = false;
		$this->_run('strip auto increment', 'stripAutoIncrement', null, $settings);
	}

/**
 * stripComments method
 *
 * @param array $settings array()
 * @return void
 * @access public
 */
	public function stripComments($settings = array()) {
		if (!empty($settings['toFile'])) {
			$file = $settings['toFile'];
		} else {
			if (isset($this->params['file'])) {
				$file = $this->params['file'];
			} elseif (!empty($this->args[0])) {
				$file = $this->args[0];
			} else {
				$file = CONFIGS . 'schema' . DS . $this->settings['connection'];
				if (isset($this->args[0])) {
					$file .= '_' . Inflector::underscore($this->args[0]);
				}
				$file .= '.sql';
			}
		}
		$settings['file'] = $file;
		$settings['toFile'] = false;
		$this->_run('strip comments', 'stripComments', null, $settings);
	}

/**
 * dump method
 *
 * @return void
 * @access public
 */
	public function dump() {
		$this->_run('dump');
	}

/**
 * import method
 *
 * @return void
 * @access public
 */
	public function import() {
		$file = '';
		if (isset($this->params['file'])) {
			$file = $this->params['file'];
		} elseif (!empty($this->args[0])) {
			$file = $this->args[0];
		}
		if (!is_file($file)) {
			if ($file) {
				$file = '_' . $file;
			}
			$file = CONFIGS . 'schema' . DS . $this->settings['connection'] . $file . '.sql';
		}
		if (empty($this->params['force'])) {
			$this->out(file_get_contents($file, null, null, 0, 1000) . '...');
			$continue = strtoupper($this->in("Import $file into {$this->settings['connection']}?", array('Y', 'N')));
			if ($continue !== 'Y') {
				$this->out('Import aborted');
				return $this->_stop();
			}
		}
		$settings['file'] = $file;
		$this->_run('import', 'import', false, $settings);
	}

/**
 * compare method
 *
 * @return void
 * @access public
 */
	public function compare() {
		$to = '_current_';
		if (!empty($this->args[1])) {
			$from = $this->args[0];
			$to = $this->args[1];
		} elseif (!empty($this->args[0])) {
			$from = $this->args[0];
		} else {
			$from = '';
		}
		if ($to === '_current_') {
			$to = TMP . 'to.sql';
			$this->_run('dump', 'dump', false, array('toFile' => $to));
		}
		if (!is_file($from)) {
			if ($from) {
				$from = '_' . $from;
			}
			$from = CONFIGS . 'schema' . DS . $this->settings['connection'] . $from . '.sql';
			if (!is_file($from)) {
				return trigger_error('MiDbShell:: ' . $from . ' not found, cannot compare schemas');
			}
		}
		copy($from, TMP . 'from.sql');
		$from = TMP . 'from.sql';
		$settings = compact('to', 'from');
		$settings['debug'] = true;
		$settings['return'] = true;
		$result = $this->_run('diff', 'diff', false, $settings);
		foreach($result as $i => $line) {
			if (strpos('-- ', $line) === 0) {
				unset($result[$i]);
			}
		}
		debug ($result); //@ignore
	}

/**
 * run method
 *
 * @param string $friendlyName ''
 * @param mixed $commandName null
 * @return void
 * @access protected
 */
	protected function _run($friendlyName = '', $commandName = null, $version = null, $settings = array()) {
		$settings = array_merge($this->settings, $settings);
		if (!$commandName) {
			$commandName = $friendlyName;
		}
		$db =& ConnectionManager::getDataSource($settings['connection']);
		$name = $this->db->config['driver'];

		if ($version === null) {
			if (isset($this->args[0]) && $this->args[0] == '*') {
				$file = str_replace('_*', '', $settings['toFile']);
				foreach($settings['commands'][$name] as $version => $_) {
					if (strpos($version, $commandName) === false) {
						continue;
					}
					$version = str_replace($commandName, '', $version);
					if ($version) {
						$settings['toFile'] = str_replace('.sql', '_' . Inflector::underscore($version) . '.sql', $file);
					} else {
						$settings['toFile'] = $file;
					}
					$this->_run($friendlyName, $commandName, $version);
				}
				return;
			} elseif (!empty($this->args[0])) {
				$version = $this->args[0];
			}
		}
		if ($version) {
			$version = ucfirst(Inflector::camelize($version));
			$commandName .= $version;
			$friendlyName .= $version;
		}
		$config = $db->config;

		if (!isset($settings['commands'][$name][$commandName])) {
			return $this->out("ERROR: no command defined for $commandName");
		}
		$command = $settings['commands'][$name][$commandName];
		$command = $this->_command($command, $config, $name, $settings);
		$this->out("Running $friendlyName");
		return $this->_out($command, $settings);
	}

/**
 * welcome method
 *
 * @return void
 * @access protected
 */
	public function _welcome() {
		if ($this->settings['quiet']) {
			return;
		}
		parent::_welcome();
	}

/**
 * command method
 *
 * @param mixed $string
 * @param mixed $replacements
 * @param mixed $name
 * @return void
 * @access protected
 */
	protected function _command($string, $replacements, $name, $settings = array()) {
		$settings = array_merge($this->settings, $settings);
		$replacements = am($settings, $replacements, $settings['commands'][$name]);
		foreach($replacements as $key => &$value) {
			if (stripos('file', $key) !== false) {
				$value = escapeshellarg($value);
			}
		}
		$check = $return = $string;
		do {
			$check = $return;
			$return = String::insert($return, $replacements);
		} while ($check !== $return);
		return preg_replace('@\s+@', ' ', $return);
	}

/**
 * out method
 *
 * @param mixed $command
 * @return void
 * @access protected
 */
	protected function _out($command, $settings = array()) {
		$settings = array_merge($this->settings, $settings);
		if (!empty($settings['debug'])) {
			$this->out($command);
		}
		if (!empty($settings['return'])) {
			exec($command, $return);
			return $return;
		}
		if (empty($settings['toFile'])) {
			$this->out(`$command`);
		} else {
			$this->out('generating ' . $settings['toFile']);
			$command .= ' > ' . escapeshellarg($settings['toFile']);
			`$command`;
		}
	}

/**
 * backupName method
 *
 * @param mixed $name
 * @return void
 * @access protected
 */
	protected function _backupName($name) {
		$name .= '_' . date('ymd-H') . str_pad((int)(date('i') / 10) * 10, 2, '0');
		$dir = dirname($name);
		if (!is_dir($dir)) {
			new Folder($dir, true);
		}
		return $name;
	}
}
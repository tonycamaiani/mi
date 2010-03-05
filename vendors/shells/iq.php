<?php
/**
 * Iq is a simple shell for downloading apps, plugins and vendors
 *
 * This is apt-get for cake
 *
 * PHP 5
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
 * @since         v 1.0 (20-Aug-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Vendor', array('Mi.MiInstall'));

/**
 * Iq class
 *
 * @uses          Shell
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class IqShell extends Shell {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public $settings = array();

/**
 * help method
 *
 * @access public
 * @return void
 */
	public function help() {
		if (!empty($this->args[0])) {
			switch (low($this->args[0])) {
			case 'update':
				$this->out('update is used to resynchronize the package index files from their sources.');
				$this->out(' The indexes of available packages are fetched from the location(s) specified in');
				$this->out(' ' . CAKE . 'config' . DS . 'sources.txt');
				return;
			case 'upgrade':
				$this->out('cake iq upgrade app');
				$this->out('	- upgrade your application code');
				$this->out('cake iq upgrade cake');
				$this->out('	- upgrade your cake version');
				$this->out('cake iq upgrade plugin "*"');
				$this->out('	- upgrade your plugins');
				$this->out('cake iq upgrade plugin foo');
				$this->out('	- upgrade your foo plugin');
				$this->out('cake iq upgrade vendor "*"');
				$this->out('	- upgrade your vendors');
				$this->out('cake iq upgrade vendors bar');
				$this->out('	- upgrade your bar vendor');
				$this->out('cake iq upgrade "*"');
				$this->out('	- vote for all of the above');
				$this->hr();
				return;
			default:
				$this->out(__d('mi', 'Sorry, no specific help on that', true));
			}
		}
		$exclude = array('plugins', 'vendors', 'settings');
		$shell = get_class_methods('Shell');
		$methods = get_class_methods('MiInstall');
		$methods = array_diff($methods, $shell);
		$methods = array_diff($methods, $exclude);
		$help['update'] = 'update - Update list of available packages';
		$help['upgrade'] = 'upgrade - Perform an upgrade';
		$help['install'] = 'install - Install a new package';
		$help['remove'] = 'remove - Remove a package';
		$help['autoRemove'] = 'autoRemove - Remove unused plugins/vendors';
		$help['check'] = 'check - Verify no missing dependencies';
		$help['details'] = 'details - show package details';
		foreach ($methods as $method) {
			if (!isset($help[$method]) && $method[0] !== '_') {
				$help[$method] = $method;
			}
		}
		$this->out('Iq. Version ' . MiInstall::$version);
		$this->out('Usage: cake iq command');
		$this->out('');
		$this->out('Iq is a simple shell for downloading and installing apps, plugins and vendors');
		$this->out('');
		$this->out('Commands:');
		foreach($help as $message) {
			$this->out("\t" . $message);
		}
		$this->hr();
	}

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	public function initialize() {
		if (!empty($this->params['q']) || !empty($this->params['quiet']) || !empty($this->params['-quiet'])) {
			$this->settings['quiet'] = true;
		}
		return true;
	}

/**
 * main method
 *
 * @access public
 * @return void
 */
	public function main() {
		if (!$this->args || isset($this->params['h'])) {
			return $this->help();
		}
		$function = Inflector::camelize($this->args[0]);
		$function[0] = low($function[0]);
		unset($this->args[0]);
		if (!method_exists('MiInstall', $function)) {
			return $this->help();
		}
		$result = call_user_func_array(array('MiInstall', $function), $this->args);
		if ($result == true) {
			$this->out($function . ' completed successfully');
		}
		$this->_neatOut($result);
	}

/**
 * install method
 *
 * @return void
 * @access public
 */
	public function install($id = null) {
		if (!$id) {
			if (!$this->args) {
				$id = $this->_choosePackage();
			} else {
				$id = $this->args[0];
			}
		}
		$details = MiInstall::details($id);
		if (!empty($details['Depends'])) {
			$result = MiInstall::check($details['Depends']);
			if ($result !== true) {
				$dependsString = "\n" . implode($result, ",\n");
				$this->out("$id depends upon the following pacakges: {$dependsString}");
				if (!$missing) {
					$choice = $this->in('Missing dependencies will be installed automatically. Continue?', array('y', 'n'));
					if ($choice == 'n') {
						return false;
					}
				}
			}
		}
		$this->_chooseBase($details, $this->settings);
		$result = MiInstall::install($id, $this->settings);
		$this->_neatOut($result);
		/*
		if (empty($cmd)) {
			return false;
		}
		$return = MiInstall::_system($cmd);
		if ($return[0]) {
			return false;
		}
		if (file_exists($this->params['working'] . DS . 'config' . DS . 'database.php')) {
			$schema = $target . DS . 'config' . DS . 'schema' . DS . $id . '.php';
			if (file_exists($schema)) {
				$this->dbUpdate($id, $schema);
			}
		}
		*/
	}

/**
 * remove method
 *
 * @return void
 * @access public
 */
	public function remove() {
		if (!$this->args) {
			$id = $this->_choosePackage('to remove', true);
		} else {
			$id = $this->args[0];
		}
		if (!$id) {
			return false;
		}
		$details = MiInstall::details($id);
		if (!$details) {
			$this->out($id . ' does not appear to be installed. Nothing to do');
			return;
		}
		$choice = $this->in("Confirm removing {$id} ?", array('y', 'n'));
		if ($choice != 'y') {
			return false;
		}
		$result = MiInstall::remove($details);
		if (!$result) {
			$this->out("removing $id failed");
		}
		$this->_neatOut($result);
	}

/**
 * check method
 *
 * @return void
 * @access public
 */
	public function check() {
		$return = MiInstall::check();
		if ($return === true) {
			return $this->out('No missing dependencies found');
		}
		$this->out('Missing dependencies found:');
		foreach($return as $missing) {
			$this->out(' - ' . $missing);
		}
		$choice = $this->in("Install missing dependencies?", array('y', 'n'));
		if ($choice != 'y') {
			return false;
		}
		foreach($return as $missing) {
			$this->install($missing);
		}
	}

/**
 * details method
 *
 * @return void
 * @access public
 */
	public function details() {
		if (!$this->args) {
			$id = $this->_choosePackage();
		} else {
			$id = $this->args[0];
		}
		$details = MiInstall::details($id);
		$this->_neatOut($details);
	}

/**
 * chooseBase method
 *
 * @param array $details array()
 * @param array $settings array()
 * @return void
 * @access protected
 */
	protected function _chooseBase($details = array(), $settings = array()) {
		if (isset($settings['base'])) {
			return $settings['base'];
		}
		if (in_array($details['Type'], array('plugin', 'vendor'))) {
			$paths = App::path(Inflector::pluralize($details['Type']));
			foreach($paths as $i => $path) {
				if (!is_dir($path)) {
					unset($paths[$i]);
				}
			}
			if (count($paths) == 1) {
				return $this->settings['base'] = dirname(current($paths));
			}
			$this->out('Multiple possibilities:');
			$choices = array();
			foreach($paths as $i => $path) {
				$choices[] = $i + 1;
				$this->out($i + 1 . ' ' . $path);
			}
			$choice = $this->in('Install to which location?', $choices);
			if (isset($paths[$choice -1])) {
				return $this->settings['base'] = dirname($paths[$choice -1]);
			}
			return false;
		} elseif ($details['Type'] == 'virtual') {
			$paths = App::path('vendors');
			foreach($paths as $i => $path) {
				if (!is_dir($path)) {
					unset($paths[$i]);
				}
			}
			if (count($paths) == 1) {
				return $this->settings['base'] = dirname(current($paths));
			}
			foreach($paths as $i => $path) {
				$choices[] = $i + 1;
				$this->out($i + 1 . ' ' . dirname($path) . DS . '<type>' . DS . '<package>');
			}
			$choice = $this->in('Install to which location?', $choices);
			if (isset($paths[$choice -1])) {
				return $this->settings['base'] = dirname($paths[$choice -1]);
			}
		}
		return $this->settings['base'] = '';
	}

/**
 * choosePackage method
 *
 * @return void
 * @access protected
 */
	protected function _choosePackage($action = 'to install', $installed = false) {
		if ($installed) {
			$plugins = MiInstall::plugins();
			foreach($plugins as $plugin) {
				$this->out($plugin);
			}
			$choice = $this->in('Type the name of what you want ' . $action);
			if (!$choice) {
				$this->out('no choice made');
				$this->_stop();
			}
			return $choice;
		}
		$page = 0;
		$options = MiInstall::details($this->params);
		$options = array_chunk($options, 5, true);
		$choice = false;
		while(!$choice && $page < count($options)) {
			$this->out('id                  Description');
			$this->hr();
			foreach ($options[$page] as $id => $description) {
				$id = str_pad($id, 20);
				$this->out($id . $description);
			}
			$choice = $this->in("Type the name of what you want $action, or enter for more options");
			$page++;
		}
		if (!$choice || !MiInstall::details($choice)) {
			$this->out('no choice made');
			$this->_stop();
		}
		return $choice;
	}

/**
 * neatOut method
 *
 * @param mixed $out
 * @param string $indent ''
 * @return void
 * @access protected
 */
	protected function _neatOut($out, $indent = '') {
		if (is_numeric($out)) {
			return;
		}
		if (is_array($out)) {
			foreach($out as $key => $rows) {
				if (is_numeric($key)) {
					$this->_neatOut($rows, $indent);
					continue;
				}
				$rows = (array)$rows;
				if (count($rows) === 1) {
					$this->out($indent . $key . "\t" . $rows[0]);
					return;
				}
				$this->out($indent . $key);
				$this->_neatOut($rows, $indent . "  ");
			}
			return;
		}
		$this->out($indent . $out);
	}

/**
 * run method
 *
 * @return void
 * @access public
 */
	public function dbUpdate($id = null, $schema = null) {
		$options = array('name' => Inflector::camelize($id));
		$Schema = $this->Schema->load($options);
		if (!$Schema) {
			$this->err(sprintf(__('%s could not be loaded', true), $this->Schema->path . $this->Schema->file));
			$this->_stop();
		}

		$table = null;

		switch ($command) {
			case 'create':
				$this->__create($Schema, $table);
			break;
			case 'update':
				$this->__update($Schema, $table);
			break;
			default:
				$this->err(__('Command not found', true));
				$this->_stop();
		}
	}

/**
 * Schema Update database with Schema object
 * Should be called via the run method
 *
 * @access private
 */
	private function __update(&$Schema, $table = null) {
		$db =& ConnectionManager::getDataSource($this->Schema->connection);

		$this->out(__('Comparing Database to Schema...', true));
		$options = array();
		if (isset($this->params['f'])) {
			$options['models'] = false;
		}
		$Old = $this->Schema->read($options);
		$compare = $this->Schema->compare($Old, $Schema);

		$contents = array();

		if (empty($table)) {
			foreach ($compare as $table => $changes) {
				$contents[$table] = $db->alterSchema(array($table => $changes), $table);
			}
		} elseif (isset($compare[$table])) {
			$contents[$table] = $db->alterSchema(array($table => $compare[$table]), $table);
		}

		if (empty($contents)) {
			$this->out(__('Schema is up to date.', true));
			$this->_stop();
		}

		$this->out("\n" . __('The following statements will run.', true));
		$this->out(array_map('trim', $contents));
		if ('y' == $this->in(__('Are you sure you want to alter the tables?', true), array('y', 'n'), 'n')) {
			$this->out('');
			$this->out(__('Updating Database...', true));
			$this->__run($contents, 'update', $Schema);
		}

		$this->out(__('End update.', true));
	}

/**
 * Runs sql from __create() or __update()
 *
 * @access private
 */
	private function __run($contents, $event, &$Schema) {
		if (empty($contents)) {
			$this->err(__('Sql could not be run', true));
			return;
		}
		Configure::write('debug', 2);
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;

		foreach ($contents as $table => $sql) {
			if (empty($sql)) {
				$this->out(sprintf(__('%s is up to date.', true), $table));
			} else {
				if (!$Schema->before(array($event => $table))) {
					return false;
				}
				$error = null;
				if (!$db->execute($sql)) {
					$error = $table . ': '  . $db->lastError();
				}

				$Schema->after(array($event => $table, 'errors' => $error));

				if (!empty($error)) {
					$this->out($error);
				} else {
					$this->out(sprintf(__('%s updated.', true), $table));
				}
			}
		}
	}

/**
 * welcome method
 *
 * @return void
 * @access protected
 */
	public function _welcome() {
		if (!empty($this->settings['quiet'])) {
			return;
		}
		parent::_welcome();
	}
}
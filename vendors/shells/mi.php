<?php
/**
 * Short description for mi.php
 *
 * Long description for mi.php
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
 * @subpackage    mi.vendors.shells
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('Folder','File','model'.DS.'connection_manager');
App::import('Vendor', 'Mi.MiCache');

/**
 * MiShell class
 *
 * @uses          Shell
 * @package       mi
 * @subpackage    mi.vendors.shells
 */
class MiShell extends Shell {

/**
 * help method
 *
 * @access public
 * @return void
 */
	public function help() {
		$this->out('Mi. A utility for finding out information about your application. Usage:');
		if (!empty($this->args[0])) {
			switch (low($this->args[0])) {
			case 'controllers':
				$this->out('cake mi Controllers');
				$this->out('	- list all controllers and their paths');
				$this->out('cake mi Controllers <a plugin name>');
				$this->out('	- list all plugin controllers and their paths');
				$this->hr();
				return;
			case 'actions':
				$this->out('cake mi Actions');
				$this->out('	- select a controller, and list all actions');
				$this->out('cake mi Actions <a controller name>');
				$this->out('	- list all actions');
				$this->out('cake mi Actions <a controller name> admin');
				$this->out('	- list all admin actions');
				$this->hr();
				return;
			case 'models':
				$this->out('cake mi Models');
				$this->out('	- list all models and their paths');
				$this->out('cake mi Models <a plugin name>');
				$this->out('	- list all plugin models and their paths');
				$this->hr();
				return;
			case 'views':
				$this->out('cake mi Views');
				$this->out('	- select a controller, and list all view files and their paths');
				$this->out('cake mi Views <A controller name>');
				$this->out('	- list all views and their paths');
				$this->hr();
				return;
			case 'plugins':
				$this->out('cake mi Plugins');
				$this->out('	- list all plugins and their paths');
				$this->hr();
				return;
			case 'components':
				$this->out('cake mi Components');
				$this->out('	- list all components and their paths');
				$this->out('cake mi Components <a plugin name>');
				$this->out('	- list all plugin components and their paths');
				$this->hr();
				return;
			case 'behaviors':
				$this->out('cake mi Behaviors');
				$this->out('	- list all behaviors and their paths');
				$this->out('cake mi Components <a plugin name>');
				$this->out('	- list all plugin behaviors and their paths');
				$this->hr();
				return;
			case 'helpers':
				$this->out('cake mi Helpers');
				$this->out('	- list all helpers and their paths');
				$this->hr();
				return;
			case 'datasources':
				$this->out('cake mi Datasources');
				$this->out('	- list all datasources and their paths');
				$this->hr();
				return;
			case 'tables':
				$this->out('cake mi Tables');
				$this->out('	- select a connection, and list all tables it contains');
				$this->out('cake mi Tables <connection>');
				$this->out('	- list all tables for the specified connection');
				$this->out('cake mi Tables *');
				$this->out('	- list all tables, for all connections');
				$this->hr();
				return;
			default:
				$this->out(__d('mi', 'Sorry, no specific help on that', true));
			}
		}
		$this->out('cake mi');
		$this->out('	- Interactive mode');
		$this->out('cake mi help');
		$this->out('	- this text');
		$this->out('cake mi <what>');
		$this->out('	- Information about your selection');
		$this->out('cake mi help controllers');
		$this->out('cake mi help actions');
		$this->out('cake mi help models');
		$this->out('cake mi help views');
		$this->out('cake mi help plugins');
		$this->out('cake mi help components');
		$this->out('cake mi help behaviors');
		$this->out('cake mi help helpers');
		$this->out('cake mi help datasources');
		$this->out('cake mi help tables');
		$this->out('	- More detailed help on your selection');
		$this->hr();
	}

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	public function initialize() {
		return true;
	}

/**
 * main method
 *
 * @access public
 * @return void
 */
	public function main() {
		$shortKeys = array('e' => 'exclude');
		foreach ($this->params as $key => $value) {
			if (in_array($key, array('app', 'root', 'working'))) {
				continue;
			}
			if (isset($shortKeys[$key])) {
				$key = $shortKeys[$key];
			}
			if (isset($this->$key) && is_array($this->$key)) {
				$value = explode(',', $value);
			}
			$this->$key = $value;
		}
		if (!isset($this->args[0])) {
			$this->_choice();
		}
		if(low($this->args[0]) == 'help') {
			$this->help();
			return;
		}
		$function = Inflector::pluralize(Inflector::Classify($this->args[0]));
		$function[0] = low($function[0]);
		unset($this->args[0]);
		$admin = false;
		if ($function == 'actions' && empty($this->args[1]) && !empty($this->args[2]) && $this->args[2] == 'admin') {
			$this->args = array();
			$admin = true;
		}
		if (!class_exists('Mi')) {
			include_once(dirname(dirname(__FILE__)) . DS . 'mi.php');
		}
		if (!$this->args) {
			if ($function == 'actions') {
				$this->_controllers('Actions');
			} elseif ($function == 'views') {
				$this->_controllers('Views');
			} elseif ($function == 'tables') {
				$this->_connections();
			}
		}
		if ($admin) {
			$this->args[] = 'admin';
			}
		$result = call_user_func_array(array('Mi', $function), $this->args);
		$function = Inflector::humanize(Inflector::underscore(Inflector::singularize($function)));
		if (!$result) {
			return;
		}
		if (is_numeric(key($result))) {
			$this->out(str_pad(sprintf(__d('mi', '%1$s Results', true), $function), 50));
			$this->out(str_pad('', 49, '-'));
			foreach ($result as $path => $name) {
				$name = str_pad($name, 50);
				$this->out($name);
			}
			return;
		}
		$this->out(str_pad(sprintf(__d('mi', '%1$s Results', true), $function), 50) . 'Details');
		$this->out(str_pad('', 49, '-') . '|' . str_pad('', 49, '-'));
		foreach ($result as $path => $name) {
			$name = str_pad($name, 50);
			$this->out($name . $path);
		}
	}

/**
 * controllers method
 *
 * @param mixed $what
 * @return void
 * @access protected
 */
	protected function _controllers($what) {
		$this->out($what . ' for which controller?');
		$controllers = array_values(MiCache::mi('controllers'));
		foreach ($controllers as $i => $controller) {
			$this->out($i + 1 . '. ' . $controller);
		}
		$controller = '';
		while ($controller == '') {
			$controller = $this->in(__d('mi', "Enter a number from the list above, or 'q' to exit", true), null, 'q');
			if ($controller === 'q') {
				$this->out(__d('mi', "Exit", true));
				$this->_stop();
			}
			if ($controller == '' || !is_numeric($controller) || $controller > count($controllers)) {
				$this->out(__d('mi', 'Error:', true));
				$this->out(__d('mi', "The number you selected was not an option. Please try again.", true));
				$controller = '';
			}
		}
		$this->args[] = $controllers[$controller -1];
	}

/**
 * connections method
 *
 * @return void
 * @access protected
 */
	protected function _connections() {
		$this->out('Which connection do you want to see?');
		$sources =& ConnectionManager::enumConnectionObjects();
		$sources = array_keys($sources);
		foreach ($sources as $i => $source) {
			$this->out($i + 1 . '. ' . $source);
		}
		$this->out($i + 2 . '. * - list all');
		$sources[] = '*';
		$default = array_search('default', $sources) + 1;
		$source = '';
		while ($source == '') {
			$source = $this->in(__d('mi', "Enter a number from the list above, or 'q' to exit", true), null, $default);
			if ($source === 'q') {
				$this->out(__d('mi', "Exit", true));
				$this->_stop();
			}
			if ($source == '' || !is_numeric($source) || $source > count($sources)) {
				$this->out(__d('mi', 'Error:', true));
				$this->out(__d('mi', "The number you selected was not an option. Please try again.", true));
				$controller = '';
			}
		}
		$this->args[] = $sources[$source -1];
	}

/**
 * choice method
 *
 * @return void
 * @access protected
 */
	protected function _choice() {
		$this->out('What do you want to see?');
		$options = array(
			'c' => '[C]ontrollers',
			'a' => '[A]ctions',
			'i' => 'Adm[i]n actions',
			'm' => '[M]odels',
			'v' => '[V]iews',
			'p' => '[P]lugins',
			'o' => 'C[o]mponents',
			'b' => '[B]ehaviors',
			'h' => '[H]elpers',
			'd' => '[D]atasources',
			't' => '[T]ables',
			'h' => '[H]elp',
		);
		foreach ($options as $option) {
			$this->out($option);
		}
		$option = '';
		while ($option == '') {
			$option = low($this->in(__d('mi', "Make a selection, or 'q' to exit", true), null, 'q'));
			if ($option === 'q') {
				$this->out(__d('mi', "Exit", true));
				$this->_stop();
			}
			if ($option == '' || !isset($options[$option[0]])) {
				$this->out(__d('mi', 'Error:', true));
				$this->out(__d('mi', "The option you supplied was empty, or not an option. Please try again.", true));
				$option = '';
			}
		}
		if ($option[0] != 'i') {
			$this->args[] = str_replace(array('[', ']'), '', $options[$option[0]]);
		} else {
			$this->args[] = 'actions';
			$this->args[] = null;
			$this->args[] = 'admin';
		}
	}
}
?>
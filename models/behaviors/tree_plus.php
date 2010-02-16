<?php
/**
 * Short description for tree_plus.php
 *
 * Long description for tree_plus.php
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
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * TreePlusBehavior class
 *
 * @uses          ModelBehavior
 * @package       mi
 * @subpackage    mi.models.behaviors
 */
class TreePlusBehavior extends ModelBehavior {

/**
 * name property
 *
 * @var string 'TreePlus'
 * @access public
 */
	var $name = 'TreePlus';

/**
 * afterSave method
 *
 * @param mixed $Model
 * @return void
 * @access public
 */
	function afterSave(&$Model) {
		if ($Model->hasField('depth')) {
			$this->resetDepths($Model);
		}
	}

/**
 * resetDepths method
 *
 * Adding single table update. Typically ~20 times faster than using a loop
 *
 * @param mixed $id
 * @return void
 * @access public
 */
	function resetDepths(&$Model, $id = null) {
		if (!$id) {
			$table = $Model->table;
			$Model->query("UPDATE $table SET depth = (
				SELECT wrapper.parents FROM (
					SELECT
						this.id as row,
						COUNT(parent.id) as parents
					FROM
						$table AS this
					LEFT JOIN $table AS parent ON (
						parent.lft < this.lft AND
						parent.rght > this.rght)
					GROUP BY
						this.id
				) AS wrapper WHERE wrapper.row = $table.id)");
			$db =& ConnectionManager::getDataSource($Model->useDbConfig);
			if (!$db->error) {
				return true;
			}
			$max = ini_get('max_execution_time');
			if ($max) {
				set_time_limit (max($Model->find('count') / 10), 30);
			}
			$Model->updateAll(array('depth' => 0));
			$Model->displayField = 'id';
			$nodes = $Model->find('list', compact('conditions'));
			foreach ($nodes as $id => $_) {
				$Model->resetDepths($id);
			}
			return true;
		}
		$Model->id = $id;
		$path = $Model->getPath($id, array('id'));
		$Model->saveField('depth', count($path));
		return true;
	}
}
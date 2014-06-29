<?php
/**
 *
 * TaskType model for the DevTrack system
 * Stores the Types for Tasks in the system
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     DevTrack Development Team 2012
 * @link          http://github.com/SourceKettle/devtrack
 * @package       DevTrack.Model
 * @since         DevTrack v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @property Task $Task
 */
App::uses('AppModel', 'Model');

class TaskType extends AppModel {

/**
 * Display field
 */
	public $displayField = 'name';

/**
 * hasMany associations
 */
	public $hasMany = array(
		'Task' => array(
			'className' => 'Task',
			'foreignKey' => 'task_type_id',
			'dependent' => false,
		)
	);

	public function nameToID($type_name) {
		$found = $this->find('first', array('conditions' => array('LOWER(name)' => strtolower(trim($type_name)))));
		if(empty($found)){
			return 0;
		}
		return $found['TaskType']['id'];
	}

}

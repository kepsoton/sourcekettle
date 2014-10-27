<?php
/**
 * TaskFixture
 *
 */
class TaskFixture extends CakeTestFixture {

	// Force InnoDB table type so we can test transactions
	public function create($db) {
	    $this->fields['tableParameters']['engine'] = 'InnoDB';
	    return parent::create($db);
	}
/**
 * Import
 *
 * @var array
 */
	public $import = array('model' => 'Task');

	public $records = array(

		array(
			'id' => 1,
			'project_id' => 2,
			'owner_id' => 2,
			'task_type_id' => 1,
			'task_status_id' => 3,
			'task_priority_id' => 2,
			'assignee_id' => 2,
			'milestone_id' => 2,
			'time_estimate' => 160,
			'story_points' => 12,
			'subject' => 'Resolved Major Task 1 for milestone 2',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 2,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 1,
			'task_priority_id' => 1,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 240,
			'story_points' => 0,
			'subject' => 'Open Minor Task 2 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 3,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 2,
			'task_priority_id' => 3,
			'assignee_id' => 0,
			'milestone_id' => 0,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'In Progress Urgent Task 3 for no milestone',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 4,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 2,
			'task_priority_id' => 3,
			'assignee_id' => 2,
			'milestone_id' => 1,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'In progress Urgent Task 4 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 5,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 1,
			'task_priority_id' => 3,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 1245,
			'story_points' => 0,
			'subject' => 'Open Urgent Task 5 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 6,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 2,
			'task_priority_id' => 4,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'In Progress Blocker Task 7 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 7,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 3,
			'task_priority_id' => 2,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'Resolved Major Task 7 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 8,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 4,
			'task_priority_id' => 4,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'Closed Blocker Task 8 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 9,
			'project_id' => 2,
			'owner_id' => 3,
			'task_type_id' => 1,
			'task_status_id' => 5,
			'task_priority_id' => 1,
			'assignee_id' => 0,
			'milestone_id' => 1,
			'time_estimate' => 145,
			'story_points' => 0,
			'subject' => 'Dropped Minor Task 9 for milestone 1',
			'description' => 'lorem ipsum dolor sit amet',
		),
		array(
			'id' => 10,
			'project_id' => 2,
			'owner_id' => 2,
			'task_type_id' => 1,
			'task_status_id' => 2,
			'task_priority_id' => 2,
			'assignee_id' => 2,
			'milestone_id' => 2,
			'time_estimate' => 160,
			'story_points' => 14,
			'subject' => 'In Progress Major Task 1 for milestone 2',
			'description' => 'lorem ipsum dolor sit amet',
		),
	);

}

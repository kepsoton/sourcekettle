<?php
/**
 *
 * MilestonesController Controller for the SourceKettle system
 * Provides the hard-graft control of the Milestones for projects
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	 SourceKettle Development Team 2012
 * @link			http://github.com/SourceKettle/sourcekettle
 * @package		SourceKettle.Controller
 * @since		 SourceKettle v 0.1
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppProjectController', 'Controller');
//App::uses('Project', 'Model');

class MilestonesController extends AppProjectController {

	public $helpers = array('Task');

	public $uses = array('Milestone', 'Project');

	// Which actions need which authorization levels (read-access, write-access, admin-access)
	protected function _getAuthorizationMapping() {
		return array(
			'index'  => 'read',
			'open'  => 'read',
			'closed'  => 'read',
			'view'   => 'read',
			'plan'   => 'write',
			'add'   => 'write',
			'edit'   => 'write',
			'close'   => 'write',
			'reopen'   => 'write',
			'burndown' => 'read',
			'delete'   => 'write',
		);
	}

	public function isAuthorized($user) {
		if (!$this->sourcekettle_config['Features']['task_enabled']['value']) {
			if ($this->sourcekettle_config['Features']['task_enabled']['source'] == "Project-specific settings") {
				throw new ForbiddenException(__('This project does not have task tracking enabled. Please contact a project administrator to enable task tracking.'));
			} else {
				throw new ForbiddenException(__('This system does not allow task tracking. Please contact a system administrator to enable task tracking.'));
			}
		}

		return parent::isAuthorized($user);
	}
/**
 * beforeFilter function.
 *
 * @access public
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
	}

/**
 * index method
 *
 * @return void
 */
	public function index($project = null) {
		return $this->redirect(array('project' => $project, 'action' => 'open'));
	}

/**
 * index method
 *
 * @return void
 */
	public function open($project = null) {
		$project = $this->_getProject($project);
		$milestones = $this->Milestone->getOpenMilestones();
		$this->set('milestones', $milestones);
		$this->render('open_closed');
	}

/**
 * index method
 *
 * @return void
 */
	public function closed($project = null) {
		$project = $this->_getProject($project);
		$milestones = $this->Milestone->getClosedMilestones();
		$this->set('milestones', $milestones);
		$this->render('open_closed');
	}

/**
 * view method
 *
 * @return void
 */
	public function view($project = null, $id = null) {
		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);

		$this->set('title_for_layout', $milestone['Milestone']['subject']);

		$backlog = $this->Milestone->tasksOfStatusForMilestone($id, 'open');
		$inProgress = $this->Milestone->tasksOfStatusForMilestone($id, 'in progress');
		$completed = $this->Milestone->tasksOfStatusForMilestone($id, array('resolved', 'closed'));
		$iceBox = $this->Milestone->tasksOfStatusForMilestone($id, 'dropped');

		// Final value is min size of the board
		$max = max(count($backlog), count($inProgress), count($completed), 3);

		// If the user has write access, they can drag and drop tasks
		$draggable = $this->Milestone->Project->hasWrite($this->Auth->user('id'));
		$this->set('draggable', $draggable);

		// Calculate number of points complete/total for the milestone
		$points_total = 0;
		foreach ($milestone['Tasks'] as $k => $v){
			if ($k == 'dropped') continue;
			$points_total += $v['points'];
		}
		$points_todo = $milestone['Tasks']['in progress']['points'] + $milestone['Tasks']['open']['points'];
		$points_complete = $points_total - $points_todo;

		$this->set('milestone', $milestone);
		$this->set('backlog_empty', $max - count($backlog));
		$this->set('inProgress_empty', $max - count($inProgress));
		$this->set('completed_empty', $max - count($completed));
		$this->set(compact('backlog', 'inProgress', 'completed', 'iceBox', 'points_complete', 'points_todo', 'points_total'));
	}

/**
 * plan method
 *
 * @return void
 */
	public function plan($project = null, $id = null) {
		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);

		$this->set('title_for_layout', $milestone['Milestone']['subject']);

		$mustHave   = $this->Milestone->tasksOfPriorityForMilestone($id, 'blocker');
		$shouldHave = $this->Milestone->tasksOfPriorityForMilestone($id, 'urgent');
		$couldHave  = $this->Milestone->tasksOfPriorityForMilestone($id, 'major');
		$mightHave  = $this->Milestone->tasksOfPriorityForMilestone($id, 'minor');

		$this->Project->id = $project['Project']['id'];
		$wontHave   = $this->Project->getProjectBacklog();

		$this->set('milestone', $milestone);

		// If the user has write access, they can drag and drop tasks
		$draggable = $this->Milestone->Project->hasWrite($this->Auth->user('id'));
		$this->set('draggable', $draggable);

		$this->set(compact('mustHave', 'shouldHave', 'couldHave', 'mightHave', 'wontHave'));
	}

/**
 * add method
 *
 * @return void
 */
	public function add($project = null) {
		$project = $this->_getProject($project);

		if ($this->request->is('post')) {
			$this->Milestone->create();

			$this->request->data['Milestone']['project_id'] = $project['Project']['id'];

			// Force new milestones into the 'open' state, this makes the most sense...
			$this->request->data['Milestone']['is_open'] = true;

			if ($this->Flash->c($this->Milestone->save($this->request->data))) {
				return $this->redirect(array('project' => $project['Project']['name'], 'action' => 'view', $this->Milestone->id));
			}
		}
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($project = null, $id = null) {
		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);

		if ($this->request->is('post') || $this->request->is('put')) {
			$this->request->data['Milestone']['project_id'] = $project['Project']['id'];

			if ($this->Flash->u($this->Milestone->save($this->request->data))) {
				return $this->redirect(array('project' => $project['Project']['name'], 'action' => 'view', $id));
			}
		} else {
			$this->request->data = $milestone;
		}
		$this->set('milestone', $milestone);
	}

/**
 * close method
 *
 * @param string $id
 * @return void
 */
	public function close($project = null, $id = null) {
		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);
		if (!$milestone['Milestone']['is_open']) {
			throw new NotFoundException(__("Cannot close milestone - it is already closed!"));
		}

		if ($this->request->is('post') || $this->request->is('put')) {

			if (!isset($this->request->data['Milestone']['new_milestone'])) {
				$newMilestone = 0;
			} else {
				$newMilestone = $this->request->data['Milestone']['new_milestone'];
			}

			// Manual transactions used here for good reason:
			// saving all related stuff fails, as we're changing the milestone_id
			// i.e. making it no longer related. So, let's do it this way.
			$dataSource = $this->Milestone->getDataSource();
			$dataSource->begin();

			// First attempt to shift the tasks to the new milestone ID
			if (!$this->Flash->u($this->Milestone->shiftTasks($id, $newMilestone))) {
				$dataSource->rollback();

			// Now update the milestone status itself
			} else {
				$milestone = $this->Milestone->open($id);
				$milestone['Milestone']['is_open'] = 0;
				if (!$this->Flash->u($this->Milestone->save($milestone))) {
					$dataSource->rollback();
				} else {
					$dataSource->commit();
					return $this->redirect(array('project' => $project['Project']['name'], 'action' => 'index'));
				}
			}

		} else {
			$this->request->data = $milestone;
		}

		// For the form, build a list of other open milestones we can attach tasks to
		$otherMilestones = array(0 => '(no milestone)');
		foreach ($this->Milestone->getOpenMilestones() as $m) {
			$otherMilestones[$m['Milestone']['id']] = $m['Milestone']['subject'];
		}

		unset($otherMilestones[$id]);
		ksort($otherMilestones);

		$this->set('other_milestones', $otherMilestones);
		$this->set('milestone', $milestone);
		$this->set('name', $milestone['Milestone']['subject']);
	}

/**
 * reopen method
 *
 * @param string $id
 * @return void
 */
	public function reopen($project = null, $id = null) {
		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);

		if($milestone['Milestone']['is_open']){
			throw new NotFoundException(__("Cannot re-open milestone - it is already open!"));
		}

		if ($this->request->is('post') || $this->request->is('put')) {

			$milestone = $this->Milestone->open($id);
			$milestone['Milestone']['is_open'] = 1;

			if ($this->Flash->u($this->Milestone->save($milestone))) {
				return $this->redirect(array('project' => $project['Project']['name'], 'action' => 'index'));
			}

		} else {
			$this->request->data = $milestone;
		}
		$this->set('milestone', $milestone);
		$this->set('name', $milestone['Milestone']['subject']);
	}

/**
 * delete method
 *
 * @param string $id
 * @return void
 */
	public function delete($project = null, $id = null) {

		$project = $this->_getProject($project);
		$milestone = $this->Milestone->open($id);

		if ($this->request->is('post')) {

			$newMilestone = @$this->request->data['Milestone']['new_milestone'];

			$dataSource = $this->Milestone->getDataSource();
			$dataSource->begin();

			// First attempt to shift the tasks to the new milestone ID
			if (!$this->Flash->u($this->Milestone->shiftTasks($id, $newMilestone, true))) {
				$dataSource->rollback();

			// Now delete the milestone.
			} else {
				$milestone = $this->Milestone->open($id);
				if (!$this->Flash->d($this->Milestone->delete())) {
					$dataSource->rollback();
				} else {
					$dataSource->commit();
					return $this->redirect(array('project' => $project['Project']['name'], 'action' => 'index'));
				}
			}

		} else {
			$this->request->data = $milestone;
		}

		// For the form, build a list of other open milestones we can attach tasks to
		$otherMilestones = array(0 => '(no milestone)');
		foreach ($this->Milestone->getOpenMilestones() as $m) {
			$otherMilestones[$m['Milestone']['id']] = $m['Milestone']['subject'];
		}
		unset($otherMilestones[$id]);
		ksort($otherMilestones);

		$this->set('other_milestones', $otherMilestones);
		$this->set('milestone', $milestone);
		$this->set('name', $milestone['Milestone']['subject']);
	}

	public function burndown($project = null, $id = null) {

		$milestone = $this->Milestone->open($id);

		$now = new DateTime();

		// Start date: provided in GET or POST data, or use the milestone creation date
		if (isset($this->request->query['start'])) {
			$start = new DateTime($this->request->query['start']);
		} elseif (isset($this->request->data['start'])) {
			$start = new DateTime($this->request->data['start']);
		} elseif (isset($milestone['Milestone']['starts']) && $milestone['Milestone']['starts'] != '0000-00-00' ) {
			$start = new DateTime($milestone['Milestone']['starts']);
		} else {
			$start = new DateTime($milestone['Milestone']['created']);
		}

		// End date: provided in GET or POST data, or use the due date if it's in the future,
		// finally falling back to the current date
		if (isset($this->request->query['end'])) {
			$end = new DateTime($this->request->query['end']);
		} elseif (isset($this->request->data['end'])) {
			$end = new DateTime($this->request->data['end']);
		} elseif (isset($milestone['Milestone']['due']) ) {
			$end = new DateTime($milestone['Milestone']['due']);
			if ($end < $now) {
				$end = $now;
			}
		} else {
			$end = $now;
		}


		// Find logged changes between the start and end dates
		$log = array(
			'days'    => array(),
			'tasks'   => array('open' => array(), 'closed' => array()),
			'minutes' => array('open' => array(), 'closed' => array()),
			'points'  => array('open' => array(), 'closed' => array()),
			'highs'   => array('tasks' => 0, 'minutes' => 0, 'points' => 0),
		);

		$entries = $this->Milestone->MilestoneBurndownLog->find('all', array(
			'conditions' => array(
				'milestone_id' => $id,
				'timestamp <=' => $end->format('Y-m-d 23:59:59'),
				'timestamp >=' => $start->format('Y-m-d 00:00:00'),
			),
			'fields' => array(
				'timestamp',
				'DATE(timestamp) AS day',
				'open_task_count',
				'open_minutes_count',
				'open_points_count',
				'closed_task_count',
				'closed_minutes_count',
				'closed_points_count',
			),
			'order' => array('timestamp'),
			'recursive' => -1,
		));
		
		// Get the last entry for each day - so we're plotting the latest available info for the day
		$day = $start->format('Y-m-d');
		foreach ($entries as $entry) {
			if ($entry[0]['day'] != $day) {
				// Padding, so we catch days where nothing changed
				$last = new DateTime($day, new DateTimeZone('UTC'));
				$new  = new DateTime($entry[0]['day'], new DateTimeZone('UTC'));

				// If padding is not needed, i.e. we got two consecutive days, $last is the same as $new now
				$last->add(new DateInterval('P1D'));

				// If not, keep incrementing $last until they match and copy the previous day's data
				while ($new->diff($last)->d > 0) {
					$str = $last->format('Y-m-d');
					$log['days'][] = $str;
					$log['tasks']['open'][$str]     = @$log['tasks']['open'][$day];
					$log['minutes']['open'][$str]   = @$log['minutes']['open'][$day];
					$log['points']['open'][$str]    = @$log['points']['open'][$day];
					$log['tasks']['closed'][$str]   = @$log['tasks']['closed'][$day];
					$log['minutes']['closed'][$str] = @$log['minutes']['closed'][$day];
					$log['points']['closed'][$str]  = @$log['points']['closed'][$day];
					$last->add(new DateInterval('P1D'));
				}

				// Finally, whether padding was added or not, add our new entry to the days list
				$day = $entry[0]['day'];
				$log['days'][] = $day;
			}

			// Add in all the counts - note that if we get multiple counts for a single day, we'll
			// overwrite until we only have th elatest count for the day.
			$log['tasks']['open'][$day]     = $entry['MilestoneBurndownLog']['open_task_count'];
			$log['minutes']['open'][$day]   = $entry['MilestoneBurndownLog']['open_minutes_count'];
			$log['points']['open'][$day]    = $entry['MilestoneBurndownLog']['open_points_count'];
			$log['tasks']['closed'][$day]   = $entry['MilestoneBurndownLog']['closed_task_count'];
			$log['minutes']['closed'][$day] = $entry['MilestoneBurndownLog']['closed_minutes_count'];
			$log['points']['closed'][$day]  = $entry['MilestoneBurndownLog']['closed_points_count'];

			// Find the "high point" to plot the ideal burndown line
			if ($log['tasks']['open'][$day] > $log['highs']['tasks']) {
				$log['highs']['tasks'] = $log['tasks']['open'][$day];
			}
			if ($log['minutes']['open'][$day] > $log['highs']['minutes']) {
				$log['highs']['minutes'] = $log['minutes']['open'][$day];
			}
			if ($log['points']['open'][$day] > $log['highs']['points']) {
				$log['highs']['points'] = $log['points']['open'][$day];
			}
		}

		$this->set(compact('milestone', 'log'));

	}

}

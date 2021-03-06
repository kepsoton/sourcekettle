<?php
App::uses('AppController', 'Controller');
/**
 * ProjectGroups Controller
 *
 * @property ProjectGroup $ProjectGroup
 */
class ProjectGroupsController extends AppController {

	public $uses = array(
		'ProjectGroup', 'Project', 'Team', 'GroupCollaboratingTeam'
	);

	public function isAuthorized($user) {

		// No public pages here, must be logged in
		if (empty($user)) {
			return false;
		}

		// Deactivated users explicitly do not get access
		// (NB they should not be able to log in anyway, of course!)
		if (@$user['is_active'] != 1) {
			return false;
		}

		// If you are logged in, you can view project groups and autocomplete project group names
		if ($this->action == 'view' || $this->action == 'api_autocomplete') {
			return true;
		}

		// Sysadmins can do anything...
		if (@$user['is_admin'] == 1) {
			return true;
		}

		// Admins only for all but viewing project groups
		return false;
	}
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->set('pageTitle', __('Administration'));
		$this->set('subTitle', __('project groups'));
		$this->ProjectGroup->contain();
		$this->set('projectGroups', $this->paginate());
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($group = null) {
		if (!is_numeric($group)) {
			$group = $this->ProjectGroup->field('id', array('name' => $group));
		}

		$projectGroup = $this->ProjectGroup->findById($group);

		if (empty($projectGroup)) {
			throw new NotFoundException(__('Invalid group'));
		}
		$this->set('pageTitle', __('Project group'));
		$this->set('subTitle', $projectGroup['ProjectGroup']['name']);

		// TODO should really be pulled in by the model
		foreach ($projectGroup['GroupCollaboratingTeam'] as $i => $ct) {
			$projectGroup['GroupCollaboratingTeam'][$i]['team_name'] = $this->ProjectGroup->GroupCollaboratingTeam->Team->field('name', array('id' => $ct['team_id']));
			$projectGroup['GroupCollaboratingTeam'][$i]['access_level'] = $this->ProjectGroup->Project->Collaborator->accessLevelIdToName($ct['access_level']);
		}
		$this->set(compact('projectGroup'));
	}

	// No special admin permission needed to view teams
	public function admin_view($group = null) {
		return $this->redirect(array('action' => 'view', 'group' => $group, 'admin' => false));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		$this->set('pageTitle', __('Project group'));
		$this->set('subTitle', __('add a project group'));
		if ($this->request->is('post')) {
			$this->ProjectGroup->create();
			$data = $this->_cleanPost(array("ProjectGroup.name", "ProjectGroup.description", "Project"));
			if ($this->ProjectGroup->save($data)) {
				$this->Session->setFlash(__('The project group has been saved'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The project group could not be saved. Please try again.'));
			}
		}
		$members = array();
		$nonMembers = $this->ProjectGroup->Project->find('list');
		$admins = array();
		$users = array();
		$guests = array();
		$otherTeams = $this->Team->find('list');
		$this->set(compact('members', 'nonMembers', 'admins', 'users', 'guests', 'otherTeams'));
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		$this->set('pageTitle', __('Administration'));
		$this->set('subTitle', __('organise your projects'));
		if (!$this->ProjectGroup->exists($id)) {
			throw new NotFoundException(__('Invalid project group'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {

			// This is a fudge and a half... for anything where we're *changing* the level,
			// there will be an existing entry in the database - retrieve the IDs.
			$data = $this->_cleanPost(array("ProjectGroup.name", "ProjectGroup.description"));
			$data['ProjectGroup']['id'] = $id;
	
			// TODO better cleaning here
			$data['GroupCollaboratingTeam'] = @$this->request->data['GroupCollaboratingTeam'];
			$data['Project'] = $this->_cleanPost(array("Project"));
			if (isset($data['GroupCollaboratingTeam'])) {
				foreach ($data['GroupCollaboratingTeam'] as $x => $gct) {
					$gct = $this->GroupCollaboratingTeam->findByTeamIdAndProjectGroupId($gct['team_id'], $id);
					if (isset($gct['GroupCollaboratingTeam'])) {
						$data['GroupCollaboratingTeam'][$x]['id'] = $gct['GroupCollaboratingTeam']['id'];
					}
				}
			}
			if ($this->ProjectGroup->saveAll($data)) {
				$this->Session->setFlash(__('The project group has been saved'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The project group could not be saved. Please try again.'));
				// TODO a bit of a fudge, we're probably passing the group list in a stupidly wrong format, this avoids the code below not getting a "proper" array of projects... blech
				$this->request->data['Project'] = array();
				$this->request->data['GroupCollaboratingTeam'] = array();
			}
		} else {
			$options = array('conditions' => array('ProjectGroup.' . $this->ProjectGroup->primaryKey => $id));
			$this->request->data = $this->ProjectGroup->find('first', $options);
		}

		$members = array();
		$nonMembers = $this->ProjectGroup->Project->find('list');
		foreach ($this->request->data['Project'] as $member) {
			$member = $member['id'];
			
			$members[$member] = $nonMembers[$member];
			unset($nonMembers[$member]);
		}

		$admins = array();
		$users = array();
		$guests = array();
		$otherTeams = $this->Team->find('list');
		foreach ($this->request->data['GroupCollaboratingTeam'] as $team) {
			if ($team['access_level'] == 0) {
				$guests[$team['team_id']] = $otherTeams[$team['team_id']];
			} elseif ($team['access_level'] == 1) {
				$users[$team['team_id']] = $otherTeams[$team['team_id']];
			} elseif ($team['access_level'] == 2) {
				$admins[$team['team_id']] = $otherTeams[$team['team_id']];
			}
			unset($otherTeams[$team['team_id']]);
		}
		$this->set(compact('members', 'nonMembers', 'admins', 'users', 'guests', 'otherTeams'));
	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @throws MethodNotAllowedException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->ProjectGroup->id = $id;
		if (!$this->ProjectGroup->exists()) {
			throw new NotFoundException(__('Invalid project group'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->ProjectGroup->delete()) {
			$this->Session->setFlash(__('Project group deleted'));
			return $this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Project group was not deleted'));
		return $this->redirect(array('action' => 'index'));
	}

/**
 * api_autocomplete function.
 *
 * @access public
 * @return void
 */
	public function api_autocomplete() {
		$this->layout = 'ajax';

		$this->User->contain();
		$data = array('projectGroups' => array());

		if (isset($this->request->query['query'])
			&& $this->request->query['query'] != null
			&& strlen($this->request->query['query']) > 0) {

			$query = strtolower($this->request->query['query']);

			// At 3 characters, start matching anywhere within the name
			if(strlen($query) > 2){
				$query = "%$query%";
			} else {
				$query = "$query%";
			}

			$project_groups = $this->ProjectGroup->find(
				"all",
				array(
					'conditions' => array(
						'OR' => array(
							'LOWER(ProjectGroup.name) LIKE' => $query,
							'LOWER(ProjectGroup.description) LIKE' => $query,
						)
					),
					'fields' => array(
						'ProjectGroup.name',
						'ProjectGroup.description',
					)
				)
			);
			foreach ($project_groups as $project_group) {
				$data['projectGroups'][] = $project_group['ProjectGroup']['name'] . " [" . $project_group['ProjectGroup']['description'] . "]";
			}

		}
		$this->set('data', $data);
		$this->render('/Elements/json');
	}
}

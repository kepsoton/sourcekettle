<?php
App::uses('AppController', 'Controller');
/**
 * ProjectGroups Controller
 *
 * @property ProjectGroup $ProjectGroup
 */
class ProjectGroupsController extends AppController {

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->ProjectGroup->recursive = 0;
		$this->set('projectGroups', $this->paginate());
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!$this->ProjectGroup->exists($id)) {
			throw new NotFoundException(__('Invalid project group'));
		}
		$options = array('conditions' => array('ProjectGroup.' . $this->ProjectGroup->primaryKey => $id));
		$this->set('projectGroup', $this->ProjectGroup->find('first', $options));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->ProjectGroup->create();
			if ($this->ProjectGroup->save($this->request->data)) {
				$this->Session->setFlash(__('The project group has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The project group could not be saved. Please try again.'));
			}
		}
		$projects = $this->ProjectGroup->Project->find('list');
		$this->set(compact('projects'));
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!$this->ProjectGroup->exists($id)) {
			throw new NotFoundException(__('Invalid project group'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->ProjectGroup->save($this->request->data)) {
				$this->Session->setFlash(__('The project group has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The project group could not be saved. Please try again.'));
			}
		} else {
			$options = array('conditions' => array('ProjectGroup.' . $this->ProjectGroup->primaryKey => $id));
			$this->request->data = $this->ProjectGroup->find('first', $options);
		}
		$projects = $this->ProjectGroup->Project->find('list');
		$this->set(compact('projects'));
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
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Project group was not deleted'));
		$this->redirect(array('action' => 'index'));
	}

/**
 * api_autocomplete function.
 *
 * @access public
 * @return void
 */
	public function api_autocomplete() {
		$this->layout = 'ajax';

		$this->User->recursive = -1;
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
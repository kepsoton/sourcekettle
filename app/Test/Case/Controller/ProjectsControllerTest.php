<?php
App::uses('ProjectsController', 'Controller');
require_once(__DIR__ . DS . 'AppControllerTest.php');


/**
 * ProjectsController Test Case
 *
 */
class ProjectsControllerTestCase extends AppControllerTest {

/**
 * Fixtures
 *
 * @var array
 */
    public $fixtures = array(
		'app.setting',
		'app.project',
		'app.project_history',
		'app.repo_type',
		'app.collaborator',
		'app.user',
		'app.task',
		'app.task_type',
		'app.task_dependency',
		'app.task_comment',
		'app.task_status',
		'app.task_priority',
		'app.time',
		'app.attachment',
		'app.source',
		'app.milestone',
		'app.email_confirmation_key',
		'app.ssh_key',
		'app.api_key',
		'app.lost_password_key',
	);

	public function setUp() {
		parent::setUp("Projects");
	}


	public function testIndexNotLoggedIn() {

		// Cannot see the page when not logged in
		$this->testAction('/projects', array('method' => 'get', 'return' => 'vars'));
		$this->assertEquals($this->vars['projects'], array());
	}

	public function testIndexSystemAdmin() {

		// Log in as a system administrator - we should still only see "my" projects, not everyone's
		$this->_fakeLogin(5);
		// Perform the action, and check the user was authorized
		$ret = $this->testAction('/projects', array('method' => 'get', 'return' => 'view'));
		$this->assertAuthorized();

		// Check the page content looks roughly OK
		$this->assertContains('<h1>My Projects', $this->view);
		$this->assertRegexp('/<a href=".*\/project\/public\/." class="project-link">public<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/project\/private\/." class="project-link">private<\/a>/', $this->view);

		// Check the project list looks sane and has only the right entries/access levels
		$this->assertNotNull($this->vars['projects']);
		$this->assertEqual(count($this->vars['projects']), 2, "Incorrect number of projects returned");

		// Check each project 
		foreach ($this->vars['projects'] as $project) {

			// We should be a collaborator
			if (!isset($project['User']) || $project['User']['id'] != 5) {
				$this->assertTrue(false, "A project for another collaborator was found");
			}

			// We should only get these two, and with the correct access levels
			if ($project['Project']['id'] == 1 && $project['Collaborator']['access_level'] == 2) {
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 2 && $project['Collaborator']['access_level'] == 1) {
				$this->assertTrue(true, "Impossible to fail");
			} else {
				$this->assertTrue(false, "An unexpected project ID (".$project['Project']['id'].") or access level (".$project['Collaborator']['access_level'].") was retrieved");
			}
		}
	}

	public function testIndexProjectGuest() {

		// Log in as a guest on one project
		$this->_fakeLogin(3);

		$this->testAction('/projects', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		
		$this->assertContains('<h1>My Projects', $this->view);
		$this->assertRegexp('/<a href=".*\/project\/private\/." class="project-link">private<\/a>/', $this->view);

		// Check the project list looks sane and has only the right entries/access levels
		$this->assertNotNull($this->vars['projects']);
		$this->assertEqual(count($this->vars['projects']), 1, "Incorrect number of projects returned");

		// Check each project 
		foreach ($this->vars['projects'] as $project) {

			// We should be a collaborator
			if (!isset($project['User']) || $project['User']['id'] != 3) {
				$this->assertTrue(false, "A project for another collaborator was found");
			}

			// We should only get one, and with the correct access level
			if ($project['Project']['id'] == 1 && $project['Collaborator']['access_level'] == 0) {
				$this->assertTrue(true, "Impossible to fail");
			} else {
				$this->assertTrue(false, "An unexpected project ID (".$project['Project']['id'].") or access level (".$project['Collaborator']['access_level'].") was retrieved");
			}
		}
	}

	public function testPublicIndexSystemAdmin() {

		// Log in as a system administrator - we should still only see public projects, not everything
		$this->_fakeLogin(5);

		$this->testAction('/projects/public_projects', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		$this->assertContains('<h1>Public Projects', $this->view);

		$this->assertRegexp('/<a href=".*\/project\/public\/." class="project-link">public<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/project\/personal_public\/." class="project-link">personal_public<\/a>/', $this->view);

		// Check the project list looks sane and has only the right entries/access levels
		$this->assertNotNull($this->vars['projects']);
		$this->assertEqual(count($this->vars['projects']), 2, "Incorrect number of projects returned");

		// Check each project 
		foreach ($this->vars['projects'] as $project) {

			// We should only get one, and with the correct access level
			if ($project['Project']['id'] == 2){
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 4){
				$this->assertTrue(true, "Impossible to fail");
			} else {
				$this->assertTrue(false, "An unexpected project ID (".$project['Project']['id'].") was retrieved");
			}
		}
	}

	public function testPublicIndexProjectGuest() {

		// Log in as a guest - we should see all public projects, not just our own ones
		$this->_fakeLogin(3);

		$this->testAction('/projects/public_projects', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		$this->assertContains('<h1>Public Projects', $this->view);

		$this->assertRegexp('/<a href=".*\/project\/public\/." class="project-link">public<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/project\/personal_public\/." class="project-link">personal_public<\/a>/', $this->view);

		// Check the project list looks sane and has only the right entries/access levels
		$this->assertNotNull($this->vars['projects']);
		$this->assertEqual(count($this->vars['projects']), 2, "Incorrect number of projects returned");

		// Check each project 
		foreach ($this->vars['projects'] as $project) {

			// We should only get one, and with the correct access level
			if ($project['Project']['id'] == 2){
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 4){
				$this->assertTrue(true, "Impossible to fail");
			} else {
				$this->assertTrue(false, "An unexpected project ID (".$project['Project']['id'].") was retrieved");
			}
		}
	}

/**
 * testView method
 *
 * @return void
 */
	public function testViewSystemAdminOwner() {

		// System admin can see everything
		$this->_fakeLogin(5);

		$this->testAction('/project/private', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();

		$this->assertContains('<h1>private <small>Project overview</small></h1>', $this->view);

		$this->assertNotNull($this->vars['project']);
		
	}

	public function testViewSystemAdminNotOwner() {

		// System admin can see everything
		$this->_fakeLogin(5);

		$this->testAction('/project/personal', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();

		$this->assertContains('<h1>personal <small>Project overview</small></h1>', $this->view);

		$this->assertNotNull($this->vars['project']);
		
	}

	public function testViewUser() {

		$this->_fakeLogin(1);

		$this->testAction('/project/public', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();

		$this->assertContains('<h1>public <small>Project overview</small></h1>', $this->view);

		$this->assertNotNull($this->vars['project']);
		
	}

	public function testViewNotUser() {

		$this->_fakeLogin(1);

		$this->testAction('/project/personal', array('return' => 'view', 'method' => 'get'));
		$this->assertNotAuthorized();
		
	}

	public function testViewGuest() {

		$this->_fakeLogin(3);

		$this->testAction('/project/private', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();

		$this->assertContains('<h1>private <small>Project overview</small></h1>', $this->view);

		$this->assertNotNull($this->vars['project']);
		
	}

/**
 * testAdd method
 *
 * @return void
 */
	public function testAddProjectFormNotLoggedIn() {
		$this->testAction('/projects/add', array('return' => 'view', 'method' => 'get'));
		$this->assertNotAuthorized();
	}

	public function testAddProjectForm() {
		$this->_fakeLogin(3);
		$this->testAction('/projects/add', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		$this->assertContains('<form action="/projects/add"', $this->contents, "Form was not rendered");
		
	}

	public function testAddProjectWithNameClash() {
		$this->_fakeLogin(3);
		$postData = array(
			'Project' => array(
				'name' => 'private',
				'description' => 'A clashing project name',
				'repo_type' => 2,
				'public' => 1,
			)
		);

		$this->controller->Session
			->expects($this->once())
			->method('setFlash')
			->with("Project '<strong>private</strong>' could not be created. Please try again.");

		$this->testAction('/projects/add', array('return' => 'view', 'method' => 'post', 'data' => $postData));
		$this->assertAuthorized();
		$this->assertContains('<form action="/projects/add"', $this->contents, "Form was not rendered");
	}

	public function testAddProject() {
		$this->_fakeLogin(3);
		$postData = array(
			'Project' => array(
				'name' => 'newproject',
				'description' => 'A non-clashing project name',
				'repo_type' => 1,
				'public' => 1,
			)
		);

		$this->testAction('/projects/add', array('return' => 'view', 'method' => 'post', 'data' => $postData));
		$this->assertAuthorized();

		// We should be redirected to the new project page
		$this->assertNotNull($this->headers);
		$this->assertNotNull(@$this->headers['Location']);

		// PHP can parse the http:// url and Router can work out where it goes...
		$url = parse_url($this->headers['Location']);
		$url = Router::parse($url['path']);
		$this->assertEquals($url, array(
			'controller' => 'projects',
			'action' => 'view',
			'project' => 'newproject',
			'named' => array(),
			'pass' => array('newproject'),
			'plugin' => null
		));

	}

	public function testAddGitProject() {
		$this->_fakeLogin(3);
		$postData = array(
			'Project' => array(
				'name' => 'newproject_withgit',
				'description' => 'A non-clashing git project name',
				'repo_type' => 2,
				'public' => 1,
			)
		);

		$this->testAction('/projects/add', array('return' => 'view', 'method' => 'post', 'data' => $postData));
		$this->assertAuthorized();

		// We should be redirected to the new project page
		$this->assertNotNull($this->headers);
		$this->assertNotNull(@$this->headers['Location']);

		// PHP can parse the http:// url and Router can work out where it goes...
		$url = parse_url($this->headers['Location']);
		$url = Router::parse($url['path']);
		$this->assertEquals($url, array(
			'controller' => 'projects',
			'action' => 'view',
			'project' => 'newproject_withgit',
			'named' => array(),
			'pass' => array('newproject_withgit'),
			'plugin' => null
		));

	}


/**
 * testEdit method
 *
 * @return void
 */
	public function testEditNonExistant() {
		$this->_fakeLogin(5);
		try{
			$this->testAction('/project/newproject/edit', array('return' => 'view', 'method' => 'post'));
		} catch (NotFoundException $e) {
			$this->assertTrue(true, "Correct exception thrown");
		}

	}

	public function testEditProjectForm() {
		$this->_fakeLogin(5);
		$this->testAction('/project/personal/edit', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		$this->assertContains('<form action="/project/personal/edit"', $this->contents, "Form was not rendered");
		
	}

	public function testEditSystemAdminNotOwner() {
		$this->_fakeLogin(5);

		// We will attempt to change the project name. This should fail.
		$postData = array(
			'Project' => array(
				'id' => '3',
				'name' => 'newproject',
				'description' => 'Updated description of a project',
				'repo_type' => '2',
				'public' => false,
			)
		);

		$this->testAction('/project/personal/edit', array('return' => 'view', 'method' => 'post', 'data' => $postData));
		$this->assertAuthorized();

		// Check it saved correctly
		$saved = $this->controller->Project->findById(3);
		$postData['Project']['name'] = 'personal';
		unset($saved['Project']['created']);
		unset($saved['Project']['modified']);
		$this->assertEquals($saved['Project'], $postData['Project'], 'Failed to change project data');

		// We should be redirected to the new project page
		$this->assertNotNull($this->headers);
		$this->assertNotNull(@$this->headers['Location']);

		// PHP can parse the http:// url and Router can work out where it goes...
		$url = parse_url($this->headers['Location']);
		$url = Router::parse($url['path']);
		$this->assertEquals($url, array(
			'controller' => 'projects',
			'action' => 'view',
			'project' => '3',
			'named' => array(),
			'pass' => array('3'),
			'plugin' => null
		));

	}

	public function testEditProjectAdminOwner() {
		$this->_fakeLogin(7);
		$postData = array(
			'Project' => array(
				'id' => '3',
				'name' => 'newproject',
				'description' => 'Updated description of a project',
				'repo_type' => '2',
				'public' => false,
			)
		);

		$this->testAction('/project/personal/edit', array('return' => 'view', 'method' => 'post', 'data' => $postData));

		// Check it saved correctly
		$saved = $this->controller->Project->findById(3);
		$postData['Project']['name'] = 'personal';
		unset($saved['Project']['created']);
		unset($saved['Project']['modified']);
		$this->assertEquals($saved['Project'], $postData['Project'], 'Failed to change project data');

		// We should be redirected to the new project page
		$this->assertNotNull($this->headers);
		$this->assertNotNull(@$this->headers['Location']);

		// PHP can parse the http:// url and Router can work out where it goes...
		$url = parse_url($this->headers['Location']);
		$url = Router::parse($url['path']);
		$this->assertEquals($url, array(
			'controller' => 'projects',
			'action' => 'view',
			'project' => '3',
			'named' => array(),
			'pass' => array('3'),
			'plugin' => null
		));

	}

	public function testEditNotProjectAdmin() {
		$this->_fakeLogin(1);
		$postData = array(
			'Project' => array(
				'id' => '3',
				'name' => 'personal',
				'description' => 'Updated description of a project',
				'repo_type' => '2',
				'public' => false,
			)
		);
		
		$this->testAction('/project/personal/edit', array('return' => 'view', 'method' => 'post', 'data' => $postData));
		$this->assertNotAuthorized();

	}
/**
 * testDelete method
 *
 * @return void
 */
	public function testDeleteForm() {
		$this->_fakeLogin(5);
		$saved = $this->controller->Project->findById(3);
		$this->testAction('/project/personal/delete', array('return' => 'view', 'method' => 'get'));
		$this->assertAuthorized();
		$this->assertContains('<h1>Are you sure you want to delete?</h1>', $this->view);
	}

	public function testDeleteSystemAdmin() {
		$this->_fakeLogin(5);
		$this->testAction('/project/personal/delete', array('return' => 'view', 'method' => 'post'));
		$this->assertAuthorized();
		$saved = $this->controller->Project->findById(3);
		$this->assertEquals($saved, array(), "Failed to delete");
	}

	public function testDeleteProjectAdmin() {
		$this->_fakeLogin(7);
		$this->testAction('/project/personal/delete', array('return' => 'view', 'method' => 'post'));
		$saved = $this->controller->Project->findById(3);
		$this->assertEquals($saved, array(), "Failed to delete");
	}

	// TODO awaiting better authorization checks
	/*public function testDeleteNotAdmin() {
		$this->_fakeLogin(1);
		$this->testAction('/project/personal/delete', array('return' => 'view', 'method' => 'post'));
		$this->assertNotAuthorized();
	}*/

	public function testAdminIndexSystemAdmin() {

		// Log in as a system administrator - we should still only see "my" projects, not everyone's
		$this->_fakeLogin(5);

		$this->testAction('/admin/projects', array('return' => 'view', 'method' => 'get'));

		// Check the page content looks roughly OK
		$this->assertContains('<h1>Administration <small>da vinci code locator</small>', $this->view);
		$this->assertRegexp('/<a href=".*\/projects\/view\/private">private<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/projects\/view\/public">public<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/projects\/view\/personal">personal<\/a>/', $this->view);
		$this->assertRegexp('/<a href=".*\/projects\/view\/personal_public">personal_public<\/a>/', $this->view);

		// Check the project list looks sane and has only the right entries/access levels
		$this->assertNotNull($this->vars['projects']);
		$this->assertEqual(count($this->vars['projects']), 4, "Incorrect number of projects returned");

		// Check each project 
		foreach ($this->vars['projects'] as $project) {

			// We should get all 4 projects with correct repo types
			if ($project['Project']['id'] == 1 && $project['RepoType']['name'] == 'Git') {
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 2 && $project['RepoType']['name'] == 'None') {
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 3 && $project['RepoType']['name'] == 'None') {
				$this->assertTrue(true, "Impossible to fail");
			} elseif ($project['Project']['id'] == 4 && $project['RepoType']['name'] == 'None') {
				$this->assertTrue(true, "Impossible to fail");
			} else {
				$this->assertTrue(false, "An unexpected project ID (".$project['Project']['id'].") or repo type (".$project['RepoType']['name'].") was retrieved");
			}
		}
	}

/**
 * testAdminView method
 *
 * @return void
 */
	public function testAdminView() {
	}
/**
 * testAdminAdd method
 *
 * @return void
 */
	public function testAdminAdd() {
	}
/**
 * testAdminEdit method
 *
 * @return void
 */
	public function testAdminEdit() {
	}
/**
 * testAdminDelete method
 *
 * @return void
 */
	public function testAdminDelete() {
	}
}

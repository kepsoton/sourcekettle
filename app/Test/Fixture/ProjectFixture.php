<?php

app::uses('Folder', 'Utility');
class ProjectFixture extends CakeTestFixture {

	// Force InnoDB table type so we can test transactions
	public function create($db) {
	    $this->fields['tableParameters']['engine'] = 'InnoDB';
	    return parent::create($db);
	}

	public $import = array('model' => 'Project');

    public $records = array(
        array(
            'id' => 1,
            'name' => 'private',
            'description' => 'desc',
            'public' => 0,
            'repo_type' => 2,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 2,
            'name' => 'public',
            'description' => 'desc',
            'public' => 1,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 3,
            'name' => 'personal',
            'description' => 'Mr Smith\'s personal project',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 4,
            'name' => 'personal_public',
            'description' => 'Mr Smith\'s personal public project',
            'public' => 1,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 5,
            'name' => 'repoless',
            'description' => 'A repo-less project',
            'public' => 1,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 6,
            'name' => 'php-1',
            'description' => 'A project written in PHP',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 7,
            'name' => 'php-2',
            'description' => 'Another project written in PHP',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 8,
            'name' => 'java-1',
            'description' => 'A project written in Java',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 9,
            'name' => 'java-2',
            'description' => 'Another project written in Java',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 10,
            'name' => 'python-1',
            'description' => 'A project written in Python',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 11,
            'name' => 'python-2',
            'description' => 'Another project written in Python',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 12,
            'name' => 'perl-1',
            'description' => 'A project written in Perl',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2012-06-01 12:46:07',
            'modified' => '2012-06-01 12:46:07'
        ),
        array(
            'id' => 13,
            'name' => 'perl-2',
            'description' => 'Another project written in Perl',
            'public' => 0,
            'repo_type' => 1,
            'created' => '2015-06-01 12:46:07',
            'modified' => '2015-06-01 12:46:07'
        ),
    );

	// Constructor puts our repository data into the repository directory
	// This directory should match up with the Settings fixture data
	public function insert($db) {

		$this->__removeTestRepositories();
		$repoDir = realpath(__DIR__).'/repositories';
		$dataDir = realpath(__DIR__).'/repo_data';

		// NB if more repo types are added in future, this should be updated...
		foreach (array('git') as $repoType) {
			$repoTypeFolder = new Folder("$dataDir/$repoType");
			$subdirs = $repoTypeFolder->read();
			foreach ($subdirs[0] as $repo) {
				if (!preg_match("/\.$repoType$/", $repo)) {
					continue;
				}
				$repoFolder = new Folder("$dataDir/$repoType/$repo");
				$repoFolder->copy("$repoDir/$repo");
			}
		}
		return parent::insert($db);
	}

	private function __removeTestRepositories() {
		$repoDir = realpath(__DIR__).'/repositories';
		$repoTopFolder = new Folder($repoDir);
		$subdirs = $repoTopFolder->read();
		foreach ($subdirs[0] as $repo) {
			$repoFolder = new Folder("$repoDir/$repo");
			$repoFolder->delete();
		}
	}

	// Clean up our test repo data when all tests are done
	public function drop($db) {
		$this->__removeTestRepositories();
		return parent::drop($db);
	}
}

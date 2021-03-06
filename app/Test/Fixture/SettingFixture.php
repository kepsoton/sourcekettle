<?php

class SettingFixture extends CakeTestFixture {

	// Force InnoDB table type so we can test transactions
	public function create($db) {
	    $this->fields['tableParameters']['engine'] = 'InnoDB';
	    return parent::create($db);
	}

	public $import = array('model' => 'Setting');

    public $records = array(
        array(
            'id' => 1,
            'name' => 'Status.sync_required',
            'value' => 0,
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
        ),
        array(
            'id' => 3,
            'name' => 'Users.sysadmin_email',
            'value' => 'admin@example.org',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
        ),
		array(
			'id' => 4,
			'name' => 'UserInterface.alias',
			'value' => 'SourceKettle Test Site',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 5,
			'name' => 'SourceRepository.user',
			'value' => 'nobody',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 6,
			'name' => 'Features.task_enabled',
			'value' => '1',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 7,
			'name' => 'FakeSetting.fake',
			'value' => 'moose',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 8,
			'name' => 'UserInterface.theme',
			'value' => 'amelia',
			'locked' => '0',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 9,
			'name' => 'Features.source_enabled',
			'value' => '0',
			'locked' => '1',
            'created' => '2012-06-02 20:05:59',
            'modified' => '2012-06-02 20:05:59'
		),
		array(
			'id' => 10,
			'name' => 'UserInterface.terminology',
			'value' => 'default',
			'locked' => '1',
			'created' => '2014-10-28 22:29:14',
			'modified' => '2014-10-28 22:29:14'
		),
    );

	public function __construct() {
		$this->records[] = array(
			'id' => 2,
			'name' => 'SourceRepository.base',
			'value' => realpath(__DIR__).'/repositories',
			'locked' => '0',
			'created' => '2012-06-02 20:05:59',
			'modified' => '2012-06-02 20:05:59'
		);
		parent::__construct();
	}
}

<?php
/**
 *
 * View class for APP/tasks/chart for the SourceKettle system
 * Shows a gantt chart for a project
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     SourceKettle Development Team 2014
 * @link          http://github.com/SourceKettle/sourcekettle
 * @package       SourceKettle.View.Projects
 * @since         SourceKettle v 1.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$this->Html->css('tasks', null, array ('inline' => false));
$this->Html->css('projects.index', null, array ('inline' => false));
?>
<?=$this->element('gantt')?>


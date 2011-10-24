<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define all the backup steps that will be used by the backup_googlecollab_activity_task
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete googlecollab structure for backup, with file and id annotations
 */
class backup_googlecollab_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $googlecollab = new backup_nested_element('googlecollab', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'defaulttype',
            'timemodified'));

        $googlecollabuser = new backup_nested_element('userdata');

        $googlecollabdocs = new backup_nested_element('googlecollab_docs', array('id'), array(
            'docid', 'groupid', 'timecreated'
        ));

        // Build the tree
        $googlecollab->add_child($googlecollabuser);
        $googlecollabuser->add_child($googlecollabdocs);

        // Define sources
        $googlecollab->set_source_table('googlecollab', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $googlecollabdocs->set_source_table('googlecollab_docs', array(
                'actid' => backup::VAR_PARENTID)
            );
        };

        // Define id annotations
        $googlecollabdocs->annotate_ids('group', 'groupid');

        // Define file annotations
        $googlecollab->annotate_files('mod_googlecollab', 'intro', null);
        $googlecollab->annotate_files('mod_googlecollab', 'template', null);

        // Return the root element (googlecollab), wrapped into standard activity structure
        return $this->prepare_activity_structure($googlecollab);
    }
}

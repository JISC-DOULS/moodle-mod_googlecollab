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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_googlecollab_activity_task
 */

/**
 * Structure step to restore one googlecollab activity
 */
class restore_googlecollab_activity_structure_step extends restore_activity_structure_step {

    public $newitemidis;

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('googlecollab', '/activity/googlecollab');
        $paths[] = new restore_path_element('googlecollab_docs',
            '/activity/googlecollab/user/googlecollab_docs');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_googlecollab($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the googlecollab record
        $newitemid = $DB->insert_record('googlecollab', $data);
        $this->newitemidis = $newitemid;
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_googlecollab_docs($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->actid = $this->get_new_parentid('googlecollab');
        $data->groupid = $this->get_mappingid('group', $data->groupid);

        $newitemid = $DB->insert_record('googlecollab_docs', $data);
        $this->set_mapping('googlecollab_docs', $oldid, $newitemid);
    }

    protected function after_execute() {
        global $DB;
        // Add googlecollab related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_googlecollab', 'intro', null);
        $this->add_related_files('mod_googlecollab', 'template', null);

        //Template files itemids relate to group id - update for a new context?
        $fs = get_file_storage();
        $cm = get_coursemodule_from_instance('googlecollab', $this->newitemidis);
        $context = context_module::instance($cm->id);
        $files = $fs->get_area_files($context->id, 'mod_googlecollab', 'template');
        foreach ($files as $filerec) {
            if ($newgroupid = $this->get_mappingid('group', $filerec->get_itemid())) {
                $newrec = new stdClass();
                $newrec->id = $filerec->get_id();
                $newrec->itemid = $newgroupid;
                $DB->update_record('files', $newrec);
            }
        }
    }
}

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
 * Manages saving and editing templates
 *
 * @package    portfolioact
 * @subpackage portfolioactmode_template
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/googlecollab/locallib.php');
require_once($CFG->dirroot.'/mod/googlecollab/renderer.php');
require_once($CFG->dirroot.'/mod/googlecollab/db/access.php');
require_once($CFG->dirroot.'/mod/googlecollab/local_forms.php');

$maxbytes = 1024 * 1024;

$groupid = required_param('group',  PARAM_INT);
$mode = required_param('mode',  PARAM_ALPHA);
$action = required_param('action',  PARAM_ALPHA);

$cmid = $id = optional_param('id', 0, PARAM_INT);
$cm = get_coursemodule_from_id('googlecollab', $id, 0, false, MUST_EXIST);
$googlecollab = googlecollab::get_instance($cm->instance);
$context = get_context_instance(CONTEXT_MODULE, $googlecollab->cm->id);
require_capability('mod/googlecollab:manage', $context );
//$context_course = get_context_instance(CONTEXT_COURSE, $googlecollab->course->id);

$PAGE->set_url('/mod/googlecollab/templates.php', array('id' => $googlecollab->cm->id));
$PAGE->set_title($googlecollab->googlecollab->name);
$PAGE->set_heading($googlecollab->course->shortname);
$PAGE->navbar->add(get_string('createupdatetemplate', 'googlecollab'));

$fs = get_file_storage();
$context = get_context_instance(CONTEXT_MODULE, $googlecollab->cm->id);
$files = $fs->get_area_files($context->id, 'mod_googlecollab', 'template', $groupid, null, false);
$havetemplate = count($files);
$doc = $DB->get_record('googlecollab_docs', array('actid' => $googlecollab->googlecollab->id, 'groupid' => $groupid));

$mform = new googlecollab_create_template(qualified_me());
$draftitemid = file_get_submitted_draft_itemid('template');

$notify = '';

if ($action == 'create') {

    if ($havetemplate) {

        $notify .= $googlecollab->renderer->cannot_create();

    } else {
        //group templates can always be updated even if the group activity has started
        if ((!$doc) || ($groupid == 0)) {
            //offer the create option - the file does not exist and the activity has not yet been setup on google

             //filepicker is designed for one file but you get the contents as a string which is not convenient.
            //TODO test all file formats - should be convertable to document, spreadsheet or presentation
            $mform->formhandle->addElement('filemanager', 'template', get_string('template', 'googlecollab'), null, array('subdirs' => 0,
                 'maxbytes' => $maxbytes, 'maxfiles' => 1,  'accepted_types' => array('*.doc',
                 '*.rtf', '*.docx', '*.xls', '*.ppt', '*.csv', '*.pps')));


            //actual files -> drafts with this draft id, so now associated with this instance
            file_prepare_draft_area($draftitemid, $context->id, 'mod_googlecollab', 'template', $groupid);

        } else {
            //file does not exist but the  activity has been started.
            $notify .= $googlecollab->renderer->cannot_create();
        }
    }

} else if ($action == 'update') {
    //if user has deleted a template with the picker and then is re-adding one
    //this will be a create
    //group templates can always be updated
    if ((!$doc) || ($groupid == 0)) {

        //offer the update option - the file exists but the activity has not yet been setup on google
        $mform->formhandle->addElement('filemanager', 'template', get_string('template', 'googlecollab'), null, array('subdirs' => 0,
             'maxbytes' => $maxbytes, 'maxfiles' => 1,  'accepted_types' => array('*.doc',
             '*.rtf', '*.docx', '*.xls', '*.ppt', '*.csv', '*.pps')));


        //actual files -> drafts with this draft id, so now associated with this instance
        file_prepare_draft_area($draftitemid, $context->id, 'mod_googlecollab', 'template', $groupid);

        $currentfile = new stdClass();
        //$currentfile->id = null;
        $currentfile->template =  $draftitemid;
        $mform->set_data($currentfile);//associate all the drafts with the control

    } else {
        // activity has been started.

        $notify .= $googlecollab->renderer->cannot_update();
    }

}

$mform->add_action_buttons();


if ($mform->is_cancelled()) {

    $url = new moodle_url('/mod/googlecollab/templates.php',
        array('id'=>$googlecollab->cm->id));

    redirect($url);

} else if ($fromform = $mform->get_data()) {


    file_save_draft_area_files($draftitemid, $context->id,
     'mod_googlecollab', 'template', $groupid,
      array('subdirs' => false, 'maxbytes' => $maxbytes));

    $url = new moodle_url('/mod/googlecollab/templates.php',
       array('id'=>$googlecollab->cm->id));

    redirect($url);


}

echo $OUTPUT->header();

echo $googlecollab->renderer->render_heading($googlecollab->course->fullname);
echo $googlecollab->renderer->createupdatetemplate();
echo $googlecollab->renderer->groupname_as_subheading_for_templates($groupid);

echo $notify;

$mform->display();


// Finish the page
echo $OUTPUT->footer();

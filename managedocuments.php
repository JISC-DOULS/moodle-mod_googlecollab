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

$page = optional_param('page', 0, PARAM_INT);

$cmid = $id = optional_param('id', 0, PARAM_INT);

$cm = get_coursemodule_from_id('googlecollab', $id, 0, false, MUST_EXIST);
$googlecollab = googlecollab::get_instance($cm->instance);
$context = context_module::instance($googlecollab->cm->id);
require_capability('mod/googlecollab:manage', $context );

$PAGE->set_url('/mod/googlecollab/managedocuments.php', array('id' => $googlecollab->cm->id));
$PAGE->set_title($googlecollab->googlecollab->name);
$PAGE->set_heading($googlecollab->course->shortname);

echo $OUTPUT->header();

echo $googlecollab->renderer->render_heading($googlecollab->course->fullname);
echo $googlecollab->renderer->render_activity_heading($googlecollab->googlecollab->name);
echo $googlecollab->renderer->managedocumentsintro();


$userresult='';
$pageaction = optional_param('action', 'none', PARAM_ALPHAEXT);
if ($pageaction != 'none') {
    confirm_sesskey();
    $useremail = required_param('useremail',  PARAM_TEXT);//PARAM_EMAIL doesn't work (=
    if (empty($useremail)) {
        $userresult = $googlecollab->renderer->invalidemail();
    } else {
        if ($pageaction == 'adduser') {
            $usergroup = required_param('usergroup',  PARAM_INT);
            $docid = $googlecollab->get_doc_for_group($usergroup);
            $ret = $googlecollab->add_user_to_doc_acl($docid, $useremail, 'writer');
            if ($ret) {
                if ($usergroup == 0) {
                    $groupname = $googlecollab->course->shortname . ' ' . get_string('coursegroup', 'googlecollab');
                } else {
                    $groupname = groups_get_group_name($usergroup);
                }
                $userresult = $googlecollab->renderer->userresult($ret, true, $useremail, $groupname );
            } else {
                 $userresult = $googlecollab->renderer->userresult($ret);
            }
        } else if ($pageaction == 'remove') {
            $docid = required_param('docid', PARAM_RAW);
            $ret = $googlecollab->del_user_from_doc_acl($docid, $useremail);
            $userresult = $googlecollab->renderer->userresult($ret, false);
        }
    }
}


$documents = $DB->get_records('googlecollab_docs', array('actid' => $googlecollab->googlecollab->id), null, '*' );

if (empty($documents)) {

    echo $googlecollab->renderer->nodocumentsyet();
} else {

    echo $googlecollab->renderer->ajax_message_area_all();

    if ($page == 0) {
        echo $googlecollab->renderer->reset_all();
    }

    echo $userresult;

    echo $googlecollab->renderer->documents_list($googlecollab, $documents, $page);
}

$errtxt = get_string('delete_notok', 'googlecollab');

$PAGE->requires->js_init_call('M.googlecollab.init',
     array($googlecollab->googlecollab->id, $errtxt), true, googlecollab_get_js_module());


// Finish the page
echo $OUTPUT->footer();

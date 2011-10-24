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
 * Main activity view
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL  v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid = $id = optional_param('id', 0, PARAM_INT);
$currentgroup = optional_param('group', 0, PARAM_INT); // Group ID
$cm = get_coursemodule_from_id('googlecollab', $id, 0, false, MUST_EXIST);
$googlecollab = googlecollab::get_instance($cm->instance);


$PAGE->set_url('/mod/googlecollab/view.php', array('id' => $googlecollab->cm->id));
$PAGE->set_title($googlecollab->googlecollab->name);
$PAGE->set_heading($googlecollab->course->shortname);
$PAGE->requires->js_init_call('M.googlecollab.viewresizer',
    array(), true, googlecollab_get_js_module());

$PAGE->set_button(update_module_button($googlecollab->cm->id, $googlecollab->course->id,
    get_string('modulename', 'googlecollab')));

echo $OUTPUT->header();

$groupmode = groups_get_activity_groupmode($cm);
//all participants is groupid 0 ....careful
if (empty($currentgroup)) {
    $currentgroup = groups_get_activity_group($cm, true);
    $currentgroup = !empty($currentgroup) ? $currentgroup : 0;
}

$docid = $googlecollab->getdocref($currentgroup);//ID of doc for this activity

echo $googlecollab->renderer->render_group_selector($googlecollab, $currentgroup);

echo $googlecollab->renderer->render_activity_heading($googlecollab->googlecollab->name);

echo $googlecollab->renderer->render_intro($googlecollab->googlecollab->intro);

$showsharedoc = false;//Set true to show the interactive shared doc
$checkcap = false;//Set to true to check cap on read only view
$loginfo = 'viewdoc';

if ($groupmode == NOGROUPS) {
    $showsharedoc = true;//Default is everyone gets the proper doc
} else if ($groupmode == SEPARATEGROUPS) {

    $showsharedoc = groups_is_member($currentgroup, $USER->id);
    $checkcap = true;//Check cap when looking at other groups

} else if ($groupmode == VISIBLEGROUPS) {
    $showsharedoc = groups_is_member($currentgroup, $USER->id);
}

//If group is 0 check for special case when google groups on
if ($currentgroup === 0) {

    if ($googlecollab->siteconfig->gappgroups == googlecollab::GROUPMODE_ON) {
        $showsharedoc = false;//only interact if in special course group
        //If group mode is on we need a course group for the doc sharing
        if ($groupid = groups_get_group_by_name($googlecollab->course->id,
            $googlecollab->get_moodle_course_group_name())) {
            //Course group exists if user is in this show shared doc
            if (groups_is_member($groupid)) {
                $showsharedoc = true;
            }
        }
    }
}

//Code to control what the user sees
if ($showsharedoc &&
    has_capability('mod/googlecollab:viewdoc', $googlecollab->modcontext)) {
    if (empty($docid)) {
        //Create doc and share with group or individual
        //TODO - should this always happen - or only for some?
        $docid = $googlecollab->create_document($currentgroup, $USER->id);
        if (empty($docid)) {
            throw new moodle_exception('googleerror', 'googlecollab');
        }
    }
    //Share doc...
    $sharingid = $googlecollab->get_sharing_name($currentgroup);

    if (!$shared = $googlecollab->add_sharing_on_the_fly($docid, $sharingid)) {
        //Sharing failed show error and show read only ver
        echo $OUTPUT->notification(get_string('sharingerror', 'googlecollab'));
    }
    $sharingurl = $googlecollab->get_sharing_link($docid);
    if (empty($sharingurl)) {
        throw new moodle_exception('googleerror', 'googlecollab');
    }
    $usercheck = $googlecollab->check_user_in_google();
    //Note: Presentations can't be edited in iframe - they have different link so check for this
    if (!$shared || !$usercheck || strpos($sharingurl, 'present')) {
        //Show the read only instead
        echo $googlecollab->renderer->render_gdoc_link($sharingurl);
        $readingurl = $googlecollab->get_reading_link($docid);
        echo $googlecollab->renderer->documentreader($readingurl);
        $loginfo = 'viewreaddoc';
    } else {
        echo $googlecollab->renderer->documenteditor($sharingurl);
    }

} else {
    //Show read only doc
    if ($checkcap) {
        if (!has_capability('mod/googlecollab:viewall', $googlecollab->modcontext) &&
            !has_capability('moodle/site:accessallgroups', $googlecollab->modcontext)) {
            //Neither capability
            echo $googlecollab->renderer->nopermissionsondoc();
        } else {
            $checkcap = false;
        }
    }
    if (!$checkcap) {//Note shouldn't be else if check cap is reset/off
        if (empty($docid)) {
            echo $googlecollab->renderer->documentnotavailable();
            $loginfo = 'viewnodoc';
        } else {
            //admin/tutor might need to get to actual doc but not be in group
            //In some instances we've already checked these above - do again anyway
            if (has_capability('mod/googlecollab:viewall', $googlecollab->modcontext) ||
                has_capability('moodle/site:accessallgroups', $googlecollab->modcontext)) {
                $sharingurl = $googlecollab->get_sharing_link($docid);
                if ($sharingurl) {
                    echo $googlecollab->renderer->render_gdoc_link($sharingurl);
                }
            }
            $readingurl = $googlecollab->get_reading_link($docid);
            echo $googlecollab->renderer->documentreader($readingurl);
            $loginfo = 'viewreaddoc';
        }
    }
}

// Finish the page
echo $OUTPUT->footer();

add_to_log($googlecollab->course->id, 'googlecollab', 'view', "view.php?id=" . $googlecollab->cm->id,
   $loginfo, $googlecollab->cm->id);

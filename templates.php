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
$context = get_context_instance(CONTEXT_MODULE, $googlecollab->cm->id);
require_capability('mod/googlecollab:manage', $context );

$PAGE->set_url('/mod/googlecollab/templates.php', array('id' => $googlecollab->cm->id));
$PAGE->set_title($googlecollab->googlecollab->name);
$PAGE->set_heading($googlecollab->course->shortname);

echo $OUTPUT->header();

echo $googlecollab->renderer->render_heading($googlecollab->course->fullname);
echo $googlecollab->renderer->render_activity_heading($googlecollab->googlecollab->name);

echo html_writer::tag('p', get_string('templateinst', 'googlecollab'));

$groups = groups_get_all_groups($googlecollab->course->id, 0, $googlecollab->cm->groupingid);
$groupmode = groups_get_activity_groupmode($googlecollab->cm);

switch ($groupmode) {

    case NOGROUPS:
        echo $googlecollab->renderer->groups_nogroups($googlecollab);
        break;
    case SEPARATEGROUPS:
        echo $googlecollab->renderer->groups_templates_in_course($googlecollab, $groups, $page);
        break;

    case VISIBLEGROUPS:
        echo $googlecollab->renderer->groups_templates_in_course($googlecollab, $groups, $page);

    default:


}

// Finish the page
echo $OUTPUT->footer();

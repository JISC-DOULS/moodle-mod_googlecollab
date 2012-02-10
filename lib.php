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
 * Library of interface functions and constants for module googlecollab
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the googlecollab specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package mod
 * @subpackage googlecollab
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function googlecollab_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $googlecollab An object from the form in mod_form.php
 * @return int The id of the newly inserted googlecollab record
 */
function googlecollab_add_instance($googlecollab) {
    global $DB;

    $googlecollab->timecreated = time();
    $googlecollab->timemodified = $googlecollab->timecreated;
    # You may have to add extra stuff in here #

    return $DB->insert_record('googlecollab', $googlecollab);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $googlecollab An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function googlecollab_update_instance($googlecollab) {
    global $DB;

    $googlecollab->timemodified = time();
    $googlecollab->id = $googlecollab->instance;

    # You may have to add extra stuff in here #

    return $DB->update_record('googlecollab', $googlecollab);
}



/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function googlecollab_user_outline($course, $user, $mod, $googlecollab) {
    $return = new stdClass;
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function googlecollab_user_complete($course, $user, $mod, $googlecollab) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in googlecollab activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function googlecollab_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function googlecollab_cron () {
    return true;
}

/**
 * Must return an array of users who are participants for a given instance
 * of googlecollab. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $googlecollabid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function googlecollab_get_participants($googlecollabid) {
    return false;
}

/**
 * This function returns if a scale is being used by one googlecollab
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $googlecollabid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function googlecollab_scale_used($googlecollabid, $scaleid) {
    global $DB;

    $return = false;

    return $return;
}

/**
 * Checks if scale is being used by any instance of googlecollab.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any googlecollab
 */
function googlecollab_scale_used_anywhere($scaleid) {
    global $DB;
    //TODO looks to me like the wrong params passed and it will throw a PHP exception
    if ($scaleid and $DB->record_exists('googlecollab', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}



/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $chatnode The node to add module settings to
 */
function googlecollab_extend_settings_navigation(settings_navigation
    $settings, navigation_node $googlecollabnode) {
    global  $PAGE, $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');
    $googlecollab = googlecollab::get_instance($PAGE->cm->instance);
    $googlecollab->nav_menu_item_templates($googlecollabnode);
    $googlecollab->nav_menu_item_documents($googlecollabnode);

}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function googlecollab_delete_instance($id) {
    global $DB, $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');

    if (!$googlecollab = $DB->get_record('googlecollab', array('id'=>$id))) {
        return false;
    }
    # Delete any dependent records here #

    $googlecollab = googlecollab::get_instance($id, false);

    //delete any documents from google
    $docs = $DB->get_records('googlecollab_docs', array('actid' => $id), null, '*');
    foreach ($docs as $doc) {
        $ret  = $googlecollab->delete_document($doc->docid, true);
    }

    $cm = get_coursemodule_from_instance('googlecollab', $id);
    //delete any template files in the file system
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'googlecollab', 'template');

    $DB->delete_records('googlecollab_docs', array('actid' => $id));

    $DB->delete_records('googlecollab', array('id' => $id));

    return true;
}



/**
 * Used to include custom Javascript for this module
 *
 * @return array
 */

function googlecollab_get_js_module() {
    global $PAGE;
    return array(
        'name' => 'googlecollab',
        'fullpath' => '/mod/googlecollab/module.js',
        'requires' => array('base', 'dom',  'io', 'node', 'json')
    );
}




/**
 * Serves the read only versions of the Google Docs.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function googlecollab_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    //completely paranoid repeating of this check
    require_course_login($course, true, $cm);

    $filename = $args[0];
    $filepath = $CFG->tempdir . '/' . googlecollab::TEMPDIR . '/' . $filename;
    if (!file_exists($filepath)) {
        return false;
    }
    send_file($filepath, $filename, 'default',  0, false, false);
}

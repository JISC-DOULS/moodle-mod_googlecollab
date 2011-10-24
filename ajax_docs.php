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
 * AJAX handler for googlecollab
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/googlecollab/locallib.php');



$groupid = required_param('groupid', PARAM_TEXT);
$actid = required_param('actid', PARAM_INT);
$actid = (string) $actid;

$googlecollab = googlecollab::get_instance($actid);

$response = new stdClass();

confirm_sesskey();

if (! isloggedin()) {

    $response->status = 0;
    echo json_encode($response, JSON_FORCE_OBJECT);
    exit;
}




$response->status = 1;
$response->result = true;

if ($groupid == 'all') {

    $docs = $DB->get_records('googlecollab_docs', array('actid' => $actid), null, '*');
    foreach ($docs as $doc) {
        $ret  = $googlecollab->delete_document($doc->docid, true);
        if ($ret == false) {
            $response->result = false;
        }
    }

    try {
        $DB->delete_records('googlecollab_docs', array('actid' => $actid));
    } catch (Exception $e) {
        $response->result = false;
    }


} else {
    $groupid = (int) $groupid;

    try {
        $doc = $DB->get_record('googlecollab_docs', array('actid' => $actid, 'groupid' => $groupid),  '*');
    } catch (Exception $e) {

        $response->result = false;
        $response->message = get_string('delete_notok', 'googlecollab');
        echo json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }

    if (empty($doc)) {
        $response->result = false;
        $response->message = get_string('delete_notok', 'googlecollab');
          echo json_encode($response, JSON_FORCE_OBJECT);
          exit;
    }

    $ret = $googlecollab->delete_document($doc->docid, true);

    if ($ret == false) {
        $response->result = false;
           $response->message = get_string('delete_notok', 'googlecollab');
        echo json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }


    try {
        $DB->delete_records('googlecollab_docs', array('actid' => $actid, 'groupid' => $groupid));
    } catch (Exception $e) {
        $response->result = false;
        $response->message = get_string('delete_notok', 'googlecollab');
        echo json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }


}


$response->message = get_string('delete_ok', 'googlecollab');
echo json_encode($response, JSON_FORCE_OBJECT);
exit;

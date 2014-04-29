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
 * @package mod
 * @subpackage googlecollab
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * check for Moodle
 */

defined('MOODLE_INTERNAL') || die();

if (!class_exists('OAuthException')) {
    require_once(dirname(__FILE__).'/oauth.php');
}
require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Class for googlecollab
 *
 * Class for googlecollab. Instantiates an instance of the class and requires the user be logged in.
 *
 * @package mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class googlecollab  {

    const GROUPMODE_OFF = 0;
    const GROUPMODE_ON = 1;
    const USENAME = 0;
    const USEEMAIL = 1;

    const GOOGLELISTDATASCOPE = 'https://docs.google.com/feeds/';
    const TEMPDIR = 'googlecollab';

    const NEWDOC = 0;
    const NEWSHEET = 1;
    const NEWPRES = 2;

    protected $currentgroup;
    protected $googleapps_docs;

    private static $instance;

    /**
     *
     * @param Int $actid activityid only used if page instance
     * @param Boolean $pageinstance set to false if using in a lib etc
     */
    private function __construct($actid, $pageinstance = true) {
        global $DB, $PAGE;

        $this->patt_course = '/^(.+?) Course Group$/';
        $this->patt_tutorgroup = '/^(.+?) TG \[(\d*)\] (.+)$/';
        $this->patt_clustergroup = '/^(.+?) combined (tutor )?group$/';

        $this->siteconfig = get_config('mod_googlecollab');

        if ($pageinstance) {
            $googlecollab = $DB->get_record('googlecollab', array('id' => $actid), '*', MUST_EXIST);
            $course = $DB->get_record('course',
                array('id' => $googlecollab->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('googlecollab',
                $googlecollab->id, $course->id, false, MUST_EXIST);

            $this->googlecollab = $googlecollab;
            $this->cm = $cm;
            $this->course = $course;

            require_login($course, false, $cm);

            $this->renderer = $PAGE->get_renderer('mod_googlecollab');

            if (!empty($this->siteconfig->gapps_consumerws)) {
                $this->siteconfig->gapps_consumersecret = $this->get_secret_from_ws();
            }

            $this->modcontext = context_module::instance($this->cm->id);
        }

    }


    public static function get_instance($actid, $notlib = true) {

        if (!self::$instance) {
            self::$instance = new googlecollab($actid, $notlib);
        }

        return self::$instance;

    }

    public function get_secret_from_ws() {
        $url = $this->siteconfig->gapps_consumerws;
        if (!stripos($url, 'wsdl')) {
            $url .= '?WSDL';
        }

        try {
            if (debugging()) {
                ini_set("soap.wsdl_cache_enabled", "0");
            }
            $soapclient = new SoapClient($url);
            $secret = $soapclient->GetAuthSecret()->GetAuthSecretResult;

        } catch (Exception $e) {
            //Call failed
            throw new moodle_exception('Call to Google web service failed. Google docs cannot be accessed.');
        }
        return $secret;
    }


    /**
     * Produces a menu item for the side navigation for edit settings
     * @param mixed $node
     */

    public function nav_menu_item_templates(&$node) {

        if (has_capability('mod/googlecollab:manage', $this->modcontext) ) {

            $url = new moodle_url('/mod/googlecollab/templates.php',
            array('id'=>$this->cm->id));
            $node->add(get_string('managetemplates', 'googlecollab'), $url);

        }
    }

    /**
     * Produces a menu item for the side navigation for edit settings
     * @param mixed $node
     */

    public function nav_menu_item_documents(&$node) {

        if (has_capability('mod/googlecollab:manage', $this->modcontext) ) {

            $url = new moodle_url('/mod/googlecollab/managedocuments.php',
            array('id'=>$this->cm->id));
            $node->add(get_string('managedocuments', 'googlecollab'), $url);

        }
    }


    /*
     * Returns an array for the site wide group settings
     *
     * @return array
     */

    public static function google_mode_settings_choice() {
        return array(
        self::GROUPMODE_OFF => get_string('groupmode_off', 'googlecollab'),
        self::GROUPMODE_ON => get_string('groupmode_on', 'googlecollab')
        );
    }

    /*
     * Returns an array for the user email address
     *
     * @return array
     */

    public static function google_usermail_settings_choice() {
        return array(
        self::USENAME => get_string('usermail_username', 'googlecollab'),
        self::USEEMAIL => get_string('usermail_mail', 'googlecollab')
        );
    }


    /**
     * Sets the group id
     *
     * @param int $groupid
     */

    public function setgroup($groupid) {
        $this->currentgroup = $groupid;
    }


    /**
     * Get the Google document ref for the current group
     *
     * @param int $groupid
     *
     * @return boolean|string false on no record or the doc id
     */

    public function getdocref($groupid) {

        global $DB;

        $rec = $DB->get_record('googlecollab_docs',
            array('groupid' => (int)$groupid, 'actid' => (int)$this->googlecollab->id), '*' );

        if (empty($rec) || empty($rec->docid)) {//we don't have one
            return false;
        } else {
            return $rec->docid;
        }

    }

    /**
     * Returns the url at which the document can be edited
     *
     * @params string $docid
     * @return string
     */

    public function get_sharing_link($docid) {

        //      $patt = '/(?:document:|spreadsheet:|presentation:)/';
        //      $docidraw = preg_replace($patt, '', $docid);
        //      return 'https://docs.google.com/a/' .
        //         get_config('mod_googlecollab', 'gapps_googleappsdomain') .
        //        '/document/d/'.
        //        $docidraw . '/edit?hl=en_US' ;

        //https://docs.google.com/a/gapps-acct.open.ac.uk/spreadsheet/ccc?key=0AoG0FIOvSgLXdFRnc2d6aWMyekJXUVJXeWljNHhONFE&hl=en_US
        //https://docs.google.com/a/gapps-acct.open.ac.uk/document/d/153k34t0jIXNShxBF-JYJrG8Yq4ExIAAZqbqD9xCQXAs/edit?hl=en_US
        //https://docs.google.com/a/gapps-acct.open.ac.uk/present/edit?id=0AYG0FIOvSgLXZHdjOHc1bV8xY3NkcDU3Zng&hl=en_US

        $googleapps_docs = googleapps_docs::get_instance();
        $result = $googleapps_docs->get_sharing_link($docid);
        //Add a redirect url?
        if (!empty($this->siteconfig->gapps_prelink)) {
            $result = $this->siteconfig->gapps_prelink . urlencode($result);
        }
        return $result;

    }



    /**
     * Returns the name of the Moodle Group that represents the whole course
     * @return string
     */
    public function get_moodle_course_group_name () {
        $shortname = $this->course->shortname;
        return $shortname . ' Course Group';
    }

    /**
     * Returns the Google group name for the 'everyone' group for the course (all tutors and students)
     *
     * @return string
     */

    public function get_google_course_group_name() {

        $shortname = $this->course->shortname;
        return $shortname . '-CVPG';
    }

    /**
     * Create a new document for a group on Google Docs
     *
     * Create a new document for a group on Google Docs. Log into the site's
     * Google Account to do this if necessary. Save the new document ref in the
     * database. Return the new doc id. Throw exception on error.
     *
     * @param int $userid
     * @return boolean|string docid
     */

    public function create_document($groupid, $userid) {

        global $DB, $CFG;

        //todo - if there is no group mode -
        //what are the google documents called?
        //we will still have Moodle groups.

        $template = $this->get_template($groupid);
        $newdocname = $this->get_new_doc_name($groupid);
        $googleapps_docs = googleapps_docs::get_instance();

        if (empty($template)) {
            //no template; create an empty document
            $doctype = 'document';//Google doc type
            switch ($this->googlecollab->defaulttype) {
                case self::NEWSHEET: $doctype = 'spreadsheet';
                break;
                case self::NEWPRES: $doctype = 'presentation';
                break;
            }
            $docid = $googleapps_docs->create_empty_document($newdocname, $doctype);
        } else {
            //using template which is a stored_file
            //create the document using our put method
            //get docid for this

            $docid =  $googleapps_docs->create_document_from_template($template, $newdocname);

        }

        if ($docid === false) {
            return false;
        }

        //write gid to db
        $rec = new stdClass();
        $rec->actid = $this->googlecollab->id;
        $rec->docid = $docid;
        $rec->groupid = $groupid;
        $rec->timecreated = time();
        $DB->insert_record('googlecollab_docs', $rec);
        return $docid;

    }


    /**
     * Returns a template as a Moodle file object
     *
     * Returns a template as a Moodle file object or false if one does not exist.
     *
     * @param string $groupname
     * @return boolean|stored_file
     */


    public function get_template($groupid) {

        $template = false;

        $context = $this->modcontext;

        //TODO maybe make the component here and in /scaffold/designer a constant
        $fileinfo = array(
            'component' => 'mod_googlecollab',
            'filearea' => 'template',
            'itemid' => $groupid,
            'contextid' => $context->id);

        //or just get file assuming we can be sure of the name e.g. use the Google Name?
        $fs = get_file_storage();
        $tree = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'],
        $fileinfo['filearea'], $fileinfo['itemid'], null, true);

        //we are assuming only one matching file...
        //shld be ok because of only allowing one file per filepicker...

        foreach ($tree as $hash => $fileinfo) {

            $filename = $fileinfo->get_filename();
            $filesize = $fileinfo->get_filesize();

            if ($filename == '.' && $filesize == 0) {
                continue;
            }

            $template = $fileinfo;

        }

        //if the group does not have a template use the course one if it exists
        if (($template === false) && ($groupid != 0)) {
            return $this->get_template(0);
        }

        return $template;
    }

    /**
     * Given the group id work out if the doc should be shared with a group or an individual
     * @param Int $groupid
     * @returns String Email address to share with
     */
    public function get_sharing_name($groupid) {
        if ($this->siteconfig->gappgroups == self::GROUPMODE_ON &&
            $this->is_standard_group($groupid)) {
            //their google account supports groups which map onto the moodle groups
            $sharingid = $this->get_group_maillist_name($groupid);
        } else {
            $sharingid = $this->get_individual_maillist_name();
        }
        return $sharingid;
    }

    /**
     * Returns the name of the Google Account mailing list for a group
     *
     * Returns the name of the Google Account mailing list for a group.
     * Override this function to implement a different mapping pattern
     *
     * @param int $groupid
     * @return string
     */

    protected function get_group_maillist_name($groupid) {

        if ( $groupid == 0) {//no groups case: we share it with the course group
            $groupname = $this->get_google_group_name_from_moodle_group_name($this->get_moodle_course_group_name());
        } else {
            $groupname = $this->get_google_group_name_from_moodle_group_name(groups_get_group_name($groupid));
        }

        $domain = $this->siteconfig->gapps_googleappsdomain;
        $mailist = $groupname . '@' . $domain;
        $mailist = strtolower($mailist);
        return $mailist;
    }

    /**
     * Returns the name of the Google Account email for an individual
     *
     * @param int $groupid
     * @return string
     */

    protected function get_individual_maillist_name($userid = null) {

        global $USER, $DB;

        if (is_null($userid)) {
            $username = $USER->username;
            $mail = $USER->email;
            $userid = $USER->id;
        } else {
            $rec = $DB->get_record('user', array('id' => $userid), 'username');
            $username = $rec->username;
            $mail = $rec->email;
        }

        if ($this->siteconfig->usermail == self::USENAME) {
            $domain = $this->siteconfig->gapps_googleappsdomain;
            if ($this->siteconfig->staffmailsuffix != '') {
                $context = context_module::instance($this->cm->id);
                //Is user staff? Base this on capability as too hard to determine by role
                if (has_capability('mod/googlecollab:viewall', $context, $userid)) {
                    $username = $username . $this->siteconfig->staffmailsuffix;
                }
            }
            $mailist = $username . '@' . $domain;
            $mailist = strtolower($mailist);
            return $mailist;
        } else {
            return $mail;
        }
    }

    /**
     * Depending on system setting will check whether the user
     * has a valid Google Apps account for the domain
     * Default is true
     * @return Boolean true if ok
     */
    public function check_user_in_google() {
        //We can only check for certain if user is in domain
        if ($this->siteconfig->usermail == self::USENAME) {
            $username = $this->get_individual_maillist_name();
            $googleapps_docs = googleapps_docs::get_instance();
            return $googleapps_docs->user_exists($username);
        } else {
            //have to assume they have a valid google account
            return true;
        }
    }

    /**
     * Return the Doc id for a group
     *
     *
     * @param int $usergroup
     * @return string
     */

    public function get_doc_for_group($usergroup) {

        global $DB;

        $rec = $DB->get_record('googlecollab_docs', array('actid' => $this->googlecollab->id,
            'groupid' => $usergroup ), '*');

        if (empty($rec)) {
            throw new moodle_exception('documentmissing', 'googlecollab');
        }

        return $rec->docid;
    }

    /**
     * Adds a user to the acl list for a document
     *
     * @param string $docid
     * @param string $useremail the email to  add
     * @param string $role writer|reader
     * @return boolean
     *
     */

    public function add_user_to_doc_acl($docid, $useremail, $role) {

        if (! in_array($role, array('reader', 'writer'))) {
            throw new coding_exception('role must be \'writer\' or \'reader\'');
        }

        $useremail = trim($useremail);

        $role_check = $this->get_permissions($docid, $useremail);

        //calling add_sharing will return false if the user already has permissions
        //so just return true now if that is the case
        if ( ($role_check == $role) || ($role == 'reader' && $role_check == 'writer') ) {
            return true;
        }

        $googleapps_docs = googleapps_docs::get_instance();
        $shared = $googleapps_docs->add_sharing($docid, $useremail, $role);

        return $shared;

    }

    /**
     * Deletes a user from the document sharing list
     * @param $docid string doc identifier
     * @param $useremail string user to remove
     * @return Boolean success
     */
    public function del_user_from_doc_acl($docid, $useremail) {

        $useremail = trim($useremail);

        $googleapps_docs = googleapps_docs::get_instance();
        $unshared = $googleapps_docs->del_sharing($docid, $useremail);

        return $unshared;

    }



    /**
     * Checks if the group name is one which is expected to have a
     * correspondence on Google.
     *
     * Checks if the group name is one which is expected to have a
     * correspondence on Google. Override this function to implement a different mapping pattern
     *
     * @param string $moodlename
     * @return boolean
     */

    public function is_standard_group($groupid) {
        //If no groupid (e.g. no group then check course group exists)
        if ($groupid == 0 ) {
            if ($groupid = groups_get_group_by_name($this->course->id,
                $this->get_moodle_course_group_name())) {
                    return true;
            } else {
                return false;
            }
        }

        $moodlename = groups_get_group_name($groupid);

        $patt_course = $this->patt_course;
        $patt_tutorgroup = $this->patt_tutorgroup;
        $patt_clustergroup = $this->patt_clustergroup;

        if ( preg_match($patt_course, $moodlename) ||
        preg_match($patt_tutorgroup, $moodlename) ||
        preg_match($patt_clustergroup, $moodlename)) {
            return true;
        }

        return false;

    }

    /**
     * Returns the name of a matching Google Account group for Moodle group
     *
     * Returns the name of a matching Google Account group for Moodle group
     * Override this function to implement a different mapping pattern
     *
     * @param int $groupid
     * @return string
     */

    protected function get_google_group_name_from_moodle_group_name($moodlename) {

        $patt_course = $this->patt_course;
        $patt_tutorgroup = $this->patt_tutorgroup;
        $patt_clustergroup = $this->patt_clustergroup;

        if (preg_match($patt_course, $moodlename, $matches)) {
            $googlename = $matches[1] . '-CVPG';
        } else if (preg_match($patt_tutorgroup, $moodlename, $matches)) {
            $googlename = str_replace(' ', '', $matches[1]) .  '-TG-' . $matches[2] . '-' . $matches[3];
        } else if (preg_match($patt_clustergroup, $moodlename, $matches)) {
            $googlename = str_replace(' ', '', $matches[0]) . '-CG';
        } else {
            // throw new moodle_exception('nogroupmatch', 'googlecollab');
            //it is an ad hoc group.
            $googlename = str_replace(' ', '-', $moodlename);//TODO - do we have to remove unwanted chars?
        }

        return preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $googlename);

    }


    /**
     * Returns the name for a new Google Document based on the group name.
     *
     * Returns the name for a new Google Document based on the Moodle group name.
     *
     * @param int $groupid
     * @return string
     */

    protected function get_new_doc_name($groupid) {

        if ( $groupid == 0) {
            $groupname = '';
        } else {
            $groupname = $this->get_google_group_name_from_moodle_group_name(groups_get_group_name($groupid));
            //As we add in shortname to title delete in group name to avoid repeating
            $groupname = str_replace($this->course->shortname, '', $groupname);
        }

        $docname = $this->course->shortname . '-' . $this->googlecollab->name;

        if (! empty($groupname)) {
            $docname = $docname . '-' . $groupname;
        }

        return $docname;

    }


    /**
     * Shares a document with the given user
     *
     * Shares a document with the given user. This method specifically
     * shares the document as a writer. @see googlecollab::add_user_to_doc_acl
     * for one that can control role
     *
     * @param string $docid
     * @param int $maillistname email to share with null = current user
     * @return boolean
     */

    public function add_sharing_on_the_fly($docid, $maillistname = null) {

        if (is_null($maillistname)) {
            //Use current user
            $maillistname = $this->get_individual_maillist_name();
        }
        return $this->add_user_to_doc_acl($docid, $maillistname, 'writer');

    }

    /**
     * Gets the permissions on a Google doc for a given user
     *
     * @param string $docid google docs id
     * @param string $useraccount email to check against
     * @return boolean|string none|reader|writer|owner
     */

    protected function get_permissions($docid, $useraccount) {

        $googleapps_docs = googleapps_docs::get_instance();

        $mode = $googleapps_docs->get_permissions($docid, $useraccount);

        if ($mode === false) {
            return false;
        }

        return $mode;

    }

    public function get_sharing_list($docid) {
        $googleapps_docs = googleapps_docs::get_instance();

        return $googleapps_docs->get_sharing_list($docid);
    }


    /**
     * Delete a Document from Google
     *
     * Delete a Document from Google. Actually the method trashes
     * the document unless the delete parameter is passed as true
     * in which case it will permanently delete it.
     * Will also delete a cached doc and record - but not doc record
     *
     * @param string $docid
     * @return boolean
     */

    public function delete_document($docid, $delete = false, $andcached = true) {
        global $DB, $CFG;
        if ($andcached) {
            $sql = "SELECT * FROM {googlecollab_cache} WHERE " . $DB->sql_compare_text('docid') . " = ?";
            $cache = $DB->get_record_sql($sql, array($docid));
            if ($cache) {
                fulldelete($CFG->tempdir . '/' . self::TEMPDIR . '/' . $cache->filepath);
                $sql = $DB->sql_compare_text('docid') . " = ?";
                $DB->delete_records_select('googlecollab_cache', $sql, array($docid));
            }
        }

        $googleapps_docs = googleapps_docs::get_instance();
        return $googleapps_docs->delete_document($docid, $delete);

    }

    /**
     * Fetches the link to a downloaded version of a doc
     * @param unknown_type $docid
     */
    public function get_reading_link($docid) {
        global $CFG;
        $filepath = $this->fetch_doc_for_reading($docid);
        if (!$filepath) {
            return false;
        }
        if (strpos($filepath, '/embed?') || strpos($filepath, '/pub?')) {
            // We've been given a google url for embedding.
            return $filepath;
        }
        $filename = basename($filepath);
        return file_encode_url($CFG->wwwroot.'/pluginfile.php', '/' . $this->modcontext->id .
            '/mod_googlecollab/google/' . $filename);
    }

    /**
     * Download a document from Google for local display
     * For spreadsheets will publish and use embed instead if possible.
     *
     * @param string $docid
     * @return string
     */

    public function fetch_doc_for_reading($docid) {
        global $CFG;
        $googleappsdocs = googleapps_docs::get_instance();

        list($saved_etag, $saved_local_path) = $this->get_saved_etag($docid);

        $ret = $googleappsdocs->get_download_link($docid);

        if ( $ret === false) {
            return false;
        }

        list($download_link, $type, $etag)  = $ret;

        // For spreadsheets try and publish and use embed rather than downloaded pdf.
        if ($type == 'presentation' || $type == 'spreadsheet') {
            $published = $googleappsdocs->publish_doc($docid, $type);
            if ($published) {
                $docid = str_replace("$type:", '', $docid);
                if ($type == 'spreadsheet') {
                    return "https://docs.google.com/spreadsheet/pub?key=$docid&output=html&widget=true";
                } else {
                    return "https://docs.google.com/presentation/d/$docid/embed?start=false&loop=false&delayms=3000";
                }
            }
        }

        //use cache and cache file still exists
        //Note etags don't work for presentations will always get new.
        if (($saved_etag == $etag) &&
            (file_exists($CFG->tempdir . '/' . self::TEMPDIR . '/' . $saved_local_path))) {
            $local_path =  $saved_local_path;
            //var_dump('USING CACHE');
        } else {
            //var_dump('GETTING FRESH FILE');
            $local_path = basename($googleappsdocs->download_and_save($download_link, $type));
            if (!$local_path) {
                return false;
            }
            $this->save_etag($docid, $etag, $local_path );
        }

        return $local_path;

    }

    /**
     * Returns the saved etag for a document
     *
     * @param $docid
     * @return array of saved etag, the path to the corresponding file
     *
     */

    public function get_saved_etag($docid) {
        global $DB;

        $sql = "SELECT * FROM {googlecollab_cache} WHERE " . $DB->sql_compare_text('docid') . " = ?";
        $rec = $DB->get_record_sql($sql, array($docid));

        if (empty($rec)) {
            return array("", "");
        } else {
            return array($rec->etag, $rec->filepath);
        }

    }

    /**
     * Saves an cache record for a document
     *
     * @param string $docid
     * @param string $etag
     * @param string $local_path
     * @return boolean
     *
     *
     */

    public function save_etag($docid, $etag, $local_path) {
        global $DB, $CFG;

        $sql = "SELECT * FROM {googlecollab_cache} WHERE " . $DB->sql_compare_text('docid') . " = ?";
        $rec = $DB->get_record_sql($sql, array($docid));

        if ($rec) {
            fulldelete($CFG->tempdir . '/' . self::TEMPDIR . '/' . $rec->filepath);//delete old file
            $update = new stdClass();
            $update->id = $rec->id;
            $update->docid = $docid;
            $update->etag = $etag;
            $update->filepath = $local_path;
            $DB->update_record('googlecollab_cache', $update);

        } else {
            $insert = new stdClass();
            $insert->docid = $docid;
            $insert->etag = $etag;
            $insert->filepath = $local_path;
            $DB->insert_record('googlecollab_cache', $insert);
        }

    }



}

//http://gdatatips.blogspot.com/2008/11/2-legged-oauth-in-php.html

/**
 * Class googleapps_oauth
 *
 * Class for googlecollab.  Extends moodle curl and provides a method
 * to sign a request for 2 legged OAuth using the library from
 * http://oauth.googlecode.com/svn/code/php/OAuth.php
 *
 * @package mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class googleapps_oauth extends curl {

    /**
     * Constructor
     *
     * @param array $options
     */

    public function __construct( $options) {
        $options['cache'] = false;
        parent::__construct($options);
        $googlecollabinst = googlecollab::get_instance(null, false);
        if (!empty($googlecollabinst->siteconfig->gapps_consumerws) &&
            empty($googlecollabinst->siteconfig->gapps_consumersecret)) {
            //Because we had to call googlecollab with no page instance need to get secret from ws
            $googlecollabinst->siteconfig->gapps_consumersecret =
                $googlecollabinst->get_secret_from_ws();
        }
        $this->consumerkey = $googlecollabinst->siteconfig->gapps_consumerkey;
        $this->consumersecret = $googlecollabinst->siteconfig->gapps_consumersecret;
    }

    /**
     * Sign the request with the key and secret
     *
     * Sign the request with the key and secret and set any additional headers.
     *
     * @param string $url the base url of the call
     * @param array $params any additional parameters to pass as a query string
     * @param array $headers any additional headers as array or scalar
     */

    public function sign_request($url, $params = null, $headers = null, $method = 'GET') {

        $consumer = new OAuthConsumer($this->consumerkey, $this->consumersecret, null);
        $request = OAuthRequest::from_consumer_and_token($consumer, null, $method, $url, $params);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
        $this->setHeader( $request->to_header());
        if (! is_null($headers)) {
            $this->setHeader($headers);
        }

    }

    public function implode_assoc($inner_glue, $outer_glue, $array) {
        $output = array();
        foreach ($array as $key => $item) {
            $output[] = $key . $inner_glue . urlencode($item);
        }
        return implode($outer_glue, $output);
    }

    /**
     * Extend 'curl's' request method as public so we can use it.
     *
     * @param string $url
     * @param array options
     * @return boolean
     */

    public function request($url, $options = array()) {

        return parent::request($url, $options);

    }

}


class googleapps_docs  {

    private static $instance;
    protected $adminuser;

    const GOOGLEFEEDSURL = 'https://docs.google.com/feeds/default/private/full';
    const GDNAMESPACE = 'http://schemas.google.com/g/2005';
    const SCOPE = 'http://docs.google.com/feeds/ http://spreadsheets.google.com/feeds/ http://docs.googleusercontent.com/';


    private function __construct() {

        $this->adminuser = trim(get_config('mod_googlecollab', 'gapps_googleappsadminuser'));

    }

    public static function get_instance() {

        if (!self::$instance) {
            self::$instance = new googleapps_docs();
        }

        return self::$instance;

    }


    //create empty document
    public function create_empty_document($newdocname, $doctype) {

        //the feed url for this call
        $baseurl =  'https://docs.google.com/feeds/default/private/full';
        $user = $this->adminuser;
        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
            array('Content-Type: application/atom+xml', 'GData-Version: 3.0' ), 'POST');

        $data =  <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns="http://www.w3.org/2005/Atom">
<category scheme="http://schemas.google.com/g/2005#kind"
term="http://schemas.google.com/docs/2007#$doctype"/>
<title>$newdocname</title>
</entry>
EOF;

        $data = trim($data);
        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->post($url, $data, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 201 ) {

            $xml = new SimpleXMLElement($ret);
            $xml->registerXPathNamespace("gd", self::GDNAMESPACE);
            $resource = $xml->xpath("gd:resourceId");
            $resource_id = (string) $resource[0];
            $matches = array();
            if (preg_match('/^(?:document:|spreadsheet:|presentation:)(.+)$/', $resource_id, $matches)) {
                $result =  $matches[0];
                return $result;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }


    /**
     * Creates a collection in the root
     *
     * @param string $name
     * @return boolean|string false on error or resource id of created collection
     */



    /**
     * Create a document on Google based on a locally stored template file
     *
     *
     * @param file_storage $template
     * @param string $name
     */

    public function create_document_from_template($template, $name) {

        $result = $this->send_file_resumable_using_file($template, $name);

        if ($result == false) {
            return false;
        }

        return $result;

    }


    /**
     * Sends a file to Google Docs using the resumable method
     *
     * The Google resumable API method supports sending a file in chunks. Current usage
     * is to send the whole file in the first chunk
     * See http://code.google.com/apis/documents/docs/3.0/developers_guide_protocol.html
     * #ResumableUpload
     * This method uses an overriden version of put() which accepts a file handle so
     * we don't need to get the file path.
     *
     * @param stored_file $file
     * @param string $name
     * @return boolean
     *
     */

    protected function send_file_resumable_using_file($file, $name) {

        /*
         using file handles also means we can handle files broken into
         chunks without writing to disk
         if $file->get_filesize(0 > toobig
         $str = $file->get_content()
         $chunks = the $str split into chunks
         loop:
         $fh = fopen('php://memory', 'rw');
         fwrite($fh, $chunk[0]);
         rewind($fh);
         send this chunk.
         end loop
         */

        global $CFG;

        //the feed url for this call
        ///folder:root/contents
        $baseurl =  'https://docs.google.com/feeds/upload/create-session/default/private/full';
        $user = $this->adminuser;
        $params = array('xoauth_requestor_id' => $user);

        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' ), 'POST');
        //'Content-Type: application/atom+xml'

        $filesize = $file->get_filesize();
        $connector->setHeader("Content-Length: 0");
        $connector->setHeader("Content-Type: ". $file->get_mimetype());
        $connector->setHeader("Slug: ". $name);
        $connector->setHeader("X-Upload-Content-Type: ". $file->get_mimetype());
        $connector->setHeader("X-Upload-Content-Length: ". $filesize);
        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);
        $ret = $connector->post($url, '', array('CURLOPT_HEADER'=>true));

        if ($connector->info['http_code'] !== 200) {
            return false; //the request to get a unique uri failed
        }

        $return_headers = $this->http_parse_headers($ret);

        //   var_dump($file->get_mimetype(), $return_headers);exit;

        if (! array_key_exists('Location', $return_headers)) {
            return false;
        }

        $fh = $file->get_content_file_handle();

        //send the file in one chunk
        unset($connector);
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($return_headers['Location'], null,
        array( 'GData-Version: 3.0' ), 'PUT');

        $range_string = "bytes 0-". ($filesize - 1) . "/" . $filesize;

        $connector->setHeader("Content-Length: ".$filesize);
        $connector->setHeader("Content-Type: ". $file->get_mimetype());
        $connector->setHeader("Content-Range: ". $range_string );
        //user request directly as curl moodle's put wants a path
        $options = array();
        $options['CURLOPT_PUT']        = 1;
        $options['CURLOPT_INFILESIZE'] = $filesize;
        $options['CURLOPT_INFILE']     = $fh;
        //if (!isset($this->options['CURLOPT_USERPWD'])) {
        //    $this->setopt(array('CURLOPT_USERPWD'=>'anonymous: noreply@moodle.org'));
        //}
        $url = $return_headers['Location'] . '?' . $connector->implode_assoc('=', '&', $params);
        $ret = $connector->request($return_headers['Location'], $options);

        //var_dump('AAA', $connector->info['http_code'], $ret, 'AAA');exit;

        if ($connector->info['http_code'] == 201) {

            $xml = new SimpleXMLElement($ret);
            $xml->registerXPathNamespace("gd", self::GDNAMESPACE);
            $xml->registerXPathNamespace("default", "http://www.w3.org/2005/Atom"); //simple xml requires you register the default namespace

            $content = $xml->xpath("default:content");
            $downloadlink = $content[0]->attributes()->src;//see the SimpleXML documentation

            $resource = $xml->xpath("gd:resourceId");
            $resource_id = (string) $resource[0];
            $matches = array();
            if (preg_match('/^(?:document:|spreadsheet:|presentation:)(.+)$/', $resource_id, $matches)) {
                $result =  $matches[0];
                return $result;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }



    /**
     * Return http headers as array
     *
     * @param string $headers
     * @return array
     *
     */

    private function http_parse_headers($headers) {

        $patt1 = '/^HTTP/';
        $patt2 = '/(^.+?): *(.+)$/';
        $return_headers = array();
        //covers case of \r\n and \n
        $headers = str_replace("\r", "", $headers);
        $headers = explode("\n", $headers);

        foreach ($headers as $line) {
            if (preg_match($patt1, $line)) {
                continue;
            }
            if (preg_match($patt2, $line, $matches)) {
                $return_headers[$matches[1]] = $matches[2];
            }
        }

        return $return_headers;

    }


    public function get_document_as_html() {

        return 'display in iframe read only version';
    }

    /**
     * Returns the url at which the document can be edited
     *
     * @params string $docid
     * @return string
     */

    public function get_sharing_link($docid) {

        //the feed url for this call
        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid;
        $user = $this->adminuser;

        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        //'Content-Type: application/atom+xml',
        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' ), 'GET');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->get($url, null, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 200 ) {

            $xml = new SimpleXMLElement($ret);
            //$xml->registerXPathNamespace("gd", self::GDNAMESPACE);
            $xml->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
            $resource = $xml->xpath("//default:link[@rel='alternate']");
            $href =  (string) $resource[0]->attributes()->href;
            if (empty($href )) {
                return false;
            }

            return $href;

        } else {
            return false;
        }
    }

    /**
     * Saves a Google doc to a local web accessible location
     *
     *
     * @param string $link the Google download link
     * @param string $type document|spreadsheet|presentation
     * @return the local web address (absolute url)
     */

    public function download_and_save($link, $type) {
        //we will have issues with spreadsheets and presentations, scope
        //var_dump('type is' . $type);
        //var_dump('link is' , $link);
        global $CFG;
        $urlparts = parse_url($link);
        $baseurl = $urlparts['scheme'] . '://' . $urlparts['host'] . $urlparts['path'];
        parse_str($urlparts['query'], $output);
        $params = $output;
        //not clear if this is necessary. scope may be associated
        //with the consumer key on the Apps Control Panel
        //http://code.google.com/googleapps/domain/articles/2lo-in-tasks-for-admins.html
        //TODO - someone with access to the Control Panel should confirm
        //we support the required scopes
        //$params['scope'] = self::SCOPE;

        switch ($type) {
            case 'presentation':
                $params['exportFormat'] = 'pdf';
                $params['format'] = 'pdf';
                $extension = '.pdf';
                break;
            case 'document':
                $params['exportFormat'] = 'html';
                $params['format'] = 'html';
                $extension = '.htm';
                break;
            case 'spreadsheet':
                $params['exportFormat'] = 'html';
                $params['format'] = 'html';
                $extension = '.htm';
                break;
        }

        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' ), 'GET');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request

        $filedata = $connector->get($url, null, array('CURLOPT_HEADER'=>false));

        //Google will output empty docs as invalid pdfs with no stream - check
        if ($extension == '.pdf' && !strpos($filedata, 'stream')) {
            return false;
        }
        if ($extension == '.htm') {
            // Update any relative urls made by Google (e.g. css in spreadsheets).
            $filedata = str_replace('"/static/', '"https://drive.google.com/static/', $filedata);
        }

        $ret = make_temp_directory(googlecollab::TEMPDIR);

        $tmpfile = tempnam($ret, 'googlecollab');
        //rename with extension to make serving easier
        rename($tmpfile, $tmpfile .  $extension);
        $tmpfile = $tmpfile . $extension;//getting value of rename doesn't work...
        $fh = fopen($tmpfile, 'w');
        fwrite($fh, $filedata);
        fclose($fh);

        return $tmpfile;

    }


    /**
     * Returns the url from which the document can be downloaded.
     *
     * Returns the url at which the document can be downloaded. Does not
     * actually download the document. @see googleapps_docs::download_and_save()
     *
     * @param string $docid
     * @return boolean|array false if error or array of download link, doctype, etag .
     */

    public function get_download_link($docid) {

        //the feed url for this call
        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid;
        $user = $this->adminuser;

        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        //'Content-Type: application/atom+xml',
        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' ), 'GET');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->get($url, null, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 200 ) {

            $xml = new SimpleXMLElement($ret);
            $xml->registerXPathNamespace("gd", self::GDNAMESPACE);
            $xml->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");

            $resource = $xml->xpath("/default:entry/default:content");
            if (empty($resource )) {
                return false;
            }
            $src =  (string) $resource[0]->attributes()->src;

            $resource2 = $xml->xpath("/default:entry/gd:resourceId");

            if (empty($resource2 )) {
                return false;
            }
            $resource_id = (string) $resource2[0];
            $match = preg_match('/^(document|spreadsheet|presentation):(.+)$/', $resource_id, $matches);
            if (! isset($matches[1])) {
                return false;
            }
            $type = $matches[1];

            $resource3 = $xml->xpath("/default:entry");
            //var_dump($resource3);
            $etag = (string) $resource3[0]->attributes(self::GDNAMESPACE)->etag;
            //var_dump('ETAG ON RESOURCE FEED for IS:', $docid, $etag);

            $resource4 = $xml->xpath("/default:entry/default:updated");
            $updated = (string) $resource4[0];
            //var_dump('updated RESOURCE FEED for IS:',  $updated );

            return (array($src, $type, $etag));

        } else {
            return false;
        }
    }

    /**
     * Attempts to publish doc so can be used for embedding
     * @param string $docid
     * @param string $doctype
     * @return boolean publish success
     */
    public function publish_doc($docid, $doctype = 'presentation') {
        global $CFG;
        $docid = urlencode($docid);
        $data = <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:docs="http://schemas.google.com/docs/2007"
    xmlns:gd="http://schemas.google.com/g/2005">
    <category scheme="http://schemas.google.com/g/2005#kind"
        term="http://schemas.google.com/docs/2007#$doctype" />
    <docs:publish value="true" />
    <docs:publishAuto value="true" />
    <docs:publishOutsideDomain value="true" />
</entry>
EOF;
        $data = trim($data);

        $baseurl = "https://docs.google.com/feeds/default/private/full/$docid/revisions/0";
        $user = $this->adminuser;

        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
                array('GData-Version: 3.0', 'If-Match: *', 'Content-Type: application/atom+xml'), 'PUT');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        $file = tempnam($CFG->tempdir . '/googlecollab/', 'gct');
        file_put_contents($file, $data);
        $ret = $connector->put($url, array('file' => $file), array('CURLOPT_HEADER' => false, 'CURLOPT_USERPWD' => null));
        unlink($file);
        // Always get error, even when doc is published. See:
        // http://stackoverflow.com/questions/5708638/google-documents-list-api-how-to-publish-a-document .
        // Try and access doc to verify if successful.
        $connector->resetopt();
        $connector = new curl();
        $docid = str_replace("$doctype%3A", '', $docid);
        $url = "https://docs.google.com/$doctype/d/$docid/embed";
        if ($doctype == 'spreadsheet') {
            $url = "https://docs.google.com/spreadsheet/pub?key=$docid&output=html&widget=true";
        }
        $ret = $connector->get($url);
        if ($connector->info['http_code'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns whether user is on domain *No way of checking this using docs list api
     * Use alternate methods for now...
     * @param $email
     */
    public function user_exists($email) {

        if (isset($_SERVER['HTTP_SAMS_USER_AUTHIDS'])) {
            //SAMS authentication
            if (strpos($_SERVER['HTTP_SAMS_USER_AUTHIDS'], 'GOOGLE') !== false || empty($CFG->debugdisplay)) {
                return true;
            } else {
                return false;
            }
        } else {
            //Default always true
            return true;
        }

    }

    public function add_user_to_sharing_list() {

    }

    /**
     * Shares a document with the given group
     *
     * Shares a document with the given group. Google appears to set the permission
     * even if the group does not exist.
     *
     *
     * @param int $docid
     * @param string $email - an individual or group email acccont to add to the ACL
     * @return boolean
     */

    public function add_sharing($docid, $email, $role = 'reader' ) {

        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid.'/acl';
        $user = $this->adminuser;
        $params = array(
            'xoauth_requestor_id' => $user,
            'send-notification-emails' => 'false'//don't notify by email
        );
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
        array('Content-Type: application/atom+xml', 'GData-Version: 3.0' ), 'POST');

        $data =  <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gAcl='http://schemas.google.com/acl/2007'>
  <category scheme='http://schemas.google.com/g/2005#kind'
    term='http://schemas.google.com/acl/2007#accessRule'/>
  <gAcl:role value='$role'/>
  <gAcl:scope type='user' value='$email'/>
</entry>

EOF;

        $data = trim($data);
        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->post($url, $data, array('CURLOPT_HEADER'=>false));

        //nb a 409 and error message is returned if the user already has access.

        if ( $connector->info['http_code'] == 201 ) {

            return true;

        } else {
            return false;
        }

    }

    /**
     * Deletes the user from teh doc sharing list
     * @param $docid string
     * @param $email string
     * @return Boolean success
     */
    public function del_sharing($docid, $email) {

        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid.'/acl';
        $baseurl .= "/user:$email";
        $user = $this->adminuser;
        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
        array('Content-Type: application/atom+xml', 'GData-Version: 3.0' ), 'DELETE');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->delete($url, '', array('CURLOPT_HEADER'=>false));

        //nb a 409 and error message is returned if the user already has access.
        if ( $connector->info['http_code'] == 200 ) {

            return true;

        } else {
            return false;
        }

    }

    /**
     * Returns result of getting the documents sharing list
     * @param $docid
     * @return boolean|array of username(email) => role
     */
    public function get_sharing_list($docid) {
        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid.'/acl';
        $user = $this->adminuser;
        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        $connector->sign_request($baseurl, $params,
        array('Content-Type: application/atom+xml', 'GData-Version: 3.0' ), 'GET');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->get($url, null, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 200 ) {
            $users = array();
            $xml = new SimpleXMLElement($ret);

            $xml->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
            $xml->registerXPathNamespace("gAcl", "http://schemas.google.com/acl/2007");
            //$entries = $xml->xpath("//entry/gAcl:scope[@type='user' and @value='" . $useraccount . "']");
            $entries = $xml->xpath("//default:feed/default:entry");

            foreach ($entries as $node) {
                $node->registerXPathNamespace("gAcl", "http://schemas.google.com/acl/2007");
                $scopenode = $node->xpath("gAcl:scope");
                $acct = (string) $scopenode[0]->attributes()->value;
                $rolenode = $node->xpath("gAcl:role");
                $role = (string) $rolenode[0]->attributes()->value;
                $users[$acct] = $role;
            }
            return $users;
        } else {
            return false;
        }
    }

    /**
     * Gets the permissions on a Google doc for a given google account
     *
     * @param string $docid
     * @param int $userid
     * @return boolean|string none|reader|writer|owner
     */

    public function get_permissions($docid, $useraccount) {

        $ret = $this->get_sharing_list($docid);

        if (!$ret) {
            return false;
        }

        $role = 'none';

        if (isset($ret[$useraccount])) {
            return $ret[$useraccount];
        }

        return $role;

    }

    /**
     * Delete a document on Google by docid
     *
     *
     * @param string $docid
     * @param boolean $delete true to completely delete false to just trash
     * @return boolean
     *
     */

    public function delete_document($docid, $delete = false) {

        //the feed url for this call
        $baseurl =  'https://docs.google.com/feeds/default/private/full/'.$docid;
        $user = $this->adminuser;
        $params = array('xoauth_requestor_id' => $user);
        $connector = new googleapps_oauth(array('debug' => 0));
        //'Content-Type: application/atom+xml',
        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' ), 'GET');

        $url = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        //our resume method sets CURLOPT_HEADER to true and filelib curl does not reset this option
        //in its cleann_opt method which is called before every request
        $ret = $connector->get($url, null, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 200 ) {

            $xml = new SimpleXMLElement($ret);
            $xml->registerXPathNamespace("default", "http://www.w3.org/2005/Atom");
            $resource = $xml->xpath("/default:entry/default:link[@rel='edit']");

            $editlink = (string) $resource[0]->attributes()->href;

        } else {
            return false;
        }

        //now send delete to the editlink
        unset($connector);

        $connector = new googleapps_oauth(array('debug' => 0));
        //'Content-Type: application/atom+xml',

        if ($delete) {
            $params['delete'] = 'true';

        }

        $connector->sign_request($baseurl, $params,
        array( 'GData-Version: 3.0' , 'If-Match: *' ), 'DELETE');

        $url2 = $baseurl . '?' . $connector->implode_assoc('=', '&', $params);

        $ret = $connector->delete($url2, null, array('CURLOPT_HEADER'=>false));

        if ( $connector->info['http_code'] == 200 ) {
            return true;

        } else {
            return false;
        }

    }

}

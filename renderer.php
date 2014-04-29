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
 * Workshop module renderering methods are defined here
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright  2011 The open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/mod/googlecollab/local_forms.php');

class mod_googlecollab_renderer extends plugin_renderer_base {

    /**
     * Return a message in case of Activity which is not yet set up.
     * @param $cmid;
     * @param boolean $canedit
     * @param string $mode
     * @return string
     */

    public function render_googlecollab_notyetsetup($cmid) {

        $string = html_writer::tag('p', get_string('notyetsetup',
                'googlecollab') );

        return $string;
    }



    /**
     * Return a message to explain the Document manage screen
     *
     * @return string
     */

    public function managedocumentsintro() {

        $string = html_writer::tag('p', get_string('managedocumentsintro',
                'googlecollab') );

        return $string;
    }


    /**
     * Return the group name for display in a subheading on the manage templates page
     *
     * @param int $groupid
     * @return string
     */



    public function groupname_as_subheading_for_templates($groupid) {

        $groupname = groups_get_group_name($groupid);

        if (empty($groupname)) {
            return html_writer::tag('p', get_string('coursegrouptemplate', 'googlecollab'));
        } else {
            return html_writer::tag('p', $groupname);
        }

    }

    /**
     * Return the group name for display in a subheading
     *
     * @param int $groupid
     * @param int $groupmode
     * @return string
     */


    public function  groupname_as_subheading($groupid, $groupmode) {
        $groupname = groups_get_group_name($groupid);

        $str = get_string('currentgroup', 'googlecollab');

        if (empty($groupname)) {
            if ($groupmode == NOGROUPS) {
                return '';
            } else {
                $str.= get_string('choosegroup', 'googlecollab');
                return html_writer::tag('p', $str);
            }
        } else {

            return html_writer::tag('p', $str . $groupname);
        }

    }

    /**
     * Return the cannot create message
     *
     * @return string
     */

    public function cannot_create() {
        return html_writer::tag('p', get_string('cannotcreate',
                'googlecollab') );

    }

    /**
     * Return the cannot create message
     *
     * @return string
     */

    public function cannot_update() {
        return html_writer::tag('p', get_string('cannotupdate',
                'googlecollab') );

    }

    /**
     * Return a br string
     *
     * @return string
     */

    public function breaker() {
        return html_writer::tag('br' , '');

    }

    /**
     * Return the cannot create message
     *
     * @return string
     */

    public function render_cannotupdate() {
        return html_writer::tag('p', get_string('cannotupdate',
                'googlecollab') );

    }

    /**
     * Return the cannot view/edit message
     *
     * @return string
     */

    public function nopermissionsondoc() {
        return html_writer::tag('p', get_string('cannotvieworedit',
                'googlecollab') );

    }




    /**
     * Return the header for manage templates page
     *
     * @return string
     */

    public function manage_templates() {

        return $this->output->heading(get_string('managetemplates',
                'googlecollab'), 2);

    }

    /**
     * Return the header for create/add templates page
     *
     * @return string
     */

    public function createupdatetemplate() {

        return $this->output->heading(get_string('createupdatetemplate',
                'googlecollab'), 2);

    }


    /**
     * Returns the text wrapped in a para
     *
     * @param string $intro
     * @return string
     */

    public function render_intro($intro) {
        return html_writer::tag('div', $intro );

    }

    /**
     * Returns the heading
     *
     * @param string $coursename
     * @return string
     */

    public function render_heading($coursename) {
        return $this->output->heading($coursename, 1);

    }

    /**
     * Returns the activity heading
     *
     * @param string $coursename
     * @return string
     */

    public function render_activity_heading($actname) {
        return $this->output->heading($actname, 2);

    }

    public function render_gdoc_link($link) {
        $out = html_writer::tag('p', get_string('doclinkdesc', 'googlecollab'));
        $out .= html_writer::start_tag('p');
        $out.= html_writer::tag('a', get_string('doclinktext', 'googlecollab'),
            array('href' => $link, 'id' => 'gdoclink'));
        $out .= html_writer::end_tag('p');
        return $out;
    }


    /**
     * Return a table with groups and templates for the separate groups case
     *
     *
     * @param array $groups
     * @return string
     */

    public function groups_templates_in_course($googlecollab, $groups, $page) {
        global $DB, $OUTPUT;

        $str = "";

        $str.= $this->output->heading(get_string('coursetemplate', 'googlecollab'), 3);
        $str.= html_writer::tag('p', get_string('coursetemplateintro', 'googlecollab') );

        $str.= $this->groups_nogroups($googlecollab);

        $str.= $this->output->heading(get_string('grouptemplates', 'googlecollab'), 3);

        $str.= html_writer::tag('p', get_string('templatesintro', 'googlecollab') );

        $itemsperpage = 10;
        $tot = count($groups);
        $url = new moodle_url('/mod/googlecollab/templates.php', array('id' => $googlecollab->cm->id ));

        $offset = $page * $itemsperpage;
        $groups_slice = array_slice($groups, $offset , $itemsperpage);

        $table = new html_table();
        $groupname = get_string('groupname', 'googlecollab');
        $template = get_string('template', 'googlecollab');
        $table->head = array($groupname, $template);
        //$table->attributes['class'] = 'generaltable';
        $fs = get_file_storage();
        $context = context_module::instance($googlecollab->cm->id);

        foreach ($groups_slice as $groupid => $group) {

            $files = $fs->get_area_files($context->id, 'mod_googlecollab', 'template', $group->id, null, false);

            $havetemplate = count($files);

            $doc = $DB->get_record('googlecollab_docs', array('actid' => $googlecollab->googlecollab->id, 'groupid' => $group->id));
            $disabledbut = '';
            if ($havetemplate) {
                $buttontext = get_string('updatetemplate', 'googlecollab');
                $action = 'update';
                $state = false;
            } else {
                $buttontext = get_string('createtemplate', 'googlecollab');
                $state = false;
                $action = 'create';
            }
            if ($doc) {
                $action = '';
                $state = true;
                $disabledbut = $OUTPUT->notification(get_string('googledocexists', 'googlecollab'));
            }

            $url = new moodle_url('/mod/googlecollab/managetemplate.php',
                array('id'=>$googlecollab->cm->id, 'group' => $group->id,
                'mode' => SEPARATEGROUPS, 'action' => $action));
            $button =  $this->output->single_button($url,
                $buttontext, 'get', array('disabled'=>$state, 'title'=>$buttontext));

            $table->data[] = array($group->name, $disabledbut . $button);

        }
        $pageurl = new moodle_url('/mod/googlecollab/templates.php', array('id' => $googlecollab->cm->id));
        $str.= html_writer::table($table);
        $str.= $this->output->paging_bar($tot, $page, $itemsperpage, $pageurl, 'page' );
        return $str;
    }

    /**
     * Return message that no documents have been created yet
     *
     */

    public function nodocumentsyet() {

        $string = html_writer::tag('p', get_string('nodocumentsyet',
                'googlecollab') );
        return $string;

    }

    /**
     * Produce a <p> element into which AJAX messages can be inserted
     *
     * @return string
     */

    public function ajax_message_area_all() {

        return html_writer::tag('p', '', array('class'=>'googlecollab_docs_message_all'));
    }

    /**
     * Produce a <p> element into which AJAX messages can be inserted
     *
     * @return string
     */

    public function ajax_message_area() {

        return html_writer::tag('p', '', array('class'=>'googlecollab_docs_message'));
    }

    /**
     * Return invalid email message
     *
     */

    public function invalidemail() {

        return html_writer::tag('p', get_string('emailerror', 'googlecollab'), array('class'=>'googlecollab_error'));

    }


    /**
     * Produces a button which allows user to reset all documents.
     * @return string
     *
     */

    public function reset_all() {

        $buttontext = get_string('resetall', 'googlecollab');

        $button = html_writer::tag('button', $buttontext, array('id'=>'docreset_all', 'class'=>'googlecollab_docs_reset'));
        $button.= html_writer::tag('br'  , '');
        echo $button;

    }

    /**
     * Return a message concerning result of adding a user to an ACL
     *
     * @param boolean $result
     * @param string $email optional
     * @param string $groupname optional
     *
     * @return $string
     */

    public function userresult($result, $add = true, $email = '', $groupname = '') {

        if ($result) {
            if ($add) {
                $txt =  get_string('addeduserokpart1', 'googlecollab', $email) .
                get_string('addeduserokpart2', 'googlecollab', $groupname);
                $str = html_writer::tag('p', $txt );
            } else {
                $str = html_writer::tag('p', get_string('deluserok', 'googlecollab'));
            }

            return $str;
        } else {
            if ($add) {
                return html_writer::tag('p', get_string('addedusernotok', 'googlecollab'));
            } else {
                return html_writer::tag('p', get_string('delusernotok', 'googlecollab'));
            }
        }

    }


    /**
     * Return a table with groups and templates for the separate groups case
     *
     * @param array $documents array of records
     * @param int $page
     *
     * @return string
     */

    public function documents_list($googlecollab, $documents, $page) {
        global $DB, $OUTPUT;

        $str = "";

        $str.= $this->output->heading(get_string('documentslist', 'googlecollab'), 3);
        $str.= $this->ajax_message_area();

        $itemsperpage = 10;
        $tot = count($documents);
        $offset = $page * $itemsperpage;
        $docs_slice = array_slice($documents, $offset , $itemsperpage);

        $table = new html_table();
        $groupnamehd = get_string('groupname', 'googlecollab');
        $resethd = get_string('reset', 'googlecollab');
        $userhd = get_string('adduserhd', 'googlecollab');
        $aclhd = get_string('aclhd', 'googlecollab');
        $createdhd = get_string('createdhd', 'googlecollab');
        $table->head = array($groupnamehd, $createdhd, $resethd, $userhd, $aclhd);
        // $table->attributes['class'] = 'generaltable';
        $fs = get_file_storage();
        $context = context_module::instance($googlecollab->cm->id);

        foreach ($docs_slice as $docid => $doc) {

            $buttonid = 'docreset_' . $doc->groupid;
            $buttontext = get_string('reset', 'googlecollab');

            //$button =  $this->output->single_button('#', $buttontext, 'get',
                //array('id'=>$buttonid, 'disabled'=>false, 'title'=>$buttontext));

            $button = html_writer::tag('button', $buttontext,
                array('id'=>$buttonid, 'class'=>'googlecollab_docs_reset'));

            if ($doc->groupid == 0) {
                $groupname = $googlecollab->course->shortname . ' ' . get_string('coursegroup', 'googlecollab');
            } else {
                $groupname = groups_get_group_name($doc->groupid);
            }
            $usercol = '';

            $urlparams = array('id' => $googlecollab->cm->id);
            $submitto = new moodle_url('/mod/googlecollab/managedocuments.php', $urlparams);

            $form = '<form method="post" action="' . $submitto->out() . '" >' .
                '<div><input type="text" class="googlecollab_user_textfield" name="' .
                'useremail"/><input type="hidden" name="action" value="adduser"/>' .
                '<input type="hidden" name="usergroup" value="' . $doc->groupid .'"/>' .
                '<input type="submit" class="googlecollab_user_submit" value="' .
                get_string('adduser', 'googlecollab') . '"/>' .
                '<input type="hidden" name="sesskey" value="' . sesskey() .'"/>' .
                '</div></form>';
            $sharelist = '';
            //Get everyone that the document is shared with and add their name + remove link
            $sharedwith = $googlecollab->get_sharing_list($doc->docid);
            if ($sharedwith) {
                foreach ($sharedwith as $user => $role) {
                    $sharelist .= html_writer::start_tag('p', array('class' => 'gdocuser'));
                    $sharelist .= $user . ' (' . $role .')';
                    if (strtolower($role) != 'owner') {
                        $urlparams = array(
                            'id' => $googlecollab->cm->id,
                            'sesskey' => sesskey(),
                            'useremail' => $user,
                            'action' => 'remove',
                            'docid' => $doc->docid
                        );
                        $dellink = new moodle_url('/mod/googlecollab/managedocuments.php', $urlparams);
                        $sharelist .= html_writer::start_tag('a', array('href' => $dellink));
                        $sharelist .= html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->pix_url('t/delete'),
                            'title' => get_string('removeuser', 'googlecollab'),
                            'alt' => get_string('removeuser', 'googlecollab')
                            ));
                        $sharelist .= html_writer::end_tag('a');
                    }
                    $sharelist .= html_writer::end_tag('p');
                }
            }

            $createtime = userdate($doc->timecreated,
                get_string('strftimedatetime', 'langconfig'));
            $sharelink = $googlecollab->get_sharing_link($doc->docid);
            if ($sharelink) {
                $doclink = html_writer::tag('a', $createtime, array('href' => $sharelink));
            } else {
                $doclink = $createtime;
            }

            $table->data[] = array($groupname, $doclink, $button, $form, $sharelist);

        }
        $pageurl = new moodle_url('/mod/googlecollab/managedocuments.php', array('id' => $googlecollab->cm->id));
        $str.= html_writer::table($table);
        $str.= $this->output->paging_bar($tot, $page, $itemsperpage, $pageurl, 'page' );
        return $str;
    }

    public function render_group_selector($googlecollab, $currentgroup) {

        $outstr = "";

        global $CFG, $USER;

        //  $context = context_module::instance($googlecollab->cm->id);

        $groupmode = groups_get_activity_groupmode($googlecollab->cm);

        switch ($groupmode) {
            case NOGROUPS:
                break;
            case SEPARATEGROUPS:
                $outstr.= $this->output->container_start('googlecollab_groups');
                $url = new moodle_url('mod/googlecollab/view.php');
                //TODO - the url must be absolute - are we sure url->out is relative?
                $params = array('id'=>$googlecollab->cm->id);
                $url->params($params);
                $outstr.= groups_print_activity_menu($googlecollab->cm, $CFG->wwwroot . '/'.  $url->out(), true);
                $outstr.= $this->output->container_end();
                return $outstr;
                break;
            CASE VISIBLEGROUPS:
                $outstr.= $this->output->container_start('googlecollab_groups');
                $url = new moodle_url('mod/googlecollab/view.php');
                //TODO - the url must be absolute - are we sure url->out is relative?
                $params = array('id'=>$googlecollab->cm->id);
                $url->params($params);
                $outstr.= groups_print_activity_menu($googlecollab->cm, $CFG->wwwroot . '/'.  $url->out(), true);
                $outstr.= $this->output->container_end();
                return $outstr;
            default:
                // error
                return 'error';

        }

        return;

    }

    /**
     * Return a message that no document exists
     *
     * @return string
     *
     */

    public function documentnotavailable() {

        $string = html_writer::tag('p', get_string('notyetcreated',
                'googlecollab') );

        return $string;
    }

    /**
     * Display the local file in an iframe
     *
     * @param string  $url
     * @return string
     */


    public function documentreader($url) {
        if (!$url) {
            return false;
        }

        if (core_useragent::get_device_type() == 'mobile' || core_useragent::get_user_device_type() == 'mobile') {
            if (strpos($url, 'https://docs.google.com') !== false) {
                return '';// As link is to google will try and open in drive app - so skip.
            }
            return html_writer::tag('a', get_string('readonlylink', 'googlecollab'),
                array('href' => $url));
        }

        $outstr = "";

        $outstr.= html_writer::tag('iframe', '', array('id' => 'googlecollab_googlewindow',
            'src' => $url, 'class' => 'googlecollab_google_frame_reader', 'allowfullscreen' => 'true',
                'mozallowfullscreen' => true, 'webkitallowfullscreen' => true));
        return $outstr;

    }

    /**
     * Returns the string for a no groups case button in template manager
     *
     * @param mixed $googlecollab
     * @return string
     */

    public function groups_nogroups($googlecollab) {
        global $DB, $OUTPUT;
        $fs = get_file_storage();
        $context = context_module::instance($googlecollab->cm->id);
        $files = $fs->get_area_files($context->id, 'mod_googlecollab', 'template', 0, null, false);
        $havetemplate = count($files);
        $doc = $DB->get_record('googlecollab_docs',
            array('actid' => $googlecollab->googlecollab->id, 'groupid' => 0));

        $out = '';
        if ($havetemplate) {
            $buttontext = get_string('updatetemplate', 'googlecollab');
            $action = 'update';
            $state = false;
        } else {
            $buttontext = get_string('createtemplate', 'googlecollab');
            $state = false;
            $action = 'create';
        }
        //Do not allow template to be created/updated if doc exists
        if ($doc) {
            $state = true;
            $action = '';
            $out .= $OUTPUT->notification(get_string('googledocexists', 'googlecollab'));
        }

        $url = new moodle_url('/mod/googlecollab/managetemplate.php',
        array('id'=>$googlecollab->cm->id, 'group' => 0, 'mode' => NOGROUPS, 'action' => $action));
        $out .= $this->output->single_button($url,
            $buttontext, 'get', array('disabled'=>$state, 'title'=>$buttontext));
        return $out;

    }

    /**
     * Return the iframe in which to show the Google Docs page
     *
     * @return string
     */


    public function documenteditor($url) {

        if (core_useragent::get_device_type() == 'mobile' || core_useragent::get_user_device_type() == 'mobile') {
            return $this->render_gdoc_link($url);
        }

        $outstr = html_writer::tag('p', get_string('docbrowserwarn', 'googlecollab'),
            array('class' => 'docbrowserwarn'));

        $outstr .= $this->render_gdoc_link($url);

        $outstr .= html_writer::tag('iframe', '', array('id'=>'googlecollab_googlewindow',
            'src'=>$url, 'class'=>'googlecollab_google_frame_writer') );
        return $outstr;

    }


}

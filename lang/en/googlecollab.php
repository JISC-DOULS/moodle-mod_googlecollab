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
 * English strings for googlecollab
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package mod
 * @subpackage googlecollab
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//TODO - Tidy up order of strings
$string['nogooglecollabs'] = 'No Google Collaborative Activities Available';
$string['modulename'] = 'Google Collaborative Activity';
$string['pluginname'] = 'Google Collaborative Activity';
$string['modulenameplural'] = 'Google Collaborative Activities';
$string['googlecollabname'] = 'Google Collaborative Activity';
$string['defaulttype'] = 'Document type where template not used';
$string['defaulttypedoc'] = 'Document';
$string['defaulttypesheet'] = 'Spreadsheet';
$string['defaulttypepres'] = 'Presentation';
$string['usermail'] = 'Google Apps user email';
$string['usermail_desc'] = 'When sharing with an individual user a document will be shared based on either user name @ Google Apps domain name, or email in their profile.';
$string['usermail_username'] = 'username @ domain';
$string['usermail_mail'] = 'Email from profile';
$string['staffmailsuffix'] = 'Suffix added to mail address of non students';
$string['staffmailsuffix_desc'] = 'If set (and username@domain selected) the value will be added after the username when creating the Google mail address for non-students.';
$string['notyetsetup'] = 'Not yet setup';
$string['doclinktext'] = 'Edit document in Google';
$string['readonlylink'] = 'View read-only version of document';
$string['docbrowserwarn'] = 'Please note that some browsers or browser settings may prevent the Google document from displaying on this page. The document can also be accessed directly by using the edit link below.';
$string['pluginadministration'] = 'Google Collaborative Activity administration';
$string['managetemplates'] = 'Manage Document Templates';
$string['googlecollab:manage'] = 'Manage Google documents and templates';
$string['groupsenabled'] = 'Enable group collaboration on Google';
$string['groupsenabled_desc'] = 'Enable this if your Google Apps domain supports groups which can be directly mapped onto your Moodle course groups.';
$string['groupmode_off'] = 'Group mode off';
$string['groupmode_on'] = 'Group mode on';
$string['template'] = 'Template';
$string['groupname'] = 'Group Name';
$string['templatesintro'] = 'The groups in the course can optionally have a template. When the group collaborative activity is started it will be based on this template (if provided). Templates cannot be changed once the Collaborative Activity has started. ';
$string['createtemplate'] = 'Create template';
$string['updatetemplate'] = 'Update template';
$string['cannotcreate'] = 'A template already exists for this group. ';
$string['cannotupdate'] = 'The Activity for this Group has already been started so you cannot create or change the template. ';
$string['managetemplates'] = 'Manage Templates';
$string['templateinst'] = 'All Google documents for the actvity can be created with an initial template. You can only add or update a template when the Google document for the actvity/group does not exist. The type of document used for the template will determine the type of Google document created.';
$string['createupdatetemplate'] = 'Add or change a template';
$string['grouptemplates'] = 'Group Templates';
$string['coursetemplate'] = 'Course Template';
$string['coursetemplateintro'] = 'Set the template for all groups in this course. If the group does not have a specific one set (below) it will use this one';
$string['cannotvieworedit'] = 'Sorry. You do not have permission to view or edit the document for this group.';
$string['notyetcreated'] = 'No document has been started yet for this group by any of its members';
$string['choosegroup'] = 'choose a group:';
$string['currentgroup'] = 'Current group: ';
$string['coursegrouptemplate'] = 'Default template for course group';
$string['managedocuments'] = 'Manage Documents';
$string['managedocumentsintro'] = 'You can reset any activity here. This will delete the Google document and the document will be started again.
<br/>You can also add an individual to the document sharing list to give them editing access in Google Docs.';
$string['nodocumentsyet'] = 'No documents have been created yet';
$string['documentslist'] = 'Documents List';
$string['coursegroup'] = 'Course Group';
$string['reset'] = 'Reset';
$string['resetall'] = 'Reset All Documents in this Activity';
$string['delete_ok'] = 'Success';
$string['delete_notok'] = 'Errors ocurred in the delete operation';
$string['adduserhd'] = 'Add extra user';
$string['aclhd'] = 'Document shared with';
$string['createdhd'] = 'Created (links to doc)';
$string['removeuser'] = 'Remove user from sharing list';
$string['adduser'] = 'Share with user (email)';
$string['addeduserokpart1'] = '> User {$a} added to the ACL List ';
$string['addeduserokpart2'] = ' for Group {$a}  ';
$string['deluserok'] = 'User removed from sharing list';
$string['addedusernotok'] = 'The user could not be added to the sharing for the document';
$string['delusernotok'] = 'The user could not be deleted from the sharing for the document';
$string['googledocexists'] = 'A document is already in use by this group. This must be deleted before templates can be added/updated.';
//errors
$string['googleerror'] = 'An error occured trying to connect to Google';
$string['nogroupmatch'] = 'When trying to map the Moodle Group onto a corresponding Google group the match could not be performed. Likely the Moodle group is not named in an expected way.';

$string['consumerkey'] = 'Consumer key ';
$string['consumerkey_desc'] = 'Your Google Apps OAuth consumer key ';

$string['consumersecret'] = 'Consumer secret ';
$string['consumersecret_desc'] = 'Your Google Apps OAuth consumer secret ';

$string['consumerws'] = 'Secret web service';
$string['consumerws_desc'] = 'Get the consumer secret from a web service - set to url to use instead of [consumersecret].';

$string['googleappsdomain'] = 'Google Apps Domain';
$string['googleappsdomain_desc'] = 'Your Google Apps domain';

$string['googleappsadminuser'] = 'Google Apps Account Admin user full email address';
$string['googleappsadminuser_desc'] = 'For example: name@gapps-acct.yourdomain.com';

$string['prelink'] = 'Use redirect to Google Docs';
$string['prelink_desc'] = 'When presenting links to Google documents append the actual url to the url set e.g. www.myplace.com?googledoc=https://www.google... . Use if you want to implement extra user checking etc.';

$string['googlecollab:viewall'] = 'Read only access to all Google documents in the activity (also treated as non-student for sharing)';
$string['googlecollab:viewdoc'] = 'Access to view the collaborative document';
$string['emailerror'] = 'Invalid email address';

$string['readonly'] = 'Read Only Mode';
$string['sharingerror'] = 'There was an error whilst trying to share the document. You will now be shown the read only version.';
$string['whyreadonly'] = 'Why is this Read Only?';
$string['whyreadonly_help'] = 'Presentations can only be edited directly within Google, you are not a member of this group or you do not have a Google Apps account with this institution.';

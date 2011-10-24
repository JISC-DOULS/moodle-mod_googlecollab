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
 * Module settings - sub plugins must be manually addedPARAM_TEXT
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/locallib.php');


$settings->add(new admin_setting_configselect('mod_googlecollab/usermail',
    get_string('usermail', 'googlecollab'),
    get_string('usermail_desc', 'googlecollab'), googlecollab::USENAME,
   googlecollab::google_usermail_settings_choice()
    ));

$settings->add(new admin_setting_configtext('mod_googlecollab/staffmailsuffix',
    get_string('staffmailsuffix', 'googlecollab'),
    get_string('staffmailsuffix_desc', 'googlecollab'), '',
   PARAM_TEXT));

$settings->add(new admin_setting_configselect('mod_googlecollab/gappgroups',
    get_string('groupsenabled', 'googlecollab'),
    get_string('groupsenabled_desc', 'googlecollab'), googlecollab::GROUPMODE_OFF,
    googlecollab::google_mode_settings_choice()
    ));

$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_googleappsdomain',
    get_string('googleappsdomain', 'googlecollab'),
    get_string('googleappsdomain_desc', 'googlecollab'), '',
   PARAM_TEXT));


$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_consumerkey',
    get_string('consumerkey', 'googlecollab'),
    get_string('consumerkey_desc', 'googlecollab'), '',
   PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_consumersecret',
    get_string('consumersecret', 'googlecollab'),
    get_string('consumersecret_desc', 'googlecollab'), '',
   PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_consumerws',
    get_string('consumerws', 'googlecollab'),
    get_string('consumerws_desc', 'googlecollab'), '',
   PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_googleappsadminuser',
    get_string('googleappsadminuser', 'googlecollab'),
    get_string('googleappsadminuser_desc', 'googlecollab'), '',
   PARAM_TEXT));

$settings->add(new admin_setting_configtext('mod_googlecollab/gapps_prelink',
    get_string('prelink', 'googlecollab'),
    get_string('prelink_desc', 'googlecollab'), '',
   PARAM_TEXT));

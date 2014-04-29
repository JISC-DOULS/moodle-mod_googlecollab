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
 * This file keeps track of upgrades to the portfolioact module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installtion to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in
 * lib/ddllib.php
 *
 * @package mod
 * @subpackage portfolioact
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_portfolioact_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_googlecollab_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    $result = true;

    /// And upgrade begins here. For each one, you'll need one
    /// block of code similar to the next one. Please, delete
    /// this comment lines once this file start handling proper
    /// upgrade code.

    /// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
    ///     $result = result of "/lib/ddllib.php" function calls
    /// }

    /// Lines below (this included)  MUST BE DELETED once you get the first version
    /// of your module ready to be installed. They are here only
    /// for demonstrative purposes and to show how the portfolioact
    /// iself has been upgraded.

    /// For each upgrade block, the file portfolioact/version.php
    /// needs to be updated . Such change allows Moodle to know
    /// that this file has to be processed.

    /// To know more about how to write correct DB upgrade scripts it's
    /// highly recommended to read information available at:
    ///   http://docs.moodle.org/en/Development:XMLDB_Documentation
    /// and to play with the XMLDB Editor (in the admin menu) and its
    /// PHP generation posibilities.

    /// And that's all. Please, examine and understand the 3 example blocks above. Also
    /// it's interesting to look how other modules are using this script. Remember that
    /// the basic idea is to have "blocks" of code (each one being executed only once,
    /// when the module version (version.php) is updated.

    /// Lines above (this included) MUST BE DELETED once you get the first version of
    /// yout module working. Each time you need to modify something in the module (DB
    /// related, you'll raise the version and add one upgrade block here.

    /// Final return of upgrade result (true/false) to Moodle. Must be
    /// always the last line in the script

    if ($oldversion < 2011092601) {

        // Define key docid (unique) to be dropped form googlecollab_cache
        $table = new xmldb_table('googlecollab_cache');
        $key = new xmldb_key('docid', XMLDB_KEY_UNIQUE, array('docid'));

        // Launch drop key docid ** MUST DROP KEY BEFORE CHANGE OF TYPE
        $dbman->drop_key($table, $key);

        // Changing type of field docid on table googlecollab_cache to char
        $table = new xmldb_table('googlecollab_cache');
        $field = new xmldb_field('docid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of type for field docid
        $dbman->change_field_type($table, $field);

         // Define key docid (unique) to be added to googlecollab_cache
        $table = new xmldb_table('googlecollab_cache');
        $key = new xmldb_key('docid', XMLDB_KEY_UNIQUE, array('docid'));

        // Launch add key docid ** REINSTATE KEY
        $dbman->add_key($table, $key);

        // googlecllab savepoint reached
        upgrade_mod_savepoint(true, 2011092601, 'googlecollab');
    }

    return $result;
}

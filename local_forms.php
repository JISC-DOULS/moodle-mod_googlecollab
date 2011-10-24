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
 * Forms for managing the templates
 *
 * @package    mod
 * @subpackage googlecollab
 * @copyright 2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');




/**
 * Template form
 *
 * A template form to use in page code to establish a form
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class googlecollab_create_template extends moodleform {

    public $formhandle;

    /**
     * Definition of the setting form elements
     */
    public function definition() {
        $mform  = $this->_form;

    }

    /**
     * Construct the form
     *
     * Construct the form and expose the form object externally.
     *
     * @param string $url
     */

    public function __construct($url) {
        parent::__construct($url);
        $this->formhandle =   $this->_form;
    }

}

class googlecollab_adduser extends moodleform {

    public $formhandle;

    /**
     * Definition of the setting form elements
     */
    public function definition() {
        $mform  = $this->_form;

    }

    /**
     * Construct the form
     *
     * Construct the form and expose the form object externally.
     *
     * @param string $url
     */

    public function __construct($url) {
        parent::__construct($url);
        $this->formhandle =   $this->_form;
    }


}

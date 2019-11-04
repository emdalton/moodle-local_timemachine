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
 * Code checker library code.
 *
 * @package    local_timemachine
 * @copyright  Elizabeth Dalton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (defined('CLI_SCRIPT') && CLI_SCRIPT == true) {
    define('BLOCK_CHAR', '.');
} else {
    define('BLOCK_CHAR', '&#9608;');
}

require_once($CFG->libdir . '/formslib.php');

// Files required by uninstallation processes.
//require_once($CFG->dirroot . '/course/lib.php');
//require_once($CFG->libdir . '/adminlib.php');
//require_once($CFG->libdir . '/filelib.php');

/**
 * Action form for the Timemachine page.
 *
 * @copyright  Elizabeth Dalton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_timemachine_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', true);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('action', PARAM_BOOL);

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'oldnew', '', get_string('timeshiftnewer', 'local_timemachine'), 1);
        $radioarray[] = $mform->createElement('radio', 'oldnew', '', get_string('timeshiftolder', 'local_timemachine'), 0);
        $mform->setDefault('oldnew', 0);
        $mform->addGroup($radioarray, 'oldnewarray', '', array(' '), false);
        $mform->setType('oldnew', PARAM_BOOL);

        $mform->addElement('text', 'days', get_string('timeshiftdays', 'local_timemachine'));
        $mform->setType('days', PARAM_INT);
       
        $mform->addElement('submit', 'submitbutton', get_string('timeshiftgo', 'local_timemachine'));
    }
}

/**
 * Here we:
 *
 * - Timeshift database timestamp fields (there is a list of excluded fields)
 *
 * @access public
 * @return void
 */
function timeshift_all($timeshiftdays) {
    global $DB;

    // Some bigint fields with names including time or date are not timestamps.
    $excludedcolumns = get_excluded_timestamp_columns();


    // Iterate through all system tables and shift timestamp fields.
    // No cache to refresh the list as we deleted some tables in this script.

    $tables = $DB->get_tables(false);
    foreach ($tables as $tablename) {
        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . 'debug test';
        }
        $toupdate = array();
        $columns = $DB->get_columns($tablename, false);
        foreach ($columns as $columnname => $column) {
            // Some bigint fields with 'date' or 'time' in name are not timestamps.
            if (!empty($excludedcolumns[$tablename]) && in_array($columnname, $excludedcolumns[$tablename])) {
                continue;
            }
            // datestamp fields are any bigint fields where name includes text 'date' or 'time', all of them but the excluded ones.
            if (($column->type === 'bigint') && (strstr($columnname,'time') || strstr($columnname,'date') )) {
                $toupdate[$columnname] = (object)['vartype' => $column->type];
            }
        }
        // Update all table records if there is any timestamp column that should be shifted.
        if (!empty($toupdate)) {
            timeshift_table_records($tablename, $toupdate, $timeshiftdays);
        }
    }
    purge_all_caches();


}

function timeshift_table_records($tablename, $columns, $timeshiftdays) {
    global $DB;

 // need to get value for shiftdays
   $timeshiftseconds = $timeshiftdays*60*60*24;
 
  
    foreach ($columns as $column => $colinfo) {
    	//echo BLOCK_CHAR . "\n shifting column " . $tablename . " " . $column . " by number of days = ". $timeshiftdays;

        $sql = "UPDATE {" . $tablename . "} SET " . $column . " = CASE
            WHEN " . $column . " IS NULL THEN NULL
            WHEN " . $DB->sql_length($column) . " = 0 THEN '0'
            WHEN " . $column . "+" . $timeshiftseconds . " > NOW() THEN NOW()
            ELSE " . $column . "+" . $timeshiftseconds . "
        END";
        $DB->execute($sql);
    }
}

function assign_if_not_null(&$object, $field, $newvalue) {
    if (
        property_exists($object, $field) &&
        isset($object->$field) &&
        !empty($object->$field)
    ) {
        $object->$field = $newvalue;
    }
}


function get_excluded_timestamp_columns() {
    //these are columns that are type bigint and include text 'time' or 'date' but are not timestamps
    return array(
        'backup_controllers' => array('executiontime'),
        'block_rss_client' => array('skiptime'),
        'event' => array('timesort'),
        'lesson' => array('timelimit'),
        'lesson' => array('completiontimespent'),
        'lesson_timer' => array('lessontime'),
        'mnet_session' => array('confirm_timeout'),
        'quiz' => array('timelimit'),
        'quiz_overrides' => array('timelimit'),
        'search_index_requests' => array('partialtime')

    );
}



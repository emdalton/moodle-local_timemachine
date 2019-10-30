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
 * Shift timestamps throughout a site
 *
 * @package    local_timemachine
 * @copyright  2019 Elizabeth Dalton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/timemachine/locallib.php');

$timemachine = optional_param('action',  false,  PARAM_BOOL);

// Allow more time for long query runs.
set_time_limit(0);

// Start page output.
admin_externalpage_setup('local_timemachine');
$PAGE->set_url($CFG->wwwroot . '/local/timemachine/index.php');
$title = get_string('pluginname', 'local_timemachine');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

if (!debugging() || empty($CFG->maintenance_enabled)) {
    $debugging = new moodle_url('/admin/settings.php', array('section' => 'debugging'));
    $maintenance = new moodle_url('/admin/settings.php', array('section' => 'maintenancemode'));
    $langparams = (object)array('debugging' => $debugging->out(false), 'maintenance' => $maintenance->out(false));
    echo $OUTPUT->notification(get_string('nodebuggingmaintenancemode', 'local_timemachine', $langparams));
    echo $OUTPUT->footer();
    die();
}

if ($timemachine) {

    require_sesskey();

    // Exectute anonmisation based on form selections.
    $others = optional_param('timeshift', false, PARAM_BOOL);

    if ($others) {
        echo $OUTPUT->heading(get_string('timeshift', 'local_timemachine'), 3);
        timemachine_others($activities, $password);
    }

    echo html_writer::tag('p', get_string('done', 'local_timemachine'), array('style' => 'margin-top: 20px;'));

    $home = new \moodle_url('/');
    echo html_writer::tag('a', get_string('continue'), array('href' => $home->out(false), 'class' => 'btn btn-primary'));

} else {

    // Display the form.
    echo $OUTPUT->notification(get_string('warning', 'local_timemachine'));
    $mform = new local_timemachine_form(new moodle_url('/local/timemachine/'));
    $mform->display();

}

echo $OUTPUT->footer();

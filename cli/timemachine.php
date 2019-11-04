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
 * timemachine personal identifiers
 *
 * @package    local_timemachine
 * @copyright  2019 Elizabeth Dalton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/timemachine/locallib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'newer' => false,
        'older' => false,
        'days' => 0,
        'help' => false
    ), array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

$help =
"Shifts the timestamps on your site by the specified number of days. 

Options:
--newer             Shifts the dates forward so the site appears to be newer than it was before the shift
--older             Shifts the dates backward so the site appears to be older than it was before the shift
--days              Required: The number of days to shift the site. Must be a postive integer. A value of 0 does nothing.
-h, --help          Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/timemachine/cli/timemachine.php --newer --days 365
";

if ($options['days'] === false) {
    echo $help;
    exit(0);
}

if ($options['help']) {
    echo $help;
    exit(0);
}
if (!debugging() || (empty($CFG->maintenance_enabled) && !file_exists("$CFG->dataroot/climaintenance.html"))) {
    echo $OUTPUT->notification(get_string('nodebuggingmaintenancemodecli', 'local_timemachine'));
    exit(1);
}

$unique = array_unique($options);
if (count($unique) === 1 && reset($unique) === false) {
    echo $help;
    exit(0);
}


// Allow more time for long query runs.
set_time_limit(0);

// Exectute anonmisation based on selections.
if ($options['newer']) {
    echo $OUTPUT->heading(get_string('timeshiftnewer', 'local_timemachine'), 3);
    timeshift_all($options['days']);
}

if ($options['older']) {
    echo $OUTPUT->heading(get_string('timeshiftolder', 'local_timemachine'), 3);
    timeshift_all(-1*$options['days']);
}


exit(0);

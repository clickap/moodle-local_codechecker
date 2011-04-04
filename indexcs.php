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
 * Run the code checker from the web.
 *
 * @package    local
 * @subpackage codechecker
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/codechecker/locallib.php');


$path = optional_param('path', '', PARAM_PATH);
if ($path) {
    $pageparams = array('path' => $path);
} else {
    $pageparams = array();
}

$context = get_context_instance(CONTEXT_SYSTEM);
$pagename = get_string('pluginname', 'local_codechecker');

$PAGE->set_url(new moodle_url('/local/codechecker/', $pageparams));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname. ': ' . $pagename);
$PAGE->navbar->add($pagename);

raise_memory_limit(MEMORY_HUGE);

require_login();
require_capability('moodle/site:config', $context);

$mform = new local_codechecker_form(new moodle_url('/local/codechecker/'));
$mform->set_data((object) array('path' => $path));
if ($data = $mform->get_data()) {
    redirect('./?path=' . urlencode($data->path));
}

if ($path) {
    $fullpath = $CFG->dirroot . '/' . trim($path, '/');
    if (!is_file($fullpath) && !is_dir($fullpath)) {
        $fullpath = null;
    }
}

$output = $PAGE->get_renderer('local_codechecker');

echo $OUTPUT->header();

if ($path) {
    if ($fullpath) {
        $phpcs = new PHP_CodeSniffer();
        $phpcs->setCli(new local_codechecker_codesniffer_cli());
        $phpcs->setIgnorePatterns(local_codesniffer_get_ignores());
        $numerrors = $phpcs->process($fullpath,
                $CFG->dirroot . '/local/codechecker/moodle');
        $problems = $phpcs->getFilesErrors();
        echo $output->cs_report($problems, $phpcs, $numerrors);

    } else {
        echo $output->invald_path_message($path);
    }
}

$mform->display();
echo $OUTPUT->footer();
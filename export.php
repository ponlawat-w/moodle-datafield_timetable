<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/exportsettings_form.php');

$fid = required_param('fid', PARAM_INT);
$uid = optional_param('uid', null, PARAM_INT);

if (!$field = $DB->get_record('data_fields', ['id' => $fid])) {
    throw new moodle_exception('Invalid field ID');
}
if (!$data = $DB->get_record('data', ['id' => $field->dataid])) {
    throw new moodle_exception('Invalid database module ID');
}
if (!$course = get_course($data->course)) {
    throw new moodle_exception('Cannot get course module');
}
if (!$cm = get_coursemodule_from_instance('data', $data->id, $course->id)) {
    throw new moodle_exception('Invalid course module');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/data:exportallentries', $context);

$exportsettingsform = new exportsettings_form($field);

if ($exportsettingsform->is_submitted() && $exportsettingsform->is_validated()) {
    $settings = $exportsettingsform->get_data();

    // Save settings
    $savesettings = clone($settings);
    unset($savesettings->userid);
    $field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_EXPORTSETTINGS} = json_encode($savesettings);
    $DB->update_record('data_fields', $field);

    // Export as CSV
    datafield_timetable_exportcredits($field, $settings);
    exit;
}
if ($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_EXPORTSETTINGS}) {
    // Load settings
    $settings = json_decode($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_EXPORTSETTINGS});
    if ($uid) {
        $settings->userid = $uid;
    }
    $exportsettingsform->set_data($settings);
} else if ($uid) {
    $settings = new stdClass();
    $settings->userid = $uid;
    $exportsettingsform->set_data($settings);
}

$PAGE->set_url(new moodle_url('/mod/data/field/timetable/export.php', ['fid' => $fid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('exporttitle', 'datafield_timetable', $data->name));

echo $OUTPUT->header();

echo html_writer::tag('h1', get_string('exportsettings', 'datafield_timetable'));

$exportsettingsform->display();

echo $OUTPUT->footer();

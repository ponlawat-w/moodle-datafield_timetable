<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib.php');
require_once(__DIR__ . '/lib.php');

require_login();
$dataid = required_param('dataid', PARAM_INT);
$data = $DB->get_record('data', ['id' => $dataid]);
if (!$data) {
    throw new moodle_exception('Invlid parameter');
}
require_login($data->course);

header('Content-Type: application/vnd.ms-word');
header('Content-Disposition: attachment; filename=export.doc');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

echo html_writer::tag('style', 'table { border-collapse: collapse; } td, th { border: 1px solid #000000; }');

$records = $DB->get_records('data_records', ['dataid' => $data->id, 'userid' => $USER->id], 'timecreated ASC');
foreach ($records as $record) {
    data_print_template('singletemplate', [$record], $data);
    echo html_writer::start_tag('hr');
}

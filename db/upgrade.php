<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

function xmldb_datafield_timetable_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2019051904) {
        $defaulttype = urlencode('Activity Type');
        $fields = $DB->get_records('data_fields', ['type' => 'timetable']);
        foreach ($fields as $field) {
            $categoriesnewdata = '1=>' . $defaulttype;
            $categoriesdataexploded = explode(PHP_EOL, $field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});
            foreach ($categoriesdataexploded as $category) {
                $categoriesnewdata .= PHP_EOL . '1,' . $category;
            }

            $field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES} = $categoriesnewdata;
//            $DB->update_record('data_fields', $field);

            $contents = $DB->get_records('data_content', ['fieldid' => $field->id]);
            foreach ($contents as $content) {
                $newactivities = [];
                $activities = explode(PHP_EOL, $content->content);
                foreach ($activities as $activity) {
                    $activitydata = explode(';', $activity);
                    if (count($activitydata) == 4) {
                        $newactivities[] = implode(';', [
                            $activitydata[0],
                            $activitydata[1],
                            $activitydata[2],
                            '1=' . $activitydata[3]
                        ]);
                    }
                }

                $content->content = implode(PHP_EOL, $newactivities);
                $DB->update_record('data_content', $content);
            }
        }

        upgrade_plugin_savepoint(true, 2019051904, 'datafield', 'timetable');
    }

    return true;
}

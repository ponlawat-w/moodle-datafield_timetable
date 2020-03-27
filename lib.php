<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/activity.class.php');

const DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES = 'param1';
const DATAFIELD_TIMETABLE_COLUMN_FIELD_TIME_STEP = 'param2';
const DATAFIELD_TIMETABLE_COLUMN_FIELD_EXPORTSETTINGS = 'param3';

const DATAFIELD_TIMETABLE_CREDIT_WORK = 'w';
const DATAFIELD_TIMETABLE_CREDIT_BREAK = 'b';
const DATAFIELD_TIMETABLE_CREDIT_IGNORED = 'n';

function datafield_timetable_gethourselect($attr = [], $selected = null) {
    $options = '';
    for ($h = 0; $h < 24; $h++) {
        $optattr = [
            'value' => $h
        ];
        if ($selected == $h) {
            $attr['selected'] = 'selected';
        }

        $options .= html_writer::tag('option', $h < 10 ? "0{$h}" : $h, $optattr);
    }

    return html_writer::tag('select', $options, $attr);
}

function datafield_timetable_getminuteselect($step = 30, $attr = [], $selected = null) {
    if (!$step || $step < 0) {
        $step = 30;
    }

    $options = '';
    for ($m = 0; $m < 60; $m += $step) {
        $optattr = [
            'value' => $m
        ];
        if ($selected == $m) {
            $attr['selected'] = 'selected';
        }

        $options .= html_writer::tag('option', $m < 10 ? "0{$m}" : $m, $optattr);
    }

    return html_writer::tag('select', $options, $attr);
}

function datafield_timetable_gettimerangeselects($step = 30) {
//    $sharedstyle = 'size: 0.7em; padding: 0; margin: 0;';
    $alerttimeinvalid = html_writer::div(get_string('alerttimeinvalid', 'datafield_timetable'),
        'alert alert-danger datafield_timetable-alerttimeinvalid', [
            'style' => 'padding: 2px; margin: 0;'
        ]);
    $alerttimeconflicts = html_writer::div(get_string('alerttimeconflicts', 'datafield_timetable'),
        'alert alert-danger datafield_timetable-alerttimeconflicts', [
            'style' => 'padding: 2px; margin: 0;'
        ]);

    $fromhourattr = ['class' => 'datafield_timetable-timeselect datafield_timetable-fromhourselect'];
    $fromminuteattr = ['class' => 'datafield_timetable-timeselect datafield_timetable-fromminuteselect'];
    $tohourattr = ['class' => 'datafield_timetable-timeselect datafield_timetable-tohourselect'];
    $tominuteattr = ['class' => 'datafield_timetable-timeselect datafield_timetable-tominuteselect'];

    $from = datafield_timetable_gethourselect($fromhourattr) . ':'
        . datafield_timetable_getminuteselect($step, $fromminuteattr);
    $to = datafield_timetable_gethourselect($tohourattr) . ':'
        . datafield_timetable_getminuteselect($step, $tominuteattr);

    $fromdiv = html_writer::div($from, 'datafield_timetable-timeselect');
    $betweendiv = html_writer::div(' ~ ', 'datafield_timetable-timebetween');
    $todiv = html_writer::div($to, 'datafield_timetable-timeselect');

    return html_writer::div($fromdiv . $betweendiv . $todiv) . $alerttimeinvalid . $alerttimeconflicts;
}

function datafield_timetable_getcategories($categories_raw, $reindex = false) {
    $rows = explode(PHP_EOL, $categories_raw);
    $data = [];
    foreach ($rows as $row) {
        $category = explode('=>', $row);
        if (count($category) != 2) {
            continue;
        }

        $idraw = explode(',', $category[0]);
        if (count($idraw) == 1) {
            $data[$idraw[0]] = [
                'id' => $idraw[0],
                'name' => urldecode($category[1]),
                'items' => []
            ];
        } else if (count($idraw) == 2 && isset($data[$idraw[0]])) {
            $data[$idraw[0]]['items'][$idraw[1]] = [
                'id' => $idraw[1],
                'name' => urldecode($category[1])
            ];
        }
    }

    if ($reindex) {
        $data = array_values($data);
        foreach ($data as $idx => $category) {
            $data[$idx]['items'] = array_values($category['items']);
        }
    }

    return $data;
}

function datafield_timetable_getslottemplate($field, $categories) {
    $alertinputrequired = html_writer::div(get_string('required'),
        'alert alert-danger datafield_timetable-alertinputrequired', [
            'style' => 'padding: 2px; margin: 0;'
        ]);
    $input = html_writer::start_tag('input', [
        'type' => 'text',
        'class' => 'datafield_timetable-activityinput',
        'placeholder' => get_string('activity', 'datafield_timetable')
    ]);

    $time = datafield_timetable_gettimerangeselects($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_TIME_STEP});

    $categoryselects = '';
    foreach ($categories as $category) {
        $options = html_writer::tag('option', '- ' . $category['name'] . ' -', ['value' => 0]);
        foreach ($category['items'] as $item) {
            $options .= html_writer::tag('option', $item['name'], [
                'value' => $item['id']
            ]);
        }
        $select = html_writer::tag('select', $options, [
            'class' => 'datafield_timetable-categoryselect',
            'data-id' => $category['id']
        ]);

        $categoryselects .= html_writer::div($select);
    }

    $delete = html_writer::tag('button', '×', [
        'type' => 'button',
        'class' => 'btn btn-danger datafield_timetable-delete_btn'
    ]);

    $colattr = [
        'style' => 'vertical-align: top;'
    ];

    $timecol = html_writer::div(
        $time,
        'datafield_timetable-col datafield_timetable-col_time',
        $colattr);
    $activitycol = html_writer::div(
        $input . $alertinputrequired,
        'datafield_timetable-col datafield_timetable-col_activity',
        $colattr);
    $categorycol = html_writer::div(
        $categoryselects,
        'datafield_timetable-col datafield_timetable-col_categories',
        $colattr);
    $deletecol = html_writer::div(
        $delete,
        'datafield_timetable-col datafield_timetable-col_deletebtn',
        $colattr);

    return html_writer::div($timecol . $activitycol . $categorycol . $deletecol,
        'datafield_timetable-row datafield_timetable-slot_template');
}

function datafield_timetable_getaddfield($content, $field) {

    $addbtn = html_writer::tag('button', get_string('addactivity', 'datafield_timetable'), [
        'type' => 'button',
        'class' => 'btn btn-success datafield_timetable-add_btn'
    ]);

    $headrow = html_writer::div(
        html_writer::div(
            html_writer::div(get_string('time', 'datafield_timetable'),
                'datafield_timetable-col datafield_timetable-col_time') .
            html_writer::div(get_string('activity', 'datafield_timetable'),
                'datafield_timetable-col datafield_timetable-col_activity') .
            html_writer::div(get_string('categories', 'datafield_timetable'),
                'datafield_timetable-col datafield_timetable-col_categories')
        ), 'datafield_timetable-row datafield_timetable-row_head');

    $categories = datafield_timetable_getcategories($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});
    $bodyrow = html_writer::div(
        datafield_timetable_getslottemplate($field, $categories),
        'datafield_timetable-body');

    $content = $headrow . $bodyrow
        . html_writer::div($addbtn)
        . html_writer::start_tag('input', [
            'type' => 'hidden',
            'class' => 'datafield_timetable-data',
            'name' => "field_{$field->id}",
            'value' => $content
        ]);

    return html_writer::div($content, 'datafield_timetable-timetable', [
        'data-fieldid' => $field->id
    ]);
}

function datafield_timetable_toactivities($content) {
    $activities = [];
    $rows = explode(PHP_EOL, $content);
    foreach ($rows as $row) {
        $activities[] = new Activity($row);
    }

    return $activities;
}

/**
 * @param Activity $activity
 * @param $categories
 * @return string
 */
function datafield_timetable_getactivitytr($activity, $categories) {
//    $timetd = html_writer::tag('td',
//        html_writer::div($activity->getfromtime(), 'datafield-timetable_timetable-fromtime') .
//        html_writer::div($activity->gettotime(), 'datafield-timetable_timetable-totime')
//        , ['class' => 'datafield-timetable_timetable-time']);
    $timetd = html_writer::tag('td',
        $activity->getfromtime() . ' ~ ' . $activity->gettotime());
    $activitytd = html_writer::tag('td', $activity->activity);
    $categorytds = '';
    foreach ($categories as $id => $category) {
        $categorytds .= html_writer::tag('td', $activity->getcategoryname($categories, $id));
    }

    return html_writer::tag('tr',
        $timetd . $activitytd . $categorytds);
}

function datafield_timetable_getdisplaylisttemplate($content) {
    $activities = datafield_timetable_toactivities($content);
    $countstr = get_string(
        count($activities) == 1 ? 'activitycount' : 'activitiescount',
        'datafield_timetable',
        count($activities));

    $from = 1440;
    $to = 0;
    foreach ($activities as $activity) {
        if ($activity->from < $from) {
            $from = $activity->from;
        }
        if ($activity->to > $to) {
            $to = $activity->to;
        }
    }

    $fromtostr = get_string('fromto', 'datafield_timetable', [
        'from' => Activity::gettimestring($from),
        'to' => Activity::gettimestring($to)
    ]);

    return "{$fromtostr} ({$countstr})";
}

function datafield_timetable_getdisplaysingletemplate($content, $categoriesraw) {
    $categories = datafield_timetable_getcategories($categoriesraw);
    $activities = datafield_timetable_toactivities($content);

    $categoryths = '';
    foreach ($categories as $category) {
        $categoryths .= html_writer::tag('th', $category['name']);
    }

    $rows = '';
    foreach ($activities as $activity) {
        $rows .= datafield_timetable_getactivitytr($activity, $categories);
    }

    $thead = html_writer::tag('thead',
        html_writer::tag('tr',
            html_writer::tag('th', get_string('time', 'datafield_timetable')) .
            html_writer::tag('th', get_string('activity', 'datafield_timetable')) .
            $categoryths)
    );
    $tbody = html_writer::tag('tbody', $rows);

    $table = html_writer::tag('table', $thead . $tbody, [
        'class' => 'table table-bordered datafield_timetable-timetable'
    ]);

    return html_writer::tag('style', '
        .datafield_timetable-timetable {
            width: auto;
        }
        .datafield_timetable-timetable td, .datafield_timetable-timetable th {
            vertical-align: middle;
        }
        //.datafield_timetable-timetable tbody tr {
        //    height: 60px;
        //}
        .datafield-timetable_timetable-time {
            position: relative;
        }
        .datafield-timetable_timetable-fromtime {
            position: absolute;
            top: 5px;
        }
        .datafield-timetable_timetable-totime {
            position: absolute;
            bottom: 5px;
        }
    ')
        . $table;
}

function datafield_timetable_getcreditcategories($settings, $type) {
    $results = [];
    foreach ($settings as $key => $value) {
        if ($value == $type && strpos($key, 'category-') === 0) {
            $results[] = substr($key, strlen('category-'));;
        }
    }
    return $results;
}

function datafield_timetable_exportcredits($field, $settings, $csvdelimitor = ',') {
    global $DB;

    $workcategories = datafield_timetable_getcreditcategories($settings, DATAFIELD_TIMETABLE_CREDIT_WORK);
    $breakcategories = datafield_timetable_getcreditcategories($settings, DATAFIELD_TIMETABLE_CREDIT_BREAK);

    $records = $DB->get_records_sql(
        'SELECT c.*, r.timecreated FROM {data_records} r JOIN {data_content} c ON r.id = c.recordid WHERE r.userid = ? AND c.fieldid = ? ORDER BY r.timecreated ASC'
    , [$settings->userid, $field->id]);

    $user = $DB->get_record('user', ['id' => $settings->userid]);
    $userfullname = fullname($user);
    $time = date('Y m d');
    $filename = "{$field->name} - {$userfullname} - {$time}.csv";

    session_write_close();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo implode($csvdelimitor, [
        'SerialNumber',
        'Date',
        'Start',
        'End',
        'Break',
        'WorkingHours',
        'Credits',
        'CumulativeWorkingHours',
        'CumulativeCredits'
    ]);

    $i = 0;
    $sumworking = 0;
    $sumcredits = 0;
    foreach ($records as $record) {
        $activities = datafield_timetable_toactivities($record->content);
        $start = null;
        $end = null;
        $breaking = 0;
        $working = 0;

        foreach ($activities as $activity) {
            if (is_null($start) || $activity->from < $start) {
                $start = $activity->from;
            }
            if (is_null($end) || $activity->to > $end) {
                $end = $activity->to;
            }

            if ($activity->hascategories($workcategories, '-')) {
                $working += $activity->getduration();
            } else if ($activity->hascategories($breakcategories, '-')) {
                $breaking += $activity->getduration();
            }
        }

        $sumworking += $working;
        $credit = $settings->hourpercredit ? $working / $settings->hourpercredit : 0;
        $sumcredits += $credit;

        $row = [];
        $row[] = ++$i;
        $row[] = userdate($record->timecreated, '%d %m %Y');
        $row[] = Activity::gettimestring($start);
        $row[] = Activity::gettimestring($end);
        $row[] = $breaking;
        $row[] = $working;
        $row[] = $credit;
        $row[] = $sumworking;
        $row[] = $sumcredits;

        echo PHP_EOL . implode($csvdelimitor, $row);
    }
}

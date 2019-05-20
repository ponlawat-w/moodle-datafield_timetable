<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/activity.class.php');

const DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES = 'param1';
const DATAFIELD_TIMETABLE_COLUMN_FIELD_TIME_STEP = 'param2';

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
    $alerttimeinvalid = html_writer::div(get_string('alerttimeinvalid', 'datafield_timetable'),
        'alert alert-danger datafield_timetable-alerttimeinvalid', [
            'style' => 'padding: 2px; margin: 0;'
        ]);
    $alerttimeconflicts = html_writer::div(get_string('alerttimeconflicts', 'datafield_timetable'),
        'alert alert-danger datafield_timetable-alerttimeconflicts', [
            'style' => 'padding: 2px; margin: 0;'
        ]);
    $from = datafield_timetable_gethourselect(['class' => 'datafield_timetable-fromhourselect']) . ':'
        . datafield_timetable_getminuteselect($step, ['class' => 'datafield_timetable-fromminuteselect']);
    $to = datafield_timetable_gethourselect(['class' => 'datafield_timetable-tohourselect']) . ':'
        . datafield_timetable_getminuteselect($step, ['class' => 'datafield_timetable-tominuteselect']);

    return html_writer::div($from . ' ~ ' . $to) . $alerttimeinvalid . $alerttimeconflicts;
}

function datafield_timetable_getcategories($categories_raw) {
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

    return $data;
}

function datafield_timetable_getslottemplate($field, $categories) {
    $selecttds = '';
    foreach ($categories as $category) {
        $options = '';
        foreach ($category['items'] as $item) {
            $options .= html_writer::tag('option', $item['name'], [
                'value' => $item['id']
            ]);
        }
        $select = html_writer::tag('select', $options, [
            'class' => 'datafield_timetable-categoryselect',
            'data-id' => $category['id']
        ]);

        $selecttds .= html_writer::tag('td', $select);
    }

    $alertinputrequired = html_writer::div(get_string('required'),
        'alert alert-danger datafield_timetable-alertinputrequired', [
            'style' => 'padding: 2px; margin: 0;'
        ]);
    $input = html_writer::start_tag('input', [
        'type' => 'text',
        'class' => 'datafield_timetable-activityinput'
    ]);
    $time = datafield_timetable_gettimerangeselects($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_TIME_STEP});
    $delete = html_writer::tag('button', '×', [
        'type' => 'button',
        'class' => 'btn btn-danger datafield_timetable-delete_btn'
    ]);

    $tdattr = [
        'style' => 'vertical-align: top;'
    ];

    $timetd = html_writer::tag('td', $time, $tdattr);
    $inputtd = html_writer::tag('td', html_writer::div($input) . $alertinputrequired, $tdattr);
    $deletetd = html_writer::tag('td', $delete, $tdattr);

    return html_writer::tag('tr', $timetd . $inputtd . $selecttds . $deletetd, [
        'class' => 'datafield_timetable-slot_template'
    ]);
}

function datafield_timetable_getaddfield($content, $field) {
    $categories = datafield_timetable_getcategories($field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});
    $categoryths = '';
    foreach ($categories as $category) {
        $categoryths .= html_writer::tag('th', $category['name']);
    }

    $addbtn = html_writer::tag('button', get_string('addactivity', 'datafield_timetable'), [
        'type' => 'button',
        'class' => 'btn btn-success datafield_timetable-add_btn'
    ]);

    $thead = html_writer::tag('thead',
        html_writer::tag('tr',
            html_writer::tag('th', get_string('time', 'datafield_timetable')) .
            html_writer::tag('th', get_string('activity', 'datafield_timetable')) .
            $categoryths .
            html_writer::tag('th', '')
        )
    );
    $tbody = html_writer::tag('tbody',
        datafield_timetable_getslottemplate($field, $categories), [
            'class' => 'datafield_timetable-tbody'
        ]);

    $table = html_writer::tag('table', $thead . $tbody)
        . html_writer::div($addbtn)
        . html_writer::start_tag('input', [
            'type' => 'hidden',
            'class' => 'datafield_timetable-data',
            'name' => "field_{$field->id}",
            'value' => $content
        ]);
//        .html_writer::tag('textarea', $content, [
//            'class' => 'datafield_timetable-data',
//            'name' => "field_{$field->id}",
//            'rows' => 30,
//            'cols' => 50
//        ]);

    return html_writer::div($table, 'datafield_timetable-timetable', [
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

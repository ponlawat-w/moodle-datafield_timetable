<?php

require_once(__DIR__ . '/lib.php');

class data_field_timetable extends data_field_base {

    public $type = 'timetable';

    public function __construct($field = 0, $data = 0, $cm = 0) {
        parent::__construct($field, $data, $cm);
        $this->copy_icon();
        $this->init_page();
    }

    private function init_page() {
        global $PAGE;
        $cm = get_coursemodule_from_instance('data', $this->field->dataid);
        $modcontext = context_module::instance($cm->id);

        $items = [];
        if (has_capability('mod/data:exportallentries', $modcontext)) {
            $items[] = [
                'text' => get_string('calculatecredits', 'datafield_timetable'),
                'url' => (new moodle_url('/mod/data/field/timetable/export.php', ['fid' => $this->field->id]))->out()
            ];
        }
        if (has_capability('mod/data:exportownentry', $modcontext)) {
            $items[] = [
                'text' => get_string('downloadreport', 'datafield_timetable'),
                'url' => (new moodle_url('/mod/data/field/timetable/exportprintable.php', ['dataid' => $this->field->dataid, 'exporting' => true]))->out()
            ];
        }
        $PAGE->requires->js_init_code('DATAFIELD_TIMETABLE_EXTENDMENU = ' . json_encode($items) . ';', false);
        $PAGE->requires->jquery();
        $PAGE->requires->js('/mod/data/field/timetable/extendmenu.js');
    }

    private function copy_icon() {
        if (!file_exists(__DIR__ . '/../../pix/field/timetable.svg')
            && is_writable(__DIR__ . '/../../pix/field/')) {
            copy(__DIR__ . '/pix/icon.svg', __DIR__ . '/../../pix/field/timetable.svg');
        }
    }

    function display_search_field() {
        return '';
    }

    function display_add_field($recordid = 0, $formdata = null) {
        global $PAGE, $DB;

        if ($formdata) {
            $content = $formdata->{"field_{$this->field->id}"};
        } else if ($recordid) {
            $content = $DB->get_field('data_content', 'content', [
                'fieldid' => $this->field->id,
                'recordid'=>$recordid
            ]);
        } else {
            $content = '';
        }

        echo html_writer::start_tag('link', [
            'rel' => 'stylesheet',
            'href' => new moodle_url('/mod/data/field/timetable/addentrystyle.css')
        ]);
        $PAGE->requires->js(new moodle_url('/mod/data/field/timetable/addfield.js'));
        return datafield_timetable_getaddfield($content, $this->field);
    }

    function display_edit_field() {
        global $PAGE;
        $PAGE->requires->js(new moodle_url('/mod/data/field/timetable/editfield.js'));
        parent::display_edit_field();
    }

    function display_browse_field($recordid, $template) {
        global $DB;

        $content = $DB->get_record('data_content', ['recordid' => $recordid, 'fieldid' => $this->field->id]);

        if ($template == 'listtemplate') {
            return datafield_timetable_getdisplaylisttemplate($content);
        }
        if ($template == 'singletemplate') {
            return datafield_timetable_getdisplaysingletemplate($content, $this->field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});
        }

        return parent::display_browse_field($recordid, $template);
    }

    function update_content($recordid, $value, $name = '') {
        return parent::update_content($recordid, $value, $name);
    }

    public function parse_search_field($defaults = null) {
        $param = 'f_' . $this->field->id;
        if (empty($defaults[$param])) {
            $defaults = array($param => '');
        }
        return optional_param($param, $defaults[$param], PARAM_NOTAGS);
    }

    /**
     * @param object $record
     * @return string
     */
    function export_text_value($record) {
        if (is_string($record)) {
            return $record;
        }
        $categories = datafield_timetable_getcategories($this->field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});
        $activities = datafield_timetable_toactivities($record->content);
        $lines = [];
        foreach ($activities as $activity) {
            $lines[] = $activity->to_export_text($categories);
        }
        return implode(PHP_EOL, $lines);
    }
}

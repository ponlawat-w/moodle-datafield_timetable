<?php

require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../../../../../lib/formslib.php');

class exportsettings_form extends moodleform {
    private $field;

    public function __construct($field) {
        $this->field = $field;
        parent::__construct();
    }

    private function getusers() {
        global $DB;
        $dataid = $this->field->dataid;
        $order = get_string('fullnamedisplay');
        $f = strpos($order, '$a->firstname');
        $l = strpos($order, '$a->lastname');

        $ordersql = $f === FALSE || $l === FALSE || $f < $l ?
            'u.firstname ASC, u.lastname ASC' : 'u.lastname ASC, u.firstname ASC';

        return $DB->get_records_sql(
            'SELECT DISTINCT u.* FROM {data_records} r JOIN {user} u ON r.userid = u.id WHERE r.dataid = ? ORDER BY ' . $ordersql
        , [$dataid]);
    }

    private function getuseroptions($users) {
        $options = [];
        foreach ($users as $user) {
            $options[$user->id] = fullname($user);
        }
        return $options;
    }

    public function definition() {
        $form = &$this->_form;

        $users = $this->getusers();

        $form->addElement('hidden', 'fid', $this->field->id);
        $form->setType('fid', PARAM_INT);

        $form->addElement('select', 'userid', get_string('targetuser', 'datafield_timetable'), $this->getuseroptions($users));
        $form->setType('userid', PARAM_INT);

        $form->addElement('text', 'hourpercredit', get_string('hourpercredit', 'datafield_timetable'));
        $form->setType('hourpercredit', PARAM_INT);
        $form->setDefault('hourpercredit', 30);

        $form->addElement('header', 'creditheader', get_string('creditcalculation', 'datafield_timetable'));
        $form->setExpanded('creditheader', true);

        $categories = datafield_timetable_getcategories($this->field->{DATAFIELD_TIMETABLE_COLUMN_FIELD_CATEGORIES});

        foreach ($categories as $category) {
            $form->addElement('static', 'categorystatic_' . $category['id'], html_writer::tag('h3', $category['name']));
            foreach ($category['items'] as $item) {
                $radioname = "category-{$category['id']}-{$item['id']}";
                $radios = [
                    $form->createElement('radio', $radioname, '', get_string('credit_work', 'datafield_timetable'), DATAFIELD_TIMETABLE_CREDIT_WORK),
                    $form->createElement('radio', $radioname, '', get_string('credit_break', 'datafield_timetable'), DATAFIELD_TIMETABLE_CREDIT_BREAK),
                    $form->createElement('radio', $radioname, '', get_string('credit_ignore', 'datafield_timetable'), DATAFIELD_TIMETABLE_CREDIT_IGNORED)
                ];
                $form->addGroup($radios, "radios-{$category['id']}-{$item['id']}", $item['name'], [' '], false);
                $form->setType($radioname, PARAM_ALPHA);
                $form->setDefault($radioname, DATAFIELD_TIMETABLE_CREDIT_IGNORED);
            }
        }

        $this->add_action_buttons(false, get_string('export', 'datafield_timetable'));
    }
}

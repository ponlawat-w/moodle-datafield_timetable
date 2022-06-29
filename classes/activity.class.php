<?php

class Activity {
    public $from = 0;
    public $to = 0;
    public $activity = '';
    public $categories = [];

    public function __construct($row) {
        $data = explode(';', $row);
        if (count($data) != 4) {
            return;
        }

        $this->from = $data[0];
        $this->to = $data[1];
        $this->activity = urldecode($data[2]);
        $this->categories = [];
        $categoriesraw = explode(',', trim($data[3]));
        foreach ($categoriesraw as $categoryraw) {
            $categorydata = explode('=', $categoryraw);
            if (count($categorydata) == 2) {
                $this->categories[$categorydata[0]] = $categorydata[1];
            }
        }
    }

    private static function totwodigit($number) {
        return $number < 10 ? "0{$number}" : $number;
    }

    public static function gettimestring($time) {
        $hour = self::totwodigit(floor($time / 60) % 24);
        $minute = self::totwodigit($time % 60);

        return "{$hour}:{$minute}";
    }

    public function getfromtime() {
        return self::gettimestring($this->from);
    }

    public function gettotime() {
        return self::gettimestring($this->to);
    }

    public function getduration() {
        return $this->to - $this->from;
    }

    public function getcategoryname($categories, $categoryid) {
        if (isset($this->categories[$categoryid])
            && isset($categories[$categoryid])
            && isset($categories[$categoryid]['items'])
            && isset($categories[$categoryid]['items'][$this->categories[$categoryid]])) {
            return $categories[$categoryid]['items'][$this->categories[$categoryid]]['name'];
        }

        return '';
    }

    public function hascategory($categorystr, $delimiter = '-') {
        $categorystrs = explode($delimiter, $categorystr);
        if (count($categorystrs) != 2) {
            return false;
        }

        if (!isset($this->categories[$categorystrs[0]])) {
            return false;
        }

        return $this->categories[$categorystrs[0]] == $categorystrs[1];
    }

    public function hascategories($categorystrs, $delimitor = '-') {
        foreach ($categorystrs as $categorystr) {
            if ($this->hascategory($categorystr, $delimitor)) {
                return true;
            }
        }
        return false;
    }

    public function to_export_text($categories) {
        $items = [$this->getfromtime() . '-' . $this->gettotime(), $this->activity];
        foreach ($categories as $categoryid => $category) {
            $items[] = $category['name'] . ': ' . $this->getcategoryname($categories, $categoryid);
        }
        return implode(' / ', $items);
    }
}

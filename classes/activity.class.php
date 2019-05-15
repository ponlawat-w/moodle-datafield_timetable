<?php

class Activity {
    public $from = 0;
    public $to = 0;
    public $activity = '';
    public $category = 0;

    public function __construct($row) {
        $data = explode(';', $row);
        if (count($data) != 4) {
            return;
        }

        $this->from = $data[0];
        $this->to = $data[1];
        $this->activity = urldecode($data[2]);
        $this->category = trim($data[3]);
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

    public function getcategoryname($categories) {
        return isset($categories[$this->category]) ? $categories[$this->category] : '';
    }
}

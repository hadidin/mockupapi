<?php

namespace App\Http\Libraries;

class Utility
{
    public static function convertToDecimal($val)
    {
        if (!self::isDecimal($val)) {
            return number_format($val, 2, '.', '');
        }
        if (strpos(strrev($val), ".") != "2") {
            return number_format($val, 2, '.', '');
        }

        return $val;
    }

    public static function isDecimal($val)
    {
        return is_numeric($val) && floor($val) != $val;
    }

    public static function secondsToTime($seconds)
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");

        $months = $dtF->diff($dtT)->format('%m');
        $days = $dtF->diff($dtT)->format('%d');
        $hours = $dtF->diff($dtT)->format('%h');
        $minutes = $dtF->diff($dtT)->format('%i');

        $time = "";

        if ($months > 0)
            $time .= $months . "M ";

        if ($days > 0)
            $time .= $days . "d ";

        if ($hours > 0)
            $time .= $hours . "h ";

        $time .= $minutes . "m";

        return $time;
    }
}

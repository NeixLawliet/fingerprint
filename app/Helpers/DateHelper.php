<?php
namespace App\Helpers;
use Carbon\Carbon;

class DateHelper
{
	public static function getCurrentDate($format="Y-m-d H:i:s", $time_zone=null) {
        if ($time_zone) {
            return Carbon::now($time_zone)->format($format);
        } else {
            return Carbon::now()->format($format);
        }
    }

    public static function addDateTime($unit,$src,$n, $format="") {
        if($src=='now') {
            $s = Carbon::now();
        } else {
            $s = Carbon::parse($src);
        }
        $unit = "add".ucwords(strtolower($unit))."s";
        $ret = $s->$unit($n);
        if(!empty($format)) {
            return $ret->format($format);
        } else {
            return $ret;
        }
    }

    public static function compareDate($src,$dest,$comp='eq') {
        $s = ($src == 'now') ? Carbon::now() : Carbon::parse($src);
        $d = ($dest == 'now') ? Carbon::now() : Carbon::parse($dest);
        return $s->$comp($d);
    }

    public static function getDateTimeDiff($unit,$src,$dest) {
        $s = Carbon::parse($src);
        $d = Carbon::parse($dest);
        $unit = "diffIn".ucwords(strtolower($unit))."s";
        return $s->$unit($d,false);
    }

    public static function getDateTimeRanges($start,$end,$unit='month', $format='Y-m') 
    {
        $res = [];
        $n = self::getDateTimeDiff($unit, $start, $end);
        for ($i=0; $i <= $n; $i++) {
            $res[] = self::parsingDate($start, $format);
            $start = self::addDateTime($unit, $start, 1, $format);
        }

        return $res;
    }

    /**
     * Date range
     *
     * @param $first
     * @param $last
     * @param string $step
     * @param string $format
     * @return array
     */
    public static function dateRange( $first, $last, $step = '+1 day', $format = 'Y-m-d' ) {
        $dates = [];
        $current = strtotime( $first );
        $last = strtotime( $last );

        while( $current <= $last ) {

            $dates[] = date( $format, $current );
            $current = strtotime( $step, $current );
        }

        return $dates;
    }

    public static function parsingDate($src,$format="Y-m-d H:i:s",$opts=[]) {
        if(isset($opts['month_convertion'])) {
            $src = self::monthConvertion($src,$opts['month_convertion']['langFrom'],$opts['month_convertion']['langTo'],$opts['month_convertion']['type']);
        }

        $res =  Carbon::parse($src)->format($format);
        
        if(isset($opts['res_month_convertion'])) {
            $res = self::monthConvertion($res,$opts['res_month_convertion']['langFrom'],$opts['res_month_convertion']['langTo'],$opts['res_month_convertion']['type']);
        }
        return $res;
    }

    public static function monthConvertion($src,$langFrom='en',$langTo='id',$type='long') {
        $months['id'] = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        $months['en'] = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
        $src = ucwords(strtolower($src));
        if (strpos($src, "Nop") !== false) {
            $src = str_replace("Nop", "Nov", $src);
        }
        $agustus = (strpos($src, "g") !== false && strpos($src, "s") !== false  && strpos($src, "t") !== false); 
        $split = explode(' ',$src);
        if (count($split) == 1) {
            $split = explode('-',$src);
        }
        if($agustus) {
            $monthFrom = substr($months[$langFrom][7],0,strlen($split[1]));
            $monthTo = ($type=='short') ? substr($months[$langTo][7],0,3) : $months[$langTo][7];
            $src = str_replace($monthFrom, $monthTo, $src);
        } else {
            foreach ($months[$langFrom] as $key => $value) {
                $monthFrom = substr($value,0,strlen($split[1]));
                $monthTo = ($type=='short') ? substr($months[$langTo][$key],0,3) : $months[$langTo][$key];
                $src = str_replace($monthFrom, $monthTo, $src);
            }
        }
        return $src;
    }

    public static function serializeDateTime($src,$unit="day") {
        return Carbon::parse($src)->$unit;
    }

    public static function convertTimestampToString($src,$format="Y-m-d H:i:s") {
        if(is_numeric($src)) {
            return Carbon::createFromTimestamp($src)->format($format);
        } else {
            return self::parsingDate($src,$format);
        }
    }

    public static function milisecondToDateTime($s) {
        return date("Y-m-d H:i:s",$s/1000);
    }

    public static function createDateTime($y=null,$m=1,$d=1,$h=0,$i=0,$s=0){
        if(is_null($y)) $y = date("Y");
        $dt = Carbon::create($y, $m, $d, $h, $i, $s);
        return $dt;
    }
    
	public static function getLastDayOfMonth($dt,$format="Y-m-d H:i:s"){
        return $dt->endOfMonth()->format($format);
    }

    public static function dateFormat($src, $format='Y-m-d H:i:s') {
        return Carbon::parse($src)->format($format);
    }

    public static function isEndOfYearDate($date)
    {
        return (self::parsingDate($date,'dm')==3112);
    }

    public static function isStartOfYearDate($date)
    {
        return (self::parsingDate($date,'m')==1 && self::parsingDate($date,'d')==1 );
    }

    public static function isEndOfMonth($date)
    {
        return (self::parsingDate($date,'Y-m-d')==self::parsingDate($date,'Y-m-t'));
    }

    public static function getPeriodFromDate($date, $dateStart=null)
    {
        $period = 'daily';

        if (!isset($date)) return $period;

        if (!isset($dateStart)) {
            if (self::isEndOfYearDate($date)) {
                $period = 'yearly';
            } else if (self::isEndOfMonth($date)) {
                $period = 'monthly';
            }
        } else {
            if (self::isStartOfYearDate($dateStart) && self::isEndOfYearDate($date)) {
                $period = 'yearly';
            } else if (self::parsingDate($dateStart,'d')==1 && self::isEndOfMonth($date)) {
                $period = 'monthly';
            }
        }

        return $period;
    }

    public static function currentDateTime($user_timezone = 'Asia/Jakarta')
    {
        $date = new \DateTime("now", new \DateTimeZone($user_timezone) );

        $only_date = $date->format('Y-m-d');
        $only_time = $date->format('H:i:s');
        $datetime = $date->format('Y-m-d H:i:s');

        return [
            'date' => $only_date,
            'time' => $only_time,
            'datetime' => $datetime
        ];
    }
}

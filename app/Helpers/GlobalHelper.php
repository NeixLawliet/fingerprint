<?php

namespace App\Helpers;

use App\Notifications\TelegramError;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use DateTime;

class GlobalHelper
{
    public static function findString($needle, $haystack, $i, $word)
    {   // $i should be "" or "i" for case insensitive
        if (strtoupper($word)=="W") {   // if $word is "W" then word search instead of string in string search.
            if (preg_match("/\b{$needle}\b/{$i}", $haystack)) {
                return true;
            }
        } else {
            if(preg_match("/{$needle}/{$i}", $haystack)) {
                return true;
            }
        }
        return false;
        // Put quotes around true and false above to return them as strings instead of as bools/ints.
    }

    public static function truncateString($inputString)
    {
        // Jika panjang string lebih dari 12 karakter
        if (strlen($inputString) > 12) {
            // Potong menjadi 12 karakter pertama
            return substr($inputString, 0, 12);
        }

        // Jika panjang string kurang dari atau sama dengan 12 karakter, kembalikan string aslinya
        return $inputString;
    }

    public static function dayEngToInd($english) {
        if ($english == 'Monday') {
            $day = 'Senin';
        } else if ($english == 'Tuesday') {
            $day = 'Selasa';
        } else if ($english == 'Wednesday') {
            $day = 'Rabu';
        } else if ($english == 'Thursday') {
            $day = 'Kamis';
        } else if ($english == 'Friday') {
            $day = 'Jum\'at';
        } else if ($english == 'Saturday') {
            $day = 'Sabtu';
        } else if ($english == 'Sunday') {
            $day = 'Minggu';
        } else {
            $day = 'Unknown';
        }

        return $day;
    }

    public static function numberToMonthIndo($number) {
        if ($number == '01' || $number == 1) {
            $month = 'Januari';
        } else if ($number == '02' || $number == 2) {
            $month = 'Februari';
        } else if ($number == '03' || $number == 3) {
            $month = 'Maret';
        } else if ($number == '04' || $number == 4) {
            $month = 'April';
        } else if ($number == '05' || $number == 5) {
            $month = 'Mei';
        } else if ($number == '06' || $number == 6) {
            $month = 'Juni';
        } else if ($number == '07' || $number == 7) {
            $month = 'Juli';
        } else if ($number == '08' || $number == 8) {
            $month = 'Agustus';
        } else if ($number == '09' || $number == 9) {
            $month = 'September';
        } else if ($number == '10' || $number == 10) {
            $month = 'Oktober';
        } else if ($number == '11' || $number == 11) {
            $month = 'November';
        } else if ($number == '12' || $number == 12) {
            $month = 'Desember';
        }

        return $month;
    }

    public static function minutes($time)
    {
        $time = explode(':', $time);
        return ($time[0]*60) + ($time[1]) + ($time[2]/60);
    }

    public static function minuteToHourMinute($minutes) 
    {
        $hours = floor($minutes / 60);
        $min = $minutes - ($hours * 60);

        return $hours." jam, ".$min." menit";
    }

    public static function convertSeparator($number, $separator = '.')
    {
        if (empty($number) && $number !== 0 && $number !== '0') {
            return 0;
        }
        
        $number = str_replace($separator, '', $number);
        
        $number = str_replace(',', '.', $number);
        
        return floatval($number);
    }

    public static function checkThousandSeparator($number)
    {
        if (preg_match('/(\.|,)\d{3}/', $number)) {
            if (strpos($number, '.') !== false) {
                return '.'; // Mengandung pemisah ribuan titik
            } else if (strpos($number, ',') !== false) {
                return ','; // Mengandung pemisah ribuan koma
            }
        }

        return ''; // Tidak mengandung pemisah ribuan
    }

    public static function periodDateTime($date, $dateTo = null)
    {
        if ($dateTo) {
            $ages_interval = date_diff(date_create($dateTo), date_create($date));
        } else {
            $ages_interval = date_diff(date_create(), date_create($date));
        }
        $age = $ages_interval->format("%Y thn, %M bln, %d hr");

        return $age;
    }

    public static function dateIndo($date, $with_day = false) 
    {
        if ($date) {
            $day = '';
            if ($with_day) {
                $day = self::dayEngToInd(date('l', strtotime($date)));
            }

            $fullDate = explode('-', $date);

            $date = $fullDate[2];
            $month = $fullDate[1];
            $year = $fullDate[0];

            return ($day ? $day . ', ': '') . $date.' '.self::numberToMonthIndo($month).' '.$year;
        }

        return '-';
    }

    public static function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
    {
        $datetime1 = date_create($date_1);
        $datetime2 = date_create($date_2);

        $interval = date_diff($datetime1, $datetime2);

        return $interval->format($differenceFormat);
    }

    public static function search($array, $key, $value)
    {
        // dd($value);
        $results = [];

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, self::search($subarray, $key, $value));
            }
        }
        
        return $results;
    }

    public static function camelToSnake($camel)
    {
        $snake = preg_replace('/[A-Z]/', '_$0', $camel);
        $snake = strtolower($snake);
        $snake = ltrim($snake, '_');
        return $snake;
    }

    public static function getRealQuery($query, $dumpIt = false)
    {
        $params = array_map(function ($item) {
            return "'{$item}'";
        }, $query->getBindings());
        
        $result = Str::replaceArray('?', $params, $query->toSql());
        if ($dumpIt) {
            dd($result);
        }
        return $result;
    }

    public static function randomText( $type = 'alnum', $length = 8 )
    {
        switch ( $type ) {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string) $type;
                break;
        }


        $crypto_rand_secure = function ( $min, $max ) {
            $range = $max - $min;
            if ( $range < 0 ) return $min; // not so random...
            $log    = log( $range, 2 );
            $bytes  = (int) ( $log / 8 ) + 1; // length in bytes
            $bits   = (int) $log + 1; // length in bits
            $filter = (int) ( 1 << $bits ) - 1; // set all lower bits to 1
            do {
                $rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
                $rnd = $rnd & $filter; // discard irrelevant bits
            } while ( $rnd >= $range );
            return $min + $rnd;
        };

        $token = "";
        $max   = strlen( $pool );
        for ( $i = 0; $i < $length; $i++ ) {
            $token .= $pool[$crypto_rand_secure( 0, $max )];
        }

        return $token;
    }

    public static function getClientIP()
    {
        $ip = 'Unknown';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_address = explode(',', $ip);
        return $ip_address[0];
    }

    public static function escapeJsonString($value) 
    {  
        $escapers = ['\n'];
        $replacements = [", "];
        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }

    public static function pushLog($type,$request,$response,$trace=[]) 
    {
        $isAPILimit = false;
        if(isset($response['error_message']['error'])) {
            $error = $response['error_message']['error'];
            $isAPILimit = isset($error['detail']['api_rate_limit']);
        }

        if($type == 'error') {
            $trace_split = [];

            for ($i=0; $i<3; $i++) {
                if (isset($trace['#0'.$i])) {
                    $trace_split['#0'.$i] = $trace['#0'.$i];
                }
            }
            $encoded = json_encode(utf8ize(array_merge($request, $response, ['trace' => $trace_split])));
            \Log::error($encoded);

            if (!env('APP_DEBUG')) {
                Notification::route('telegram', env('TELEGRAM_LOGGER_CHAT_ID'))->notify(new TelegramError(['data' => $encoded]));
            }
        } else if ($type == 'need_debug') {
            Notification::route('telegram', env('TELEGRAM_LOGGER_CHAT_ID'))->notify(new TelegramError(['data' => $request]));
        } else {
            $encoded = json_encode(array_merge($request, $response));
            \Log::info(self::escapeJsonString($encoded));
        }
    }

    public static function getPolygonCenter($points) 
    {
        $num_points = count($points);

        $x = 0;
        $y = 0;
        $area = 0;

        for ($i = 0; $i < $num_points; $i++) {
            $j = ($i + 1) % $num_points;
            $x_sum = ($points[$i][0] + $points[$j][0]) * ($points[$i][0] * $points[$j][1] - $points[$j][0] * $points[$i][1]);
            $y_sum = ($points[$i][1] + $points[$j][1]) * ($points[$i][0] * $points[$j][1] - $points[$j][0] * $points[$i][1]);
            $area += $points[$i][0] * $points[$j][1] - $points[$j][0] * $points[$i][1];
            $x += $x_sum;
            $y += $y_sum;
        }

        $area *= 0.5;

        $x = $x / (6 * $area);
        $y = $y / (6 * $area);

        return [$x, $y];
    }

    function calculateCenterFromPath($path) 
    {
        //algoritma bounding box not pretty accurate
        
        //Algoritma centroid
        $commands = ['M', 'L', 'C'];
        $points = [];
        $start = null;
        $x = null;
        $y = null;

        preg_match_all('/([a-z][^a-z]*)/i', $path, $matches);

        foreach ($matches[0] as $part) {
            $command = substr($part, 0, 1);

            if (in_array($command, $commands)) {
                $coords = explode(' ', trim(substr($part, 1)));

                foreach ($coords as $i => $coord) {
                    if ($i % 2 === 0) {
                        $x = floatval($coord);
                    } else {
                        $y = floatval($coord);

                        if ($command === 'M') {
                            $start = [$x, $y];
                            $points[] = [$x, $y];
                        } elseif ($command === 'L') {
                            $points[] = [$x, $y];
                        } elseif ($command === 'C') {
                            $points[] = [$x, $y];
                            // Add two additional points for the bezier curve
                            $points[] = [floatval($coords[$i + 1]), floatval($coords[$i + 2])];
                            $points[] = [floatval($coords[$i + 3]), floatval($coords[$i + 4])];
                        }
                    }
                }
            } elseif ($command === 'Z' && count($points) > 0) {
                $points[] = $start;
            }
        }

        $area = 0;
        $cx = 0;
        $cy = 0;

        foreach ($points as $i => $point) {
            if (isset($points[$i + 1])) {
                $x0 = $point[0];
                $y0 = $point[1];
                $x1 = $points[$i + 1][0];
                $y1 = $points[$i + 1][1];
                $f = $x0 * $y1 - $x1 * $y0;
                $area += $f;
                $cx += ($x0 + $x1) * $f;
                $cy += ($y0 + $y1) * $f;
            }
        }

        if ($area === 0) {
            return [$x, $y];
        }

        $area /= 2;
        $cx /= (6 * $area);
        $cy /= (6 * $area);

        return [$cx, $cy];
    }

    public static function natural_language_join( array $list, $conjunction = 'and' ) : string {
        $oxford_separator = count( $list ) == 2 ? ' ' : ', ';
        $last = array_pop( $list );

        if ( $list ) {
            return implode( ', ', $list ) . $oxford_separator . $conjunction . ' ' . $last;
        }

        return $last;
    }

    public static function slugify($text, string $divider = '_')
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public static function convertTime($time) {
        $timeParts = explode(':', $time);
        $hours = $timeParts[0];
        $minutes = $timeParts[1];
        $seconds = $timeParts[2];

        if ($hours >= 24) {
            $hours -= 24;
        }

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public static function isValidEmail($email) 
    {
        // Menggunakan filter_var untuk memeriksa format email
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function countMonths($monthStart, $yearStart, $monthEnd, $yearEnd) 
    {
        $startDate = new DateTime("$yearStart-$monthStart-01");
        $endDate = new DateTime("$yearEnd-$monthEnd-01");

        $interval = $endDate->diff($startDate);

        $totalMonths = ($interval->y * 12) + ($interval->m + 1);

        return $totalMonths;
    }
}
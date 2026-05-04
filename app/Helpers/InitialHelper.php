<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Google\Auth\ApplicationDefaultCredentials;

class InitialHelper
{
    /**
     * Generate initials from a name
     *
     * @param string $name
     * @return string
     */
    public static function generate(string $name) : string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }
        return self::makeInitialsFromSingleWord($name);
    }

    /**
     * Make initials from a word with no spaces
     *
     * @param string $name
     * @return string
     */
    protected function makeInitialsFromSingleWord(string $name) : string
    {
        preg_match_all('#([A-Z]+)#', $name, $capitals);
        if (count($capitals[1]) >= 2) {
            return substr(implode('', $capitals[1]), 0, 2);
        }
        return strtoupper(substr($name, 0, 2));
    }

    public static function spellNumberInIndonesian($number)
    {
        $result = "";
        $number = strval($number);
        if (!preg_match("/^[0-9]{1,15}$/", $number)) return false;

        $ones           = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan"];
        $majorUnits     = ["", "ribu", "juta", "milyar", "trilyun"];
        $minorUnits     = ["", "puluh", "ratus"];
        $length         = strlen($number);
        $isAnyMajorUnit = false;
        
        for ($i = 0, $pos = $length - 1; $i < $length; $i++, $pos--) {
            if ($number[$i] != '0') {
                if ($number[$i] != '1') {
                    $result .= $ones[$number[$i]].' '.$minorUnits[$pos % 3].' ';
                } else if ($pos % 3 == 1 && $number[$i + 1] != '0') {
                    if ($number[$i + 1] == '1')
                        $result .= "sebelas ";
                    else
                        $result .= $ones[$number[$i + 1]]." belas ";
                    $i++; $pos--;
                } else if ($pos % 3 != 0) {
                    $result .= "se".$minorUnits[$pos % 3].' ';
                } else if ($pos == 3 && !$isAnyMajorUnit) {
                    $result .= "se";
                } else {
                    $result .= "satu ";
                }
                $isAnyMajorUnit = true;
            }

            if ($pos % 3 == 0 && $isAnyMajorUnit) {
                $result         .= $majorUnits[$pos / 3].' ';
                $isAnyMajorUnit = false;
            }
        }
        $result = trim($result);
        if ($result == "") $result = "nol";

        return ucwords($result);
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
        if ($number == '01') {
            $month = 'Januari';
        } else if ($number == '02') {
            $month = 'Februari';
        } else if ($number == '03') {
            $month = 'Maret';
        } else if ($number == '04') {
            $month = 'April';
        } else if ($number == '05') {
            $month = 'Mei';
        } else if ($number == '06') {
            $month = 'Juni';
        } else if ($number == '07') {
            $month = 'Juli';
        } else if ($number == '08') {
            $month = 'Agustus';
        } else if ($number == '09') {
            $month = 'September';
        } else if ($number == '10') {
            $month = 'Oktober';
        } else if ($number == '11') {
            $month = 'November';
        } else if ($number == '12') {
            $month = 'Desember';
        }

        return $month;
    }

    public static function minutes($time){
        $time = explode(':', $time);
        return ($time[0]*60) + ($time[1]) + ($time[2]/60);
    }

    public static function minuteToHourMinute($minutes) {
        $hours = floor($minutes / 60);
        $min = $minutes - ($hours * 60);

        return $hours." jam, ".$min." menit";
    }

    public static function convertSeparator($number)
    {
        $number = str_replace('.', '', $number);

        if ($number > 0) {
            return $number;
        }

        return 0;
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

    public static function dateIndo($date) 
    {
        if ($date) {
            $fullDate = explode('-', $date);

            $date = $fullDate[2];
            $month = $fullDate[1];
            $year = $fullDate[0];

            return $date.' '.self::numberToMonthIndo($month).' '.$year;
        }

        return '-';
    }

    private static function getAccessToken()
    {
        $keyFilePath = __DIR__ . '/../Lib/mandep-apps-082c473ba6c5.json';

        if (!getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyFilePath);
        }

        $auth = ApplicationDefaultCredentials::getCredentials('https://www.googleapis.com/auth/firebase.messaging');
        $token = $auth->fetchAuthToken();
        return $token['access_token'];
    }

    public static function notifToUser($params)
    {
        $access_token = self::getAccessToken();
        $projectId = "mandep-apps"; // Ganti dengan Firebase Project ID Anda
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        foreach ($params['fcm_tokens'] as $token) {
            $message = [
                "message" => [
                    "token" => $token, 
                    "notification" => [
                        "title" => $params['title'],
                        "body"  => $params['body'],
                        // "image" => $params['image']
                    ],
                    "data" => [
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                        "id" => "1",
                        "status" => "done"
                    ],
                    // "android" => [
                    //     "notification" => [
                    //         "image" => $params['image'] ?? ''
                    //     ]
                    // ],
                    // "apns" => [
                    //     "payload" => [
                    //         "aps" => [
                    //             "mutable-content" => 1
                    //         ]
                    //     ],
                    //     "fcm_options" => [
                    //         "image" => $params['image'] ?? ''
                    //     ]
                    // ]
                ]
            ];

            // if (isset($params['image']) && $params['image']) {
            //     $message['message']['notification']['image'] = $params['image'];
            // }

            // dd($message);

            try {
                $client = new Client();
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $access_token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $message
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                return [
                    'error' => $e->getMessage(),
                    'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
                ];
            }
        }
    }

        // $data = [
        //     "notification" => [
        //         "body" => $params['body'],
        //         "title" => $params['title']
        //     ],
        //     "priority" => "high",
        //     "data" => [
        //         "click_action" => "FLUTTER_NOTIFICATION_CLICK",
        //         "id" => "1",
        //         "status" => "done"
        //     ],
        //     "registration_ids" => $params['fcm_token']
        // ];


        // $url = 'https://fcm.googleapis.com/fcm/send';

        // $ch = curl_init($url); 
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: key=AAAAfzgJPLs:APA91bHQHq5vy-MZf4pTbJfeS_AqEr-l2cBeZ7iRjUaUL4USZO7WATL1o14q4QKTB9VrS04_7si2arTNM7EQz_FT5B-75lRsqQ3_dsqcF0r-IUMH_kt8SxuLlkwmBhVuGKcIQ4QsGuV6']);
        // $result = curl_exec($ch);
        // curl_close($ch);
    // }
}
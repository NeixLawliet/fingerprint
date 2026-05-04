<?php

namespace App\Helpers;

use App\Models\Invoices;
use App\Models\Sales;
use App\Models\Transactions;
use Carbon\Carbon;

class AutoNumberHelper
{
    public static function initGenerateNumber($prefix)
    {
        $data = [];

        if ($prefix == null || $prefix == '') {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Prefix should exist!'
            ]);
        } else {
            switch ($prefix) {
                case "S-":
                    $data = [
                        'class' => Sales::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "INV":
                    $data = [
                        'class' => Invoices::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "TR-":
                    $data = [
                        'class' => Transactions::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                default:
                    throw new \Exception("Prefix {$prefix} is not registered in AutoNumberHelper.");
            }
        }

        return self::generateNumber($data);
    }

    private static function generateNumber($params)
    {
        $now = Carbon::now();
        $prefixSize = (strlen($params['prefix'])) + 10;

        $prefix = $params['prefix'];
        $prefix .= $now->year . sprintf('%02d', $now->month);

        $data = $params['class']::whereRaw('LENGTH(' . $params['field'] . ') = ?', $prefixSize)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where($params['field'], 'like', $prefix . '%')
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($data == null) {
            $prefix .= sprintf('%04d', 1);
        } else {
            $repeat = true;
            $last = substr($data[$params['field']], -4);
            $new = sprintf('%04d', ++$last);
            while ($repeat) {
                $data = $params['class']::where($params['field'], $prefix . $new)->first();
                if ($data == null) {
                    $repeat = false;
                    $prefix .= sprintf('%04d', $new);
                } else {
                    $new++;
                }
            }
        }
        return $prefix;
    }
}

<?php
namespace App\Helpers;

use App\Models\Companies\v1\AccountingClosings;
use App\Models\Companies\v1\AccountingJournals;
use App\Models\Companies\v1\Budgets;
use App\Models\Companies\v1\CashIns;
use App\Models\Companies\v1\CashOuts;
use App\Models\Companies\v1\DebtRepayments;
use App\Models\Companies\v1\PurchaseInvoices;
use App\Models\Companies\v1\PurchaseOrders;
use App\Models\Companies\v1\PurchaseRequests;
use App\Models\Companies\v1\StudentBillings;
use App\Models\Companies\v1\StudentPayments;
use Carbon\Carbon;

class AutoNumberCompaniesHelper
{
    public static function initGenerateNumber($prefix, $date = '', $format = 'date')
    {
        $data = [];

        if ($prefix == null || $prefix == '') {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'Prefix should exist!']);
        } else {
            switch ($prefix) {
                case "JU":
                    $data = ['class' => AccountingJournals::class , 'field' => 'number', 'prefix' => $prefix, 'separator' => ''];
                    break;
                case "BU":
                    $data = [
                        'class' => Budgets::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "PR":
                    $data = [
                        'class' => PurchaseRequests::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "PO":
                    $data = [
                        'class' => PurchaseOrders::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "PI":
                    $data = [
                        'class' => PurchaseInvoices::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "AC":
                    $data = [
                        'class' => AccountingClosings::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "CI":
                    $data = [
                        'class' => CashIns::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "CO":
                    $data = [
                        'class' => CashOuts::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "DR":
                    $data = [
                        'class' => DebtRepayments::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "INV":
                    $data = [
                        'class' => StudentBillings::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;
                case "PAY":
                    $data = [
                        'class' => StudentPayments::class,
                        'field' => 'number',
                        'prefix' => $prefix
                    ];
                    break;



                default:
                    echo "Your favorite color is neither red, blue, nor green!";
            }
        }

        return $format === 'increment' ? self::generateNumberIncrement($data) : self::generateNumber($data, $date);
    }

    private static function generateNumber($params, $date)
    {
        $now = Carbon::now();
        $prefixSize = (strlen($params['prefix'])) + 10;

        $month_param = $now->month;
        $year_param = $now->year;

        if ($date != '') {
            $expl_date = explode('-', $date);
            if (count($expl_date) > 1) {
                $month_param = $expl_date[1];
                $year_param = $expl_date[0];
            }
        }

        $prefix = $params['prefix'];
        $prefix .= $year_param . sprintf('%02d', $month_param);

        $data = $params['class']::whereRaw('LENGTH(' . $params['field'] . ') = ?', $prefixSize)
            ->where($params['field'], 'ilike', $prefix . '%')->orderBy('id', 'DESC')
            ->first();

        if ($data == null) {
            $prefix .= sprintf('%04d', 1);
        } else {
            $repeat = true;
            $last = substr($data[$params['field']], -4);
            $last = ++$last;

            $new = sprintf('%04d', $last);
            while ($repeat)
            {
                $data = $params['class']::where($params['field'], $prefix . $new)->first();

                if ($data == null) {
                    $repeat = false;
                    $prefix .= sprintf('%04d', $new);
                } else {
                    $new = sprintf('%04d', ++$new);
                }
            }
        }

        return $prefix;
    }

    private static function generateNumberIncrement($params)
    {
        $prefix = $params['prefix'];
        $field = $params['field'];
        $model = $params['class'];
        $padLength = $params['pad'] ?? 5;

        $repeat = true;
        $increment = 1;

        $last = $model::where($field, 'like', $prefix . '%')
            ->orderByRaw('LENGTH(' . $field . ') DESC')
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int)substr($last[$field], strlen($prefix));
            $increment = $lastNumber + 1;
        }

        while ($repeat) {
            $number = $prefix . str_pad($increment, $padLength, '0', STR_PAD_LEFT);

            $exists = $model::where($field, $number)->exists();
            if (!$exists) {
                $repeat = false;
            } else {
                $increment++;
            }
        }

        return $number;
    }

    public static function generateContactCode($name)
    {
        $name = trim($name);
        if (empty($name)) {
            $prefix = 'X';
        } else {
            $words = preg_split('/\s+/', $name);
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper(mb_substr($word, 0, 1));
                }
            }
            $prefix = $initials ?: 'X';
        }

        $fullPrefix = $prefix . '-';
        $padLength = 4;

        $last = \App\Models\Companies\v1\Contacts::where('code', 'like', $fullPrefix . '%')
            ->orderByRaw('LENGTH(code) DESC')
            ->orderBy('id', 'desc')
            ->first();

        $increment = 1;
        if ($last) {
            $lastNumber = (int) substr($last->code, strlen($fullPrefix));
            $increment = $lastNumber + 1;
        }

        $repeat = true;
        while ($repeat) {
            $code = $fullPrefix . str_pad($increment, $padLength, '0', STR_PAD_LEFT);
            $exists = \App\Models\Companies\v1\Contacts::where('code', $code)->exists();
            if (!$exists) {
                $repeat = false;
            } else {
                $increment++;
            }
        }

        return $code;
    }
}
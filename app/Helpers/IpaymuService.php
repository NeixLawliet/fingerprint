<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Mail;

class IpaymuService
{
    protected $url;
    protected $apiKey;
    protected $vaNumber;

    public function __construct()
    {
        $this->url = 'https://sandbox.ipaymu.com/api/v2';
        $this->apiKey = 'SANDBOX61789FD4-914A-43EA-9117-123D526EC2BB-20220721160358';
        $this->vaNumber = '0000008980839888';
    }

    private function generateSignature($body, $method)
    {
        $json_body = json_encode($body, JSON_UNESCAPED_SLASHES);
        $request_body = strtolower(hash('sha256', $json_body));
        $string_to_sign = strtoupper($method) . ':' . $this->vaNumber . ':' . $request_body . ':' . $this->apiKey;
        $signature = hash_hmac('sha256', $string_to_sign, $this->apiKey);

        return $signature;
    }

    public function getPaymentMethod()
    {
        $timestamp = Date('YmdHis');

        $ch = curl_init($this->url.'/payment-method-list');

        $headers = array(
            'Content-Type: application/json',
            'va: ' . $this->vaNumber,
            'signature: ' . $this->generateSignature('', 'POST'),
            'timestamp: ' . $timestamp
        );

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $err = curl_error($ch);
        $response = curl_exec($ch);
        curl_close($ch);

        if($err) {
            dd($err);
        } else {
            return json_decode($response, true);
        }
    }

    public function sendPaymentInquiry($params)
    {
        //Request Body//
        // $body['product']    = array('headset', 'softcase');
        // $body['qty']        = array('1', '3');
        // $body['price']      = array('100000', '20000');
        // $body['returnUrl']  = 'https://mywebsite.com/thankyou';
        // $body['cancelUrl']  = 'https://mywebsite.com/cancel';
        // $body['notifyUrl']  = 'https://mywebsite.com/notify';
        //End Request Body//

        //Generate Signature
        // *Don't change this
        $json_body = json_encode($params, JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($params, 'POST');
        $timestamp = Date('YmdHis');
        //End Generate Signature

        $ch = curl_init($this->url.'/payment/direct');

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'va: ' . $this->vaNumber,
            'signature: ' . $signature,
            'timestamp: ' . $timestamp
        );

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $err = curl_error($ch);
        $response = curl_exec($ch);
        curl_close($ch);

        if($err) {
            dd($err);
        } else {
            return json_decode($response, true);
        }
    }

    public function paymentCallback($params, $header_signature)
    {
        DB::beginTransaction();

        $signature = hash_hmac('sha256', json_encode($params), $this->privateKey);

        if ($signature == $header_signature) {

        }

        return response()->json([
            'status' => 'error',
            'message' => 'Signature does not match!',
            'data' => null
        ]);
    }
}
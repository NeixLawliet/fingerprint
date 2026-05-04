<?php

namespace App\Helpers;

class DuitkuService
{
    protected $url;
    protected $merchantCode;
    protected $merchantKey;

    public function __construct()
    {
        // $this->url = 'https://sandbox.duitku.com';
        // $this->merchantCode = 'DS13073';
        // $this->merchantKey = '83f771d308f9a0234d97cd96df6decf6';
        $this->url = 'https://passport.duitku.com';
        $this->merchantCode = 'D9122';
        $this->merchantKey = '4c72847116265e6745572f1d02008d11';
    }

    public function getPaymentMethod($total)
    {
        $datetime = date('Y-m-d H:i:s');  

        $signature = hash('sha256',$this->merchantCode . (string)$total . $datetime . $this->merchantKey);

        $itemsParam = [
            'merchantcode' => $this->merchantCode,
            'amount' => (string)$total,
            'datetime' => $datetime,
            'signature' => $signature
        ];

        $params_string = json_encode($itemsParam);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            // CURLOPT_URL => 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod',
            CURLOPT_URL => $this->url.'/webapi/api/merchant/paymentmethod/getpaymentmethod',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $params_string,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode == 200) {
            return [
                'status' => 'success',
                'data' => json_decode($response, true),
                'message' => 'Success retrieve payment list'
            ];
        } else {
            return [
                'status' => 'error',
                'data' => 'Server Error' . $response,
                'message' => 'Server Error' . $response
            ];
        }
    }

    public function sendPaymentInquiry($params)
    {
        $paymentAmount = $params['total'];
        $paymentMethod = $params['payment_method']; // VC = Credit Card
        $merchantOrderId = $params['invoice_number']; // from merchant, unique
        $productDetails = 'SiMandor';
        $email = $params['email']; // your customer email
        $phoneNumber = $params['phone']; // your customer phone number (optional)
        $additionalParam = ''; // optional
        $merchantUserInfo = ''; // optional
        $customerVaName = $params['name']; // display name on bank confirmation display
        $callbackUrl = 'https://app.simandor.id/api/duitku_callback'; // url for callback
        $returnUrl = 'https://app.simandor.id/choose-company'; // url for redirect
        $expiryPeriod = $params['expiry_in_minutes']; // set the expired time in minutes
        $signature = md5($this->merchantCode . $merchantOrderId . $paymentAmount . $this->merchantKey);

        // Customer Detail
        $firstName = $params['name'];
        $lastName = "";

        // Address
        $alamat = $params['address'] ?? '';
        $city = "Jakarta";
        $postalCode = "";
        $countryCode = "ID";

        $address = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => $alamat,
            'city' => $city,
            'postalCode' => $postalCode,
            'phone' => $phoneNumber,
            'countryCode' => $countryCode
        ];

        $customerDetail = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'billingAddress' => $address,
            'shippingAddress' => $address
        ];

        $itemDetails = $params['items'];

        $params = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'additionalParam' => $additionalParam,
            'merchantUserInfo' => $merchantUserInfo,
            'customerVaName' => $customerVaName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'itemDetails' => $itemDetails,
            'customerDetail' => $customerDetail,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => $expiryPeriod
        ];

        $params_string = json_encode($params);
        //echo $params_string;
        // $url = 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry'; // Sandbox
        $url = $this->url.'/webapi/api/merchant/v2/inquiry'; // Production
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($params_string))                                                                       
        );   
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        //execute post
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            return [
                'status' => 'success',
                'data' => json_decode($response, true),
                'message' => 'Success retrieve payment list'
            ];
        } else {
            return [
                'status' => 'error',
                'data' => json_decode($response, true),
                'message' => 'Server Error' . $httpCode
            ];
        }
    }
}
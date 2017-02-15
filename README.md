# Avaya PHP Wrapper

## 1. Overview

This is a SoapClient wrapper for use with Avaya AE Webservices.

Courtesy of the Tow.com.au team :) - https://www.tow.com.au

## 2. Example

```

$wsdl = "https://my-avaya-server-ip:443/axis/services/TelephonyService?wsdl";

// get these details from avaya
$username = 'myavayauser@switchName';
$password = 'myavayapassword';

$avaya = new AvayaWrapper($wsdl, $username, $password);

// your local extension
$my_extension = 200;
// another local extension
$destination_extension = 201;
// 0 to dial out, then phone number in 04 format
$outside_number = '00412345678';

$call_response = $avaya->request('makeCall', [
	'originatingExtension' => $my_extension,
	'originatingNumber' => $destination_extension,
]);

```

## 3. TODO

Composer package
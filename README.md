# Avaya PHP Wrapper

## 1. Overview

This is a PHP wrapper for use with Avaya AE Webservices. Consists 2 classes:

AvayaSoap.php - Simple SOAP wrapper

AvayaSocket - More complicated wrapper using TCP connection

Courtesy of the Tow.com.au team :) - https://www.tow.com.au

## 2. SOAP Example

```
$wsdl = "https://my-avaya-server-ip:443/axis/services/TelephonyService?wsdl";

// SOAP login details (you might need an account made just for SOAP)
$username = 'myavayauser@switchName';
$password = 'myavayapassword';

$avaya = new AvayaSOAP($wsdl, $username, $password);

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

## 3. Socket Example

```
$host = 'my-avaya-server-ip';
$port = 4171;
$switchName = 'MySwitch';

// SOCKET login details (might be different to SOAP, does not include switchName)
$username = 'myavayauser';
$password = 'myavayapassword';

$avaya = new AvayaSocket($host, $port, $username, $password, $switchName);
$avaya->start(); // start application session

// your local extension
$my_extension = 200;
// another local extension
$destination_extension = 201;

$callID = $avaya->makeCall(my_extension, $destination_extension);

sleep(1);

$avaya->holdCall($my_extension, callID);

sleep(5);

$avaya->retrieveCall($my_extension, callID);

sleep(5);

$avaya->generateDigits($my_extension, callID, '1');

sleep(2);

$avaya->clearCall($my_extension, callID);

```

## 4. TODO

Composer package
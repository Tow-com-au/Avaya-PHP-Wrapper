<?php

class AvayaSoap {

	private $soap, $sessionID = false;

	public function __construct($wsdl, $username, $password) {
		$options = [
			'uri'=>'http://schemas.xmlsoap.org/soap/envelope/',
			'connection_timeout'=>15,
			'trace'=>true,
			'encoding'=>'UTF-8',
			'exceptions'=>true,
		    'login' => $username,
		    'password' => $password
		];
		$this->soap = new SoapClient($wsdl, $options);
	}

	public function start() {
		try {
			$this->soap->attach();
		} catch (Exception $e) {
			return false; // failed to start session
		}
		$xml = simplexml_load_string($this->soap->__getLastResponse());
		foreach ($xml->children('soapenv', true)->Header->children('ns1', true)->sessionID as $sid) {
			$this->sessionID = $sid;
		}
		$header = new SoapHeader('http://xml.avaya.com/ws/session', 'sessionID', $this->sessionID);
		$this->soap->__setSoapHeaders($header);
		return true; // session is active
	}

	public function request($method, $params) {
		if (!$this->sessionID and !$this->start()) return false; // failed to start session
		return $this->soap->$method($params);
	}

}
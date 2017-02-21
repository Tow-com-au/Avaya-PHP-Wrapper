<?php

class AvayaSocket {

	private $sessionID = false, $socket = false;
	private $host, $port, $username, $password, $timeout, $invoke_id, $switchName;
	private $protocol_version, $xmlns_xsi, $xmlns_xsd, $xmlns;

	public function __construct($host, $port, $username, $password, $switchName = '', $timeout = 180) {
		$this->protocol_version = 'http://www.ecma-international.org/standards/ecma-323/csta/ed3/priv2';
		$this->xmlns_xsi = "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"";
		$this->xmlns_xsd = "xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"";
		$this->xmlns = "xmlns=\"http://www.ecma-international.org/standards/ecma-323/csta/ed3\"";
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->timeout = $timeout;
		$this->invoke_id = '0001';
		$this->switchName = $switchName;
	}

	public function getInvokeId() {
		$invoke_id = $this->invoke_id;
		$this->invoke_id = str_pad(((int)$this->invoke_id)+1, 4, "0", STR_PAD_LEFT);
		return $invoke_id;
	}

	public function start($session_duration = 180) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<StartApplicationSession " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		                "<applicationInfo>" .
		                    "<applicationID>RPTC</applicationID>" .
		                    "<applicationSpecificInfo>" .
		                        "<ns1:SessionLoginInfo xmlns=\"http://www.avaya.com/csta\">" .
		                            "<ns1:userName>" . $this->username . "</ns1:userName>" .
		                            "<ns1:password>" . $this->password . "</ns1:password>" .
		                            "<ns1:sessionCleanupDelay>60</ns1:sessionCleanupDelay>" .
		                        "</ns1:SessionLoginInfo>" .
		                    "</applicationSpecificInfo>" .
		                "</applicationInfo>" .
		                "<requestedProtocolVersions>" .
		                "<protocolVersion>" . $this->protocol_version . "</protocolVersion>" .
		                "</requestedProtocolVersions>" .
		                "<requestedSessionDuration>".$session_duration."</requestedSessionDuration>" .
		            "</StartApplicationSession>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			$this->sessionID = $xml->sessionID;
			return true;
		}
		else {
			return false;
		}
	}

	public function keepAlive($session_duration = 180) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<ResetApplicationSessionTimer " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		                "<sessionID>" . $this->sessionID . "</sessionID>" .
		                "<requestedSessionDuration>" . $session_duration . "</requestedSessionDuration>" .
		            "</ResetApplicationSessionTimer>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			$this->sessionID = $xml->sessionID;
			return true;
		}
		else {
			return false;
		}
	}

	public function stop() {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<StopApplicationSession " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		                "<sessionID>" . $this->sessionID . "</sessionID>" .
		                "</requestedProtocolVersions>" .
		                "<sessionEndReason>" .
		                	"<appEndReason>Application Request</appEndReason>" .
		                "</sessionEndReason>" .
		            "</StopApplicationSession>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			$this->sessionID = false;
			$this->closeSocket();
			return true;
		}
		else {
			return false;
		}
	}

	public function snapshotDevice($agent) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<SnapshotDevice " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		                "<snapshotObject typeOfNumber=\"other\" mediaClass=\"notKnown\">" . $agent . ':' . $this->switchName . "::0</snapshotObject>" .
		            "</SnapshotDevice>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			if (isset($xml->crossRefIDorSnapshotData->snapshotData->snapshotDeviceResponseInfo->connectionIdentifier->callID)) {
				return $xml->crossRefIDorSnapshotData->snapshotData->snapshotDeviceResponseInfo->connectionIdentifier->callID;
			}
			return false;
			//return $xml->crossRefIDorSnapshotData->snapshotData;
		}
		else {
			return false;
		}
	}

	public function makeCall($agent, $destNo) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<MakeCall " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		                "<callingDevice typeOfNumber=\"other\" mediaClass=\"notKnown\">" . $agent . ':' . $this->switchName . "::0</callingDevice>" .
		           		"<calledDirectoryNumber typeOfNumber=\"other\" mediaClass=\"notKnown\">" . $destNo . ':' . $this->switchName . "::0</calledDirectoryNumber>" .
		            	"<userData>" .
		            		"<string></string>" .
		            	"</userData>" .
		            	"<callCharacteristics>" .
		            		"<priorityCall>false</priorityCall>" .
		            	"</callCharacteristics>" .
		            "</MakeCall>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			return $xml->callingDevice->callID;
		}
		else {
			return false;
		}
	}

	public function holdCall($agent, $callID) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<HoldCall " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		            	"<callToBeHeld>" .
		            		"<deviceID typeOfNumber=\"other\" mediaClass=\"notKnown\">".$agent.":"+$this->switchName+"::0</deviceID>" .
		            		"<callID>".$callID."</callID>" .
		            	"</callToBeHeld>" .
		            "</HoldCall>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			if (isset($xml->unspecified)) return false; // unspecified error
			return true;
		}
		else {
			return false;
		}
	}

	public function clearCall($agent, $callID) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<ClearCall " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		            	"<callToBeCleared>" .
		            		"<deviceID typeOfNumber=\"other\" mediaClass=\"notKnown\">".$agent.":"+$this->switchName+"::0</deviceID>" .
		            		"<callID>".$callID."</callID>" .
		            	"</callToBeCleared>" .
		            	"<userData>" .
		            		"<string></string>" .
		            	"</userData>" .
		                "<reason>Application Request</reason>" .
		            "</ClearCall>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			if (isset($xml->unspecified)) return false; // unspecified error
			return true;
		}
		else {
			return false;
		}
	}

	public function GetDisplay($agent) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<GetDisplay " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		            	"<deviceID typeOfNumber=\"other\" mediaClass=\"notKnown\">".$agent.":".$this->switchName."::0</deviceID>" .
		           		// "<DisplayID>0</DisplayID>".
		            "</GetDisplay>";

		$xml = $this->sendXml($request_xml, true);
		if ($xml) {
			if (isset($xml->unspecified)) return false; // unspecified error
			return true;
		}
		else {
			return false;
		}
	}

	public function getButtonInformation($agent) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<GetButtonInformation " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		            	"<deviceID typeOfNumber=\"other\" mediaClass=\"notKnown\">".$agent.":".$this->switchName."::0</deviceID>" .
		           		// "<buttonID>0</buttonID>".
		            "</GetButtonInformation>";

		$xml = $this->sendXml($request_xml, true);
		if ($xml) {
			if (isset($xml->unspecified)) return false; // unspecified error
			return true;
		}
		else {
			return false;
		}
	}

	public function pressButton($agent, $callID, $button) {
		$request_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
		            "<ButtonPress " . $this->xmlns_xsi ."   " . $this->xmlns_xsd ." xmlns=\"http://www.ecma-international.org/standards/ecma-354/appl_session\">" .
		            	"<deviceID typeOfNumber=\"other\" mediaClass=\"notKnown\">".$agent.":".$this->switchName."::0</deviceID>" .
		            	"<button>".$button."</button>".
		            "</ButtonPress>";

		$xml = $this->sendXml($request_xml);
		if ($xml) {
			if (isset($xml->unspecified)) return false; // unspecified error
			return true;
		}
		else {
			return false;
		}
	}

	public function getSocket() {
		if (!$this->socket) {
			$this->socket = socket_create(AF_INET,SOCK_STREAM,0) or die("Unable to create a socket");
			socket_connect($this->socket, $this->host, $this->port) or die("Unable to connect to the socket");
		}
		return $this->socket;
	}

	public function closeSocket() {
		if ($this->socket) {
			socket_close($this->socket);
			$this->socket = false;
		}
	}

	/*
	 * For header information refer to: 
	 *      http://www.ecma-international.org/flat/publications/files/ECMA-ST/ECMA-323.pdf
	 * 
	 * | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 |   
	 *  VERSION|LENGTH |   INVOKE ID   |   XML PAYLOAD
	 * 
	 * VERSION: 2 bytes (we hard-code to '00' aka XML without SOAP)
	 * LENGTH: 2 bytes (big-endian packed, that's how we get it down to 2 bytes)
	 * INVOKE ID: 4 bytes (we define this ourselves, from '0001' to '9999')
	 */
	public function sendXml($request_xml, $show_xml = false) {
		$socket = $this->getSocket();

		$xml_header_len = 8;
		$msg = $this->getInvokeId().$request_xml;
		$total_len = strlen($request_xml) + $xml_header_len;

		socket_write($socket, '00', 2);
		$n_o_total_len = pack('n', $total_len);
		socket_write($socket, $n_o_total_len, strlen($n_o_total_len));
		socket_write($socket, $msg, strlen($msg));

		usleep(150000);

		$read = socket_read($socket, 1024);
		
		$response_xml = substr($read, 8); // lazy load, just ignoring headers
		if ($show_xml) {
			echo $response_xml.PHP_EOL;
		}

		$xml = simplexml_load_string($response_xml);
		return $xml;
	}

}
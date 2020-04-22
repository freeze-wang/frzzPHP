<?php
class Plus_HttpQueue {

	private $queueName;
	private $host;
	private $port;
	private $auth;
	private $charset;

	private $socket; //persist connection

	function __construct($queueName, $host = '127.0.0.1', $port = 1201, $auth = '', $charset = 'utf-8') {
		$this->queueName = $queueName;
		$this->host = $host;
		$this->port = $port;
		$this->auth = $auth;
		$this->charset = $charset;
	}

	function __destruct() {
		if ($this->socket)
			fclose($this->socket);
	}


	public function get() {

		$result = $this->http_get("/?auth=" . $this->auth . "&charset=" . $this->charset . "&name=" . $this->queueName . "&opt=get");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
		if ($result["data"] == "HTTPSQS_GET_END") {
			return null;
		} else {
			return unserialize($result["data"]);
		}

	}

	public function put($obj) {

		$queue_data = serialize($obj);
		$result = $this->http_post("/?auth=" . $this->auth . "&charset=" . $this->charset . "&name=" . $this->queueName . "&opt=put", $queue_data);
		if ($result["data"] == "HTTPSQS_PUT_OK") {
			return true;
		} else if ($result["data"] == "HTTPSQS_PUT_END") {
			return $result["data"];
		}
		return false;

	}

	public function reset() {

		//http://host:port/?name=your_queue_name&opt=reset&auth=mypass123
		$result = $this->http_get("/?auth=" . $this->auth . "&name=" . $this->queueName . "&opt=reset");
		if (is_array($result) && array_key_exists('data', $result) && $result["data"] == "HTTPSQS_RESET_OK") {
			return true;
		}

		return false;

	}

	public function status() {

		$result = $this->http_get("/?auth=" . $this->auth . "&charset=" . $this->charset . "&name=" . $this->queueName . "&opt=status_json");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
		return json_decode($result["data"], true);

	}

	private function http_get($query) {

		if (!$this->socket || feof($this->socket)) {
			$this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 5);
			if (!$this->socket) {
				return false;
			}
		}

		$out = "GET ${query} HTTP/1.1\r\n";
		$out .= "Host: ${host}\r\n";
		$out .= "\r\n";
		fwrite($this->socket, $out);
		$line = trim(fgets($this->socket));
		$header .= $line;
		list($proto, $rcode, $result) = explode(" ", $line);
		$len = -1;
		while (($line = trim(fgets($this->socket))) != "") {
			$header .= $line;
			if (strstr($line, "Content-Length:")) {
				list($cl, $len) = explode(" ", $line);
			}
			if (strstr($line, "Pos:")) {
				list($pos_key, $pos_value) = explode(" ", $line);
			}
			if (strstr($line, "Connection: close")) {
				$close = true;
			}
		}
		if ($len < 0) {
			return false;
		}

		$body = fread($this->socket, $len);
		$fread_times = 0;
		while (strlen($body) < $len) {
			$body1 = fread($this->socket, $len);
			$body .= $body1;
			unset($body1);
			if ($fread_times > 100) {
				break;
			}
			$fread_times++;
		}
		if ($close) {
			fclose($this->socket);
		}
		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
		return $result_array;

	}

	public function http_post($query, $body) {

		if (!$this->socket || feof($this->socket)) {
			$this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 1);
			if (!$this->socket) {
				return false;
			}
		}

		$out = "POST ${query} HTTP/1.1\r\n";
		$out .= "Host: ${host}\r\n";
		$out .= "Content-Length: " . strlen($body) . "\r\n";
		$out .= "\r\n";
		$out .= $body;
		fwrite($this->socket, $out);
		$line = trim(fgets($this->socket));
		$header .= $line;
		list($proto, $rcode, $result) = explode(" ", $line);
		$len = -1;
		while (($line = trim(fgets($this->socket))) != "") {
			$header .= $line;
			if (strstr($line, "Content-Length:")) {
				list($cl, $len) = explode(" ", $line);
			}
			if (strstr($line, "Pos:")) {
				list($pos_key, $pos_value) = explode(" ", $line);
			}
			if (strstr($line, "Connection: close")) {
				$close = true;
			}
		}
		if ($len < 0) {
			return false;
		}

		$body = @fread($this->socket, $len);
		if ($close) {
			fclose($this->socket);
		}

		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
		return $result_array;

	}

}
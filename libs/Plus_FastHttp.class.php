<?php
class Plus_FastHttp {

	private $url;
	private $proxyIp;
	private $proxyPort;
	private $timeOut;
	private $transferTimeOut;

	public function __construct($url, $proxyIp = '', $proxyPort = 0, $timeOut = 3, $transferTimeOut = 600) {

		//URL地址
		if (!$url) throw new Exception("必须指定URL地址！");
		$this->url = $url;
		$this->timeOut = $timeOut;
		$this->transferTimeOut = $transferTimeOut;

		//代理服务器I的设置
		if ($proxyIp) {
			$this->proxyIp = $proxyIp;
			$this->proxyPort = $proxyPort ? $proxyPort : 80;
		}

	}

	public function setProxy($ip, $port = 80) {
		if ($ip) {
			$this->proxyIp = $ip;
			$this->proxyPort = $port;
		}
	}

	public function GET($params = null, $headers = null) {

		//组合带参数的URL
		$url = $this->url;

		$urlInfo = parse_url($url);
		$ssl = array_key_exists('scheme', $urlInfo) ? ($urlInfo['scheme'] == 'https') : false;
		$host = $urlInfo['host'];
		$port = array_key_exists('port', $urlInfo) ? $urlInfo['port'] : ($ssl ? 443 : 80);
		if ($this->proxyIp && $this->proxyPort) {
			$proxy = "http://{$this->proxyIp}:{$this->proxyPort}";
			$proxyInfo = parse_url($proxy);
			$host = $proxyInfo['host'];
			$port = $proxyInfo['port'];
		}

		$query = array_key_exists('query', $urlInfo) ? $urlInfo['query'] : false;
		if ($params && is_array($params)) {
			$query .= ($query ? '&' : '') . http_build_query($params);
		}
		$requstHeaders = array(
			'Host: ' . $urlInfo['host'],
			'Content-Length: 0'
		);

		if ($headers && is_array($headers))
			$requstHeaders = array_merge($requstHeaders, $headers);

		$requestURI = (array_key_exists('path', $urlInfo) ? $urlInfo['path'] : '/') . '?' . $query;
		$socket = fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $errno, $errstr, $this->timeOut);
        $response = '';
		if ($errno == 0 && is_resource($socket)) {

			$request = "GET {$requestURI} HTTP/1.1\r\n";
			foreach ($requstHeaders as $header) {
				$request .= $header . "\r\n";
			}
			$request .= "\r\n";

			fwrite($socket, $request);

			$contentLength = 0;
			$redirectUrl = '';
			$chunked = false;

			$line = trim(fgets($socket));
			if ($line) {
				list($protocol, $responseCode, $responseText) = explode(" ", $line);
				while (($line = trim(fgets($socket))) != "") {
					if (strpos($line, "Content-Length:") === 0) {
						list($cl, $contentLength) = explode(" ", $line);
					}
					if (strpos($line, "Transfer-Encoding:") === 0) {
						$chunked = strstr($line, "chunked");
					}
					if (strpos($line, "Location:") === 0) {
						list($lc, $redirectUrl) = explode(" ", $line);
					}
				}
				if ($contentLength > 0) {
					$response = stream_get_contents($socket, intval($contentLength));
				} else if ($chunked) {
					$length = hexdec(fgets($socket));
					while ($length > 0) {
						$response .= stream_get_contents($socket, $length);
						fgets($socket); // skip the \r\n of data block end
						$length = hexdec(fgets($socket));
					}
				}
			}
			fclose($socket);

			if ($redirectUrl) {
				$this->url = $redirectUrl;
				return $this->GET(null, $headers);
			}

		}
		return $response;

	}

	public function POST($params = null, $headers = false) {

		//组合带参数的URL
		$url = $this->url;

		$urlInfo = parse_url($url);
		$ssl = array_key_exists('scheme', $urlInfo) ? ($urlInfo['scheme'] == 'https') : false;
		$host = $urlInfo['host'];
		$port = array_key_exists('port', $urlInfo) ? $urlInfo['port'] : ($ssl ? 443 : 80);
		if ($this->proxyIp && $this->proxyPort) {
			$proxy = "http://{$this->proxyIp}:{$this->proxyPort}";
			$proxyInfo = parse_url($proxy);
			$host = $proxyInfo['host'];
			$port = $proxyInfo['port'];
		}

		$query = array_key_exists('query', $urlInfo) ? $urlInfo['query'] : false;

		$postFields = '';
		if ($params && is_array($params)) {
			$postFields = http_build_query($params);
		}
		$requstHeaders = array(
			'Host: ' . $urlInfo['host'],
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: ' . strlen($postFields)
		);

		if ($headers && is_array($headers))
			$requstHeaders = array_merge($requstHeaders, $headers);

		$requestURI = (array_key_exists('path', $urlInfo) ? $urlInfo['path'] : '/') . ($query ? ('?' . $query) : '');

		$contentLength = 0;
		$response = '';
		$redirectUrl = '';
		$chunked = false;

		$socket = fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $errno, $errstr, $this->timeOut);
		if ($errno == 0) {

			$request = "POST {$requestURI} HTTP/1.1\r\n";
			foreach ($requstHeaders as $header) {
				$request .= $header . "\r\n";
			}
			$request .= "\r\n";

			if ($postFields)
				$request .= $postFields;

			fwrite($socket, $request);

			$line = trim(fgets($socket));
			if ($line) {
				list($protocol, $responseCode, $responseText) = explode(" ", $line);
				while (($line = trim(fgets($socket))) != "") {
					if (strpos($line, "Content-Length:") === 0) {
						list($cl, $contentLength) = explode(" ", $line);
					}
					if (strpos($line, "Transfer-Encoding:") === 0) {
						$chunked = strstr($line, "chunked");
					}
					if (strpos($line, "Location:") === 0) {
						list($lc, $redirectUrl) = explode(" ", $line);
					}
				}
				if ($contentLength > 0) {
					$response = stream_get_contents($socket, intval($contentLength));
				} else if ($chunked) {
					$length = hexdec(fgets($socket));
					while ($length > 0) {
						$response .= stream_get_contents($socket, $length);
						fgets($socket); // skip the \r\n of data block end
						$length = hexdec(fgets($socket));
					}
				}
			}
			fclose($socket);

			if ($redirectUrl) {
				$this->url = $redirectUrl;
				return $this->GET(null, $headers);
			}

		}
		return $response;

	}


	public function downloadFile($params = null, $headers = null, $dir = "./") {

		//组合带参数的URL
		$url = $this->url;
		$pos = strrpos($url, '/');
		$fileName = substr($url, $pos + 1, strlen($url) - $pos);

		$urlInfo = parse_url($url);
		$ssl = array_key_exists('scheme', $urlInfo) ? ($urlInfo['scheme'] == 'https') : false;
		$host = $urlInfo['host'];
		$port = array_key_exists('port', $urlInfo) ? $urlInfo['port'] : ($ssl ? 443 : 80);
		if ($this->proxyIp && $this->proxyPort) {
			$proxy = "http://{$this->proxyIp}:{$this->proxyPort}";
			$proxyInfo = parse_url($proxy);
			$host = $proxyInfo['host'];
			$port = $proxyInfo['port'];
		}

		$query = array_key_exists('query', $urlInfo) ? $urlInfo['query'] : false;
		if ($params && is_array($params)) {
			$query .= ($query ? '&' : '') . http_build_query($params);
		}
		$requstHeaders = array(
			'Host: ' . $urlInfo['host'],
			'Content-Length: 0'
		);

		if ($headers && is_array($headers))
			$requstHeaders = array_merge($requstHeaders, $headers);

		$requestURI = (array_key_exists('path', $urlInfo) ? $urlInfo['path'] : '/') . '?' . $query;
		$socket = fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $errno, $errstr, $this->timeOut);
		if ($errno == 0) {

			$request = "GET {$requestURI} HTTP/1.1\r\n";
			foreach ($requstHeaders as $header) {
				$request .= $header . "\r\n";
			}
			$request .= "\r\n";

			fwrite($socket, $request);

			$contentLength = 0;
			$redirectUrl = '';
			$response = '';
			$chunked = false;

			$line = trim(fgets($socket));
			if ($line) {
				list($protocol, $responseCode, $responseText) = explode(" ", $line);
				while (($line = trim(fgets($socket))) != "") {
					if (strpos($line, "Content-Length:") === 0) {
						list($cl, $contentLength) = explode(" ", $line);
					}
					if (strpos($line, "Transfer-Encoding:") === 0) {
						$chunked = strstr($line, "chunked");
					}
					if (strpos($line, "Location:") === 0) {
						list($lc, $redirectUrl) = explode(" ", $line);
					}
				}
				if ($contentLength > 0) {
					$response = stream_get_contents($socket, intval($contentLength));
				} else if ($chunked) {
					$length = hexdec(fgets($socket));
					while ($length > 0) {
						$response .= stream_get_contents($socket, $length);
						fgets($socket); // skip the \r\n of data block end
						$length = hexdec(fgets($socket));
					}
				}
			}
			fclose($socket);

			if ($redirectUrl) {
				$this->url = $redirectUrl;
				return $this->downloadFile(null, $headers, $dir);
			}

		}

		$downloadFileName = $dir . $fileName;
		file_put_contents($downloadFileName, $response, FILE_BINARY | LOCK_EX);

		return $downloadFileName;

	}
}
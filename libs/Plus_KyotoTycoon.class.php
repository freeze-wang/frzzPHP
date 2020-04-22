<?php
if (!defined('CHAR_EOL')) define('CHAR_EOL', "\n");
if (!defined('CHAR_TAB')) define('CHAR_TAB', "\t");
class Plus_KyotoTycoon {

	//服务器主机
	protected $hostAddr;
	//数据库名
	protected $db;
	//是否长连接
	protected $persist;
	//curl资源
	protected $socket;

	/**
	 * 构造器
	 * @throws Exception 如果没有curl扩展则抛出异常
	 */
	public function __construct() {

		//检查PHP是否可调用stream_socket_client
		if (!is_callable("stream_socket_client"))
			throw new Exception("php5 needed!");

	}

	/**
	 * 解构器
	 */
	public function __destruct() {
		$this->close();
	}


	/**
	 * 连接到KTServer
	 * @param  string $host 主机
	 * @param  int $port 端口
	 * @param  string|null $db 数据库
	 * @param  bool $persist 是否长连接
	 * @return void
	 * @throws Exception
	 */
	public function connect($host = '127.0.0.1', $port = 1978, $db = null, $persist = true) {

		$this->hostAddr = sprintf("%s:%d", $host, $port);
		$this->db = $db;
		$this->persist = $persist;

		if ($this->persist) {
			$this->socket = stream_socket_client('tcp://' . $this->hostAddr, $errNo, $errStr, 5);
			if ($errNo != 0) throw new Exception($errStr);
		}

	}

	/**
	 * 关闭到KTServer的连接
	 * @return void
	 */
	public function close() {

		if ($this->persist && $this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}

	}

	/**
	 * 增加一个KEYVALUE对
	 * @param  string $key 键
	 * @param  mixed $value 值
	 * @param  int $expire 超时（秒）
	 * @return bool 成功返回true,当$key存在的时候返回false
	 */
	public function add($key, $value, $expire = 0) {

		$url = $this->makeUrl('add');
		$data = array('key' => $key, 'value' => $value);
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 替换一个KEYVALUE对
	 * @param  string $key 键
	 * @param  mixed $value 值
	 * @param  int $expire 超时（秒）
	 * @return bool 成功返回true,当$key不存在的时候返回false
	 */
	public function replace($key, $value, $expire = 0) {

		$url = $this->makeUrl('replace');
		$data = array('key' => $key, 'value' => $value);
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 向一个KEY的VALUE增加内容
	 * @param  string $key 键
	 * @param  mixed $value 值
	 * @param  int $expire 超时（秒）
	 * @return bool 成功返回true,当$key不存在的时候返回false
	 */
	public function append($key, $value, $expire = 0) {

		$url = $this->makeUrl('append');
		$data = array('key' => $key, 'value' => $value);
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 增加一个KEY的值（整型)
	 * @param  string $key 键
	 * @param  int $num 增加的数量
	 * @param  int $orig 初始数量
	 * @param  int $expire 超时（秒）
	 * @return bool|int 成功返回增加后的数值，否则为false
	 */
	public function increment($key, $num, $orig = 0, $expire = 0) {

		$url = $this->makeUrl('increment');
		$data = array('key' => $key, 'num' => intval($num), 'orig' => intval($orig));
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		if ($response['SUCCESS']) {
			return intval($response['DATA']['num']);
		} else {
			return false;
		}

	}

	/**
	 * 增加一个KEY的值（双精度数)
	 * @param  string $key 键
	 * @param  double $num 增加的数量
	 * @param  double $orig 初始数量
	 * @param  double $expire 超时（秒）
	 * @return bool|double 成功返回增加后的数值，否则为false
	 */
	public function increment_double($key, $num, $orig = 0, $expire = 0) {

		$url = $this->makeUrl('increment_double');
		$data = array('key' => $key, 'num' => doubleval($num), 'orig' => doubleval($orig));
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		if ($response['SUCCESS']) {
			return doubleval($response['DATA']['num']);
		} else {
			return false;
		}

	}

	/**
	 * 设置一个KEYVALUE对
	 * @param  string $key 键
	 * @param  mixed $value 值
	 * @param  int $expire 超时（秒）
	 * @return bool 成功返回true,否则返回false
	 */
	public function set($key, $value, $expire = 0) {

		$url = $this->makeUrl('set');
		$data = array('key' => $key, 'value' => $value);
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 获取一个KEY的值
	 * @param  string $key 键
	 * @return bool 成功返回值，否则返回false
	 */
	public function get($key) {

		$url = $this->makeUrl('get');
		$data = array('key' => $key);
		$response = $this->sendRequest($url, $data);
		if ($response['SUCCESS']) {
			return $response['DATA']['value'];
		} else {
			return false;
		}

	}

	/**
	 * 获取一个KEY的值并删除
	 * @param  string $key 键
	 * @return bool 成功返回值，否则返回false
	 */
	public function seize($key) {

		$url = $this->makeUrl('seize');
		$data = array('key' => $key);
		$response = $this->sendRequest($url, $data);
		if ($response['SUCCESS']) {
			return $response['DATA']['value'];
		} else {
			return false;
		}

	}

	/**
	 * 删除一个KEY的值
	 * @param  string $key 键
	 * @return bool 成功返回值，否则返回false
	 */
	public function remove($key) {

		$url = $this->makeUrl('remove');
		$data = array('key' => $key);
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 扫描数据库，清除超时数据的空间
	 * @param  int $step 清除的步长，如果不指定，则扫描整个数据库
	 * @return bool 成功返回true，否则返回false
	 */
	public function vacuum($step = 0) {

		$url = $this->makeUrl('vacuum');
		$data = array('step' => $step);
		$response = $this->sendRequest($url, $data);
		return ($response['SUCCESS']);

	}

	/**
	 * 批量设置KEYVALUE对
	 * @param  array $bulk KEYVALUE数组
	 * @param  int $expire 超时时间（秒）
	 * @param  bool $atomic 是否原子操作
	 * @return int 操作成功的记录数量
	 */
	public function set_bulk($bulk, $expire = 0, $atomic = true) {

		$url = $this->makeUrl('set_bulk');
		$data = ($atomic) ? array('atomic' => '') : array();
		if ($expire) $data['xt'] = intval($expire);
		$response = $this->sendRequest($url, $data, $bulk);
		return intval($response['DATA']['num']);

	}

	/**
	 * 批量获取KEY的VALUE
	 * @param  array $bulk KEY数组
	 * @param  bool $atomic 是否原子操作
	 * @return array [0]-返回数量 [1]-KEYVALUE数组
	 */
	public function get_bulk($bulk, $atomic = true) {

		$url = $this->makeUrl('get_bulk');
		$data = ($atomic) ? array('atomic' => '') : array();
		$response = $this->sendRequest($url, $data, $bulk);
		return array(intval($response['DATA']['num']), $response['DATA']['BULK']);

	}

	/**
	 * 批量删除KEY
	 * @param  array $bulk KEY数组
	 * @param  bool $atomic 是否原子操作
	 * @return int 返回删除的数量
	 */
	public function remove_bulk($bulk, $atomic = true) {

		$url = $this->makeUrl('remove_bulk');
		$data = ($atomic) ? array('atomic' => '') : array();
		$response = $this->sendRequest($url, $data, $bulk);
		return intval($response['DATA']['num']);

	}

	/**
	 * 获取指定开头的键值
	 * @param  string $prefix 指定开头
	 * @param  int $max 返回最多的数量，不设置返回全部
	 * @return array [0]-KEY数量 [1]-KEY数组
	 */
	public function match_prefix($prefix, $max = -1) {

		$url = $this->makeUrl('match_prefix');
		$data = array('prefix' => $prefix, 'max' => $max);
		$response = $this->sendRequest($url, $data);
		return array(intval($response['DATA']['num']), $response['DATA']['KEYS']);

	}

	/**
	 * 获取符合正则表达式的键值
	 * @param  $regex 正则表达式
	 * @param  $max 返回最多的数量，不设置返回全部
	 * @return array [0]-KEY数量 [1]-KEY数组
	 */
	public function match_regex($regex, $max = -1) {

		$url = $this->makeUrl('match_regex');
		$data = array('regex' => $regex, 'max' => $max);
		$response = $this->sendRequest($url, $data);
		return array(intval($response['DATA']['num']), $response['DATA']['KEYS']);

	}

	/**
	 * 清空数据库（慎用）
	 * @return bool 成功返回true，否则返回false
	 */
	public function clear() {

		$url = $this->makeUrl('clear');
		$response = $this->sendRequest($url, null);
		return $response['SUCCESS'];

	}

	/**
	 * 把内存的数据同步到磁盘，执行相应的命令
	 * @param  bool $hard
	 * @param  string $command 执行的命令
	 * @return bool 成功返回true，否则返回false
	 */
	public function synchronize($hard = true, $command = '') {

		$url = $this->makeUrl('synchronize');
		$data = array();
		if ($hard) $data['hard'] = '';
		if ($command) $data['command'] = $command;
		$response = $this->sendRequest($url, $data);
		return $response['SUCCESS'];

	}

	/**
	 * 返回KT服务器报告
	 * @return array 报告数组
	 */
	public function report() {

		$url = $this->makeUrl('report');
		$response = $this->sendRequest($url, null);
		return $response['DATA'];

	}

	/**
	 * 返回数据库报告
	 * @return array 报告数组
	 */
	public function status() {

		$url = $this->makeUrl('status');
		$response = $this->sendRequest($url, array('DB' => $this->db));
		return $response['DATA'];

	}

	/**
	 * 根据命令生成请求的URL地址
	 * @param  string $cmd 命令
	 * @return string URL地址
	 */
	private function makeUrl($cmd) {
		return '/rpc/' . $cmd;
	}

	/**
	 * 发送请求到服务器
	 * @param  string $url URL地址
	 * @param  array|null $data 要发送的数据
	 * @param  array $bulk 大批量数据
	 * @return array 请求结果
	 */
	private function sendRequest($url, $data, $bulk = null) {

		//如果是短连接就初始化curl
		if (!($this->persist && !feof($this->socket))) {
			$this->socket = $this->socket = stream_socket_client('tcp://' . $this->hostAddr, $errNo, $errStr, 5);
			if ($errNo != 0) throw new Exception($errStr);
		}

		//设置是否长连接的HTTP头
		$headers = array();
		$headers[] = 'Host: ' . $this->hostAddr;
		$headers[] = 'Connection: ' . (($this->persist) ? 'Keep-Alive' : 'Close');

		//生成请求消息体
		$content = '';
		if ($data && is_array($data)) {
			//指定数据库名
			if ($this->db) $data['DB'] = $this->db;
			//编码数据
			foreach ($data as $key => $value) {
				if ($value) {
					$content .= base64_encode($key) . CHAR_TAB . base64_encode($value) . CHAR_EOL;
				} else {
					$content .= base64_encode($key) . CHAR_TAB . CHAR_EOL;
				}
			}
		}
		//如果是bulk请求
		if ($bulk && is_array($bulk)) {
			for ($i = 0; $i < count($bulk); $i++) {
				//如果只含有KEY，就只编码KEY
				if (is_scalar($bulk[$i])) {
					$content .= base64_encode('_' . $bulk[$i]) . CHAR_TAB . CHAR_EOL;
				} else if (is_array($bulk[$i])) {
					//如果是数据，则必须含有key字段
					if (!array_key_exists('key', $bulk[$i])) continue;
					$content .= base64_encode('_' . $bulk[$i]['key']) . CHAR_TAB .
						(array_key_exists('value', $bulk[$i]) ? base64_encode($bulk[$i]['value']) : '') . CHAR_EOL;
				}
			}
		}
		//计算请求消息体长度
		$contentLength = strlen($content);
		//设置请求消息体编码类型及长度的HTTP头
		$headers[] = 'Content-Type: text/tab-separated-values; colenc=B';
		$headers[] = 'Content-Length: ' . $contentLength;


		$request = "POST {$url} HTTP/1.1\r\n";
		foreach ($headers as $header) {
			$request .= $header . "\r\n";
		}
		$request .= "\r\n";
		if ($contentLength > 0) {
			$request .= $content;
		}

		//发送HTTP请求
		fwrite($this->socket, $request);

		$close = false;
		$responseCode = 0;
		$contentLength = 0;
		$responseContentType = '';

		$line = trim(fgets($this->socket));
		list($protocol, $responseCode, $responseText) = explode(" ", $line);
		while (($line = trim(fgets($this->socket))) != "") {
			if (strstr($line, "Content-Length:")) {
				list($cl, $contentLength) = explode(" ", $line);
			}
			if (strstr($line, "Content-Type:")) {
				$responseContentType = $line;
			}
			if (strstr($line, "Connection: Close")) {
				$close = true;
			}
		}
		//经测试, fread不能完全返回指定的长度，所以采用stream_get_contents
		$response = $contentLength > 0 ? stream_get_contents($this->socket, $contentLength) : '';

		//获取HTTP STATUS CODE
		$isBase64 = stripos($responseContentType, 'colenc=B') > 0;
		$isUrlEncoded = stripos($responseContentType, 'colenc=U') > 0;
		$isQuoted = stripos($responseContentType, 'colenc=Q') > 0;

		//初始化返回结果
		$result = array();
		if ($responseCode == 200) {
			//返回成功

			$result['SUCCESS'] = true;
			$result['DATA'] = array();
			//对结果进行解码
			$bodyLines = explode(CHAR_EOL, $response);
			for ($i = 0; $i < count($bodyLines); $i++) {
				$lineLength = strlen($bodyLines[$i]);
				if ($lineLength > 0) {
					$lineInfo = explode(CHAR_TAB, $bodyLines[$i]);
					if (count($lineInfo) == 2) {
						if ($lineInfo[0][0] == '_') {
							if ($isBase64) {
								$key = base64_decode(substr($lineInfo[0], 1, strlen($lineInfo[0]) - 1));
								$value = base64_decode($lineInfo[1]);
							} else if ($isUrlEncoded) {
								$key = urldecode(substr($lineInfo[0], 1, strlen($lineInfo[0]) - 1));
								$value = urldecode($lineInfo[1]);
							} else if ($isQuoted) {
								$key = quoted_printable_decode(substr($lineInfo[0], 1, strlen($lineInfo[0]) - 1));
								$value = quoted_printable_decode($lineInfo[1]);
							} else {
								$key = substr($lineInfo[0], 1, strlen($lineInfo[0]) - 1);
								$value = $lineInfo[1];
							}
							if ($value) {
								$result['DATA']['BULK'][$key] = $lineInfo[1];
							} else {
								$result['DATA']['KEYS'][] = $key;
							}
						} else {
							if ($isBase64) {
								$result['DATA'][base64_decode($lineInfo[0])] = base64_decode($lineInfo[1]);
							} else if ($isUrlEncoded) {
								$result['DATA'][urldecode($lineInfo[0])] = urldecode($lineInfo[1]);
							} else if ($isQuoted) {
								$result['DATA'][quoted_printable_decode($lineInfo[0])] = quoted_printable_decode($lineInfo[1]);
							} else {
								$result['DATA'][$lineInfo[0]] = $lineInfo[1];
							}
						}
					}
				}
			}

		} else if ($responseCode == 400) {

			//无效的请求或请求参数错误
			$result['SUCCESS'] = false;
			$result['ERROR'] = 'the format of the request was invalid or the arguments are short for the called procedure.';

		} else if ($responseCode == 450) {

			//找不到结果
			$result['SUCCESS'] = true;
			$result['DATA'] = null;

		} else if ($responseCode == 500) {

			//服务器内部错误，请求终止
			$result['SUCCESS'] = false;
			$result['ERROR'] = 'The procedure was aborted by fatal error of the server program or the environment.';

		} else if ($responseCode == 501) {

			//请求的方法未实现
			$result['SUCCESS'] = false;
			$result['ERROR'] = 'The specified procedure is not implemented.';

		} else if ($responseCode == 503) {

			//请求超时
			$result['SUCCESS'] = false;
			$result['ERROR'] = 'The procedure was not done within the given time so aborted';

		} else {

			//其它错误
			$result['SUCCESS'] = false;
			$result['ERROR'] = 'Unknow error.';

		}

		//如果是短连接则关闭curl
		if (!$this->persist || $close) fclose($this->socket);
		//返回结果
		return $result;

	}

}
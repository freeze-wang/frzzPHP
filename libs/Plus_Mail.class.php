<?php
class Plus_Mail {

	private $smtpHost;
	private $smtpPort;
	private $smtpSSL;
	private $smptAuthUser;
	private $smtpAuthPassword;
	private $fromName;
	private $fromEmail;

	public function __construct($config = null) {

		$config = $config ? $config : $GLOBALS['MAIL_CONFIG'];
		$this->smtpHost = $config['SMTP_HOST'];
		$this->smtpPort = $config['SMTP_PORT'];
		$this->smtpSSL = $config['SSL'];
		$this->smptAuthUser = $config['SMTP_AUTH_USER'];
		$this->smtpAuthPassword = $config['SMTP_AUTH_PASSWORD'];
		$this->fromName = $config['FROM_NAME'];
		$this->fromEmail = $config['FROM_EMAIL'];

	}

	/**
	 * 发送邮件
	 * @param  $toName 收件人名称
	 * @param  $toEmail 收件人邮箱
	 * @param  $subject 主题
	 * @param  $content 内容
	 * @param int $contentType 信件内容类型 0-text/plain 1-text/html
	 * @param string $fromName 发信人名称
	 * @param string $fromEmail 发信人邮箱
	 * @return bool
	 */
	public function sendMail($toName, $toEmail, $subject, $content, $contentType = 0, $fromName = '', $fromEmail = '') {

		$charset = 'utf-8';

		if (!$fromName) $fromName = $this->fromName;
		if (!$fromEmail) $fromEmail = $this->fromEmail;

		/* 邮件的头部信息 */
		$content_type = ($contentType == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
		$content = base64_encode($content);

		$headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
		$headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($toName) . '?=' . '" <' . $toEmail . '>';
		$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($fromName) . '?=' . '" <' . $fromEmail . '>';
		$headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode($subject) . '?=';
		$headers[] = $content_type . '; format=flowed';
		$headers[] = 'Content-Transfer-Encoding: base64';
		$headers[] = 'Content-Disposition: inline';

		/* 获得邮件服务器的参数设置 */
		$params['host'] = $this->smtpHost;
		$params['port'] = $this->smtpPort;
		$params['ssl'] = $this->smtpSSL;
		$params['user'] = $this->smptAuthUser;
		$params['pass'] = $this->smtpAuthPassword;

		if (empty($params['host']) || empty($params['port'])) {
			die("No SMTP Host!");
		} else {

			// 发送邮件
			static $smtp;

			$send_params['recipients'] = $toEmail;
			$send_params['headers'] = $headers;
			$send_params['from'] = $fromEmail;
			$send_params['body'] = $content;

			if (!isset($smtp)) {
				$smtp = new SMTP($params);
			}

			if ($smtp->connect() && $smtp->send($send_params)) {
				return true;
			} else {
				echo $smtp->error_msg();
				return false;
			}
		}

	}

}


class SMTP {

	var $connection;
	var $recipients;
	var $headers;
	var $timeout;
	var $errors;
	var $status;
	var $body;
	var $from;
	var $host;
	var $port;
	var $ssl;
	var $helo;
	var $auth;
	var $user;
	var $pass;

	/**
	 *  参数为一个数组
	 *  host        SMTP 服务器的主机       默认：localhost
	 *  port        SMTP 服务器的端口       默认：25
	 *  helo        发送HELO命令的名称      默认：localhost
	 *  user        SMTP 服务器的用户名     默认：空值
	 *  pass        SMTP 服务器的登陆密码   默认：空值
	 *  timeout     连接超时的时间          默认：5
	 * @return  bool
	 */
	function SMTP($params = array()) {

		if (!defined('CRLF')) {
			define('CRLF', "\r\n", true);
		}

		$this->timeout = 10;
		$this->status = 1;
		$this->host = 'localhost';
		$this->port = 25;
		$this->auth = false;
		$this->user = '';
		$this->pass = '';
		$this->errors = array();

		foreach ($params AS $key => $value) {
			$this->$key = $value;
		}

		$this->helo = $this->host;

		//  如果没有设置用户名则不验证
		$this->auth = ('' == $this->user) ? false : true;

	}

	function connect($params = array()) {

		if (!isset($this->status)) {
			$obj = new SMTP($params);

			if ($obj->connect()) {
				$obj->status = 2;
			}

			return $obj;
		} else {
			$this->connection = @fsockopen(($this->ssl ? 'ssl://' : '') . $this->host, $this->port, $errno, $errstr, $this->timeout);
			@socket_set_timeout($this->connection, 0, 250000);

			$greeting = $this->get_data();
			if (is_resource($this->connection)) {
				$this->status = 2;
				return $this->auth ? $this->ehlo() : $this->helo();
			} else {
				$this->errors[] = 'Failed to connect to server: ' . $errstr;
				return false;
			}
		}
	}

	/**
	 * 参数为数组
	 * recipients      接收人的数组
	 * from            发件人的地址，也将作为回复地址
	 * headers         头部信息的数组
	 * body            邮件的主体
	 */

	function send($params = array()) {

		foreach ($params AS $key => $value) {
			$this->set($key, $value);
		}

		if ($this->is_connected()) {
			//  服务器是否需要验证
			if ($this->auth) {
				if (!$this->auth()) {
					return false;
				}
			}

			$this->mail($this->from);

			if (is_array($this->recipients)) {
				foreach ($this->recipients AS $value) {
					$this->rcpt($value);
				}
			} else {
				$this->rcpt($this->recipients);
			}

			if (!$this->data()) {
				return false;
			}

			$headers = str_replace(CRLF . '.', CRLF . '..', trim(implode(CRLF, $this->headers)));
			$body = str_replace(CRLF . '.', CRLF . '..', $this->body);
			$body = $body[0] == '.' ? '.' . $body : $body;

			$this->send_data($headers);
			$this->send_data('');
			$this->send_data($body);
			$this->send_data('.');

			return (substr($this->get_data(), 0, 3) === '250');

		} else {
			$this->errors[] = 'Not connected!';
			return false;
		}
	}

	function helo() {
		if (is_resource($this->connection)
			AND $this->send_data('HELO ' . $this->helo)
				AND substr($error = $this->get_data(), 0, 3) === '250'
		) {
			return true;
		} else {
			$this->errors[] = 'HELO command failed, output: ' . trim(substr($error, 3));
			return false;
		}
	}

	function ehlo() {
		if (is_resource($this->connection)
			AND $this->send_data('EHLO ' . $this->helo)
				AND substr($error = $this->get_data(), 0, 3) === '250'
		) {
			return true;
		} else {
			$this->errors[] = 'EHLO command failed, output: ' . trim(substr($error, 3));
			return false;
		}
	}

	function auth() {
		if (is_resource($this->connection)
			AND $this->send_data('AUTH LOGIN')
				AND substr($error = $this->get_data(), 0, 3) === '334'
					AND $this->send_data(base64_encode($this->user)) // Send username
						AND substr($error = $this->get_data(), 0, 3) === '334'
							AND $this->send_data(base64_encode($this->pass)) // Send password
								AND substr($error = $this->get_data(), 0, 3) === '235'
		) {
			return true;
		} else {
			$this->errors[] = 'AUTH command failed: ' . trim(substr($error, 3));
			return false;
		}
	}

	function mail($from) {
		if ($this->is_connected()
			AND $this->send_data('MAIL FROM:<' . $from . '>')
				AND substr($this->get_data(), 0, 2) === '250'
		) {
			return true;
		} else {
			return false;
		}
	}

	function rcpt($to) {
		if ($this->is_connected()
			AND $this->send_data('RCPT TO:<' . $to . '>')
				AND substr($error = $this->get_data(), 0, 2) === '25'
		) {
			return true;
		} else {
			$this->errors[] = trim(substr($error, 3));
			return false;
		}
	}

	function data() {
		if ($this->is_connected()
			AND $this->send_data('DATA')
				AND substr($error = $this->get_data(), 0, 3) === '354'
		) {
			return true;
		} else {
			$this->errors[] = trim(substr($error, 3));
			return false;
		}
	}

	function is_connected() {
		return (is_resource($this->connection) && ($this->status === 2));
	}

	function send_data($data) {
		if (is_resource($this->connection)) {
			return fwrite($this->connection, $data . CRLF, strlen($data) + 2);
		} else {
			return false;
		}
	}

	function get_data() {
		$return = '';
		$line = '';

		if (is_resource($this->connection)) {
			while (strpos($return, CRLF) === false OR $line{3} !== ' ') {
				$line = fgets($this->connection, 512);
				$return .= $line;
			}

			return trim($return);
		} else {
			return '';
		}
	}

	function set($var, $value) {
		$this->$var = $value;
		return true;
	}

	/**
	 * 获得最后一个错误信息
	 *
	 * @access  public
	 * @return  string
	 */
	function error_msg() {
		if (!empty($this->errors)) {
			$len = count($this->errors) - 1;
			return $this->errors[$len];
		} else {
			return '';
		}
	}

}
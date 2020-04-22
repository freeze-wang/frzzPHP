<?php
/**
 * Javacript语句对象，用于连接js脚本
 */
class jsStatement {

	/**
	 * @var 保存根据方法链生成的脚本
	 */
	private $script;

	/**
	 * 构造器
	 * @param $funName js函数名称
	 * @param $args js函数参数
	 */
	function __construct($funName, $args) {
		$this->constructScript($funName, $args);
	}

	/**
	 * 把生成的脚本发送到客服端
	 */
	function __destruct() {
		echo $this->script, ";", "\r\n";
	}

	/**
	 * 处理方法链
	 * @param $name
	 * @param $arguments
	 * @return jsStatement
	 */
	function __call($name, $arguments) {
		$this->constructScript($name, $arguments);
		return $this;
	}

	/**
	 * 处理属性的获取
	 * @param $name
	 * @return jsStatement
	 */
	function __get($name) {
		$this->script .= ($this->script ? "." : "") . $name;
		return $this;
	}

	/**
	 * 处理属性赋值
	 * @param $name
	 * @param $value
	 */
	function __set($name, $value) {
		if (is_string($value)) {
			$this->script .= ($this->script ? "." : "") . $name . " = \"" . $value . "\"";
		} else if (is_int($argument)) {
			$this->script .= ($this->script ? "." : "") . $name . " = " . $value;
		}
	}


	/**
	 * 根据函数名称和参数来构建脚本
	 * @param $funName
	 * @param $funArguments
	 */
	private function constructScript($funName, $funArguments) {

		$params = "";
		if ($funArguments) {
			$comma = "";
			foreach ($funArguments as $argument) {
				if (is_string($argument)) {
					$params .= $comma . '"' . $argument . '"';
				} else if (is_int($argument)) {
					$params .= $comma . $argument;
				}
				$comma = ", ";
			}
		}

		if ($funName == "window") {
			$this->script = $funName;
		} else if ($funName == "jQuery") {
			$this->script = "$" . ($params ? "(" . $params . ")" : "");
		} else {
			$this->script .= ($this->script ? "." : "") . $funName . "(" . $params . ")";
		}

	}


}

class js {

	public static function header() {
		header("Content-Type: text/javascript;");
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return jsStatement
	 */
	public static function __callStatic($name, $arguments) {
		return new jsStatement($name, $arguments);
	}


}
<?php
class Plus_Logger {

	public static function dailyLog($logName, $logContent) {

		$logDir = sprintf("%s/logs/%s/%s/", ROOT, strtolower($logName), date("Y/m"));
		if (!file_exists($logDir)) mkdir($logDir, 0755, true);
		$logFile = $logDir . date("Ymd") . ".log";
		file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

	}

	public static function monthlyLog($logName, $logContent) {

		$logDir = sprintf("%s/logs/%s/%s/", ROOT, strtolower($logName), date("Y"));
		if (!file_exists($logDir)) mkdir($logDir, 0755, true);
		$logFile = $logDir . date("m") . ".log";
        $logContent = trim($logContent) . PHP_EOL;
		file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

	}

	public static function crucialLog($logName, $logContent) {

		$logDir = sprintf("%s/logs/%s/%s/", ROOT, strtolower($logName), date("Y"));
		if (!file_exists($logDir)) mkdir($logDir, 0755, true);
		$logFile = $logDir . date("m") . ".log";
        $logContent = date('[Y-m-d H:i:s] ') . trim($logContent) . PHP_EOL;
		file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

	}

}
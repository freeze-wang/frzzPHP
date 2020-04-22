<?php
/**
 * Class Plus_DaemonProcess
 * 命令行永驻内存运行进程类
 * 注意：必须在PHP程序首行定义：declare(ticks = 1);
 */
abstract class Plus_DaemonProcess {

    protected $phpFile;
    protected $running = false;

    public function start($argc, $argv) {

        if (php_sapi_name() != 'cli') {
            die("Error: The process is not run in command line mode. \n");
        }

        if ($argc == 1 ||
            ($argc == 2 && strtolower($argv[1]) == 'start')
        ) {

            $this->phpFile = $argv[0];

            $processId = pcntl_fork();
            if ($processId == -1) {
                die("\n Error: The process failed to fork. \n");
            } else if ($processId) {
                exit(0);
            }

            if (posix_setsid() == -1) {
                die("Error: Unable to detach from the terminal window. \n");
            }

            $pid = posix_getpid();

            //写入pid
            $pidFile = $this->phpFile . '.pid';
            $f = fopen($pidFile, 'w');
            fwrite($f, $pid);
            fclose($f);

            pcntl_signal(SIGTERM, array(&$this, 'onSignal'));
            pcntl_signal(SIGINT, array(&$this, 'onSignal'));
            pcntl_signal(SIGHUP, array(&$this, 'onSignal'));

            $this->running = true;
            $this->run();

        } else if ($argc == 2 && strtolower($argv[1]) == 'stop') {
            $this->stop($argv);
        }

    }

    public abstract function run();

    public function onSignal($signal) {
        switch ($signal) {
            case SIGTERM:
                $this->running = false;
                break;
        }
    }

    public function log($content) {

        $file = $this->phpFile . ".log";
        $message = date('[Y-m-d H:i:s] ') . "$content\r\n";
        $fp = fopen($file, 'a+');
        if (flock($fp, LOCK_EX)) {
            fputs($fp, $message);
            flock($fp, LOCK_UN);
        }
        fclose($fp);

    }

    public function stop($argv) {

        $pidFile = $argv[0] . '.pid';

        if (file_exists($pidFile)) {

            $f = fopen($pidFile, 'r');
            $pid = intval(fread($f, filesize($pidFile)));
            fclose($f);

            posix_kill($pid, SIGTERM);
            unlink($pidFile);

        } else {
            die("Error: pid file '$pidFile' not exists! \n");
        }

    }

}
<?php

class Plus_Queue {

    protected $queueDir;

    function __construct($queueName, $baseDir = '/dev/shm/queue/') {

        $this->queueDir = $baseDir . strtolower($queueName);
        if (!file_exists($this->queueDir))
            mkdir($this->queueDir, 0755, true);

    }

    public function put($obj) {

        $objContent = serialize($obj);
        $objSize = strlen($objContent);

        $queueFile = $this->queueDir . DIRECTORY_SEPARATOR . time() . '_' . rand(1000, 9999) . '.queue';
        $byteWritten = file_put_contents($queueFile, $objContent, LOCK_EX);
        unset($objContent);
        return ($byteWritten == $objSize);

    }

    public function get() {

        $obj = null;
        $dir = dir($this->queueDir);
        if ($dir) {
            while (($file = $dir->read()) !== false) {
                if ($file != '.' && $file != '..') {
                    $queueFile = $this->queueDir . DIRECTORY_SEPARATOR . $file;
                    if (is_file($queueFile)) {
                        $objContent = file_get_contents($queueFile, LOCK_EX);
                        if ($objContent) {
                            $obj = unserialize($objContent);
                            unset($objContent);
                        }
                        unlink($queueFile);
                    }
                    break;
                }
            }
            $dir->close();
            unset($dir);
        }
        return $obj;

    }


}
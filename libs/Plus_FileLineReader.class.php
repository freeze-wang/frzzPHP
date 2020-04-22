<?php
/**
 * Class Plus_FileLineReader
 * 使指定的文件可以迭代
 * @author: 曾新乾
 */
class Plus_FileLineReader implements Iterator {

    private $f;
    private $index;
    private $line;

    /**
     * 指定文件名并打开文件
     * @param string $file
     * @throws Exception
     */
    function __construct($file) {

        if (!$file or !file_exists($file))
            throw new Exception("$file not exists!");

        $this->f = fopen($file, "r");
        $this->rewind();

    }

    /**
     * 关闭文件
     */
    function __destruct() {
        if (is_resource($this->f))
            fclose($this->f);
    }


    public function current() {
        return $this->line;
    }

    public function next() {
        $this->index++;
        return ($this->line = fgets($this->f));
    }

    public function key() {
        return $this->index;
    }

    public function valid() {
        return !feof($this->f);
    }

    public function rewind() {
        fseek($this->f, 0);
        $this->index = 0;
        $this->line = fgets($this->f);
    }

}
<?php
class Plus_IPLocation {

    /**
     * QQWry.Dat文件指针
     *
     * @var resource
     */
    var $filePointer;

    /**
     * 第一条IP记录的偏移地址
     *
     * @var int
     */
    var $firstIP;

    /**
     * 最后一条IP记录的偏移地址
     *
     * @var int
     */
    var $lastIP;

    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var int
     */
    var $totalIP;

    /**
     * 返回读取的长整型数
     *
     * @access private
     * @return int
     */
    function getLong() {
        //将读取的little-endian编码的4个字节转化为长整型数
        $result = unpack('Vlong', fread($this->filePointer, 4));
        return $result['long'];
    }

    /**
     * 返回读取的3个字节的长整型数
     *
     * @access private
     * @return int
     */
    function getLong3() {
        //将读取的little-endian编码的3个字节转化为长整型数
        $result = unpack('Vlong', fread($this->filePointer, 3) . chr(0));
        return $result['long'];
    }

    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @access private
     * @param string $ip
     * @return string
     */
    function packIP($ip) {
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
        return pack('N', intval(ip2long($ip)));
    }

    /**
     * 返回读取的字符串
     *
     * @access private
     * @param string $data
     * @return string
     */
    function getString($data = "") {
        $char = fread($this->filePointer, 1);
        while (ord($char) > 0) { // 字符串按照C格式保存，以结束
            $data .= $char; // 将读取的字符连接到给定字符串之后
            $char = fread($this->filePointer, 1);
        }
        return iconv('GBK', 'UTF-8', $data);
    }

    /**
     * 返回地区信息
     *
     * @access private
     * @return string
     */
    function getArea() {
        $byte = fread($this->filePointer, 1); // 标志字节
        switch (ord($byte)) {
            case 0: // 没有区域信息
                $area = "";
                break;
            case 1:
            case 2: // 标志字节为1或2，表示区域信息被重定向
                fseek($this->filePointer, $this->getLong3());
                $area = $this->getString();
                break;
            default: // 否则，表示区域信息没有被重定向
                $area = $this->getString($byte);
                break;
        }
        return $area;
    }

    /**
     * 根据所给 IP 地址或域名返回所在地区信息
     *
     * @access public
     * @param string $ip
     * @return array
     */
    function getLocation($ip) {

        if (!$this->filePointer) return null; // 如果数据文件没有被正确打开，则直接返回空
        $location['ip'] = gethostbyname($ip); // 将输入的域名转化为IP地址
        $ip = $this->packIP($location['ip']); // 将输入的IP地址转化为可比较的IP地址
        // 不合法的IP地址会被转化为255.255.255.255
        // 对分搜索
        $l = 0; // 搜索的下边界
        $u = $this->totalIP; // 搜索的上边界
        $findip = $this->lastIP; // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息）
        while ($l <= $u) { // 当上边界小于下边界时，查找失败

            $i = floor(($l + $u) / 2); // 计算近似中间记录
            fseek($this->filePointer, $this->firstIP + $i * 7);
            $beginip = strrev(fread($this->filePointer, 4)); // 获取中间记录的开始IP地址
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式
            // 以便用于比较，后面相同。
            if ($ip < $beginip) { // 用户的IP小于中间记录的开始IP地址时
                $u = $i - 1; // 将搜索的上边界修改为中间记录减	一
            } else {
                fseek($this->filePointer, $this->getLong3());
                $endip = strrev(fread($this->filePointer, 4)); // 获取中间记录的结束IP地址
                if ($ip > $endip) { // 用户的IP大于中间记录的结束IP地址时
                    $l = $i + 1; // 将搜索的下边界修改为中间记录加一
                } else { // 用户的IP在中间记录的IP范围内时

                    $findip = $this->firstIP + $i * 7;
                    break; // 则表示找到结果，退出循环
                }
            }
        }

        //获取查找到的IP地理位置信息
        fseek($this->filePointer, $findip);
        $location['beginip'] = long2ip($this->getLong()); // 用户IP所在范围的开始地址
        $offset = $this->getLong3();
        fseek($this->filePointer, $offset);
        $location['endip'] = long2ip($this->getLong()); // 用户IP所在范围的结束地址
        $byte = fread($this->filePointer, 1); // 标志字节
        switch (ord($byte)) {
            case 1: // 标志字节为1，表示国家和区域信息都被同时重定向
                $countryOffset = $this->getLong3(); // 重定向地址

                fseek($this->filePointer, $countryOffset);
                $byte = fread($this->filePointer, 1); // 标志字节
                switch (ord($byte)) {
                    case 2: // 标志字节为2，表示国家信息又被重定向
                        fseek($this->filePointer, $this->getLong3());
                        $location['country'] = $this->getString();
                        fseek($this->filePointer, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default: // 否则，表示国家信息没有被重定向

                        $location['country'] = $this->getString($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2: // 标志字节为2，表示国家信息被重定向
                fseek($this->filePointer, $this->getLong3());
                $location['country'] = $this->getString();
                fseek($this->filePointer, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default: // 否则，表示国家信息没有被重定向

                $location['country'] = $this->getString($byte);
                $location['area'] = $this->getarea();
                break;
        }
        if ($location['country'] == " CZ88.NET") { // CZ88.NET表示没有有效信息
            $location['country'] = "未知";
        }
        if ($location['area'] == " CZ88.NET") {
            $location['area'] = "";
        }
        return $location;
    }

    /**
     * 构造函数，打开 QQWry.Dat 文件并初始化类中的信息
     */
    function __construct() {

        $this->filePointer = 0;
        $diskFile = ROOT . "/lib/qqwry.dat";
        $cacheFile = '/dev/shm/qqwry.dat';
        if (file_exists($cacheFile) || file_exists($diskFile)) {
            if (!file_exists($cacheFile)) {
                copy($diskFile, $cacheFile);
            }
            $fileName = $cacheFile;
            if (($this->filePointer = @fopen($fileName, 'rb')) !== false) {
                $this->firstIP = $this->getLong();
                $this->lastIP = $this->getLong();
                $this->totalIP = ($this->lastIP - $this->firstIP) / 7;
                //注册析构函数，使其在程序执行结束时执行
                register_shutdown_function(array(&$this, '_IpLocation'));
            }
        } else {
            die("IP data file not found!");
        }

    }

    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件。
     *
     */
    function _IpLocation() {
        if ($this->filePointer) {
            fclose($this->filePointer);
        }
        $this->filePointer = 0;
    }

}
<?php
class Plus_MemcachedClient {

    const VERSION = '1.0';
    const MC_BUFFER_SIZE = 1024;
    const MC_TIME_OUT = 30;

    const MAGIC_REQUEST = 0x80;
    const MAGIC_RESPONSE = 0x81;

    const OP_GET = 0x00;
    const OP_SET = 0x01;
    const OP_ADD = 0x02;
    const OP_REPLACE = 0x03;
    const OP_DELETE = 0x04;
    const OP_INCREMENT = 0x05;
    const OP_DECREMENT = 0x06;
    const OP_QUIT = 0x07;
    const OP_FLUSH = 0x08;
    const OP_GETQ = 0x09;
    const OP_NO_OP = 0x0A;
    const OP_VERSION = 0x0B;
    const OP_GETK = 0x0C;
    const OP_GETKQ = 0x0D;
    const OP_APPEND = 0x0E;
    const OP_PREPEND = 0x0F;
    const OP_STAT = 0x10;
    const OP_SETQ = 0x11;
    const OP_ADDQ = 0x12;
    const OP_REPLACEQ = 0x13;
    const OP_DELETEQ = 0x14;
    const OP_INCREMENTQ = 0x15;
    const OP_DECREMENTQ = 0x16;
    const OP_QUITQ = 0x17;
    const OP_FLUSHQ = 0x18;
    const OP_APPENDQ = 0x19;
    const OP_PREPENDQ = 0x1A;

    //存储标记flag
    const FLAG_NORMAL = 0;
    const FLAG_SERIALIZED = 1;
    const FLAG_COMPRESSED = 2;

    //自定义错误
    const MC_ERR_NOT_ACTIVE = 1001;
    const MC_ERR_SOCKET_WRITE = 1002;
    const MC_ERR_SOCKET_READ = 1003;
    const MC_ERR_SOCKET_CONNECT = 1004;
    const MC_ERR_DELETE = 1005;
    const MC_ERR_HOST_FORMAT = 1006;
    const MC_ERR_HOST_DEAD = 1007;
    const MC_ERR_GET_SOCK = 1008;

    //服务器返回的错误
    const MC_ERR_NO_ERROR = 0x00;
    const MC_ERR_KEY_NOT_FOUND = 0x01;
    const MC_ERR_KEY_EXISTS = 0x02;
    const MC_ERR_VALUE_TOO_LARGE = 0x03;
    const MC_ERR_INVALID_ARGUMENTS = 0x04;
    const MC_ERR_ITEM_NOT_STORED = 0x05;
    const MC_ERR_INCR_DECR_ON_NON_NUMERIC_VALUE = 0x06;
    const MC_ERR_UNKNOW_COMMAND = 0x81;
    const MC_ERR_OUT_OF_MEMORY = 0x82;

    const DATA_TYPE_RAW_BYTES = 0x00;

    var $avaliabeSockets;
    var $debug = true;
    var $servers;
    var $active;
    var $errorNumber = 0;
    var $errorMessage = '';
    var $ompressThreshold = 1024;
    var $useCompress = true;

    public function __construct() {
        $this->useCompress = function_exists('gzcompress');
    }

    public function __destruct() {
        $this->close();
    }

    public function add($key, $value, $exptime = 0) {
        list($value, $flag) = $this->processValue($value);
        return $this->sendCommand($this->buildRequest(self::OP_ADD, $key, $value, $flag, $exptime));
    }

    public function addServer($host, $port = 11211, $persistent = false, $weight = 1, $timeout = self::MC_TIME_OUT) {
        $this->connectServer($host, $port, $timeout);
    }

    public function close() {
        $this->quit(true);
    }

    public function connect($host, $port = 11211, $timeout = self::MC_TIME_OUT) {
        $this->connectServer($host, $port, $timeout);
    }

    public function decrement($key, $value = 1) {
        return $this->sendCommand($this->buildRequest(self::OP_DECREMENT, $key, $value, 0, 0));
    }

    public function delete($key) {
        return $this->sendCommand($this->buildRequest(self::OP_DELETE, $key));
    }

    public function flush($exptime = 0) {
        return $this->sendCommand($this->buildRequest(self::OP_FLUSH, null, null, 0, $exptime));
    }

    public function get($key) {
        return $this->sendCommand($this->buildRequest(self::OP_GET, $key));
    }

    public function getMulti($keys) {

        if (!is_array($keys)) $keys = array($keys);

        $socket = $this->getSocket();
        if ($socket) {
            for ($i = 0; $i < count($keys); $i++) {
                $key = $keys[$i];
                $this->sendRequest($socket, $this->buildRequest((($i == count($keys) - 1) ? self::OP_GETK : self::OP_GETKQ), $key));
            }
            return $this->parseResponse($socket);
        } else {
            return false;
        }

    }

    public function getStats() {
        return $this->sendCommand($this->buildRequest(self::OP_STAT, null));
    }

    public function getVersion() {
        return $this->sendCommand($this->buildRequest(self::OP_VERSION, null));
    }

    public function increment($key, $value = 1) {
        return $this->sendCommand($this->buildRequest(self::OP_INCREMENT, $key, $value, 0, 0));
    }

    public function quit($forceShutdown = false) {

        if (!isset($this->avaliabeSockets)) return;

        $request = $this->buildRequest(self::OP_QUIT, null);
        if ($request) {
            foreach ($this->avaliabeSockets as $socket) {
                if (is_resource($socket)) {
                    $this->sendRequest($socket, $request);
                    if ($this->parseResponse($socket) || $forceShutdown) {
                        fclose($socket);
                    }
                }
            }
            unset($this->avaliabeSockets);
        }

    }

    public function replace($key, $value, $exptime = 0) {
        list($value, $flag) = $this->processValue($value);
        return $this->sendCommand($this->buildRequest(self::OP_REPLACE, $key, $value, $flag, $exptime));
    }

    public function set($key, $value, $exptime = 0) {
        list($value, $flag) = $this->processValue($value);
        return $this->sendCommand($this->buildRequest(self::OP_SET, $key, $value, $flag, $exptime));
    }

    public function setMulti($items, $exptime = 0) {

        if (!is_array($items)) return false;

        $socket = $this->getSocket();
        if ($socket) {
            $i = 0;
            $itemsCount = count($items);
            foreach ($items as $key => $value) {
                list($value, $flag) = $this->processValue($value);
                $this->sendRequest($socket, $this->buildRequest((($i == $itemsCount - 1) ? self::OP_SET : self::OP_SETQ), $key, $value, $flag, $exptime));
                $i++;
            }
            return $this->parseResponse($socket);
        } else {
            return false;
        }

    }

    public function setCompressThreshold($threshold) {
        $this->ompressThreshold = $threshold;
    }

    public function setServerParams($host, $port = 11211, $timeout = self::MC_TIME_OUT, $retry_interval = false, $status = false, $failure_callback = null) {

    }

    /**
    Private Functions
     */

    private function sendCommand($request) {

        if ($request) {
            $socket = $this->getSocket();
            if ($socket) {
                $this->sendRequest($socket, $request);
                return $this->parseResponse($socket);
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    private function sendRequest($socket, $package) {

        if ($package) {

            $packageLength = strlen($package);
            $offset = 0;

            while ($offset < $packageLength) {

                $result = fwrite($socket, substr($package, $offset, self::MC_BUFFER_SIZE), self::MC_BUFFER_SIZE);

                if ($result !== false) {
                    $offset += $result;
                } else if ($offset < $packageLength) {

                    $this->errorNumber = self::MC_ERR_SOCKET_WRITE;
                    $this->errorMessage = "Failed to write to socket.";

                    if ($this->debug) {
                        $sockerr = socket_last_error($socket);
                        $this->debug("sendPackage(): socket_write() returned false. Socket Error $sockerr: " . socket_strerror($sockerr));
                    }

                    return false;

                }
            }
            return true;

        } else {
            return false;
        }

    }

    private function connectServer($host, $port, $timeout = self::MC_TIME_OUT) {

        $hostAddr = "$host:$port";
        $socket = stream_socket_client('tcp://' . $hostAddr, $errNo, $errStr, $timeout);
        if ($errNo == 0) {

            $this->avaliabeSockets[$hostAddr] = $socket;
            return $socket;

        } else {

            $this->errorNumber = $errNo;
            $this->errorMessage = $errStr;

            if ($this->debug)
                $this->debug("genSocket(): Failed to connect to " . $host . ":" . $port);

            return false;

        }

    }

    private function getSocket() {

        $result = null;
        foreach ($this->avaliabeSockets as $host => $sock) {
            if ($sock) {
                $result = $sock;
                break;
            }
        }
        return $result;

    }

    private function buildRequest($opCode, $key, $value = null, $flag = 0, $exptime = 0) {

        $request = null;
        switch ($opCode) {
            case self::OP_ADD:
            case self::OP_SET:
            case self::OP_SETQ:
            case self::OP_REPLACE:
                $format = "CCnCCnNNNNNN";
                if ($key) {
                    $keyLength = strlen($key);
                    $totalBody = $keyLength + strlen($value) + 8;
                    $request = pack($format, self::MAGIC_REQUEST, $opCode, $keyLength, 8, self::DATA_TYPE_RAW_BYTES, 0, $totalBody, 0, 0, 0, $flag, $exptime) . $key . $value;
                }
                break;
            case self::OP_GET:
            case self::OP_GETQ:
            case self::OP_GETK:
            case self::OP_GETKQ:
            case self::OP_DELETE:
                $format = "CCnCCnNNNN";
                if ($key) {
                    $keyLength = strlen($key);
                    $request = pack($format, self::MAGIC_REQUEST, $opCode, $keyLength, 0, self::DATA_TYPE_RAW_BYTES, 0, $keyLength, 0, 0, 0) . $key;
                }
                break;
            case self::OP_INCREMENT:
            case self::OP_DECREMENT:
                //只处理32位的整型
                $format = "CCnCCnNNNNNNNNN";
                if ($key) {
                    $keyLength = strlen($key);
                    $totalBody = $keyLength + 20;
                    if (!is_int($value)) $value = 1;
                    $request = pack($format, self::MAGIC_REQUEST, $opCode, $keyLength, 20, self::DATA_TYPE_RAW_BYTES, 0, $totalBody, 0, 0, 0, 0, $value, 0, 0, $exptime) . $key;
                }
                break;
            case self::OP_FLUSH:
                $format = "CCnCCnNNNNN";
                $request = pack($format, self::MAGIC_REQUEST, $opCode, 0, 4, self::DATA_TYPE_RAW_BYTES, 0, 4, 0, 0, 0, $exptime);
                echo strlen($request) . "|";
                break;
            case self::OP_VERSION:
            case self::OP_STAT:
            case self::OP_QUIT:
                $format = "CCnCCnNNNN";
                $request = pack($format, self::MAGIC_REQUEST, $opCode, 0, 0, self::DATA_TYPE_RAW_BYTES, 0, 0, 0, 0, 0);
                break;
            default:
                break;
        }
        return $request;

    }

    private function parseResponse(&$socket) {

        //读取返回的头部数据
        $header = stream_get_contents($socket, 24);
        if (!$header) return false;

        //解释头部数据
        $magic = ord($header[0]);
        $opCode = ord($header[1]);
        $keyLength = (ord($header[2]) << 8 | ord($header[3]));
        $extraLength = ord($header[4]);
        $dataType = ord($header[5]);
        $status = (ord($header[6]) << 8 | ord($header[7]));
        $totalBody = (ord($header[8]) << 24 | ord($header[9]) << 16 | ord($header[10]) << 8 | ord($header[11]));
        $opaque = (ord($header[12]) << 24 | ord($header[13]) << 16 | ord($header[14]) << 8 | ord($header[15]));
        $cas = (ord($header[16]) << 56 | ord($header[17]) << 48 | ord($header[18]) << 40 | ord($header[19]) << 32 | ord($header[20]) << 24 | ord($header[21]) << 16 | ord($header[22]) << 8 | ord($header[23]));

        if ($magic == self::MAGIC_RESPONSE) {
            switch ($opCode) {
                case self::OP_ADD:
                case self::OP_SET:
                case self::OP_SETQ:
                case self::OP_REPLACE:
                case self::OP_DELETE:
                case self::OP_FLUSH:
                case self::OP_QUIT:
                    if ($status == self::MC_ERR_NO_ERROR) {
                        $response = true;
                        if ($opCode == self::OP_SETQ) {
                            $response &= $this->parseResponse($socket);
                        }
                    } else {
                        $response = false;
                        $this->errorNumber = $status;
                    }
                    break;
                case self::OP_GET:
                case self::OP_GETQ:
                case self::OP_VERSION:
                    if ($status == self::MC_ERR_NO_ERROR) {
                        $flag = 0;
                        if ($extraLength > 0) {
                            $flagData = stream_get_contents($socket, $extraLength);
                            $flag = (ord($flagData[0]) << 24 | ord($flagData[1]) << 16 | ord($flagData[2]) << 8 | ord($flagData[3]));
                        }
                        $value = stream_get_contents($socket, $totalBody - $extraLength);
                        if ($this->useCompress && (($flag & self::FLAG_COMPRESSED) == self::FLAG_COMPRESSED)) $value = gzuncompress($value);
                        if (($flag & self::FLAG_SERIALIZED) == self::FLAG_SERIALIZED) $value = unserialize($value);
                        $response = $value;
                    } else {
                        $response = false;
                        $this->errorNumber = $status;
                    }
                    break;
                case self::OP_GETK:
                case self::OP_GETKQ:
                    if ($extraLength > 0) {
                        $flagData = stream_get_contents($socket, $extraLength);
                        $flag = (ord($flagData[0]) << 24 | ord($flagData[1]) << 16 | ord($flagData[2]) << 8 | ord($flagData[3]));
                    }
                    $body = stream_get_contents($socket, $totalBody - $extraLength);
                    $key = substr($body, 0, $keyLength);
                    if ($status == self::MC_ERR_NO_ERROR) {
                        $value = substr($body, $keyLength, $totalBody - $keyLength);
                        if ($this->useCompress && (($flag & self::FLAG_COMPRESSED) == self::FLAG_COMPRESSED)) $value = gzuncompress($value);
                        if (($flag & self::FLAG_SERIALIZED) == self::FLAG_SERIALIZED) $value = unserialize($value);
                        $response[$key] = $value;
                    } else {
                        $response[$key] = null;
                        $this->errorNumber = $status;
                    }
                    if ($opCode == self::OP_GETKQ) {
                        $nextResponse = $this->parseResponse($socket);
                        if ($response && $nextResponse) $response = array_merge($response, $nextResponse);
                    }
                    break;
                case self::OP_INCREMENT:
                case self::OP_DECREMENT:
                    if ($status == self::MC_ERR_NO_ERROR) {
                        $value = stream_get_contents($socket, $totalBody);
                        $response = (ord($value[0]) << 56 | ord($value[1]) << 48 | ord($value[2]) << 40 | ord($value[3]) << 32 | ord($value[4]) << 24 | ord($value[5]) << 16 | ord($value[6]) << 8 | ord($value[7]));
                    } else {
                        $response = false;
                        $this->errorNumber = $status;
                    }
                    break;
                case self::OP_STAT:
                    if ($totalBody > 0) {
                        $body = stream_get_contents($socket, $totalBody);
                        $key = substr($body, 0, $keyLength);
                        $value = substr($body, $keyLength, $totalBody - $keyLength);
                        $response[$key] = $value;
                        $nextResponse = $this->parseResponse($socket);
                        if ($response && $nextResponse) $response = array_merge($response, $nextResponse);
                    }
                    break;

                default:
                    break;

            }
        }
        return $response;

    }

    private function processValue($value) {

        $flag = 0;
        if (!is_scalar($value)) {
            $value = serialize($value);
            $flag |= self::FLAG_SERIALIZED;
        }
        if ($this->useCompress && (strlen($value) > $this->ompressThreshold)) {
            $value = gzcompress($value);
            $flag |= self::FLAG_COMPRESSED;
        }
        return array($value, $flag);

    }

    private function hashFunc($num) {
        return sprintf("%u", crc32($num));
    }

    private function debug($text) {
        print "$text\r\n";
    }

}
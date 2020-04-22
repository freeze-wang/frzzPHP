<?php
/**
 * Class Plus_RedisClient
 * 简单的Redis客户端
 * @author: 曾新乾
 */
class Plus_RedisClient extends Redis {

    public $connected;

    function __construct($host = REDIS_HOST, $port = REDIS_PORT, $password = false) {
        parent::__construct();
        try {
            if ($this->connect($host, $port)) {
                $this->connected = !$password || $this->auth($password);
                if ($this->connected){
                    $this->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                }
            } else {
                throw new Plus_IOException(sprintf("Can not connect to Redis[%s:%d]!", $host, $port));
            }
        } catch (RedisException $e) {
            throw new Plus_IOException($e->getMessage());
        }
    }

    function __destruct() {
        if ($this->connected) {
            $this->close();
        }
    }

}
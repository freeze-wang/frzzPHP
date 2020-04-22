<?php
/**
 * Class Plus_DistributeLock
 * 基于Redis的分布式锁
 */
class Plus_DistributeLock {

    /**
     * @var string
     */
    private $lockName;
    /**
     * @var Plus_RedisClient
     */
    private $redis;

    /**
     * 构造函数
     * @param string $lockName 锁的名称
     * @param string $host Redis主机
     * @param int $port Redis端口
     * @param bool $password Redis认证密码
     */
    function __construct($lockName, $host = REDIS_HOST, $port = REDIS_PORT, $password = false) {
        $this->lockName = sprintf("PLUS_DISTRIBUTE_LOCK_%s", strval($lockName));
        $this->redis = new Plus_RedisClient($host, $port, $password);
    }

    /**
     * 释构函数
     */
    function __destruct() {
        $this->redis->close();
    }


    /**
     * 获取分布式锁
     * @param float $timeout 锁的超时时间，如果设定，超过指定时间锁会释放
     * @return bool 是否获取到锁
     */
    public function aquire($timeout = 0.00) {
        if ($this->redis->connected &&
            !$this->redis->get($this->lockName)
        ) {
            $this->redis->set($this->lockName, "1", $timeout);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 释放锁
     */
    public function release() {
        if ($this->redis->connected) {
            $this->redis->delete($this->lockName);
        }
    }

}
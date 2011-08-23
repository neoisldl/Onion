<?php
namespace Onion\Storage;

use Onion\IStorage;
use Onion\StorageError;

/**
 * Redis数据库封装
 *
 * @uses IStorage
 * @package Storage
 * @author ldl <neoisldl@gmail.com>
 */
class Redis implements IStorage {
    private $handler;

    private $config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 0,
        'prefix' => null,
     // 'password' => 'your password',
     // 'database' => 0,    // dbindex, the database number to switch to
    );

    public function __construct(array $config) {
        if (!extension_loaded('redis'))
            throw StorageError::require_extension('redis');

        if ($config) $this->config = array_merge($this->config, $config);
    }

    public function __call($fn, $args) {
        if (!$this->isConnected()) $this->connect();
        return call_user_func_array(array($this->handler, $fn), $args);
    }

    public function isConnected() {
        return $this->handler && $this->handler instanceof \Redis;
    }

    public function connect() {
        if ($this->isConnected()) return $this;

        $config = $this->config;
        $handler = new \Redis;

        if (!$handler->connect($config['host'], $config['port'], $config['timeout']))
            throw new StorageError('Connect redis server failed');

        if (isset($config['password']) && !$handler->auth($config['password']))
            throw new StorageError('Invalid password');

        if (isset($config['database']) && !$handler->select($config['database']))
            throw new StorageError('Select database['. $config['database'] .'] failed');

        if (isset($config['prefix'])) $handler->setOption(\Redis::OPT_PREFIX, $config['prefix']);

        $this->handler = $handler;
        return $this;
    }

    public function disconnect() {
        $this->handler = null;
        return $this;
    }
}

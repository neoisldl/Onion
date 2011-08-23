<?php
use Onion\MVC;
use Onion\Storage\DB\Expr;
use Onion\Utils\Events;
use Onion\Utils\Logging;

//返回一个应用程序对象
function app() {
    return MVC\Application::instance();
}

//返回一个请求对象
function req() {
    return MVC\Request::instance();
}

//返回一个响应对象
function resp() {
    return MVC\Response::instance();
}

//返回一个视图对象
function render_view($file, $vars = null) {
    $view = MVC\View::instance();
    return $view->reset()->render($file, $vars);
}

//获取配置文件的指定键值
function cfg($path = null) {
    $path = is_array($path) ? $path : func_get_args();
    return \Onion\Config::get($path);
}

//设置输出头部
function set_header($name, $val = null) {
    return resp()->setHeader($name, $val);
}

//设置session
function set_session($name, $val) {
    return call_user_func_array(array(resp(), 'setSession'), func_get_args());
}

//设置cookie
function set_cookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = true) {
    return resp()->setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
}

//返回一个get动作的键值
function get($key = null, $default = null) {
    if ($key === null) return $_GET;
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

//返回一个post动作的键值
function post($key = null, $default = null) {
    if ($key === null) return $_POST;
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

//返回一个put动作的键值
function put($key = null, $default = null) {
    static $_PUT = null;

    if ($_PUT === null) {
        if (req()->isPUT()) {
            if (strtoupper(server('request_method')) == 'PUT') {
                parse_str(file_get_contents('php://input'), $_PUT);
            } else {
                $_PUT =& $_POST;
            }
        } else {
            $_PUT = array();
        }
    }

    if ($key === null) return $_PUT;
    return isset($_PUT[$key]) ? $_PUT[$key] : $default;
}

//返回请求的键值
function request($key = null, $default = null) {
    if ($key === null) return array_merge(put(), $_REQUEST);
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : put($key, $default);
}

//是否是一个get动作
function has_get($key) {
    return array_key_exists($key, $_GET);
}

//是否是一个post动作
function has_post($key) {
    return array_key_exists($key, $_POST);
}

//是否是一个put动作
function has_put($key) {
    return array_key_exists($key, put());
}

//返回请求是否包含指定的键值
function has_request($key) {
    return array_key_exists($key, $_REQUEST);
}

//全局函数操作
function env($key = null, $default = false) {
    if ($key === null) return $_ENV;
    $key = strtoupper($key);
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

//全局函数操作
function server($key = null, $default = false) {
    if ($key === null) return $_SERVER;
    $key = strtoupper($key);
    return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
}

//session操作
function session($path = null) {
    if (!isset($_SESSION)) session_start();
    if ($path === null) return $_SESSION;

    return array_get($_SESSION, is_array($path) ? $path : func_get_args());
}

//cookie操作
function cookie($path = null) {
    if ($path === null) return $_COOKIE;

    return array_get($_COOKIE, is_array($path) ? $path : func_get_args());
}

function logger($name) {
    return Logging::getLogger($name);
}

//返回一个存储服务对象
function storage($name = null, $arg = null) {
    $manager = Onion\Storage\Manager::instance();
    if ($arg === null) return $manager->get($name);
    return call_user_func_array(array($manager, 'get'), func_get_args());
}

function dbexpr($expr) {
    if ($expr instanceof Expr) return $expr;
    return new Expr($expr);
}

/**
 * 根据key路径，在array中找出结果
 * 如果key路径不存在，返回false
 *
 * Example:
 * array_get($test, 'a', 'b', 'c');
 * 等于
 * $test['a']['b']['c']
 *
 * @param array $target
 * @param mixed $path
 * @access public
 * @return mixed
 */
function array_get($target, $path) {
    if (!is_array($target)) {
        trigger_error('array_get() excepts parameter 1 to be array', E_WARNING);
        return false;
    }

    $path = is_array($path) ? $path : array_slice(func_get_args(), 1);

    foreach ($path as $key) {
        if (!is_array($target)) return false;
        if (!array_key_exists($key, $target)) return false;

        $target =& $target[$key];
    }

    return $target;
}

/**
 * 根据key路径，设置array内的值
 *
 * Example:
 * array_set($test, 'a', 'b', 'c');
 * 等于
 * $test['a']['b'] = 'c';
 *
 * @param mixed $target
 * @param mixed $path
 * @param mixed $val
 * @access public
 * @return void
 */
function array_set(&$target, $path, $val) {
    if (!is_array($target)) {
        trigger_error('array_set() excepts parameter 1 to be array', E_WARNING);
        return false;
    }

    if (is_array($path)) {
        $key = array_pop($path);
    } else {
        $path = array_slice(func_get_args(), 1);
        list($key, $val) = array_splice($path, -2, 2);
    }

    foreach ($path as $p) {
        if (!is_array($target)) $target = array();
        if (!array_key_exists($p, $target)) $target[$p] = array();
        $target =& $target[$p];
    }

    $target[$key] = $val;
    return true;
}

// 触发对象事件
function fire_event($obj, $event, $args = null) {
    $args = ($args === null)
          ? array()
          : (is_array($args) ? $args : array_slice(func_get_args(), 2));
    return Events::instance()->fire($obj, $event, $args);
}

// 监听对象事件
function listen_event($obj, $event, $callback) {
    return Events::instance()->listen($obj, $event, $callback);
}

// 订阅类事件
function subscribe_event($class, $event, $callback) {
    return Events::instance()->subscribe($class, $event, $callback);
}

// 取消监听事件
function clear_event($obj, $event = null) {
    return Events::instance()->clear($obj, $event);
}

// 检查对象实例或者类名是否属于指定名字空间
function in_namespace($class, $namespace) {
    if (is_object($class)) $class = get_class($class);
    $class = ltrim($class, '\\');
    $namespace = trim($namespace, '\\') . '\\';
    return start_with($class, $namespace, true);
}

// 是关联数组还是普通数组
function is_assoc_array($array) {
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
}

function start_with($haystack, $needle, $case_insensitive = false) {
    if ($case_insensitive) {
        return stripos($haystack, $needle) === 0;
    } else {
        return strpos($haystack, $needle) === 0;
    }
}

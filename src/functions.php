<?php

use Detection\MobileDetect;
use GuzzleHttp\Client;
use liuyuanjun\yii2\aliyun\oss\Oss;
use liuyuanjun\yii2\helpers\HttpApi;
use liuyuanjun\yii2\web\JsonResp;
use Symfony\Component\VarDumper\VarDumper;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

if (!function_exists('env')) {
    /**
     * 获取环境变量
     * @param string $key
     * @param null $default
     * @return array|bool|mixed|string|null
     */
    function env(string $key, $default = null)
    {
        static $loaded = false;
        if (!$loaded && defined('ROOT_PATH')) {
            Dotenv\Dotenv::createImmutable(ROOT_PATH)->load();
            $_ENV = array_merge($_ENV, getenv());
            $loaded = true;
        }
        if (!isset($_ENV[$key])) {
            if ($default instanceof Closure || (is_array($default) && is_callable($default))) {
                return call_user_func($default);
            }
            return $default;
        }
        $value = $_ENV[$key];
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        return $value;
    }
}

if (!function_exists('conf')) {
    /**
     * 配置
     * @param string|array $name
     * @param mixed $default
     * @return mixed
     * @date 2021/8/26 12:07
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function conf($name, $default = null)
    {
        static $properties = [];
        if (is_array($name)) {
            $args = func_get_args();
            array_unshift($args, $properties);
            $properties = call_user_func_array('array_replace_recursive', $args);
            return true;
        }
        return ArrayHelper::getValue($properties, $name, $default);
    }
}

if (!function_exists('param')) {
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function param(string $key, $default = null)
    {
        return ArrayHelper::getValue(Yii::$app->params, $key, $default);
    }
}


if (!function_exists('db')) {
    /**
     * Db
     * @param string $id
     * @return Connection|null
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function db(string $id = 'db')
    {
        return ($redis = Yii::$app->get($id)) instanceof Connection ? $redis : null;
    }
}

if (!function_exists('redis')) {
    /**
     * Redis
     * @param string $id
     * @return \yii\redis\Connection|null
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function redis(string $id = 'redis'): ?\yii\redis\Connection
    {
        return ($redis = Yii::$app->get($id)) instanceof \yii\redis\Connection ? $redis : null;
    }
}

if (!function_exists('http')) {
    /**
     * @param $config
     * @return Client
     * @date 2021/8/30 20:14
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function http($config): Client
    {
        if (is_string($config)) $config = ['base_uri' => $config];
        return new Client($config);
    }
}

if (!function_exists('api')) {
    /**
     * @param string|array $baseUri base uri or api hosts
     * @param string $api
     * @param bool $throwError
     * @return HttpApi|null
     * @date 2021/8/31 21:03
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function api($baseUri, string $api = '', bool $throwError = false): ?HttpApi
    {
        if (is_array($baseUri)) {
            HttpApi::register($baseUri);
            return null;
        }
        $instance = HttpApi::instance($baseUri)->api($api);
        $instance->throwError = $throwError;
        return $instance;
    }
}

if (!function_exists('dd')) {
    /**
     * 打印
     * @param mixed ...$vars
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function dd(...$vars)
    {
        foreach ($vars as $v) VarDumper::dump($v);
        die(1);
    }
}

if (!function_exists('resp')) {
    /**
     * 接口返回
     * @param null|int|string|array $code
     * @return JsonResp
     * @date 2021/8/31 16:00
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function resp($code = null): JsonResp
    {
        $realCode = is_int($code) ? $code : JsonResp::$successCode;
        $instance = new JsonResp($realCode);
        if (is_string($code)) $instance->msg($code);
        elseif (is_array($code)) $instance->data($code);
        return $instance;
    }
}

if (!function_exists('device')) {
    /**
     * @return MobileDetect
     */
    function device(): MobileDetect
    {
        return new MobileDetect();
    }
}


if (!function_exists('oss')) {
    /**
     * Oss
     * @param string $id Yii component ID
     * @param bool $internal 是否使用内网请求
     * @return Oss|null
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    function oss(string $id = 'oss', bool $internal = false): ?Oss
    {
        return ($oss = Yii::$app->get(strtolower(substr($id, -3)) === 'oss' ? $id : $id . 'Oss')) instanceof Oss ? $oss->internal($internal) : null;
    }
}

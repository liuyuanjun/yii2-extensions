<?php

namespace liuyuanjun\yii2\helpers;

use GuzzleHttp\Client;
use liuyuanjun\yii2\log\Log;
use Psr\Http\Message\ResponseInterface;
use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\helpers\Json;
use yii\log\Logger;

/**
 * HTTP接口调用
 *
 * @property array $options
 * @property \Exception|null $error
 * @property ResponseInterface|null $response
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class HttpApi extends BaseObject
{
    private static $_apiHosts = [];
    private static $_logCate = 'http_api_request'; // 日志分类, 留空则不记录日志
    private static $_logFile = 'http_api_request'; // 默认日志文件名, 留空则保存至 app.log
    protected $_api;
    protected $_params = [];
    /**
     * @var array
     * @see https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html
     */
    protected $_options = ['timeout' => 20, 'headers' => []];
    protected $_log = [];
    protected $_error = null;
    protected $_response = null;
    public $throwError = false;

    /**
     * 魔术方法
     * @param string $name
     * @param array $args
     * @return static
     * @throws Exception
     * @date   2021/3/23 19:31
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function __callStatic(string $name, array $args): HttpApi
    {
        if (!isset(static::$_apiHosts[$name]))
            throw new Exception('接口[' . $name . ']未定义。');
        $instance = new static(static::$_apiHosts[$name]);
        if (!empty($args)) $instance->api($args[0]);
        return $instance;
    }

    /**
     * @param string $baseUri
     * @param array $options
     * @return HttpApi
     * @date 2021/8/31 20:53
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function instance(string $baseUri, array $options = [], bool $throwError = false): HttpApi
    {
        $baseUri = static::$_apiHosts[$baseUri] ?? $baseUri;
        $instance = new static($baseUri);
        if (!empty($options)) $instance->_options = array_merge($instance->_options, $options);
        $instance->throwError = $throwError;
        return $instance;
    }

    /**
     * 注册 [ 'test' => 'http://xxx.xxx.xxx:1234' ]
     * @param array $hosts
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function register(array $hosts)
    {
        self::$_apiHosts += $hosts;
    }

    /**
     * 设置日志
     * @param string $logCate
     * @date 2021/9/15 11:58
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function setLog(string $logCate, string $logFile = '')
    {
        self::$_logCate = $logCate;
        self::$_logFile = $logFile;
    }

    public function __construct($config = [])
    {
        if (!empty($config) && is_string($config)) {
            $this->_options['base_uri'] = $config;
        } else {
            parent::__construct($config);
        }
        $this->_options['headers']['X-Request-Id'] = Utils::requestId();
    }

    /**
     * 设置Api
     * @param string $api
     * @return $this
     * @date   2021/3/23 19:30
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function api(string $api): HttpApi
    {
        $this->_api = $api;
        return $this;
    }

    /**
     * 设置参数
     * @param array $params
     * @return $this
     * @date   2021/3/23 19:30
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function params(array $params): HttpApi
    {
        $this->_params = array_merge($this->_params, $params);
        return $this;
    }

    /**
     * 重置参数
     * @return $this
     * @date   2021/3/23 19:29
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function reset(): HttpApi
    {
        $this->_params = [];
        $this->_api = null;
        return $this;
    }

    /**
     * 设置 options
     * @param array $options
     * @return $this
     * @date 2021/9/28 17:30
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setOptions(array $options): HttpApi
    {
        $this->_options = array_merge($this->_options, $options);
        return $this;
    }

    /**
     * 获取 options
     * @return array
     * @date 2021/9/28 17:31
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * 设置 header
     * @param string $name
     * @param $value
     * @return $this
     * @date 2021/9/3 19:16
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setHeader(string $name, $value): HttpApi
    {
        $this->_options['headers'][$name] = $value;
        return $this;
    }

    /**
     * 批量设置 header
     * @param array $headers
     * @return $this
     * @date 2021/9/3 19:17
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setHeaders(array $headers): HttpApi
    {
        foreach ($headers as $name => $value) {
            $this->_options['headers'][$name] = $value;
        }
        return $this;
    }

    /**
     * get 请求
     * @param array $params
     * @param bool $jsonDecode
     * @return false|mixed|null
     * @date   2021/3/23 19:29
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function get(array $params = [], bool $jsonDecode = true)
    {
        return $this->request('get', $params, $jsonDecode);
    }

    /**
     * post 请求
     * @param array $params
     * @param bool $jsonDecode
     * @return false|mixed|null
     * @date   2021/3/23 19:29
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function post(array $params = [], bool $jsonDecode = true)
    {
        return $this->request('post', $params, $jsonDecode);
    }

    /**
     * 获取错误
     * @return \Exception|null
     * @date 2021/9/3 18:02
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getError(): ?\Exception
    {
        return $this->_error;
    }

    /**
     * 获取response
     * @return null|ResponseInterface
     * @date 2021/9/28 17:11
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->_response;
    }

    /**
     * 请求
     * @param string $method
     * @param array $params
     * @param bool $jsonDecode
     * @return false|mixed|null
     * @date   2021/3/23 18:22
     * @throws \Exception
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function request(string $method, array $params = [], bool $jsonDecode = true)
    {
        empty($params) || $this->params($params);
        try {
            if ($method == 'get') {
                $this->_options['query'] = $this->_params;
            } else {
                $this->_options['json'] = $this->_params;
            }
            $log = ['method' => $method, 'api' => $this->_api, 'options' => $this->_options];
            $client = new Client();
            /** @var ResponseInterface $res */
            $res = $this->_response = $client->$method($this->_api, $this->_options);
            $stringBody = (string)$res->getBody();
            $log['response'] = mb_strlen($stringBody) > 500 ? mb_substr($stringBody, 0, 500) . '...' : $stringBody;
            $result = $stringBody && $jsonDecode ? Json::decode($stringBody) : $stringBody;
        } catch (\Exception $e) {
            $log = $log + ['errCode' => $e->getCode(), 'errMsg' => $e->getMessage(), 'errTrace' => $e->getTraceAsString()];
            $this->_error = $e;
            $result = false;
        }
        $this->_log[] = $log;
        if (static::$_logCate)
            Log::json($log, $result === false ? Logger::LEVEL_ERROR : Logger::LEVEL_INFO, static::$_logCate, static::$_logFile);
        if ($result === false && $this->throwError && !empty($e)) throw $e;
        return $result;
    }

}

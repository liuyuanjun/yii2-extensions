<?php

namespace liuyuanjun\yii2\web;

use ArrayObject;
use liuyuanjun\yii2\helpers\Utils;
use stdClass;
use Yii;
use yii\base\BaseObject;
use yii\base\UserException;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\helpers\Json;
use yii\web\Response;

/**
 * Class JsonResp
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class JsonResp extends BaseObject
{
    const CODE_SUCCESS = 200;

    private static $_errorCodes = [0 => 'FAIL', 200 => 'OK'];
    public static $undefinedErrorMessage = '未定义错误。';

    private static $_defaultFilter;

    protected $_code = null;
    protected $_args = [];
    protected $_data = [];
    protected $_options = ['filters' => [], 'headers' => []];

    public function __construct($code, $config = [])
    {
        $this->code($code);
        parent::__construct($config);
    }

    /**
     * @param string|array $message
     * @param array|stdClass|ArrayObject $data
     * @return mixed|\yii\console\Response|Response
     * @date 2021/8/31 15:42
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function ok($message = '', $data = null)
    {
        if (static::isData($message)) {
            $data = $message;
            $message = '';
        }
        return (static::instance(static::CODE_SUCCESS, $message, $data))->resp();
    }

    /**
     * @param int|string|array $code
     * @param string|array $message
     * @param array|stdClass|ArrayObject $data
     * @return mixed|\yii\console\Response|Response
     * @date 2021/8/31 15:33
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function fail($code = 0, $message = '', $data = null)
    {
        $realMsg = $message;
        if (static::isData($code)) {
            $data = $code;
            $code = 0;
        } elseif (is_string($code)) {
            $realMsg = $code;
            $code = 0;
        } elseif ($code instanceof \Exception) {
            $realMsg = $code->getMessage();
            if (YII_DEBUG) $data = [
                'class' => get_class($code),
                'trace' => $code->getTraceAsString(),
                'file' => $code->getFile(),
                'line' => $code->getLine(),
                'previous' => $code->getPrevious()
            ];
            $code = $code->getCode();
        }
        if (static::isData($message)) {
            $data = $message;
            $realMsg = '';
        }
        return (static::instance($code, $realMsg, $data))->resp();
    }

    /**
     * @param int $code
     * @param string $message
     * @param array|stdClass|ArrayObject $data
     * @return static
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function instance(int $code, string $message = '', $data = null): JsonResp
    {
        $instance = new static($code);
        $message && $instance->msg($message);
        $data && $instance->data($data);
        return $instance;
    }

    /**
     * 注册错误信息 [ 123 => 'error message' ]
     * @param array $errorCodes
     * @date 2021/8/31 15:10
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function register(array $errorCodes)
    {
        self::$_errorCodes += $errorCodes;
    }

    /**
     * @return string[]
     * @date 2021/8/31 16:20
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function getErrCodes(): array
    {
        return self::$_errorCodes;
    }


    /**
     * 设置默认过滤器，null则取消过滤器设置
     * @param callable|null $filter
     * @date 2021/9/7 10:02
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function setDefaultFilter(callable $filter = null)
    {
        static::$_defaultFilter = $filter;
    }

    /**
     * 返回
     * @param bool $end
     * @return mixed|\yii\console\Response|Response
     * @date   2021/7/7 11:14
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function resp(bool $end = false)
    {
        $this->prepare();
        $response = Yii::$app->getResponse();
        if ($response instanceof Response) {
            $response->format = Response::FORMAT_JSON;
            $this->_options['headers']['x-request-id'] = Utils::requestId();
            $headers = $response->getHeaders();
            foreach ($this->_options['headers'] as $k => $v)
                $headers->add($k, $v);
            $response->data = $this->_data;
        } else {
            $this->print();
        }
        if ($end) {
            Yii::$app->end();
        } else {
            return $response;
        }
    }

    /**
     * alias
     * @param bool $end
     * @return \yii\console\Response|Response
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function response(bool $end = false)
    {
        return $this->resp($end);
    }

    /**
     * 抛异常，抛异常时data会忽略
     * @param string $exceptionClass
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function throw(string $exceptionClass = UserException::class)
    {
        $this->prepare();
        throw new $exceptionClass($this->_data['message'], $this->_data['code']);
    }

    /**
     * Prints a string to STDERR or STDOUT.
     * @date 2021/9/6 15:35
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function print()
    {
        $string = "[{$this->_code}] " . $this->_data['message'] . "\n";
        if ($this->_data['data']) $string .= 'DATA: ' . Json::encode($this->_data['data']) . "\n";
        if (Console::streamSupportsAnsiColors($this->_code == static::CODE_SUCCESS ? \STDOUT : \STDERR)) {
            $string = Console::ansiFormat($string, [$this->_code == static::CODE_SUCCESS ? BaseConsole::FG_GREEN : BaseConsole::FG_RED]);
        }
        fwrite($this->_code == static::CODE_SUCCESS ? \STDOUT : \STDERR, $string);
    }

    /**
     * 获取消息
     * @return string
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getMessage(): string
    {
        $this->prepare();
        return $this->_data['message'];
    }

    /**
     * 指定code
     * @param int $code
     * @return $this
     * @date 2021/9/28 16:57
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    private function code(int $code): JsonResp
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * 消息模板参数
     * @param string ...$args
     * @return $this
     */
    public function args(...$args): JsonResp
    {
        $this->_args = $args;
        return $this;
    }

    /**
     * 指定消息
     * @param string $message
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function msg(string $message): JsonResp
    {
        $this->_data['message'] = $message;
        return $this;
    }

    /**
     * alias
     * @param string $message
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function message(string $message): JsonResp
    {
        return $this->msg($message);
    }

    /**
     * 返回头
     * @param string|array $name
     * @param string $value
     * @return $this
     * @date 2021/9/15 15:36
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function header($name, string $value = ''): JsonResp
    {
        if (is_array($name)) {
            $this->_options['headers'] = array_merge($this->_options['headers'], $name);
        } else {
            $this->_options['headers'][$name] = $value;
        }
        return $this;
    }

    /**
     * 附带数据
     * @param mixed $data
     * @param callable|Closure|array|null $filter
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function data($data, callable $filter = null): JsonResp
    {
        $this->_data['data'] = $data;
        if ($filter) $this->filter($filter);
        return $this;
    }

    /**
     * 添加数据
     * @param array $data
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function addData(array $data): JsonResp
    {
        $this->_data['data'] = array_merge((array)$this->_data['data'] ?? [], $data);
        return $this;
    }

    /**
     * 添加过滤器
     * @param callable|Closure|array $filter filter需返回加工过的data数据
     * @return $this
     * @date 2021/9/7 09:38
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function filter($filter, $append = true): JsonResp
    {
        if ($append) {
            $this->_options['filters'][] = $filter;
        } else {
            $this->_options['filters'] = $filter;
        }
        return $this;
    }

    /**
     * prepare data
     * @date 2021/8/31 15:07
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function prepare()
    {
        $this->_data['code'] = $this->_code;
        $this->_data['message'] = $this->_data['message'] ?? self::$_errorCodes[$this->_code] ?? static::$undefinedErrorMessage;
        $this->_data['requestId'] = Utils::requestId();
        if (!YII_ENV_PROD) $this->_data['takeTime'] = sprintf('%.3f', (microtime(true) - YII_BEGIN_TIME) * 1000) . ' ms';
        $this->_data['serverTime'] = date('Y-m-d H:i:s', (int)YII_BEGIN_TIME);
        empty($this->_args) || $this->_data['message'] = call_user_func_array('sprintf', array_merge([$this->_data['message']], $this->_args));
//        if (isset($this->_data['data']) && empty($this->_data['data'])) $this->_data['data'] = new \ArrayObject;
        if (static::$_defaultFilter) array_unshift($this->_options['filters'], static::$_defaultFilter);
        if (!empty($this->_options['filters']) && !empty($this->_data['data'])) {
            foreach ($this->_options['filters'] as $filter) {
                $this->_data['data'] = call_user_func($filter, $this->_data['data']);
            }
        }
        if (isset($this->_data['data']) && $this->_data['data'] === null) unset($this->_data['data']);
    }

    /**
     * @param mixed $data
     * @return bool
     * @date 2021/9/8 20:21
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function isData($data): bool
    {
        return is_array($data) || $data instanceof stdClass || $data instanceof ArrayObject;
    }

}

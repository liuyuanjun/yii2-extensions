<?php

namespace liuyuanjun\yii2\extensions\web;

use liuyuanjun\yii2\extensions\helpers\Utils;
use Yii;
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
class JsonResp
{
    const CODE_SUCCESS = 200;

    private static $_errorCodes = [0 => 'FAIL', 200 => 'OK'];
    public static $undefinedErrorMessage = '未定义错误。';

    private static $_defaultFilter;

    protected $_code = null;
    protected $_args = [];
    protected $_data = [];
    protected $_filter;

    public function __construct($code)
    {
        if (!is_null($code)) {
            $this->_code = $code;
        }
    }

    /**
     * @param string|array $message
     * @param array|null $data
     * @return mixed|\yii\console\Response|Response
     * @date 2021/8/31 15:42
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function ok($message = '', array $data = null)
    {
        if (is_array($message)) {
            $data = $message;
            $message = '';
        }
        return (static::instance(static::CODE_SUCCESS, $message, $data))->resp();
    }

    /**
     * @param int|string|array $code
     * @param string|array $message
     * @param array|null $data
     * @return mixed|\yii\console\Response|Response
     * @date 2021/8/31 15:33
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function fail($code = 0, $message = '', array $data = null)
    {
        $realMsg = $message;
        if (is_array($code)) {
            $data = $code;
            $code = 0;
        } elseif (is_string($code)) {
            $realMsg = $code;
            $code = 0;
        }
        if (is_array($message)) {
            $data = $message;
            $realMsg = '';
        }
        return (static::instance($code, $realMsg, $data))->resp();
    }

    /**
     * @param int $code
     * @param string $message
     * @param array|null $data
     * @return static
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function instance(int $code, string $message = '', array $data = null): JsonResp
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
    public function response(bool $end)
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
     * 附带数据
     * @param array|object|string $data
     * @param callable|null $filter
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function data($data, callable $filter = null): JsonResp
    {
        $this->_data['data'] = $data;
        if ($filter !== null) $this->_filter = $filter;
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
        $this->_data['data'] = array_merge($this->_data['data'] ?? [], $data);
        return $this;
    }

    /**
     * 添加过滤器
     * @param callable $filter filter需返回加工过的data数据
     * @return $this
     * @date 2021/9/7 09:38
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function filter(callable $filter): JsonResp
    {
        $this->_filter = $filter;
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
        $filter = $this->_filter ?? static::$_defaultFilter;
        if (!empty($this->_data['data']) && $filter && is_callable($filter)) {
            $this->_data['data'] = call_user_func($filter, $this->_data['data']);
        } elseif (isset($this->_data['data']) && $this->_data['data'] === null) {
            unset($this->_data['data']);
        }
    }

}

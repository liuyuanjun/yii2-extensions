<?php

namespace liuyuanjun\yii2\log;

use Closure;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * Class Log
 *
 * - 存储格式使用JSON
 * - 根据 category 存入不同日志文件
 * - 文件目录可以定义常量 APP_LOG_DIR ，不定义则使用 @runtime/logs
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class Log
{
    /**
     * Logs an error message.
     * @param array $array
     * @param string $category
     * @param string|callable $logFile 日志文件名 支持 {category} 和 {date:format}
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function error(array $array, string $category = 'common', $logFile = '')
    {
        static::json($array, Logger::LEVEL_ERROR, $category, $logFile);
    }

    /**
     * Logs a warning message.
     * @param array $array
     * @param string $category
     * @param string|callable $logFile 日志文件名 支持 {category} 和 {date:format}
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function warning(array $array, string $category = 'common', $logFile = '')
    {
        static::json($array, Logger::LEVEL_WARNING, $category, $logFile);
    }

    /**
     * Logs an informative message.
     * @param array $array
     * @param string $category
     * @param string|callable $logFile 日志文件名 支持 {category} 和 {date:format}
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function info(array $array, string $category = 'common', $logFile = '')
    {
        static::json($array, Logger::LEVEL_INFO, $category, $logFile);
    }

    /**
     * Json Log
     * @param array $array
     * @param $level
     * @param string $category
     * @param string|callable $logFile 日志文件名 支持 {category} 和 {date:format}
     * @date 2021/10/31 18:00
     * @throws InvalidConfigException
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function json(array $array, $level, string $category = 'common', $logFile = '')
    {
        // 兼容旧版本
        if (str_starts_with($logFile, '_d_')) {
            $logFile = '{category}.{date:' . substr($logFile, 3) . '}';
        }
        $fullCategory = 'x_' . $category;
        $log = Yii::$app->getLog();
        if (!isset($log->targets[$fullCategory])) {
            $params = [
                'class' => JsonFileTarget::class,
                'levels' => ['error', 'warning', 'info'],
                'logVars' => [],
                'categories' => [$fullCategory],
            ];
            if ($logFile) {
                if ($logFile instanceof Closure || (is_array($logFile) && is_callable($logFile))) {
                    $logFile = call_user_func($logFile, $array, $level, $category);
                }
                if (!is_string($logFile)) {
                    throw new InvalidArgumentException('logFile must be a string');
                }
                if (strpos($logFile, '{') !== false) {
                    $params['logFile'] = str_replace('{category}', $category, $logFile);
                    // 替换 {date:format}
                    $params['logFile'] = preg_replace_callback('/\{date:([^\}]+)\}/', function ($matches) {
                        return date($matches[1]);
                    }, $params['logFile']);
                }
                $params['logFile'] = (defined('APP_LOG_DIR') ? APP_LOG_DIR : '@runtime/logs') . '/' . $params['logFile'] . '.log';
            }
            $log->targets[$fullCategory] = Yii::createObject($params);
        }
        Yii::getLogger()->log($array, $level, $fullCategory);
    }
}

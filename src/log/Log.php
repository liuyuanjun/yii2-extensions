<?php

namespace liuyuanjun\yii2\log;

use Closure;
use Yii;
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
     * @param string|callable $fileSuffix
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function error(array $array, string $category = 'common', $fileSuffix = 'c')
    {
        static::json($array, Logger::LEVEL_ERROR, $category, $fileSuffix);
    }

    /**
     * Logs a warning message.
     * @param array $array
     * @param string $category
     * @param string|callable $fileSuffix
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function warning(array $array, string $category = 'common', $fileSuffix = 'c')
    {
        static::json($array, Logger::LEVEL_WARNING, $category, $fileSuffix);
    }

    /**
     * Logs an informative message.
     * @param array $array
     * @param string $category
     * @param string|callable $fileSuffix
     * @date 2021/9/1 11:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function info(array $array, string $category = 'common', $fileSuffix = 'c')
    {
        static::json($array, Logger::LEVEL_INFO, $category, $fileSuffix);
    }

    /**
     * Json Log
     * @param array $array
     * @param $level
     * @param string $category
     * @param string|callable $fileSuffix
     * @date 2021/9/1 11:37
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function json(array $array, $level, string $category = 'common', $fileSuffix = 'c')
    {
        $fullCategory = '_custom_' . $category;
        $log = Yii::$app->getLog();
        if (!isset($log->targets[$fullCategory])) {
            if ($fileSuffix) {
                if ($fileSuffix instanceof Closure || (is_array($fileSuffix) && is_callable($fileSuffix))) {
                    $fileSuffix = call_user_func($fileSuffix, $array, $level, $category);
                } elseif (is_string($fileSuffix) && strpos($fileSuffix, '_d_') === 0) {
                    $fileSuffix = date(substr($fileSuffix, 3));
                }
            }
            $log->targets[$fullCategory] = Yii::createObject([
                'class' => JsonFileTarget::class,
                'levels' => ['error', 'warning', 'info'],
                'logFile' => (defined('APP_LOG_DIR') ? APP_LOG_DIR : '@runtime/logs') . '/' . $category . ($fileSuffix ? '.' . $fileSuffix : '') . '.log',
                'logVars' => [],
                'categories' => [$fullCategory],
            ]);
        }
        Yii::getLogger()->log($array, $level, $fullCategory);
    }
}

<?php

namespace liuyuanjun\yii2\extensions\log;

use liuyuanjun\yii2\extensions\helpers\Utils;
use Yii;
use yii\helpers\Json;
use yii\log\FileTarget;
use yii\log\Logger;

/**
 * Class JsonFileTarget
 * 把日志用JSON存成一行，方便SLS解析
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class JsonFileTarget extends FileTarget
{

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        [$array, $level, $category, $timestamp] = $message;
        $level = Logger::getLevelName($level);
        if (!is_array($array)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($array instanceof \Throwable) {
                $array = [
                    'exception' => '[' . $array->getCode() . ']' . $array->getMessage(),
                    'file' => $array->getFile() . '(' . $array->getLine() . ')',
//                    'traces' => $array->getTraceAsString(),
                ];;
            } else {
                $array = ['message' => $array];
            }
        }
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }
        $array['_traces'] = $traces;
        if (isset($message[5])) $array['_memoryUsage'] = $message[5];
        $array['_category'] = $category;
        $array['_level'] = $level;
        $array['_requestId'] = Utils::requestId();
        $array['_time'] = $this->getTime($timestamp);
//        $prefix = $this->getMessagePrefix($message);
        return Json::encode($array);
    }
}

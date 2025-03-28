<?php

namespace liuyuanjun\yii2\log;

use liuyuanjun\yii2\helpers\Utils;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\log\FileTarget;
use yii\log\Logger;
use yii\web\Request;

/**
 * Class JsonFileTarget
 * 把日志用JSON存成一行，方便SLS解析
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class JsonFileTarget extends FileTarget
{
    public $logVars = [];

    /**
     * {@inheritdoc}
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, static::filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
        $count = count($this->messages);
        if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
            if (!empty($context = $this->getContextMessage())) {
                $this->messages[] = [$context, Logger::LEVEL_INFO, 'application', YII_BEGIN_TIME, [], 0];
            }
            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export();
            $this->exportInterval = $oldExportInterval;

            $this->messages = [];
        }
    }

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
        /*
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }
        $array['_traces'] = $traces;
        */
        if (isset($message[5])) $array['_memoryUsage'] = $message[5];
        $array['_category'] = $category;
        $array['_level'] = $level;
        $array['_requestId'] = Utils::requestId();
        $array['_appId'] = Yii::$app->id ?? 'undefined';
        $array['_time'] = $this->getTime($timestamp);
        if ($prefix = $this->getMessagePrefix($message))
            $array = array_merge($prefix, $array);
        return Json::encode($array);
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getMessagePrefix($message)
    {
        if ($this->prefix !== null) {
            return call_user_func($this->prefix, $message);
        }
        return [];
    }

    /**
     * 生成context信息数组
     * @return array
     */
    protected function getContextMessage()
    {
        $context = ArrayHelper::filter($GLOBALS, $this->logVars);
        if (in_array('_POST', $this->logVars) && ($request = Yii::$app->request) instanceof Request && strpos($request->getContentType(), 'application/json') !== false) {
            $context['_POST'] = $request->getRawBody();
        }
        foreach ($this->maskVars as $var) {
            if (ArrayHelper::getValue($context, $var) !== null) {
                ArrayHelper::setValue($context, $var, '***');
            }
        }
        return $context;
    }
}

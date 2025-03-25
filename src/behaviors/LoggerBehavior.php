<?php

namespace liuyuanjun\yii2\behaviors;

use Closure;
use liuyuanjun\yii2\helpers\Utils;
use liuyuanjun\yii2\log\Log;
use Yii;
use yii\base\Behavior;
use yii\base\Controller;
use yii\helpers\Json;

/**
 * LoggerBehavior 记录Api请求日志
 *
 * ```php
 * use liuyuanjun\yii2\behaviors\LoggerBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         'logger' => [
 *              'class' => LoggerBehavior::class,
 *          ]
 *     ];
 * }
 * ```
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class LoggerBehavior extends Behavior
{
    public $logName = 'api';
    public $extInfo = [];
    public $responseMaxLength = 500;

    public function events(): array
    {
        return [Controller::EVENT_AFTER_ACTION => 'writeLog'];
    }

    /**
     * 记录 Api Log
     * @date 2021/8/30 20:47
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function writeLog()
    {
        if (property_exists($this->owner, 'disableLog') && $this->owner->disableLog) return;
        if (!$this->logName) return;
        $req = Yii::$app->request;
        $res = Yii::$app->response;
        if ($req->header->get('content-type') === 'application/xml') {
            $resData = $res->content;
        } else {
            $resData = is_string($res->data) ? str_replace(["\r", "\n"], ' ', $res->data) : Json::encode($res->data);
            if ($this->responseMaxLength > 0 && mb_strlen($resData) > $this->responseMaxLength) {
                $resData = mb_substr($resData, 0, $this->responseMaxLength) . '...';
            }
        }
        $log = array_merge([
            'route' => Yii::$app->requestedRoute,
            'get' => $req->get(),
            'rawBody' => $req->getRawBody(),
            'header' => $req->headers->toArray(),
            'response' => $resData,
            'ip' => Utils::getRealIp() ?? $req->getUserIP() ?? '-',
        ], $this->prepareExtInfo());
        Log::info($log, $this->logName, '{category}');
    }

    /**
     * 准备扩展内容
     * @return array
     * @date 2021/10/21 20:24
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    private function prepareExtInfo(): array
    {
        $log = [];
        foreach ($this->extInfo as $k => $v) {
            $log[$k] = ($v instanceof Closure || (is_array($v) && is_callable($v))) ? $v() : $v;
        }
        return $log;
    }
}

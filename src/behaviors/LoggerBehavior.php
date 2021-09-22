<?php

namespace liuyuanjun\yii2\behaviors;

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
    public $apiLog = 'api';

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
        if (!$this->apiLog) return;
        $req = Yii::$app->request;
        $res = Yii::$app->response;
        $resData = is_string($res->data) ? str_replace(["\r", "\n"], ' ', $res->data) : Json::encode($res->data);
        $log = [
            'ip' => Utils::getRealIp() ?? $req->getUserIP() ?? '-',
            'controller' => Yii::$app->controller->id,
            'action' => Yii::$app->controller->action->id,
            'getParams' => $req->get(),
            'postParams' => $req->post(),
            'response' => mb_strlen($resData) > 500 ? mb_substr($resData, 0, 500) . '...' : $resData,
        ];
        Log::info($log, $this->apiLog, '_d_ymd');
    }
}
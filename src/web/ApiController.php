<?php

namespace liuyuanjun\yii2\extensions\web;

use liuyuanjun\yii2\extensions\log\Log;
use liuyuanjun\yii2\extensions\helpers\Utils;
use Yii;
use yii\helpers\Json;
use yii\log\Logger;
use yii\web\Response;

/**
 * Class ApiController
 * @package liuyuanjun\yii2\extensions\web
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
abstract class ApiController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;
    public $enableApiLog = true;

    public function init()
    {
        parent::init();
        $this->response->getHeaders()->set('X-REQUEST-ID', Utils::requestId());
        $this->response->on(Response::EVENT_AFTER_SEND, [$this, 'writeLog']);
    }

    /**
     * 记录 Api Log
     * @date 2021/8/30 20:47
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function writeLog()
    {
        if (!$this->enableApiLog) return;
        $req = $this->request;
        $res = $this->response;
        $resData = is_array($res->data) ? Json::encode($res->data) : str_replace(["\r", "\n"], ' ', var_export($res->data, true));
        $log = [
            'ip' => Utils::getRealIp() ?? $req->getUserIP() ?? '-',
            'controller' => Yii::$app->controller->id,
            'action' => Yii::$app->controller->action->id,
            'getParams' => $req->get(),
            'postParams' => $req->post(),
            'response' => mb_strlen($resData) > 500 ? mb_substr($resData, 0, 500) . '...' : $resData,
        ];
        Log::info($log, 'api');
    }

    /**
     * @param int $defaultPageSize
     * @return array
     * @date   2021/8/20 17:52
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function getPageParams(int $defaultPageSize = 15): array
    {
        $req = Yii::$app->request;
        $page = intval($req->post('page'));
        $pageSize = intval($req->post('pageSize'));
        if ($page < 1) $page = 1;
        if ($pageSize < 1) $pageSize = $defaultPageSize;
        return [$page, $pageSize];
    }

}

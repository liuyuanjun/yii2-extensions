<?php

namespace liuyuanjun\yii2\extensions\web;

use liuyuanjun\yii2\extensions\log\Log;
use liuyuanjun\yii2\extensions\helpers\Utils;
use Yii;
use yii\helpers\Json;
use yii\web\Response;

/**
 * Trait ApiControllerTrait
 * @package liuyuanjun\yii2\extensions\web
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
trait ApiControllerTrait
{
    public $enableCsrfValidation = false;
    public $enableApiLog = true;
    public static $pageParam = 'page';
    public static $pageSizeParam = 'pageSize';

    public function init()
    {
        parent::init();
        Yii::$app->response->getHeaders()->set('X-REQUEST-ID', Utils::requestId());
        Yii::$app->response->on(Response::EVENT_AFTER_SEND, [$this, 'writeLog']);
    }

    /**
     * 记录 Api Log
     * @date 2021/8/30 20:47
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function writeLog()
    {
        if (!$this->enableApiLog) return;
        $req = Yii::$app->request;
        $res = Yii::$app->response;
        $resData = is_array($res->data) ? Json::encode($res->data) : str_replace(["\r", "\n"], ' ', var_export($res->data, true));
        $log = [
            'ip' => Utils::getRealIp() ?? $req->getUserIP() ?? '-',
            'controller' => Yii::$app->controller->id,
            'action' => Yii::$app->controller->action->id,
            'getParams' => $req->get(),
            'postParams' => $req->post(),
            'response' => mb_strlen($resData) > 500 ? mb_substr($resData, 0, 500) . '...' : $resData,
        ];
        Log::info($log, 'api', '_d_ymd');
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
        $page = intval($req->post(static::$pageParam));
        $pageSize = intval($req->post(static::$pageSizeParam));
        if ($page < 1) $page = 1;
        if ($pageSize < 1) $pageSize = $defaultPageSize;
        return [$page, $pageSize];
    }

}

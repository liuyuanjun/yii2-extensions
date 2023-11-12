<?php

namespace liuyuanjun\yii2\web;

use liuyuanjun\yii2\behaviors\LoggerBehavior;
use Yii;
use yii\web\Controller;

/**
 * Class ApiController
 * @package liuyuanjun\yii2\web
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
abstract class ApiController extends Controller
{
    public $enableCsrfValidation = false;
    public static $pageParam = 'page';
    public static $pageSizeParam = 'pageSize';
    public $disableLog = false; //使用LoggerBehavior时，可设置为true禁用日志记录

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'logger' => [
                'class' => LoggerBehavior::class,
            ]
        ];
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
        $page = intval($req->post(static::$pageParam, 1));
        $pageSize = intval($req->post(static::$pageSizeParam, $defaultPageSize));
        if ($page < 1) $page = 1;
        return [$page, $pageSize];
    }

}

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
    use ApiControllerTrait;

}

<?php

namespace liuyuanjun\yii2\helpers;

use GuzzleHttp\Client;
use liuyuanjun\yii2\log\Log;
use Yii;
use yii\helpers\Json;
use yii\log\Logger;

/**
 * Class DdMsg 钉钉消息通知
 * @package liuyuanjun\yii2\helpers
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class DdMsg
{
    private $_data = [];

    /**
     * 异常
     * @param \Exception $e
     * @return DdMsg
     * @date   2021/6/10 14:15
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function exception(\Exception $e): DdMsg
    {
        $content = "## [" . Yii::$app->name . "][" . YII_ENV . "]异常消息";
        $content .= "\n#### Exception:\n> " . $e;
        return static::markdown('[' . Yii::$app->name . ']异常消息', $content);
    }

    /**
     * 发送文本
     * @param string $content
     * @return DdMsg
     * @date   2021/6/10 14:13
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function text(string $content): DdMsg
    {
        return (new static())->setText($content);
    }

    /**
     * 发送 markdown
     * @param string $title
     * @param array|string $content
     * @return DdMsg
     * @date   2021/6/10 14:11
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function markdown(string $title, $content): DdMsg
    {
        if (is_array($content)) {
            $text = '### ' . $title . "\n";
            foreach ($content as $k => $v) {
                $text .= "\n#### {$k}:\n" . (is_string($v) ? $v : '> ' . Json::encode($v)) . "\n";
            }
        } else {
            $text = $content;
        }
        return (new static())->setMarkdown($title, $text);
    }

    /**
     * 发送链接
     * @param string $title
     * @param string $text
     * @param string $messageUrl
     * @param string $picUrl
     * @return DdMsg
     * @date   2021/6/10 14:16
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function link(string $title, string $text, string $messageUrl, string $picUrl = ''): DdMsg
    {
        return (new static())->setLink($title, $text, $messageUrl, $picUrl);
    }

    /**
     * 设置 markdown
     * @param string $title
     * @param string $text
     * @return $this
     * @date   2021/6/10 11:43
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setMarkdown(string $title, string $text): DdMsg
    {
        $this->_data['msgtype'] = 'markdown';
        $this->_data['markdown'] = [
            'title' => $title,
            'text' => $text,
        ];
        return $this;
    }

    /**
     * 设置链接
     * @param string $title
     * @param string $text
     * @param string $messageUrl
     * @param string $picUrl
     * @return $this
     * @date   2021/6/10 11:43
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setLink(string $title, string $text, string $messageUrl, string $picUrl = ''): DdMsg
    {
        $this->_data['msgtype'] = 'link';
        $this->_data['link'] = [
            'title' => $title,
            'text' => $text,
            'messageUrl' => $messageUrl,
            'picUrl' => $picUrl
        ];
        return $this;
    }

    /**
     * 设置文本信息
     * @param string $content
     * @return $this
     * @date   2021/6/10 11:41
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setText(string $content): DdMsg
    {
        $this->_data['msgtype'] = 'text';
        $this->_data['text'] = ['content' => $content,];
        return $this;
    }

    /**
     * 发送工作通知
     * @param array|string $ddUserIds
     * @return mixed
     * @date   2021/6/10 14:20
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function sendCorpNotice($ddUserIds)
    {
        return Yii::$app->dd->sendCorpMsg($ddUserIds, $this->_data);
    }

    /**
     * 发送群机器人消息
     * @param string $accessToken
     * @return false|mixed|null
     * @date 2021/8/31 11:33
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function sendRobotMsg(string $accessToken)
    {
        $webHook = 'https://oapi.dingtalk.com/robot/send?access_token=' . $accessToken;
        $client = new Client();
        $log = ['params' => $this->_data, ['accessToken' => $accessToken]];
        try {
            $res = $client->post($webHook, ['json' => $this->_data]);
            $stringBody = (string)$res->getBody();
            $log['response'] = $stringBody;
            $result = $stringBody ? Json::decode($stringBody) : false;
        } catch (\Exception | \Throwable $e) {
            $log = $log + ['errCode' => $e->getCode(), 'errMsg' => $e->getMessage(), 'errTrace' => $e->getTraceAsString()];
            $result = false;
        }
        Log::json($log, $result === false ? Logger::LEVEL_ERROR : Logger::LEVEL_INFO, 'dd_send_robot_msg');
        return $result;
    }

}

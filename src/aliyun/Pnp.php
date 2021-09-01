<?php

namespace liuyuanjun\yii2\extensions\aliyun;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Dyplsapi\V20170525\BindAxb;
use AlibabaCloud\Dyplsapi\V20170525\DyplsapiApiResolver;
use AlibabaCloud\Dyplsapi\V20170525\QueryRecordFileDownloadUrl;
use common\constants\Codes;
use common\helpers\Utils;
use yii\base\Component;
use Yii;
use yii\base\Exception;

/**
 * Class Pnp
 * @package common\components\aliyun
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 * @link    https://help.aliyun.com/document_detail/109196.html?spm=a2c4g.11186623.6.576.1ee316edrT3C30
 */
class Pnp extends Component
{
    public $accessKeyId;
    public $accessKeySecret;
    public $poolKey; //号池

    /**
     * Init
     * @throws Exception
     * @throws ClientException
     * @date   2021/3/12 19:16
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function init()
    {
//        if (is_null($this->accessKeyId)) $this->accessKeyId = conf('oss.accessKeyId');
//        if (is_null($this->accessKeySecret)) $this->accessKeySecret = conf('oss.accessKeySecret');
        if (empty($this->accessKeyId) || empty($this->accessKeySecret)) throw new Exception('配置错误！');
        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)->asDefaultClient();
    }


    /**
     * AXB绑定
     * @param string $phoneNoA   15000000000  AXB中的A号码  A号码可设置为手机号码或固定电话，固定电话需要加区号，区号和号码中间不需要加连字符，例如057188992688
     * @param string $phoneNoB
     * @param string $expiration 2019-09-05 12:00:00 绑定关系的过期时间。必须晚于当前时间1分钟以上
     * @param string $poolKey
     * @return array
     * @throws ClientException
     * @throws Exception
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @date   2021/4/14 14:06
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function bindAxb(string $phoneNoA, string $phoneNoB, string $expiration, string $poolKey = ''): array
    {
        $poolKey = $poolKey ?: $this->poolKey;
        if (empty($poolKey)) throw new Exception('没有指定号池');
        $request  = $this->getResolver()->bindAxb([
            'query' => [
                'Expiration'         => $expiration,
                'PhoneNoA'           => $phoneNoA,
                'PhoneNoB'           => $phoneNoB,
                'PoolKey'            => $poolKey,
                'IsRecordingEnabled' => true,
            ]
        ])->format('JSON');
        $response = $request->request();
        /**
         * array:4 [
         * "SecretBindDTO" => array:3 [
         * "Extension" => "15110257109"
         * "SecretNo" => "17097537449"
         * "SubsId" => "1000031810654586"
         * ]
         * "RequestId" => "B3A402C6-B7DB-43EB-99C6-24EE11B5BF8D"
         * "Message" => "OK"
         * "Code" => "OK"
         * ]
         */
        $result = $response->toArray();
        Utils::jsonLog('call_bind_axb', [
            'Expiration'         => $expiration,
            'PhoneNoA'           => $phoneNoA,
            'PhoneNoB'           => $phoneNoB,
            'PoolKey'            => $poolKey,
            'IsRecordingEnabled' => true,
            'Response'           => $result,
        ]);
        if ($result['Code'] !== 'OK')
            throw new Exception('隐私号码绑定失败。' . $result['Code'] . ':' . $result['Message'],
                $result['Code'] == 'isv.NO_AVAILABLE_NUMBER' ? Codes::CALL_PNP_NO_AVAILABLE_NUMBER : Codes::CALL_PNP_BIND_FAIL);
        return $result;
    }

    /**
     * 解除号码绑定关系
     * @param string $secretNo
     * @param string $subsId
     * @param string $poolKey
     * @return array
     * @throws ClientException
     * @throws Exception
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @date   2021/4/20 17:15
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function unbindSubscription(string $secretNo, string $subsId, string $poolKey = ''): array
    {
        $poolKey = $poolKey ?: $this->poolKey;
        if (empty($poolKey)) throw new Exception('没有指定号池');
        $request  = $this->getResolver()->unbindSubscription([
            'query' => [
                'SecretNo' => $secretNo,
                'SubsId'   => $subsId,
                'PoolKey'  => $poolKey,
            ]
        ])->format('JSON');
        $response = $request->request();
        $result   = $response->toArray();
        Utils::jsonLog('call_unbind_subscription', [
            'SecretNo' => $secretNo,
            'SubsId'   => $subsId,
            'PoolKey'  => $poolKey,
            'Response' => $result,
        ]);
        if ($result['Code'] !== 'OK') throw new Exception('解除号码绑定关系失败。' . $result['Code'] . ':' . $result['Message']);
        return $result;
    }

    /**
     * 查询号码绑定关系
     * @param string $secretNo
     * @param string $subsId
     * @param string $poolKey
     * @return array
     * @throws ClientException
     * @throws Exception
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @date   2021/8/17 14:59
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function querySubscriptionDetail(string $secretNo, string $subsId, string $poolKey = ''): array
    {
        $poolKey = $poolKey ?: $this->poolKey;
        if (empty($poolKey)) throw new Exception('没有指定号池');
        $request  = $this->getResolver()->querySubscriptionDetail([
            'query' => [
                'PhoneNoX' => $secretNo,
                'SubsId'   => $subsId,
                'PoolKey'  => $poolKey,
            ]
        ])->format('JSON');
        $response = $request->request();
        $result   = $response->toArray();
        if ($result['Code'] !== 'OK') throw new Exception('查询号码绑定关系失败。' . $result['Code'] . ':' . $result['Message']);
        return $result['SecretBindDetailDTO'];
    }

    /**
     * 获取录音文件下载地址
     * @param string $callId
     * @param string $callTime 必填，但貌似随便填个时间就行
     * @param string $poolKey
     * @return QueryRecordFileDownloadUrl
     * @throws ClientException
     * @throws Exception
     * @date   2021/3/16 09:56
     * @author Yuanjun.Liu <6879391@qq.com>
     * @link   https://help.aliyun.com/document_detail/110264.html?spm=a2c4g.11186623.6.591.301c11df6iwagd
     */
    public function queryRecordFileDownloadUrl(string $callId, string $callTime, string $poolKey = ''): QueryRecordFileDownloadUrl
    {
        $poolKey = $poolKey ?: $this->poolKey;
        if (empty($poolKey)) throw new Exception('没有指定号池');
        return $this->getResolver()->queryRecordFileDownloadUrl()->format('JSON')->withPoolKey($poolKey)
            ->withCallId($callId)->withCallTime($callTime);
    }


    /**
     * @return DyplsapiApiResolver
     * @date   2021/3/12 19:09
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getResolver(): DyplsapiApiResolver
    {
        return AlibabaCloud::dyplsapi()->v20170525();
    }
}
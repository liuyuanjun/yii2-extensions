<?php

namespace liuyuanjun\yii2\aliyun;

use yii\base\Component;
use yii\base\Exception;

/**
 * 阿里大鱼短信发送类
 *
 * @author liuyuanjun <6879391@qq.com>
 */
class Sms extends Component
{
    /**
     * @var string Access Key Id
     */
    public $accessKeyId;
    /**
     * @var string Access Key Secret
     */
    public $accessKeySecret;
    /**
     * @var string 签名
     */
    public $signName;
    /**
     * @var string 模板代码
     */
    public $templateCode;
    /**
     * @var string 接口URL
     */
    public $apiUrl = 'https://dysmsapi.aliyuncs.com';
    /**
     * @var array 参数
     */
    protected $_params = [];

    /**
     * @throws Exception
     */
    public function init()
    {
        foreach (['accessKeyId', 'accessKeySecret', 'apiUrl'] as $name) {
            if (!$this->$name) {
                throw new Exception('[DYSMS]没有找到短信发送配置。 [' . $name . ']');
            }
        }
    }

    /**
     * 设置模板返回新实例
     *
     * @param string $code
     * @param null|string $signName
     * @return static
     * @throws Exception
     */
    public function tpl($code, $signName = null)
    {
        if (!$this->templateCode || $this->templateCode === $code) {
            $this->templateCode = $code;
            return $this;
        }
        $cloned = clone $this;
        $cloned->templateCode = $code;
        if ($signName) {
            $cloned->signName = $signName;
        }
        if (!$cloned->signName) {
            throw new Exception('[DYSMS]没有设置签名。');
        }
        return $cloned;
    }

    /**
     * template 别名
     *
     * @param string $code
     * @param null|string $signName
     * @return static
     * @throws Exception
     */
    public function template($code, $signName = null)
    {
        return $this->tpl($code, $signName);
    }

    /**
     * 发送短信
     *
     * @param string $number
     * @param array $param
     * @param string $outId
     * @return bool|mixed
     * @throws Exception
     */
    public function send($number = '', $param = [], $outId = '')
    {
        $number && $this->_params['PhoneNumbers'] = $number;
        $param && $this->_params['TemplateParam'] = $param;
        $outId && $this->_params['OutId'] = $outId;
        return $this->request();
    }

    /**
     * 设置发送号码
     *
     * @param $number string 发送号码
     * @return $this
     */
    public function to($number)
    {
        $this->_params['PhoneNumbers'] = $number;
        return $this;
    }

    /**
     * 设置模板参数
     *
     * @param $param array 模板参数
     * @return $this
     */
    public function param($param)
    {
        $this->_params['TemplateParam'] = $param;
        return $this;
    }

    /**
     * 设置 outId
     *
     * @param $outId string
     * @return $this
     */
    public function outId($outId)
    {
        $this->_params['OutId'] = $outId;
        return $this;
    }

    /**
     * 重置参数
     *
     * @return $this
     */
    public function resetParams()
    {
        $this->_params = [];
        return $this;
    }

    /**
     * 请求
     *
     * @return mixed
     * @throws Exception
     */
    protected function request()
    {
        $apiParams = $this->processParams();
        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }
        $stringToSign = "GET&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));
        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $this->accessKeySecret . "&", true));
        $signature = $this->encode($sign);
        $url = $this->apiUrl . "?Signature={$signature}{$sortedQueryStringTmp}";
        $content = $this->fetchContent($url);
        return json_decode($content, true);
    }

    /**
     * 处理参数
     *
     * @return array
     * @throws Exception
     */
    protected function processParams()
    {
        $params = $this->_params;
        if (empty($params['PhoneNumbers'])) {
            throw new Exception('[DYSMS]电话号码必须指定');
        }
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        $apiParams = array_merge(array(
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0, 0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $this->accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
            "SignName" => $this->signName,
            "TemplateCode" => $this->templateCode,
        ), $params);
        ksort($apiParams);
        return $apiParams;
    }

    /**
     * 编码
     *
     * @param string $str
     * @return null|string|string[]
     */
    protected function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    /**
     * CURL 请求数据
     *
     * @param string $url
     * @return mixed
     * @throws Exception
     */
    protected function fetchContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $rtn = curl_exec($ch);
        if ($rtn === false) {
            throw new Exception('[DYSMS][CURL_' . curl_errno($ch) . ']: ' . curl_error($ch));
        }
        curl_close($ch);

        return $rtn;
    }
}

<?php

namespace liuyuanjun\yii2\extensions\aliyun\oss;

use OSS\OssClient;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Oss  阿里OSS Yii2 组件
 * @package common\components
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class Oss extends Component
{
    public    $accessKeyId;
    public    $accessKeySecret;
    public    $endpoint;
    public    $bucket;
    public    $cdnUrlPrefix;
    public    $dir = '';//目录
    protected $_ossClient;

    /**
     * @return OssClient
     * @throws Exception|\OSS\Core\OssException
     */
    public function getOssClient(): OssClient
    {
        if ($this->_ossClient === null) {
            if (!$this->accessKeyId || !$this->accessKeySecret || !$this->endpoint) {
                throw new Exception('OSS配置参数缺失');
            }
            $this->_ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
        }
        return $this->_ossClient;
    }

    /**
     * 设置bucket
     * @param string $bucket
     * @return $this
     */
    public function bucket(string $bucket): Oss
    {
        if (!$this->bucket || $this->bucket === $bucket) {
            $this->bucket = $bucket;
            return $this;
        }
        $cloned         = clone $this;
        $cloned->bucket = $bucket;
        return $cloned;
    }

    /**
     * 根据配置返回CDN URL
     * @param string $object
     * @return string
     */
    public function cdnUrl(string $object): string
    {
        $object = trim($object);
        return $object ? rtrim($this->cdnUrlPrefix, '/') . '/' . ltrim($this->jointDir($object), '/') : '';
    }

    /**
     * 提取Url里的 object name
     * @param string $url
     * @param bool $strict 只有比对相同Url前缀的才返回
     * @return string
     */
    public function getObjectNameFromUrl(string $url, bool $strict = true)
    {
        $parseUrl = parse_url($url);
        if ($strict && $parseUrl['host'] != parse_url($this->cdnUrlPrefix)['host']) {
            return false;
        }
        return ltrim($parseUrl['path'], '/');
    }

    /**
     * 给对象加目录前缀
     * @param string $object
     * @return string
     * @date   2021/7/20 18:41
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function jointDir(string $object): string
    {
        return $this->dir ? $this->dir . '/' . ltrim($object, '/') : $object;
    }

    /**
     * 字符串上传
     *
     * @param string $object
     * @param string $content
     * @param array|null $options
     * @return mixed
     * @throws Exception|\OSS\Core\OssException
     */
    public function put(string $object, string $content, array $options = NULL)
    {
        return $this->getOssClient()->putObject($this->bucket, $this->jointDir($object), $content, $options);
    }

    /**
     * 下载
     * @param string $object
     * @param string|array|null $optionsOrFilePath
     * @return string
     * @throws Exception|\OSS\Core\OssException
     */
    public function get(string $object, $optionsOrFilePath = NULL)
    {
        $options = is_string($optionsOrFilePath) ? [OssClient::OSS_FILE_DOWNLOAD => $optionsOrFilePath] : $optionsOrFilePath;
        return $this->getOssClient()->getObject($this->bucket, $this->jointDir($object), $options);
    }

    /**
     * 删除
     * @param string $object
     * @param array|null $options
     * @return string
     * @throws Exception|\OSS\Core\OssException
     */
    /**
     * @param string $object
     * @param null $options
     * @return null
     * @throws Exception
     * @throws \OSS\Core\OssException
     * @date 2021/8/26 17:19
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function delete(string $object, $options = NULL)
    {
        return $this->getOssClient()->deleteObject($this->bucket, $this->jointDir($object), $options);
    }

    /**
     * 文件上传
     *
     * @param string     $object
     * @param string     $filePath
     * @param null|array $options
     * @return mixed
     * @throws Exception
     * @throws \OSS\Core\OssException
     */
    public function upload($object, $filePath, $options = NULL)
    {
        return $this->getOssClient()->uploadFile($this->bucket, $this->jointDir($object), $filePath, $options);
    }

    /**
     * 拷贝
     *
     * @param string     $fromObject
     * @param string     $toBucket
     * @param string     $toObject
     * @param null|array $options
     * @return mixed
     * @throws Exception
     * @throws \OSS\Core\OssException
     */
    public function copy($fromObject, $toBucket, $toObject, $options = NULL)
    {
        return $this->getOssClient()->copyObject($this->bucket, $fromObject, $toBucket, $this->jointDir($toObject), $options);
    }

    /**
     * 判断是否存在
     * @param string     $object
     * @param null|array $options
     * @return bool
     * @throws Exception
     */
    public function isExists($object, $options = NULL)
    {
        return $this->getOssClient()->doesObjectExist($this->bucket, $this->jointDir($object), $options);
    }

}

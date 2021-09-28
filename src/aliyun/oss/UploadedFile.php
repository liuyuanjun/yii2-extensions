<?php

namespace liuyuanjun\yii2\aliyun\oss;

use Yii;

/**
 * Class UploadedFile 上传文件
 * @package liuyuanjun\yii2\helpers
 *
 * @property Oss $oss
 * @property-read string $errorText 错误信息 This property is read-only.
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class UploadedFile extends \yii\web\UploadedFile
{
    protected $_oss;
    public $defaultOssConnectionId = 'oss';

    const UPLOAD_ERR_ENUM = [
        UPLOAD_ERR_OK => '文件上传成功',
        UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
        UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
    ];

    /**
     * 设置OSS
     * @param Oss $oss
     * @return $this
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setOss(Oss $oss): UploadedFile
    {
        $this->_oss = $oss;
        return $this;
    }

    /**
     * 获取OSS
     * @return Oss
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getOss(): Oss
    {
        return $this->_oss ?: Yii::$app->get($this->defaultOssConnectionId);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            $result = $this->getOss()->upload($file, $this->tempName);
            $deleteTempFile && @unlink($this->tempName);
            return $result;
        }
        return false;
    }

    /**
     * 错误信息文本
     * @return string
     * @date   2021/4/6 15:31
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getErrorText(): string
    {
        return self::UPLOAD_ERR_ENUM[$this->error] ?? 'Unknown Err ' . $this->error . '.';
    }

}
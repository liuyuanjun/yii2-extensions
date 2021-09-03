<?php

namespace liuyuanjun\yii2\extensions\helpers;

use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\HttpException;

/**
 * Class Convert
 * @package liuyuanjun\yii2\extensions\helpers
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class Convert
{

    /**
     * 把数字转化成字母,计算Excel列用
     * @param int $num
     * @return bool|string
     * @author liuyuanjun
     */
    public static function num2Letter(int $num)
    {
        $num = intval($num);
        if ($num <= 0)
            return false;
        $letterArr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $letter = '';
        do {
            $key = ($num - 1) % 26;
            $letter = $letterArr[$key] . $letter;
            $num = floor(($num - $key) / 26);
        } while ($num > 0);
        return $letter;
    }

    /**
     * 把字母转化成数字,计算Excel列用
     * @param string $letter
     * @return int
     * @author liuyuanjun
     */
    public static function letterToNum(string $letter)
    {
        $letter = strtolower($letter);
        $array = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        $len = strlen($letter);
        $num = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = array_search($letter[$i], $array);
            $num += ($index + 1) * pow(26, $len - $i - 1);
        }
        return $num;
    }

    /**
     * 异常转数组
     * @param \Exception $exception
     * @return array
     */
    public static function exceptionToArray($exception)
    {
        $array = [
            'name' => ($exception instanceof Exception || $exception instanceof ErrorException) ? $exception->getName() : 'Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];
        if ($exception instanceof HttpException) {
            $array['status'] = $exception->statusCode;
        }
        if (YII_DEBUG) {
            $array['type'] = get_class($exception);
            if (!$exception instanceof UserException) {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
                $array['stack-trace'] = explode("\n", $exception->getTraceAsString());
                if ($exception instanceof \yii\db\Exception) {
                    $array['error-info'] = $exception->errorInfo;
                }
            }
        }
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = static::exceptionToArray($prev);
        }
        return $array;
    }


    /**
     * 数组转xml
     * @param array $array
     * @return string
     */
    public static function arrayToXml($array)
    {
        if (!is_array($array) || count($array) == 0) return '';
        $xml = "<xml>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * Xml转数组
     * @param string $xml
     * @return array|string
     */
    public static function xmlToArray(string $xml)
    {
        if ($xml == '') return '';
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

}

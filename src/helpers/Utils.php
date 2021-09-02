<?php

namespace liuyuanjun\yii2\extensions\helpers;

use Faker\Provider\Uuid;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\web\HttpException;
use yii\web\Request;

/**
 * Class Utils
 *
 * @author liuyuanjun
 */
class Utils
{
    protected static $_requestId;

    /**
     * 获取requestId
     * 如果请求中携带则使用携带的，如果没有就生成
     *
     * @param string|null $requestId
     * @return string
     */
    public static function requestId(string $requestId = null): string
    {
        if ($requestId) {
            static::$_requestId = $requestId;
        } elseif (!static::$_requestId) {
            $request = \Yii::$app->getRequest();
            if ($request instanceof Request) {
                static::$_requestId = $request->headers->get('X-REQUEST-ID', null);
            }
            if (!static::$_requestId) {
                static::$_requestId = Uuid::uuid();
            }
        }
        return static::$_requestId;
    }

    /**
     * 生成 或者 验证一个token
     * 仅仅简单的验证一下UA是否有变化
     *
     * @param string|null $token
     * @return bool|string
     */
    public static function accessToken(string $token = null)
    {
        $ua = \Yii::$app->request->getUserAgent();
        if ($token) {
            if ($tokenDecoded = base64_decode($token)) {
                if (explode('-', $tokenDecoded, 2)[0] == substr(md5($ua), 8, 16)) {
                    return true;
                }
            }
            return false;
        }
        return base64_encode(substr(md5($ua), 8, 16) . '-' . Uuid::uuid());
    }

    /**
     * 获取IP
     * @return string
     */
    public static function getRealIp()
    {
        $ip = FALSE;
        //客户端IP 或 NONE
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match('/^((192\.168|172\.([1][6-9]|[2]\d|3[01]))(\.([2][0-4]\d|[2][5][0-5]|[01]?\d?\d)){2}|10(\.([2][0-4]\d|[2][5][0-5]|[01]?\d?\d)){3})$/', $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        //客户端IP 或 (最后一个)代理服务器 IP
        return $ip ?: ($_SERVER['REMOTE_ADDR'] ?? null);
    }

    /**
     * 获取url中的域名部分
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public static function getDomain(string $url): string
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl) {
            throw new \Exception('invalid url ' . $url);
        }
        $array = explode('.', $parsedUrl['host']);
        $suffix = array_pop($array);
        $main = array_pop($array);
        if (in_array($suffix, ['cn', 'hk']) && in_array($main, ['com', 'net', 'gov', 'org', 'edu'])) {
            $suffix = $main . '.' . $suffix;
            $main = array_pop($array);
        }
        return $main . '.' . $suffix;
    }

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

    /**
     * 服务器IP
     * @return string
     */
    public static function getServerIp(): string
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $ip = gethostbyname($_SERVER['SERVER_NAME']);
        } else {
            // for php-cli(phpunit etc.)
            $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }

    /**
     * 判断移动设备
     * @return bool
     * @date   2020/12/3 10:54
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function isMobile(): bool
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? "");
        $mobileAgents = ['mobile', 'nokia', 'iphone', 'ipad', 'android', 'samsung', 'htc', 'blackberry'];

        return str_replace($mobileAgents, '', $agent) != $agent;
    }

    /**
     * 判断CLI
     * @return bool
     * @date   2020/12/3 10:54
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function isCli(): bool
    {
        return (bool)preg_match("/cli/i", php_sapi_name());
    }

    public static function stdOut(string $string, $arg1 = BaseConsole::FG_GREY)
    {
        if (!Utils::isCli()) return null;
        $args = func_get_args();
        array_shift($args);
        $string = '[' . (new \DateTime())->format('H:i:s.u') . '] ' . $string . PHP_EOL;
        if (Console::streamSupportsAnsiColors(\STDOUT)) {
            $string = Console::ansiFormat($string, $args);
        }
        return Console::stdout($string);
    }

    public static function stdErr(string $string, $arg1 = BaseConsole::FG_RED)
    {
        if (!Utils::isCli()) return null;
        $args = func_get_args();
        array_shift($args);
        $string = '[' . (new \DateTime())->format('H:i:s.u') . '] ' . $string . PHP_EOL;
        if (Console::streamSupportsAnsiColors(\STDERR)) {
            $string = Console::ansiFormat($string, $args);
        }
        return fwrite(\STDERR, $string);
    }

    /**
     * redis锁
     * @param string $lockKey
     * @param int $lockTime
     * @return bool
     * @date   2021/2/24 18:02
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function redisLock(string $lockKey, int $lockTime): bool
    {
        return (bool)redis()->set('_o2jd8H_:lyj:ext:redis:lock:' . $lockKey, 1, 'EX', $lockTime, 'NX');
    }

    /**
     * redis解锁
     * @param string $lockKey
     * @return mixed
     * @date   2021/2/24 18:07
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function redisUnlock(string $lockKey)
    {
        return redis()->del('_o2jd8H_:lyj:ext:redis:lock:' . $lockKey);
    }

    /**
     * 判断是否JSON
     * @param string $str
     * @return bool
     * @date   2021/1/27 17:11
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function isJson(string $str): bool
    {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * utf8 按字节截字符串，防乱码
     * @param string $string
     * @param int $offset
     * @param int $length
     * @return string
     * @date   2021/4/22 11:23
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function subStr(string $string, int $offset, int $length): string
    {
        $returnStr = '';
        $i = 0;
        $n = 0;
        $strLen = strlen($string);
        while ($n < $length && $i <= $strLen) {
            $tempStr = substr($string, $i, 1);
            $ascNum = Ord($tempStr);//得到字符串中第$i位字符的ascii码
            if ($ascNum >= 224) {    //如果ASCII位高与224，
                if ($i >= $offset) {
                    $returnStr = $returnStr . substr($string, $i, 3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
                    $n += 3;            //字串长度
                }
                $i += 3;            //实际Byte计为3
            } elseif ($ascNum >= 192) { //如果ASCII位高与192，
                if ($i >= $offset) {
                    $returnStr = $returnStr . substr($string, $i, 2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                    $n += 2;            //字串长度
                }
                $i += 2;            //实际Byte计为2
            } elseif ($ascNum >= 65 && $ascNum <= 90) {//如果是大写字母，
                if ($i >= $offset) {
                    $returnStr = $returnStr . substr($string, $i, 1);
                    $n++;
                }
                $i++;            //实际的Byte数仍计1个
            } else {             //其他情况下，包括小写字母和半角标点符号，
                if ($i >= $offset) {
                    $returnStr = $returnStr . substr($string, $i, 1);
                    $n++;
                }
                $i++;            //实际的Byte数计1个
            }
        }
        return $returnStr;
    }

}

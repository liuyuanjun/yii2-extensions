<?php

namespace liuyuanjun\yii2\extensions\helpers;

use Faker\Provider\Uuid;
use Yii;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\web\Request;

/**
 * Class Utils
 *
 * @author liuyuanjun
 */
class Utils
{
    protected static $_requestIdHeaderKey = 'X-REQUEST-ID';
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
            static::$_requestId = ($request = \Yii::$app->getRequest()) instanceof Request ? $request->headers->get(self::$_requestIdHeaderKey, Uuid::uuid()) : Uuid::uuid();
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
            return ($tokenDecoded = base64_decode($token)) && (explode('-', $tokenDecoded, 2)[0] == substr(md5($ua), 8, 16));
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
        return (bool)redis()->set('lyj:lock:' . $lockKey, 1, 'EX', $lockTime, 'NX');
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
        return redis()->del('lyj:lock:' . $lockKey);
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
    public static function utf8SubStr(string $string, int $offset, int $length): string
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

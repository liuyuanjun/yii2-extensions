<?php

namespace liuyuanjun\yii2\extensions\helpers;


/**
 * Class Format
 * @package liuyuanjun\yii2\extensions\helpers
 *
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class Fmt
{

    const F_DAY = 1;
    const F_HOUR = 2;
    const F_MINUTE = 4;
    const F_SECOND = 8;

    /**
     * 格式化时长
     * @param int $seconds
     * @param int $format
     * @return string
     * @date 2021/8/30 17:57
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function duration(int $seconds, int $format = self::F_DAY | self::F_HOUR | self::F_MINUTE | self::F_SECOND): string
    {
        $units = [self::F_DAY => [86400, '天'], self::F_HOUR => [3600, '小时'], self::F_MINUTE => [60, '分'], self::F_SECOND => [1, '秒']];
        $str = '';
        foreach ($units as $k => $v) {
            if (!($format & $k)) continue;
            if ($format == $k) {
                if (($val = round($seconds / $v[0])) || !$str) $str .= $val . $v[1];
                break;
            } elseif ($seconds) {
                if ($val = floor($seconds / $v[0])) $str .= $val . $v[1];
                $seconds = $seconds % $v[0];
            }
            $format -= $k;
        }
        return $str;
    }

    /**
     * 格式化文件大小显示
     * @param int $size
     * @return string
     */
    public static function size(int $size): string
    {
        $prec = 3;
        $size = round(abs($size));
        $units = [
            0 => " B",
            1 => " KB",
            2 => " MB",
            3 => " GB",
            4 => " TB"
        ];
        if ($size == 0) {
            return str_repeat(" ", $prec) . "0$units[0]";
        }
        $unit = min(4, floor(log($size) / log(2) / 10));
        $size = $size * pow(2, -10 * $unit);
        $digi = $prec - 1 - floor(log($size) / log(10));
        $size = round($size * pow(10, $digi)) * pow(10, -$digi);
        return $size . $units[$unit];
    }

    /**
     * 格式化电话号 返回11位手机号
     * @param string $phoneNumber
     * @return false|string
     * @date   2021/3/15 19:05
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function phoneNumber(string $phoneNumber)
    {
        $pattern = '/(086|86|\\+86)?-?(1[0-9]{10})/';
        if (preg_match($pattern, $phoneNumber, $matches)) {
            return $matches[2];
        }
        return $phoneNumber;
    }

}

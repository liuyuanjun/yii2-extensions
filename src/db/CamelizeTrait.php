<?php

namespace liuyuanjun\yii2\db;

use yii\helpers\Inflector;

/**
 * 驼峰转换 用于 Model
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
trait CamelizeTrait
{
    /**
     * 驼峰规则
     * ```
     * return ['id' => 'orderId'];
     * ```
     * @return string[]
     * @date   2021/10/25 15:48
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function camelizeRules(): array
    {
        return [];
    }

    /**
     * 获取驼峰化属性名
     * @param array $names
     * @param array $except
     * @return array
     * @author Yuanjun.Liu <6879391@qq.com>
     * @time 2023/12/22 16:36
     */
    public function camelCaseAttributes($names = [], $except = []): array
    {
        if (empty($names)) {
            $names = $this->attributes();
        }
        $camelCaseNames = array_flip($names);
        foreach ($except as $name) {
            unset($camelCaseNames[$name]);
        }
        $rules = static::camelizeRules();
        foreach ($camelCaseNames as $name => &$value) {
            $value          = $rules[$name] ?? lcfirst(Inflector::camelize($name));
        }
        return $camelCaseNames;
    }

    /**
     * 获取驼峰化属性
     * @param array $names
     * @param array $except
     * @return array
     * @date   2021/10/25 15:40
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getCamelizeAttributes($names = [], $except = [])
    {
        $values = [];
        if (empty($names)) {
            $names = $this->attributes();
        }
        $flipNames = array_flip($names);
        foreach ($except as $name) {
            unset($flipNames[$name]);
        }
        $names = array_flip($flipNames);
        $rules = static::camelizeRules();
        foreach ($names as $name) {
            $camelizeName          = $rules[$name] ?? lcfirst(Inflector::camelize($name));
            $values[$camelizeName] = $this->$name;
        }
        return $values;
    }


    /**
     * 设置驼峰属性
     * @param      $values
     * @param bool $safeOnly
     * @date   2021/10/25 15:45
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setCamelizeAttributes($values, $safeOnly = true)
    {
        $rules     = array_flip(static::camelizeRules());
        $newValues = [];
        foreach ($values as $name => $value) {
            $realName             = $rules[$name] ?? Inflector::underscore($name);
            $newValues[$realName] = $value;
        }
        $this->setAttributes($newValues, $safeOnly);
    }

}

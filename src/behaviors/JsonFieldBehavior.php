<?php

namespace liuyuanjun\yii2\behaviors;

use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * JsonFieldBehavior JSON字段 数组、Json字符串 自动转
 *
 * ```php
 * use liuyuanjun\yii2\behaviors\JsonFieldBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *          [
 *              'class' => JsonFieldBehavior::class,
 *              'fields' => [
 *                  'foo' => [
 *                      'jsonOptions' => JSON_UNESCAPED_UNICODE,
 *                      'defaultValue' => '{}',
 *                      'asArray' => true,
 *                   ]
 *               ],
 *               'defaultAsArray' => true,
 *          ]
 *     ];
 * }
 * ```
 * @author Yuanjun.Liu <6879391@qq.com>
 */
class JsonFieldBehavior extends Behavior
{
    public $fields = [];
    public $defaultJsonOptions = JSON_UNESCAPED_UNICODE;
    public $defaultAsArray = true;

    public function events(): array
    {
        return [
            BaseActiveRecord::EVENT_AFTER_FIND => 'jsonToArray',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'jsonToArray',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'jsonToArray',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'arrayToJson',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'arrayToJson',
        ];
    }

    /**
     * Json转数组
     * @date 2021/10/29 16:29
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function jsonToArray()
    {
        foreach ($this->fields as $field => $options) {
            $value = $this->owner->$field;
            if ($value === null && !isset($options['defaultValue'])) {
                continue;
            }
            $this->owner->$field = json_decode($value ?: ($options['defaultValue'] ?? '{}'), $options['asArray'] ?? $this->defaultAsArray);
        }
    }

    /**
     * 数组转Json
     * @date 2021/10/29 16:45
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function arrayToJson()
    {
        foreach ($this->fields as $field => $options) {
            $value = $this->owner->$field;
            if (is_array($value)) {
                $value = json_encode($value, $options['jsonOptions'] ?? $this->defaultJsonOptions);
            } elseif (empty($value) && isset($options['defaultValue'])) {
                $value = $options['defaultValue'];
            }
            $this->owner->$field = $value;
        }
    }

}

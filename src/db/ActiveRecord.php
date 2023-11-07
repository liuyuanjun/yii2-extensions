<?php

namespace liuyuanjun\yii2\db;


use liuyuanjun\yii2\softdelete\SoftDeleteTrait;

/**
 * Active Record
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    /*
    use SoftDeleteTrait, UtilityTrait {
        UtilityTrait::find insteadof SoftDeleteTrait;
    }
    */
    use SoftDeleteTrait, UtilityTrait;

    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }
}

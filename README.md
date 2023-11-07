# Yii2 扩展
> Yuanjun.Liu 自用，并没有很好的封装

## 软删
#### Usage:
```php
<?php
use liuyuanjun\yii2\softdelete\SoftDeleteTrait;

class ActiveRecord extends \yii\db\ActiveRecord
{
    use SoftDeleteTrait;
}
```

## DB扩展方法
> ActiveRecord::firstOrNew 查找单条或新建model 不保存

> ActiveRecord::firstOrCreate 查找或创建 保存

> ActiveRecord::insertUpdate 新增冲突则更新

> ActiveRecord::batchInsert 批量新增

> ActiveRecord::batchUpdate 批量更新

> ActiveQuery::andLikeWhere like查询条件生成

> ActiveQuery::page 按页返回结果列表
#### Usage:
```php
<?php
use liuyuanjun\yii2\softdelete\SoftDeleteTrait;
use liuyuanjun\yii2\db\UtilityTrait;

class ActiveRecord extends \yii\db\ActiveRecord
{
    use SoftDeleteTrait, UtilityTrait {
        UtilityTrait::find insteadof SoftDeleteTrait;
    }
}
```
## JSON日志
- 日志使用JSON形式存储，每行一条
- 日志按照category拆分成不同文件保存

## Aliyun组件

## Helpers
- 发送钉钉消息
- 格式化时间、文件大小
- ...
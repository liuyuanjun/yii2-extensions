<?php

namespace liuyuanjun\yii2\db;

use Yii;
use yii\db\ExpressionInterface;

/**
 * ActiveRecord 扩展
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
trait UtilityTrait
{
    /**
     * {@inheritdoc}
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    /*
    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }
    */

    /**
     * 错误字符串
     * @param bool $showAllErrors
     * @return string
     * @date   2021/8/20 20:15
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getErrorStr(bool $showAllErrors = false): string
    {
        if ($showAllErrors) {
            return implode("\n ", $this->getErrorSummary(true));
        } else {
            return !empty($errors = $this->getErrors()) && !empty($error = reset($errors)) ? reset($error) : '';
        }
    }

    /**
     * first or create
     * @param array $conditions
     * @param array $attributes
     * @param bool $runValidation
     * @return UtilityTrait
     * @date 2021/9/1 16:00
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function firstOrCreate(array $conditions, array $attributes, bool $runValidation = true)
    {
        $model = static::firstOrNew($conditions, $attributes);
        if ($model->isNewRecord) {
            $model->save($runValidation);
        }
        return $model;
    }

    /**
     * first or new
     * @param array $conditions
     * @param array $attributes
     * @return static
     * @date 2021/9/1 16:00
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function firstOrNew(array $conditions, array $attributes)
    {
        return static::findOne($conditions) ?: (new static($conditions + $attributes));
    }

    /**
     * @param array $insertColumns
     * @param array $updateColumns
     * @return int
     * @date   2021/7/20 17:15
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function insertUpdate(array $insertColumns, array $updateColumns): int
    {
        $queryBuilder = static::getDb()->getQueryBuilder();
        $tableSchema = static::getDb()->getTableSchema(static::tableName());
        $columnSchemas = $tableSchema !== null ? $tableSchema->columns : [];
        $params = [];
        $sql = $queryBuilder->insert(static::tableName(), $insertColumns, $params);
        $sets = [];
        foreach ($updateColumns as $name => $value) {
            $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
            if ($value instanceof ExpressionInterface) {
                $placeholder = $queryBuilder->buildExpression($value, $params);
            } else {
                $placeholder = $queryBuilder->bindParam($value, $params);
            }
            $sets[] = static::getDb()->quoteColumnName($name) . '=' . $placeholder;
        }
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
        return static::getDb()->createCommand($sql)->bindValues($params)->execute();
    }

    /**
     * 批量插入
     * @param array $rows
     * @param array|null $columns
     * @param bool $ignore
     * @return int
     * @date   2020/6/25 22:01
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function batchInsert(array $rows, array $columns = null, bool $ignore = false): int
    {
        if (empty($columns)) $columns = reset($rows);
        $sql = static::getDb()->queryBuilder->batchInsert(static::tableName(), $columns, $rows);
        if ($ignore) {
            $arr = explode(' ', $sql, 2);
            array_splice($arr, 1, 0, 'IGNORE');
            $sql = implode(' ', $arr);
        }
        return static::getDb()->createCommand($sql)->execute();
    }

    /**
     * @param array $rows
     * @param array|null $columns
     * @return int
     * @date   2021/7/20 15:08
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function batchInsertIgnore(array $rows, array $columns = null): int
    {
        return static::batchInsert($rows, $columns, true);
    }

    /**
     * 批量更新
     * @param array $rows [['columns'=>['status' => 1],'condition'=>'age > 30'],['columns'=>['status' => 2],'condition'=>'age < 20']]
     * @return int
     * @date   2021/7/26 11:40
     * @throws \Exception
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public static function batchUpdate(array $rows): int
    {
        $table = static::tableName();
        $command = static::getDb()->createCommand();
        $sql = '';
        foreach ($rows as $row) $sql .= $command->update($table, $row['columns'], $row['condition'])->getRawSql() . ";\n";
        $transaction = static::getDb()->beginTransaction();
        try {
            $result = static::getDb()->createCommand($sql)->execute();
            $transaction->commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

}

<?php

namespace liuyuanjun\yii2\extensions\db;


use liuyuanjun\yii2\softdelete\SoftDeleteActiveQuery;

/**
 * Class ActiveQuery.
 * 支持软删 (不影响非软删查询)
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class ActiveQuery extends SoftDeleteActiveQuery
{

    /** andLikeWhere 匹配模式 **/
    const LIKE_MODE_WORD_AND_FIELD_OR = 5; //任意字段匹配所有词
    const LIKE_MODE_WORD_AND_FIELD_AND = 9; //所有字段匹配所有词
    const LIKE_MODE_WORD_OR_FIELD_OR = 6; //任意字段匹配任意词
    const LIKE_MODE_WORD_OR_FIELD_AND = 10; //所有字段匹配任意词

    /**
     * 添加 like 关键词 and where 条件
     * @param string|array $keywords 检索关键词
     *                               '关键词1 关键词2'  字符串形式 按空格分割关键词
     *                               ['关键词1','关键词2']  数组形式
     *                               [['关键词1%',false],'关键词2']
     * @param string|array $fields 检索字段
     *                               'title'  单个字段
     *                               ['title1', 'title2']  多个字段
     * @param int $mode 模式
     *                               Query::LIKE_MODE_WORD_AND_FIELD_OR  任意字段匹配所有词  默认
     *                               Query::LIKE_MODE_WORD_OR_FIELD_AND  所有字段匹配任意词
     *                               Query::LIKE_MODE_WORD_AND_FIELD_AND 所有字段匹配所有词
     *                               Query::LIKE_MODE_WORD_OR_FIELD_OR   任意字段匹配任意词
     * @return $this
     * @date   2021/8/20 16:57
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function andLikeWhere($fields, $keywords, int $mode = self::LIKE_MODE_WORD_AND_FIELD_OR): ActiveQuery
    {
        return $this->andWhere($this->buildLikeCondition($fields, $keywords, $mode));
    }

    /**
     * 添加 like 关键词 or where 条件
     * @param $fields
     * @param $keywords
     * @param int $mode
     * @return ActiveQuery
     * @date 2021/9/8 16:13
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function orLikeWhere($fields, $keywords, int $mode = self::LIKE_MODE_WORD_AND_FIELD_OR): ActiveQuery
    {
        return $this->orWhere($this->buildLikeCondition($fields, $keywords, $mode));
    }

    /**
     * build like condition
     * @param $fields
     * @param $keywords
     * @param int $mode
     * @return $this
     * @date 2021/9/8 16:14
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function buildLikeCondition($fields, $keywords, int $mode = self::LIKE_MODE_WORD_AND_FIELD_OR): ActiveQuery
    {
        //1匹配所有词  2匹配任意词  4匹配所有字段  8匹配任意字段
        if (is_string($keywords)) {
            $keywords = explode(' ', $keywords);
        } elseif (is_bool($keywords[1])) {
            $keywords = [$keywords];
        }
        $keywords = array_unique(array_filter($keywords));
        if (empty($keywords)) return $this;
        foreach ($keywords as $k => $keyword) {
            if (is_string($keyword)) $keywords[$k] = [$keyword, strpos($keyword, '%') === false];
        }
        $fields = (array)$fields;
        $conditions = [];
        foreach ($fields as $field) {
            $temp = [];
            foreach ($keywords as $keyword) {
                $temp[] = ['like', $field, $keyword[0], $keyword[1]];
            }
            if (count($temp) == 1) {
                $temp = $temp[0];
            } else {
                array_unshift($temp, $mode & 1 ? 'AND' : 'OR');
            }
            $conditions[] = $temp;
        }
        if (count($conditions) == 1) {
            $conditions = $conditions[0];
        } else {
            array_unshift($conditions, $mode & 4 ? 'OR' : 'AND');
        }
        return $conditions;
    }

    /**
     * 分页列表
     * @param int $page
     * @param int $pageSize
     * @return array
     * @date   2020/8/5 20:44
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function page(int $page = 1, int $pageSize = 20): array
    {
        $pagination = $this->pagination($page, $pageSize);
        $rows = $this->limit($pagination['pageSize'])->offset($pagination['offset'])->all();
        return ['list' => $rows, 'page' => $pagination];
    }

    /**
     * 分页
     * @param int $page
     * @param int $pageSize
     * @return array
     * @date   2021/3/19 15:10
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function pagination(int $page = 1, int $pageSize = 20): array
    {
        $pageSize = $pageSize < 1 ? 20 : $pageSize;
        $totalNum = intval($this->count('*'));
        $totalPage = ceil($totalNum / $pageSize);
        if ($page > $totalPage) $page = $totalPage;
        if ($page < 1) $page = 1;
        return ['offset' => ($page - 1) * $pageSize, 'page' => $page, 'pageSize' => $pageSize, 'totalNum' => $totalNum, 'totalPage' => $totalPage];
    }
}

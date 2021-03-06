<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\model;

use think\Db;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Pivot;

class Relation
{
    const HAS_ONE          = 1;
    const HAS_MANY         = 2;
    const BELONGS_TO       = 3;
    const BELONGS_TO_MANY  = 4;
    const HAS_MANY_THROUGH = 5;
    const MORPH_TO         = 6;
    const MORPH_MANY       = 7;

    // 父模型对象
    protected $parent;
    /** @var  Model 当前关联的模型类 */
    protected $model;
    // 中间表模型
    protected $middle;
    // 当前关联类型
    protected $type;
    // 关联表外键
    protected $foreignKey;
    // 中间关联表外键
    protected $throughKey;
    // 关联表主键
    protected $localKey;
    // 数据表别名
    protected $alias;
    // 当前关联的JOIN类型
    protected $joinType;
    // 关联模型查询对象
    protected $query;
    // 关联查询条件
    protected $where;
    // 关联查询参数
    protected $option;

    /**
     * 架构函数
     * @access public
     * @param Model $model 上级模型对象
     */
    public function __construct(Model $model)
    {
        $this->parent = $model;
    }

    /**
     * 获取关联的所属模型
     * @access public
     */
    public function getModel()
    {
        return $this->parent;
    }

    /**
     * 获取关联的查询对象
     * @access public
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * 解析模型的完整命名空间
     * @access public
     * @param string $model 模型名（或者完整类名）
     * @return string
     */
    protected function parseModel($model)
    {
        if (isset($this->alias[$model])) {
            $model = $this->alias[$model];
        }
        if (false === strpos($model, '\\')) {
            $path = explode('\\', get_class($this->parent));
            array_pop($path);
            array_push($path, Loader::parseName($model, 1));
            $model = implode('\\', $path);
        }
        return $model;
    }

    /**
     * 获取当前关联信息
     * @access public
     * @param string $name 关联信息
     * @return array|string|integer
     */
    public function getRelationInfo($name = '')
    {
        $info = [
            'type'       => $this->type,
            'model'      => $this->model,
            'middle'     => $this->middle,
            'foreignKey' => $this->foreignKey,
            'localKey'   => $this->localKey,
            'alias'      => $this->alias,
            'joinType'   => $this->joinType,
            'option'     => $this->option,
        ];
        return $name ? $info[$name] : $info;
    }

    // 获取关联数据
    public function getRelation($name)
    {
        // 执行关联定义方法
        $relation   = $this->parent->$name();
        $foreignKey = $this->foreignKey;
        $localKey   = $this->localKey;
        $middle     = $this->middle;

        // 判断关联类型执行查询
        switch ($this->type) {
            case self::HAS_ONE:
                $result = $relation->where($foreignKey, $this->parent->$localKey)->find();
                break;
            case self::BELONGS_TO:
                $result = $relation->where($localKey, $this->parent->$foreignKey)->find();
                break;
            case self::HAS_MANY:
                $result = $relation->select();
                break;
            case self::HAS_MANY_THROUGH:
                $result = $relation->select();
                break;
            case self::BELONGS_TO_MANY:
                // 关联查询
                $pk                              = $this->parent->getPk();
                $condition['pivot.' . $localKey] = $this->parent->$pk;
                $result                          = $this->belongsToManyQuery($relation->getQuery(), $middle, $foreignKey, $localKey, $condition)->select();
                foreach ($result as $set) {
                    $pivot = [];
                    foreach ($set->getData() as $key => $val) {
                        if (strpos($key, '__')) {
                            list($name, $attr) = explode('__', $key, 2);
                            if ('pivot' == $name) {
                                $pivot[$attr] = $val;
                                unset($set->$key);
                            }
                        }
                    }
                    $set->pivot = new Pivot($pivot, $this->middle);
                }
                break;
            case self::MORPH_MANY:
                $result = $relation->select();
                break;
            case self::MORPH_TO:
                // 多态模型
                $model = $this->parseModel($this->parent->$middle);
                // 主键数据
                $pk     = $this->parent->$foreignKey;
                $result = (new $model)->find($pk);
                break;
            default:
                // 直接返回
                $result = $relation;
        }
        return $result;
    }

    /**
     * 预载入关联查询 返回数据集
     * @access public
     * @param array     $resultSet 数据集
     * @param string    $relation 关联名
     * @param string    $class 数据集对象名 为空表示数组
     * @return array
     */
    public function eagerlyResultSet($resultSet, $relation, $class = '')
    {
        /** @var array $relations */
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = false;
            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }
            if (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation);
            }
            // 执行关联方法
            $model = $this->parent->$relation();
            // 获取关联信息
            $localKey   = $this->localKey;
            $foreignKey = $this->foreignKey;
            $middle     = $this->middle;
            switch ($this->type) {
                case self::HAS_ONE:
                case self::BELONGS_TO:
                    foreach ($resultSet as $result) {
                        // 模型关联组装
                        $this->match($this->model, $relation, $result);
                    }
                    break;
                case self::HAS_MANY:
                    $range = [];
                    foreach ($resultSet as $result) {
                        // 获取关联外键列表
                        if (isset($result->$localKey)) {
                            $range[] = $result->$localKey;
                        }
                    }

                    if (!empty($range)) {
                        $this->where[$foreignKey] = ['in', $range];
                        $data                     = $this->eagerlyOneToMany($model, [
                            $foreignKey => [
                                'in',
                                $range,
                            ],
                        ], $relation, $subRelation, $closure);

                        // 关联数据封装
                        foreach ($resultSet as $result) {
                            if (!isset($data[$result->$localKey])) {
                                $data[$result->$localKey] = [];
                            }
                            $result->setAttr($relation, $this->resultSetBuild($data[$result->$localKey], $class));
                        }
                    }
                    break;
                case self::BELONGS_TO_MANY:
                    $pk    = $resultSet[0]->getPk();
                    $range = [];
                    foreach ($resultSet as $result) {
                        // 获取关联外键列表
                        if (isset($result->$pk)) {
                            $range[] = $result->$pk;
                        }
                    }

                    if (!empty($range)) {
                        // 查询关联数据
                        $data = $this->eagerlyManyToMany($model, [
                            'pivot.' . $localKey => [
                                'in',
                                $range,
                            ],
                        ], $relation, $subRelation);

                        // 关联数据封装
                        foreach ($resultSet as $result) {
                            if (!isset($data[$result->$pk])) {
                                $data[$result->$pk] = [];
                            }

                            $result->setAttr($relation, $this->resultSetBuild($data[$result->$pk], $class));
                        }
                    }
                    break;
                case self::MORPH_MANY:
                    $range = [];
                    foreach ($resultSet as $result) {
                        $pk = $result->getPk();
                        // 获取关联外键列表
                        if (isset($result->$pk)) {
                            $range[] = $result->$pk;
                        }
                    }

                    if (!empty($range)) {
                        $this->where[$foreignKey] = ['in', $range];
                        $this->where[$localKey]   = $middle;
                        $data                     = $this->eagerlyMorphToMany($model, [
                            $foreignKey => ['in', $range],
                            $localKey   => $middle,
                        ], $relation, $subRelation, $closure);

                        // 关联数据封装
                        foreach ($resultSet as $result) {
                            if (!isset($data[$result->$pk])) {
                                $data[$result->$pk] = [];
                            }
                            $result->setAttr($relation, $this->resultSetBuild($data[$result->$pk], $class));
                        }
                    }
                    break;
                case self::MORPH_TO:
                    $range = [];
                    foreach ($resultSet as $result) {
                        // 获取关联外键列表
                        if (!empty($result->$foreignKey)) {
                            $range[$result->$middle][] = $result->$foreignKey;
                        }
                    }

                    if (!empty($range)) {
                        foreach ($range as $key => $val) {
                            // 多态类型映射
                            $model = $this->parseModel($key);
                            $obj   = new $model;
                            $pk    = $obj->getPk();
                            $list  = $obj->all($val, $subRelation);
                            $data  = [];
                            foreach ($list as $k => $vo) {
                                $data[$vo->$pk] = $vo;
                            }
                            foreach ($resultSet as $result) {
                                if ($key == $result->$middle) {
                                    if (!isset($data[$result->$foreignKey])) {
                                        $data[$result->$foreignKey] = [];
                                    }
                                    $result->setAttr($relation, $this->resultSetBuild($data[$result->$foreignKey], $class));
                                }
                            }
                        }
                    }
                    break;
            }
        }
        return $resultSet;
    }

    /**
     * 封装关联数据集
     * @access public
     * @param array     $resultSet 数据集
     * @param string    $class 数据集类名
     * @return mixed
     */
    protected function resultSetBuild($resultSet, $class = '')
    {
        return $class ? new $class($resultSet) : $resultSet;
    }

    /**
     * 预载入关联查询 返回模型对象
     * @access public
     * @param Model     $result 数据对象
     * @param string    $relation 关联名
     * @param string    $class 数据集对象名 为空表示数组
     * @return Model
     */
    public function eagerlyResult($result, $relation, $class = '')
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = false;
            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }
            if (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation);
            }
            // 执行关联方法
            $model      = $this->parent->$relation();
            $localKey   = $this->localKey;
            $foreignKey = $this->foreignKey;
            $middle     = $this->middle;
            switch ($this->type) {
                case self::HAS_ONE:
                case self::BELONGS_TO:
                    // 模型关联组装
                    $this->match($this->model, $relation, $result);
                    break;
                case self::HAS_MANY:
                    if (isset($result->$localKey)) {
                        $data = $this->eagerlyOneToMany($model, [$foreignKey => $result->$localKey], $relation, $subRelation, $closure);
                        // 关联数据封装
                        if (!isset($data[$result->$localKey])) {
                            $data[$result->$localKey] = [];
                        }
                        $result->setAttr($relation, $this->resultSetBuild($data[$result->$localKey], $class));
                    }
                    break;
                case self::BELONGS_TO_MANY:
                    $pk = $result->getPk();
                    if (isset($result->$pk)) {
                        $pk = $result->$pk;
                        // 查询管理数据
                        $data = $this->eagerlyManyToMany($model, ['pivot.' . $localKey => $pk], $relation, $subRelation);

                        // 关联数据封装
                        if (!isset($data[$pk])) {
                            $data[$pk] = [];
                        }
                        $result->setAttr($relation, $this->resultSetBuild($data[$pk], $class));
                    }
                    break;
                case self::MORPH_MANY:
                    $pk = $result->getPk();
                    if (isset($result->$pk)) {
                        $data = $this->eagerlyMorphToMany($model, [$foreignKey => $result->$pk, $localKey => $middle], $relation, $subRelation, $closure);
                        $result->setAttr($relation, $this->resultSetBuild($data[$result->$pk], $class));
                    }
                    break;
                case self::MORPH_TO:
                    // 多态类型映射
                    $model = $this->parseModel($result->{$this->middle});
                    $this->eagerlyMorphToOne($model, $relation, $result, $subRelation);
                    break;

            }
        }
        return $result;
    }

    /**
     * 一对一 关联模型预查询拼装
     * @access public
     * @param string    $model 模型名称
     * @param string    $relation 关联名
     * @param Model     $result 模型对象实例
     * @return void
     */
    protected function match($model, $relation, &$result)
    {
        // 重新组装模型数据
        foreach ($result->getData() as $key => $val) {
            if (strpos($key, '__')) {
                list($name, $attr) = explode('__', $key, 2);
                if ($name == $relation) {
                    $list[$name][$attr] = $val;
                    unset($result->$key);
                }
            }
        }

        $result->setAttr($relation, !isset($list[$relation]) ? null : (new $model($list[$relation]))->isUpdate(true));
    }

    /**
     * 一对多 关联模型预查询
     * @access public
     * @param object    $model 关联模型对象
     * @param array     $where 关联预查询条件
     * @param string    $relation 关联名
     * @param string    $subRelation 子关联
     * @param bool      $closure
     * @return array
     */
    protected function eagerlyOneToMany($model, $where, $relation, $subRelation = '', $closure = false)
    {
        $foreignKey = $this->foreignKey;
        // 预载入关联查询 支持嵌套预载入
        if ($closure) {
            call_user_func_array($closure, [ & $model]);
        }
        $list = $model->where($where)->with($subRelation)->select();

        // 组装模型数据
        $data = [];
        foreach ($list as $set) {
            $data[$set->$foreignKey][] = $set;
        }
        return $data;
    }

    /**
     * 多对多 关联模型预查询
     * @access public
     * @param object    $model 关联模型对象
     * @param array     $where 关联预查询条件
     * @param string    $relation 关联名
     * @param string    $subRelation 子关联
     * @return array
     */
    protected function eagerlyManyToMany($model, $where, $relation, $subRelation = '')
    {
        $foreignKey = $this->foreignKey;
        $localKey   = $this->localKey;
        // 预载入关联查询 支持嵌套预载入
        $list = $this->belongsToManyQuery($model->getQuery(), $this->middle, $foreignKey, $localKey, $where)->with($subRelation)->select();

        // 组装模型数据
        $data = [];
        foreach ($list as $set) {
            $pivot = [];
            foreach ($set->getData() as $key => $val) {
                if (strpos($key, '__')) {
                    list($name, $attr) = explode('__', $key, 2);
                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($set->$key);
                    }
                }
            }
            $set->pivot                = new Pivot($pivot, $this->middle);
            $data[$pivot[$localKey]][] = $set;
        }
        return $data;
    }

    /**
     * 多态MorphTo 关联模型预查询
     * @access public
     * @param object    $model 关联模型对象
     * @param array     $where 关联预查询条件
     * @param string    $relation 关联名
     * @param string    $subRelation 子关联
     * @return array
     */
    protected function eagerlyMorphToOne($model, $relation, &$result, $subRelation = '')
    {
        // 预载入关联查询 支持嵌套预载入
        $pk   = $this->parent->{$this->foreignKey};
        $data = (new $model)->with($subRelation)->find($pk);
        if ($data) {
            $data->isUpdate(true);
        }
        $result->setAttr($relation, $data ?: null);
    }

    /**
     * 多态一对多 关联模型预查询
     * @access public
     * @param object    $model 关联模型对象
     * @param array     $where 关联预查询条件
     * @param string    $relation 关联名
     * @param string    $subRelation 子关联
     * @return array
     */
    protected function eagerlyMorphToMany($model, $where, $relation, $subRelation = '', $closure = false)
    {
        // 预载入关联查询 支持嵌套预载入
        if ($closure) {
            call_user_func_array($closure, [ & $model]);
        }
        $list       = $model->getQuery()->where($where)->with($subRelation)->select();
        $foreignKey = $this->foreignKey;
        // 组装模型数据
        $data = [];
        foreach ($list as $set) {
            $data[$set->$foreignKey][] = $set;
        }
        return $data;
    }

    /**
     * 设置当前关联定义的数据表别名
     * @access public
     * @param array  $alias 别名定义
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * HAS ONE 关联定义
     * @access public
     * @param string $model 模型名
     * @param string $foreignKey 关联外键
     * @param string $localKey 关联主键
     * @param array  $alias 别名定义
     * @param string $joinType JOIN类型
     * @return $this
     */
    public function hasOne($model, $foreignKey, $localKey, $alias = [], $joinType = 'INNER')
    {
        $this->type       = self::HAS_ONE;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->alias      = $alias;
        $this->joinType   = $joinType;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * BELONGS TO 关联定义
     * @access public
     * @param string $model 模型名
     * @param string $foreignKey 关联外键
     * @param string $otherKey 关联主键
     * @param array  $alias 别名定义
     * @param string $joinType JOIN类型
     * @return $this
     */
    public function belongsTo($model, $foreignKey, $otherKey, $alias = [], $joinType = 'INNER')
    {
        // 记录当前关联信息
        $this->type       = self::BELONGS_TO;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $otherKey;
        $this->alias      = $alias;
        $this->joinType   = $joinType;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * HAS MANY 关联定义
     * @access public
     * @param string $model 模型名
     * @param string $foreignKey 关联外键
     * @param string $localKey 关联主键
     * @param array  $alias 别名定义
     * @return $this
     */
    public function hasMany($model, $foreignKey, $localKey, $alias)
    {
        // 记录当前关联信息
        $this->type       = self::HAS_MANY;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->alias      = $alias;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * HAS MANY 远程关联定义
     * @access public
     * @param string $model 模型名
     * @param string $through 中间模型名
     * @param string $firstkey 关联外键
     * @param string $secondKey 关联外键
     * @param string $localKey 关联主键
     * @param array  $alias 别名定义
     * @return $this
     */
    public function hasManyThrough($model, $through, $foreignKey, $throughKey, $localKey, $alias)
    {
        // 记录当前关联信息
        $this->type       = self::HAS_MANY_THROUGH;
        $this->model      = $model;
        $this->middle     = $through;
        $this->foreignKey = $foreignKey;
        $this->throughKey = $throughKey;
        $this->localKey   = $localKey;
        $this->alias      = $alias;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * BELONGS TO MANY 关联定义
     * @access public
     * @param string $model 模型名
     * @param string $table 中间表名
     * @param string $foreignKey 关联模型外键
     * @param string $localKey 当前模型关联键
     * @param array  $alias 别名定义
     * @return $this
     */
    public function belongsToMany($model, $table, $foreignKey, $localKey, $alias)
    {
        // 记录当前关联信息
        $this->type       = self::BELONGS_TO_MANY;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->middle     = $table;
        $this->alias      = $alias;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * MORPH_MANY 关联定义
     * @access public
     * @param string $model 模型名
     * @param string $id 关联外键
     * @param string $morphType 多态字段名
     * @param string $type 多态类型
     * @return $this
     */
    public function morphMany($model, $foreignKey, $morphType, $type)
    {
        // 记录当前关联信息
        $this->type       = self::MORPH_MANY;
        $this->model      = $model;
        $this->middle     = $type;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $morphType;
        $this->query      = (new $model)->db();
        // 返回关联的模型对象
        return $this;
    }

    /**
     * MORPH_TO 关联定义
     * @access public
     * @param string $morphType 多态字段名
     * @param string $foreignKey 外键名
     * @param array  $alias 多态别名定义
     * @return $this
     */
    public function morphTo($morphType, $foreignKey, $alias)
    {
        // 记录当前关联信息
        $this->type       = self::MORPH_TO;
        $this->middle     = $morphType;
        $this->foreignKey = $foreignKey;
        $this->alias      = $alias;
        // 返回关联的模型对象
        return $this;
    }

    /**
     * BELONGS TO MANY 关联查询
     * @access public
     * @param object    $model 关联模型对象
     * @param string    $table 中间表名
     * @param string    $foreignKey 关联模型关联键
     * @param string    $localKey 当前模型关联键
     * @param array     $condition 关联查询条件
     * @return \think\db\Query|string
     */
    protected function belongsToManyQuery($model, $table, $foreignKey, $localKey, $condition = [])
    {
        // 关联查询封装
        $tableName  = $model->getTable();
        $relationFk = $model->getPk();
        return $model->field($tableName . '.*')
            ->field(true, false, $table, 'pivot', 'pivot__')
            ->join($table . ' pivot', 'pivot.' . $foreignKey . '=' . $tableName . '.' . $relationFk)
            ->where($condition);
    }

    /**
     * 保存（新增）当前关联数据对象
     * @access public
     * @param mixed     $data 数据 可以使用数组 关联模型对象 和 关联对象的主键
     * @param array     $pivot 中间表额外数据
     * @return integer
     */
    public function save($data, array $pivot = [])
    {
        // 判断关联类型
        switch ($this->type) {
            case self::HAS_ONE:
            case self::BELONGS_TO:
            case self::HAS_MANY:
                if ($data instanceof Model) {
                    $data = $data->getData();
                }
                // 保存关联表数据
                $data[$this->foreignKey] = $this->parent->{$this->localKey};
                $model                   = new $this->model;
                return $model->save($data);
            case self::BELONGS_TO_MANY:
                // 保存关联表/中间表数据
                return $this->attach($data, $pivot);
        }
    }

    /**
     * 批量保存当前关联数据对象
     * @access public
     * @param array     $dataSet 数据集
     * @param array     $pivot 中间表额外数据
     * @return integer
     */
    public function saveAll(array $dataSet, array $pivot = [])
    {
        $result = false;
        foreach ($dataSet as $key => $data) {
            // 判断关联类型
            switch ($this->type) {
                case self::HAS_MANY:
                    $data[$this->foreignKey] = $this->parent->{$this->localKey};
                    $result                  = $this->save($data);
                    break;
                case self::BELONGS_TO_MANY:
                    // TODO
                    $result = $this->attach($data, !empty($pivot) ? $pivot[$key] : []);
                    break;
            }
        }
        return $result;
    }

    /**
     * 附加关联的一个中间表数据
     * @access public
     * @param mixed     $data 数据 可以使用数组、关联模型对象 或者 关联对象的主键
     * @param array     $pivot 中间表额外数据
     * @return integer
     */
    public function attach($data, $pivot = [])
    {
        if (is_array($data)) {
            // 保存关联表数据
            $model = new $this->model;
            $model->save($data);
            $id = $model->getLastInsID();
        } elseif (is_numeric($data) || is_string($data)) {
            // 根据关联表主键直接写入中间表
            $id = $data;
        } elseif ($data instanceof Model) {
            // 根据关联表主键直接写入中间表
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }

        if ($id) {
            // 保存中间表数据
            $pk                       = $this->parent->getPk();
            $pivot[$this->localKey]   = $this->parent->$pk;
            $pivot[$this->foreignKey] = $id;
            $query                    = clone $this->parent->db();
            return $query->table($this->middle)->insert($pivot);
        } else {
            throw new Exception('miss relation data');
        }
    }

    /**
     * 解除关联的一个中间表数据
     * @access public
     * @param integer|array     $data 数据 可以使用关联对象的主键
     * @param bool              $relationDel 是否同时删除关联表数据
     * @return integer
     */
    public function detach($data, $relationDel = false)
    {
        if (is_array($data)) {
            $id = $data;
        } elseif (is_numeric($data) || is_string($data)) {
            // 根据关联表主键直接写入中间表
            $id = $data;
        } elseif ($data instanceof Model) {
            // 根据关联表主键直接写入中间表
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }
        // 删除中间表数据
        $pk                     = $this->parent->getPk();
        $pivot[$this->localKey] = $this->parent->$pk;
        if (isset($id)) {
            $pivot[$this->foreignKey] = is_array($id) ? ['in', $id] : $id;
        }
        $query = clone $this->parent->db();
        $query->table($this->middle)->where($pivot)->delete();

        // 删除关联表数据
        if (isset($id) && $relationDel) {
            $model = $this->model;
            $model::destroy($id);
        }
    }

    public function __call($method, $args)
    {
        static $baseQuery = [];
        if ($this->query) {
            if (empty($baseQuery[$this->type])) {
                $baseQuery[$this->type] = true;
                switch ($this->type) {
                    case self::HAS_MANY:
                        if (isset($this->where)) {
                            $this->query->where($this->where);
                        } elseif (isset($this->parent->{$this->localKey})) {
                            // 关联查询带入关联条件
                            $this->query->where($this->foreignKey, $this->parent->{$this->localKey});
                        }
                        break;
                    case self::HAS_MANY_THROUGH:
                        $through      = $this->middle;
                        $model        = $this->model;
                        $alias        = Loader::parseName(basename(str_replace('\\', '/', $model)));
                        $throughTable = $through::getTable();
                        $pk           = (new $this->model)->getPk();
                        $throughKey   = $this->throughKey;
                        $modelTable   = $this->parent->getTable();
                        $this->query->field($alias . '.*')->alias($alias)
                            ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
                            ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
                            ->where($throughTable . '.' . $this->foreignKey, $this->parent->{$this->localKey});
                        break;
                    case self::BELONGS_TO_MANY:
                        $pk = $this->parent->getPk();
                        $this->query->join($this->middle . ' pivot', 'pivot.' . $this->foreignKey . '=' . $this->query->getTable() . '.' . $this->query->getPk())->where('pivot.' . $this->localKey, $this->parent->$pk);
                        break;
                    case self::MORPH_MANY:
                        $pk                     = $this->parent->getPk();
                        $map[$this->foreignKey] = $this->parent->$pk;
                        $map[$this->localKey]   = $this->middle;
                        $this->query->where($map);
                        break;
                }
            }

            $result = call_user_func_array([$this->query, $method], $args);
            if ($result instanceof \think\db\Query) {
                $this->option = $result->getOptions();
                return $this;
            } else {
                $this->option = [];
                $baseQuery    = false;
                return $result;
            }
        } else {
            throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }

}

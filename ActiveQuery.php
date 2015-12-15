<?php
/**
 * Created by PhpStorm.
 * User: shanli
 * Date: 2015/12/10
 * Time: 19:42
 */

namespace wsl\ssdb;


use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRecordInterface;
use yii\db\ActiveRelationTrait;
use yii\db\QueryTrait;
use yii\helpers\ArrayHelper;

class ActiveQuery extends Component implements ActiveQueryInterface
{
    use QueryTrait;
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @event Event an event that is triggered when the query is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';

    /**
     * Constructor.
     * @param array $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    /**
     * Initializes the object.
     * This method is called at the end of the constructor. The default implementation will trigger
     * an [[EVENT_INIT]] event. If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    /**
     * Sets the [[asArray]] property.
     * @param boolean $value whether to return the query results in terms of arrays instead of Active Records.
     * @return $this the query object itself
     */
    public function asArray($value = true)
    {
        // TODO: Implement asArray() method.
    }

    /**
     * Sets the [[indexBy]] property.
     * @param string|callable $column the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given
     * row or model data. The signature of the callable should be:
     *
     * ~~~
     * // $model is an AR instance when `asArray` is false,
     * // or an array of column values when `asArray` is true.
     * function ($model)
     * {
     *     // return the index value corresponding to $model
     * }
     * ~~~
     *
     * @return $this the query object itself
     */
    public function indexBy($column)
    {
        // TODO: Implement indexBy() method.
    }

    /**
     * Specifies the relations with which this query should be performed.
     *
     * The parameters to this method can be either one or multiple strings, or a single array
     * of relation names and the optional callbacks to customize the relations.
     *
     * A relation name can refer to a relation defined in [[ActiveQueryTrait::modelClass|modelClass]]
     * or a sub-relation that stands for a relation of a related record.
     * For example, `orders.address` means the `address` relation defined
     * in the model class corresponding to the `orders` relation.
     *
     * The following are some usage examples:
     *
     * ~~~
     * // find customers together with their orders and country
     * Customer::find()->with('orders', 'country')->all();
     * // find customers together with their orders and the orders' shipping address
     * Customer::find()->with('orders.address')->all();
     * // find customers together with their country and orders of status 1
     * Customer::find()->with([
     *     'orders' => function ($query) {
     *         $query->andWhere('status = 1');
     *     },
     *     'country',
     * ])->all();
     * ~~~
     *
     * @return $this the query object itself
     */
    public function with()
    {
        // TODO: Implement with() method.
    }

    /**
     * Specifies the relation associated with the junction table for use in relational query.
     * @param string $relationName the relation name. This refers to a relation declared in the [[ActiveRelationTrait::primaryModel|primaryModel]] of the relation.
     * @param callable $callable a PHP callback for customizing the relation associated with the junction table.
     * Its signature should be `function($query)`, where `$query` is the query to be customized.
     * @return $this the relation object itself.
     */
    public function via($relationName, callable $callable = null)
    {
        // TODO: Implement via() method.
    }

    /**
     * Finds the related records for the specified primary record.
     * This method is invoked when a relation of an ActiveRecord is being accessed in a lazy fashion.
     * @param string $name the relation name
     * @param ActiveRecordInterface $model the primary model
     * @return mixed the related record(s)
     */
    public function findFor($name, $model)
    {
        // TODO: Implement findFor() method.
    }

    /**
     * Executes the query and returns all results as an array.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|ActiveRecord[] the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($db = null)
    {
        // TODO add support for orderBy
        $data = $this->executeScript($db, 'All');
        $rows = [];
        foreach ($data as $dataRow) {
            $rows[] = $dataRow;
        }
        if (!empty($rows)) {
            $models = $this->createModels($rows);
            if (!empty($this->with)) {
                $this->findWith($this->with, $models);
            }
            if (!$this->asArray) {
                foreach ($models as $model) {
                    $model->afterFind();
                }
            }
            return $models;
        } else {
            return [];
        }
    }

    /**
     * Executes the query and returns a single row of result.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($db = null)
    {
        // TODO add support for orderBy
        $row = $this->executeScript($db, 'One');
        if (empty($row)) {
            return null;
        }
        if ($this->asArray) {
            $model = $row;
        } else {
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            $model = $class::instantiate($row);
            $class = get_class($model);
            $class::populateRecord($model, $row);
        }
        if (!empty($this->with)) {
            $models = [$model];
            $this->findWith($this->with, $models);
            $model = $models[0];
        }
        if (!$this->asArray) {
            $model->afterFind();
        }
        return $model;
    }

    /**
     * Returns the number of records.
     * @param string $q the COUNT expression. Defaults to '*'.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return integer number of records.
     */
    public function count($q = '*', $db = null)
    {
        if (null === $this->orderBy) {
            /* @var $modelClass ActiveRecord */
            $modelClass = $this->modelClass;
            if (null === $db) {
                $db = $modelClass::getDb();
            }
            return $db->zsize($modelClass::keyPrefix());
        } else {
            return $this->executeScript($db, 'Count');
        }
    }

    /**
     * Returns a value indicating whether the query result contains any row of data.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return boolean whether the query result contains any row of data.
     */
    public function exists($db = null)
    {
        return $this->one($db) !== null;
    }

    /**
     * Executes a script created by [[LuaScriptBuilder]]
     * @param Connection|null $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @param string $type the type of the script to generate
     * @param string $columnName
     * @throws NotSupportedException
     * @return array|bool|null|string
     */
    protected function executeScript($db, $type, $columnName = null)
    {
        if ($this->primaryModel !== null) {
            // lazy loading
            if ($this->via instanceof self) {
                // via junction table
                $viaModels = $this->via->findJunctionRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }
        }
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if ($db === null) {
            $db = $modelClass::getDb();
        }

        // find by primary key if possible. This is much faster than scanning all records
        if (is_array($this->where) && !isset($this->where[0]) && $modelClass::isPrimaryKey(array_keys($this->where))) {
            return $this->findByPk($db, $type, $columnName);
        }

        $method = 'build' . $type;
        return $this->$method($modelClass, $db);
    }

    /**
     * 所有数据
     *
     * @param ActiveRecord $modelClass
     * @param \wsl\ssdb\Connection $db
     * @return mixed
     */
    protected function buildAll($modelClass, $db)
    {
        $rows = [];
        $pks = [];
        $keyPrefix = $modelClass::keyPrefix();
        $offset = is_null($this->offset) ? 0 : $this->offset;
        $limit = is_null($this->limit) ? $db->zsize($keyPrefix) : $this->limit;
        if ($this->orderBy) {
            foreach ($this->orderBy as $field => $sort) {
                if (SORT_ASC === $sort) {
                    $pks = $db->zrange($keyPrefix . ':f:' . $field, $offset, $limit);
                } elseif (SORT_DESC === $sort) {
                    $pks = $db->zrrange($keyPrefix . ':f:' . $field, $offset, $limit);
                }
                break;
            }
        } else {
            $pks = $db->zrange($keyPrefix, $offset, $limit);
        }
        if ($pks) {
            foreach ($pks as $pk => $scope) {
                $rows[] = $db->hgetall($pk);
            }
        }

        return $rows;
    }

    /**
     * 单条数据
     *
     * @param ActiveRecord $modelClass
     * @param \wsl\ssdb\Connection $db
     * @return mixed
     */
    protected function buildOne($modelClass, $db)
    {
        $row = [];
        $pks = [];
        $keyPrefix = $modelClass::keyPrefix();
        if ($this->orderBy) {
            foreach ($this->orderBy as $field => $sort) {
                if (SORT_ASC === $sort) {
                    $pks = $db->zrange($keyPrefix, 0, 1);
                } elseif (SORT_DESC === $sort) {
                    $pks = $db->zrrange($keyPrefix, 0, 1);
                }
                break;
            }
        } else {
            $pks = $db->zrange($keyPrefix, 0, 1);
        }
        // @todo
        if ($pks) {
            foreach ($pks as $pkv => $scope) {
                $key = $modelClass::keyPrefix() . ':a:' . $modelClass::buildKey($pkv);
                $row = $db->hgetall($key);
                break;
            }
        }

        return $row;
    }

    /**
     * 所有数据
     *
     * @param ActiveRecord $modelClass
     * @param \wsl\ssdb\Connection $db
     * @return mixed
     */
    protected function buildCount($modelClass, $db)
    {
        $count = 0;
        $keyPrefix = $modelClass::keyPrefix();
        if ($this->orderBy) {
            foreach ($this->orderBy as $field => $sort) {
                if (SORT_ASC === $sort) {
                    $count = $db->zsize($keyPrefix . ':f:' . $field);
                } elseif (SORT_DESC === $sort) {
                    $count = $db->zsize($keyPrefix . ':f:' . $field);
                }
                break;
            }
        } else {
            $count = $db->zsize($keyPrefix);
        }

        return $count;
    }

    /**
     * Fetch by pk if possible as this is much faster
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @param string $type the type of the script to generate
     * @param string $columnName
     * @return array|bool|null|string
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\NotSupportedException
     */
    private function findByPk($db, $type, $columnName = null)
    {
        if (count($this->where) == 1) {
            $pks = (array)reset($this->where);
        } else {
            foreach ($this->where as $values) {
                if (is_array($values)) {
                    // TODO support composite IN for composite PK
                    throw new NotSupportedException('Find by composite PK is not supported by redis ActiveRecord.');
                }
            }
            $pks = [$this->where];
        }
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if ($type == 'Count') {
            $start = 0;
            $limit = null;
        } else {
            $start = $this->offset === null ? 0 : $this->offset;
            $limit = $this->limit;
        }
        $i = 0;
        $data = [];
        foreach ($pks as $pk) {
            if (++$i > $start && ($limit === null || $i <= $start + $limit)) {
                $key = $modelClass::keyPrefix() . ':a:' . $modelClass::buildKey($pk);
                $result = $db->hgetall($key);
                if (!empty($result)) {
                    $data[] = $result;
                    if ($type === 'One' && $this->orderBy === null) {
                        break;
                    }
                }
            }
        }
        // TODO support orderBy
        switch ($type) {
            case 'All':
                return $data;
            case 'One':
                return reset($data);
            case 'Count':
                return count($data);
            case 'Column':
                $column = [];
                foreach ($data as $dataRow) {
                    $row = [];
                    $c = count($dataRow);
                    for ($i = 0; $i < $c;) {
                        $row[$dataRow[$i++]] = $dataRow[$i++];
                    }
                    $column[] = $row[$columnName];
                }
                return $column;
            case 'Sum':
                $sum = 0;
                foreach ($data as $dataRow) {
                    $c = count($dataRow);
                    for ($i = 0; $i < $c;) {
                        if ($dataRow[$i++] == $columnName) {
                            $sum += $dataRow[$i];
                            break;
                        }
                    }
                }
                return $sum;
            case 'Average':
                $sum = 0;
                $count = 0;
                foreach ($data as $dataRow) {
                    $count++;
                    $c = count($dataRow);
                    for ($i = 0; $i < $c;) {
                        if ($dataRow[$i++] == $columnName) {
                            $sum += $dataRow[$i];
                            break;
                        }
                    }
                }
                return $sum / $count;
            case 'Min':
                $min = null;
                foreach ($data as $dataRow) {
                    $c = count($dataRow);
                    for ($i = 0; $i < $c;) {
                        if ($dataRow[$i++] == $columnName && ($min == null || $dataRow[$i] < $min)) {
                            $min = $dataRow[$i];
                            break;
                        }
                    }
                }
                return $min;
            case 'Max':
                $max = null;
                foreach ($data as $dataRow) {
                    $c = count($dataRow);
                    for ($i = 0; $i < $c;) {
                        if ($dataRow[$i++] == $columnName && ($max == null || $dataRow[$i] > $max)) {
                            $max = $dataRow[$i];
                            break;
                        }
                    }
                }
                return $max;
        }
        throw new InvalidParamException('Unknown fetch type: ' . $type);
    }

    /**
     * 排序参数
     *
     * @param string $fieldName 排序字段名
     * @param array $params 参数
     * @param int $sort 排序类型
     * @return $this
     */
    public function orderParams($fieldName, $params, $sort = SORT_ASC)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        $fields = ArrayHelper::getValue($modelClass::$sortFields, $fieldName);
        if (is_null($fields)) {
            throw new InvalidParamException('Unknown sort field name: ' . $fieldName);
        }
        foreach ($fields as $field) {
            if (!isset($params[$field])) {
                $params[$field] = 'all';
            }
        }
        $this->orderBy(strtr(join('_', $fields), $params) . ' ' . $sort);

        return $this;
    }

}
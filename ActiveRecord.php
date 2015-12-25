<?php
/**
 * Created by PhpStorm.
 * User: shanli
 * Date: 2015/12/10
 * Time: 14:09
 */

namespace wsl\ssdb;


use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\BaseActiveRecord;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class ActiveRecord extends BaseActiveRecord
{
    /**
     * @var string Model Class
     */
    public static $modelClass;
    /**
     * @var array 排序字段
     */
    public static $sortFields;

    /**
     * Loads default values from database table schema
     *
     * You may call this method to load default values after creating a new instance:
     *
     * ```php
     * // class Customer extends \yii\db\ActiveRecord
     * $customer = new Customer();
     * $customer->loadDefaultValues();
     * ```
     *
     * @param boolean $skipIfSet whether existing value should be preserved.
     * This will only set defaults for attributes that are `null`.
     * @return $this the model instance itself.
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        foreach ($this->getTableSchema()->columns as $column) {
            if ($column->defaultValue !== null && (!$skipIfSet || $this->{$column->name} === null)) {
                $this->{$column->name} = $column->defaultValue;
            }
        }
        return $this;
    }

    /**
     * Returns the connection used by this AR class.
     * @return Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ssdb');
    }

    /**
     * Declares the name of the database table associated with this AR class.
     * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
     * with prefix [[Connection::tablePrefix]]. For example if [[Connection::tablePrefix]] is 'tbl_',
     * 'Customer' becomes 'tbl_customer', and 'OrderItem' becomes 'tbl_order_item'. You may override this method
     * if the table is not named after this convention.
     * @return string the table name
     * @throws NotSupportedException
     */
    public static function tableName()
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = static::$modelClass;

        if ($modelClass) {
            return $modelClass::tableName();
        } else {
            throw new NotSupportedException(__CLASS__);
        }
    }

    /**
     * Declares prefix of the key that represents the keys that store this records in redis.
     * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]].
     * For example, 'Customer' becomes 'customer', and 'OrderItem' becomes
     * 'order_item'. You may override this method if you want different key naming.
     * @return string the prefix to apply to all AR keys
     */
    public static function keyPrefix()
    {
        return Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
    }

    /**
     * Builds a normalized key from a given primary key value.
     *
     * @param mixed $key the key to be normalized
     * @return string the generated key
     */
    public static function buildKey($key)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif (is_string($key)) {
            return ctype_alnum($key) && StringHelper::byteLength($key) <= 32 ? $key : md5($key);
        } elseif (is_array($key)) {
            if (count($key) == 1) {
                return self::buildKey(reset($key));
            }
            ksort($key); // ensure order is always the same
            $isNumeric = true;
            foreach ($key as $value) {
                if (!is_numeric($value)) {
                    $isNumeric = false;
                }
            }
            if ($isNumeric) {
                return implode('-', $key);
            }
        }
        return md5(json_encode($key));
    }

    /**
     * Returns the schema information of the DB table associated with this AR class.
     * @return TableSchema the schema information of the DB table associated with this AR class.
     * @throws InvalidConfigException if the table for the AR class does not exist.
     */
    public static function getTableSchema()
    {
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = static::$modelClass;

        if ($modelClass) {
            return $modelClass::getTableSchema();
        } else {
            throw new InvalidConfigException("The table does not exist: " . static::tableName());
        }
    }

    /**
     * @inheritDoc
     */
    public function attributes()
    {
        return array_keys(static::getTableSchema()->columns);
    }

    /**
     * Returns the primary key **name(s)** for this AR class.
     *
     * Note that an array should be returned even when the record only has a single primary key.
     *
     * For the primary key **value** see [[getPrimaryKey()]] instead.
     *
     * @return string[] the primary key name(s) for this AR class.
     */
    public static function primaryKey()
    {
        return static::getTableSchema()->primaryKey;
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public static function populateRecord($record, $row)
    {
        $columns = static::getTableSchema()->columns;
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $row[$name] = $columns[$name]->phpTypecast($value);
            }
        }
        parent::populateRecord($record, $row);
    }

    /**
     * Inserts the record into the database using the attribute values of this record.
     *
     * Usage example:
     *
     * ```php
     * $customer = new Customer;
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->insert();
     * ```
     *
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the attributes are valid and the record is inserted successfully.
     */
//    public function insert($runValidation = true, $attributes = null)
//    {
//        if ($runValidation && !$this->validate($attributes)) {
//            Yii::info('Model not inserted due to validation error.', __METHOD__);
//            return false;
//        }
//
//        return $this->insertInternal($attributes);
//    }

    /**
     * 排序规则
     *
     * @return array
     */
    public function sortRules()
    {
        return [];
    }

    /**
     * Save data key
     *
     * @return string
     */
    public function getKey()
    {
        return static::keyPrefix() . ':a:' . static::buildKey($this->primaryKey);
    }

    /**
     * Get index names
     *
     * @return array
     */
    public function getIndexs()
    {
        $indexes = [];
        $keyPrefix = static::keyPrefix();
        foreach ($this->sortRules() as $rule) {
            $index = ArrayHelper::getValue($rule, 'index');
            $weight = ArrayHelper::getValue($rule, 'weight', time());
            $isValid = ArrayHelper::getValue($rule, 'isValid', true);
            if (is_callable($isValid)) {
                $isValid = call_user_func($isValid);
            }
            if ($isValid) {
                if (is_callable($index)) {
                    $index = call_user_func($index);
                }
                if (is_callable($weight)) {
                    $weight = call_user_func($weight);
                }
                $names = is_array($index) ? $index : [$index];
                foreach ($names as &$indexName) {
                    $indexes[] = [
                        'index' => $keyPrefix . ':f:' . $indexName,
                        'weight' => $weight,
                        'isValid' => $isValid,
                    ];
                }
            }
        }

        return $indexes;
    }

    /**
     * @inheritdoc
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }
        if (!$this->beforeSave(true)) {
            return false;
        }
        $db = static::getDb();
        $values = $this->getDirtyAttributes($attributes);
        $keyPrefix = static::keyPrefix();
        $pk = [];
        foreach ($this->primaryKey() as $key) {
            $pk[$key] = $values[$key] = $this->getAttribute($key);
            if ($pk[$key] === null) {
                // use auto increment if pk is null
                $pk[$key] = $values[$key] = $db->incr($keyPrefix . ':s:' . $key);
                $this->setAttribute($key, $values[$key]);
            } elseif (is_numeric($pk[$key])) {
                // if pk is numeric update auto increment value
                $currentPk = $db->get($keyPrefix . ':s:' . $key);
                if ($pk[$key] > $currentPk) {
                    $db->set($keyPrefix . ':s:' . $key, $pk[$key]);
                }
            }
        }
        // save pk in a find all pool
        $key = $this->getKey();
        $db->zset($keyPrefix, $key, ArrayHelper::getValue(array_values($pk), 0));

        foreach ($this->getIndexs() as $indexData) {
            $db->zset($indexData['index'], $key, $indexData['weight']);
            $db->hset($keyPrefix . ':index', $indexData['index'], time());
        }

        // save attributes
        foreach ($values as $attribute => $value) {
            if (is_bool($value)) {
                $value = (int)$value;
            }
            $db->hset($key, $attribute, $value);
        }
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);
        return true;
    }

    /**
     * Deletes rows in the table using the provided conditions.
     * WARNING: If you do not specify any condition, this method will delete ALL rows in the table.
     *
     * For example, to delete all customers whose status is 3:
     *
     * ~~~
     * Customer::deleteAll(['status' => 3]);
     * ~~~
     *
     * @param array $condition the conditions that will be put in the WHERE part of the DELETE SQL.
     * Please refer to [[ActiveQuery::where()]] on how to specify this parameter.
     * @return integer the number of rows deleted
     */
    public static function deleteAll($condition = null)
    {
        $records = self::fetchPks($condition);
        if (empty($records)) {
            return 0;
        }
        $db = static::getDb();
        $keyPrefix = static::keyPrefix();

        if (is_null($condition)) {
            $attributeKeys = [];
            $indexKey = $keyPrefix . ':index';
            $indexes = $db->hgetall($indexKey);
            foreach ($indexes as $key => $timestamp) {
                $db->zclear($key);
            }
            $db->hclear($indexKey);

            foreach ($records as $record) {
                $pkv = $keyPrefix . ':a:' . static::buildKey($record->primaryKey);
                $attributeKeys[] = $pkv;
                if (count($attributeKeys) > 1000) {
                    $db->hclear($attributeKeys);
                    $attributeKeys = [];
                }
            }
            $db->hclear($attributeKeys);
            $db->zclear($keyPrefix);
        } else {
            // 删除数据对应的索引列表数据
            foreach ($records as $record) {
                if (!is_null($condition)) {
                    foreach ($record->getIndexs() as $indexData) {
                        $db->zdel($indexData['index'], $record->getKey());
                    }
                }
            }
        }

        return true;
    }

    private static function fetchPks($condition)
    {
        $query = static::find();
        $query->where($condition);
        return $query->all();
    }

    /**
     * 获取字符的各种组合
     * 二位数组为限制自身数组下标允许改变的字符
     *
     * 示例:
     * ```php
     * $array = [1, 2, 3];
     * print_r(comb($array));
     *
     * // 输出内容:
     * // Array
     * //  (
     * //      [0] => 1_2_3
     * //      [1] => 1_2_all
     * //      [2] => 1_all_3
     * //      [3] => 1_all_all
     * //      [4] => all_2_3
     * //      [5] => all_2_all
     * //      [6] => all_all_3
     * //      [7] => all_all_all
     * //  )
     * ```
     *
     * @param array $array 需要组合的数组
     * @param bool $isAddAll 添加all字符
     * @param string|null $delimiter 分隔符 默认不分割返回数组
     * @param string|null $prefix 前缀
     * @return array
     */
    public static function comb(array $array, $isAddAll = true, $delimiter = '_', $prefix = null)
    {
        $count = 1;
        if ($isAddAll) {
            foreach ($array as &$_item) {
                if (!is_array($_item)) {
                    $_item = ['all', $_item];
                }
            }
        }
        foreach ($array as $item) {
            $count *= count($item);
        }

        $data = [];
        $repeatCount = $count;
        foreach ($array as $i => $values) {
            $data[$i] = [];
            $startIndex = 0;
            $fillCount = 0;
            $repeatCount = $repeatCount / count($values);
            do {
                foreach ($values as $j => $item) {
                    $data[$i] = array_merge($data[$i], array_fill($startIndex, $repeatCount, $item));
                    $startIndex += $repeatCount;
                    $fillCount += $repeatCount;
                }
            } while ($fillCount < $count);
        }

        $newData = [];
        foreach ($data as $i => $item) {
            foreach ($item as $k => $value) {
                if (is_null($delimiter) && !is_null($prefix) && 0 == $i) {
                    $newData[$k][] = $prefix . $value;
                    continue;
                }
                $newData[$k][] = $value;
            }
        }

        if (is_null($delimiter)) {
            return $newData;
        } else {
            $strings = [];
            foreach ($newData as $indexName) {
                if (is_null($prefix)) {
                    $strings[] = join($delimiter, $indexName);
                } else {
                    $strings[] = $prefix . join($delimiter, $indexName);
                }
            }

            return $strings;
        }
    }
}
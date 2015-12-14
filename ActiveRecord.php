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
use yii\db\StaleObjectException;
use yii\db\TableSchema;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class ActiveRecord extends BaseActiveRecord
{
    /**
     * @var string Model Class
     */
    public static $modelClass;

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
        $pk = [];
        foreach ($this->primaryKey() as $key) {
            $pk[$key] = $values[$key] = $this->getAttribute($key);
            if ($pk[$key] === null) {
                // use auto increment if pk is null
                $pk[$key] = $values[$key] = $db->incr(static::keyPrefix() . ':s:' . $key);
                $this->setAttribute($key, $values[$key]);
            } elseif (is_numeric($pk[$key])) {
                // if pk is numeric update auto increment value
                $currentPk = $db->get(static::keyPrefix() . ':s:' . $key);
                if ($pk[$key] > $currentPk) {
                    $db->set(static::keyPrefix() . ':s:' . $key, $pk[$key]);
                }
            }
        }
        // save pk in a find all pool
        $db->zset(static::keyPrefix(), static::buildKey($pk), static::buildKey($pk));
        $key = static::keyPrefix() . ':a:' . static::buildKey($pk);
        // save attributes
        foreach ($values as $attribute => $value) {
            if (is_bool($value)) {
                $value = (int) $value;
            }
            var_dump($attribute);
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
        $pks = self::fetchPks($condition);
        if (empty($pks)) {
            return 0;
        }
        $db = static::getDb();
        $attributeKeys = [];
        $db->multi();
        foreach ($pks as $pk) {
            $pk = static::buildKey($pk);
            $db->zclear(static::keyPrefix());
            $attributeKeys[] = static::keyPrefix() . ':a:' . $pk;
        }
        $db->del($attributeKeys);
        $result = $db->exec();
        return end($result);
    }

    private static function fetchPks($condition)
    {
        $query = static::find();
        $query->where($condition);
        $records = $query->all(); // TODO limit fetched columns to pk
        $primaryKey = static::primaryKey();
        $pks = [];
        foreach ($records as $record) {
            $pk = [];
            foreach ($primaryKey as $key) {
                $pk[$key] = $record[$key];
            }
            $pks[] = $pk;
        }
        return $pks;
    }
}
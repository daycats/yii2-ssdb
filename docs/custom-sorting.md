自定义字段排序
=========

创建数据模型
------------

`common/models/ssdb/User.php`
```php
/**
 * This is the ActiveRecord class for [[\common\models\User]].
 *
 * @property string $user_id
 * @property string $name
 * @property integer $age
 * @property integer $status
 */
class User extends \wsl\ssdb\ActiveRecord
{
    /**
     * @var string ModelClass 完整类名
     */
    public static $modelClass = '\console\models\SnsTestUser';

    /**
     * @var array 自定义排序字段顺序
     */
    public static $sortFields = [
        'filter' => ['age', 'status'],
    ];

    /**
     * 排序规则
     *
     * @return array
     */
    public function sortRules()
    {
        return [
            /**
             * 自定义名称,也可以使用数组默认的key
             */
            'filter' => [
                /**
                 * 索引名称
                 *
                 * @return mixed 可以是一个字符、数组、回调函数（返回字符串）、回调函数（返回数组）
                 */
                'index' => function () {
                    return $this->comb([
                        'age' => $this->age,
                        'status' => $this->status,
                    ]);
                },
                /**
                 * 权重
                 *
                 * @return mixed integer或者个回调函数(返回integer)
                 */
                'weight' => function () {
                    return $this->age;
                },
                /**
                 * 默认值为true
                 *
                 * @return mixed boolean或者回调函数(返回boolean)
                 */
                // 'isValid' => true
            ],
        ];
    }

}
```

查询示例
------------

```php
$models = SnsTestUser::find()
    ->orderParams('filter', [
        'status' => 1,
    ], 'desc')
    ->all();
foreach ($models as $itemModel) {
    print_r($itemModel->toArray());
}
```
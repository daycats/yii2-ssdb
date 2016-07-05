Yii2 SSDB
=========

实现了 Active Record、Active Query

> Yii2 SSDB GII 扩展开发中...

github: https://github.com/myweishanli/yii2-ssdb

[![Latest Stable Version](https://poser.pugx.org/myweishanli/yii2-ssdb/v/stable.png)](https://packagist.org/packages/myweishanli/yii2-ssdb)
[![Total Downloads](https://poser.pugx.org/myweishanli/yii2-ssdb/downloads.png)](https://packagist.org/packages/myweishanli/yii2-ssdb)

> 注: 功能正在开发中...

> 更详细的配置说明文档正在编写中...

> QQ群: 137158108 验证信息: github

> 有任何疑问可以发邮件到 myweishanli@gmail.com


安装
------------

安装这个扩展的首选方式是通过 [composer](http://getcomposer.org/download/).

执行

```
composer require myweishanli/yii2-ssdb:dev-master
```
或添加

```
"myweishanli/yii2-ssdb": "dev-master"
```


配置
------------

高级版是`common/config/main-local.php`

基础版是`config/web.php`

```php
'components' => [
    // ...
    'ssdb' => [
        'class' => 'wsl\ssdb\Connection',
        'host' => 'localhost',
        'port' => 8888,
    ],
],
```

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
    public static $modelClass = '\common\models\User';
}
```


Active Record、Active Query使用说明
------------

> 默认只能使用单个主键排序 更多排序查询[自定义排序规则](docs/custom-sorting.md)

> 实际项目可能需求非常复杂，如果下方例子不能满足你的要求可以加QQ群探讨

### 新增或者替换数据

```php
$userModel = new User();
$userModel->user_id = 1000000;
$userModel->name = '张三';
$userModel->age = 19;
$userModel->status = 0;
$userModel->save();
```

### 获取一条数据

```php
$model = User::find()->one();
```

### 获取一条数据 排序

```php
$model = User::find()->orderBy('user_id asc')->one();
```

### 删除全部

```php
User::deleteAll();
```

### 获取一条数据 条件查询

```php
$model = User::find()->andWhere(['user_id' => 1000000])->one();
```

### 获取一条数据 多条件查询

```php
$model = User::find()->andWhere(['user_id' => 1000000, 'age' => 19])->one();
```

### 获取所有数据列表

```php
$models = User::find()->all();
```

### 获取所有数据列表 排序

```php
$models = User::find()->orderBy('age desc')->all();
```

### 获取所有数据列表 条件查询

```php
$models = User::find()->andWhere(['user_id' => 1000000])->all();
```

### 偏移数据和限定数据返回条数

```php
$models = User::find()->offset(1)->limit(1)->all();
```

### 使用`DataProvider`

```php
$dataProvider = new ActiveDataProvider([
    'query' => User::find(),
    'pagination' => [
        'pageSize' => 20,
    ],
]);
foreach ($dataProvider->getModels() as $itemModel) {
    // code...
}
```

更多应用 
------------

- [自定义排序规则](docs/custom-sorting.md)

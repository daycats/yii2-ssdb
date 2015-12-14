Yii2 SSDB
=========

实现了 Active Record、Active Query

> 更多功能开发中...

配置
------------

`@app/config/main.php`

`@app`指你应用的目录 比如你访问的是`backend/web/index.php`那么你就配置`backend/config/main.php`即可
反之你访问的是`frontend/web/index.php`那么你就配置`frontend/config/main.php`即可

高级版是`main.php`

基础版是`web.php`

```php
'components' => [
    'ssdb' => [
        'class' => 'wsl\ssdb\Connection',
        'host' => 'localhost',
        'port' => 8888,
    ],
],
```

使用说明
------------

### 新增或者替换数据

```php
$userModel = new SnsTestUser();
$userModel->user_id = 1000000;
$userModel->name = '张三';
$userModel->age = 19;
$userModel->status = 0;
$userModel->save();
```

### 获取一条数据

```php
$model = SnsTestUser::find()->one();
```

### 获取一条数据 排序

```php
$model = SnsTestUser::find()->orderBy('user_id asc')->one();
```

### 删除全部

```php
SnsTestUser::deleteAll();
```

### 获取一条数据 条件查询

```php
$model = SnsTestUser::find()->andWhere(['user_id' => 1000000])->one();
```

### 获取一条数据 多条件查询

```
$model = SnsTestUser::find()->andWhere(['user_id' => 1000000, 'age' => 19])->one();
```

### 获取所有数据列表

```php
$models = SnsTestUser::find()->all();
```

### 获取所有数据列表 排序

```php
$models = SnsTestUser::find()->orderBy('age desc')->all();
```

### 获取所有数据列表 条件查询

```php
$models = SnsTestUser::find()->andWhere(['user_id' => 1000001])->all();
```

### 分页数据列表

```php
$models = SnsTestUser::find()->offset(1)->limit(1)->all();
```

### 使用`DataProvider`

```php
$dataProvider = new ActiveDataProvider([
    'query' => SnsTestUser::find(),
     'pagination' => [
         'pageSize' => 20,
     ],
]);
foreach ($dataProvider->getModels() as $itemModel) {
    // code...
}
```


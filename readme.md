# phpClickHouse-hyperf
Adapter to Hyperf framework of the most popular libraries:

- https://github.com/the-tinderbox/ClickhouseBuilder - good query builder

## Features
No dependency

More: https://github.com/smi2/phpClickHouse#features

## Prerequisites
- PHP 7.1
- PHP-ext swoole ^4.5.0 > 4.6.0
- Hyperf PHP
- Clickhouse server

## Installation

1. Install via composer

```sh
$ composer require wt1ao/hyperf-clickhouse
```

2. Add new connection into your config/database.php:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST'),
    'port' => env('CLICKHOUSE_PORT','8123'),
    'database' => env('CLICKHOUSE_DATABASE','default'),
    'username' => env('CLICKHOUSE_USERNAME','default'),
    'password' => env('CLICKHOUSE_PASSWORD',''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT',2),
    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY',2),
    'https' => (bool)env('CLICKHOUSE_HTTPS', null),
    'retries' => env('CLICKHOUSE_RETRIES', 0),
    'settings' => [ // optional
        'max_partitions_per_insert_block' => 300,
    ],
],
```

Then patch your .env:

```sh
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=default
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=
CLICKHOUSE_TIMEOUT_CONNECT=2
CLICKHOUSE_TIMEOUT_QUERY=2
# only if you use https connection
CLICKHOUSE_HTTPS=true
```

3. Used

You can use smi2/phpClickHouse functionality directly:

```php
/** @var \ClickHouseDB\Client $db */
$db = Clickhouse::connection('clickhouse')->getClient();
$statement = $db->select('SELECT * FROM summing_url_views LIMIT 2');
```

More about $db see here: https://github.com/smi2/phpClickHouse/blob/master/README.md

Or use dawnings of Eloquent ORM (will be implemented completely)

1. Add model

```php
<?php


namespace App\Models\Clickhouse;

use Xtwoend\HyperfClickhouse\Model;

class MyTable extends Model
{
    // Not necessary. Can be obtained from class name MyTable => my_table
    protected $table = 'my_table';

}
```

2. Add migration

```php
<?php


class CreateMyTable extends \Xtwoend\HyperfClickhouse\Migration
{
    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        static::write('
            CREATE TABLE my_table (
                id UInt32,
                created_at DateTime,
                field_one String,
                field_two Int32
            )
            ENGINE = MergeTree()
            ORDER BY (id)
        ');
    }

    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        static::write('DROP TABLE my_table');
    }
}
```

3. And then you can insert data

One row
```php
$model = MyTable::create(['model_name' => 'model 1', 'some_param' => 1]);
# or
$model = MyTable::make(['model_name' => 'model 1']);
$model->some_param = 1;
$model->save();
# or
$model = new MyTable();
$model->fill(['model_name' => 'model 1', 'some_param' => 1])->save();
```
Or bulk insert

### Non assoc way
    MyTable::insertBulk([['model 1', 1], ['model 2', 2]], ['model_name', 'some_param']);
### Assoc way
    MyTable::insertAssoc([['model_name' => 'model 1', 'some_param' => 1], ['some_param' => 2, 'model_name' => 'model 2']]);


4. Now check out the query builder
```php
$rows = MyTable::select(['field_one', new RawColumn('sum(field_two)', 'field_two_sum')])
    ->where('created_at', '>', '2020-09-14 12:47:29')
    ->groupBy('field_one')
    ->get();

var_dump($rows->rows);
```

## Advanced usage
Retries
You may enable ability to retry requests while received not 200 response, maybe due network connectivity problems.

Patch your .env:

    CLICKHOUSE_RETRIES=2
    
retries is optional, default value is 0.
0 mean only one attempt.
1 mean one attempt + 1 retry while error (total 2 attempts).

## Working with huge rows
You can chunk results like in Laravel
```php
// Split the result into chunks of 30 rows 
$rows = MyTable::select(['field_one', 'field_two'])
    ->chunk(30, function ($rows) {
        foreach ($rows as $row) {
            echo $row['field_two'] . "\n";
        }
    });
```

Buffer engine for insert queries
See https://clickhouse.tech/docs/en/engines/table-engines/special/buffer/

```php
<?php

namespace App\Models\Clickhouse;

use Xtwoend\HyperfClickhouse\Model;

class MyTable extends Model
{
    // Not necessary. Can be obtained from class name MyTable => my_table
    protected $table = 'my_table';
    // All inserts will be in the table $tableForInserts 
    // But all selects will be from $table
    protected $tableForInserts = 'my_table_buffer';
}
```
    
If you also want to read from your buffer table, put its name in $table
```php
<?php

namespace App\Models\Clickhouse;

use Xtwoend\HyperfClickhouse\Model;

class MyTable extends Model
{
    protected $table = 'my_table_buffer';
}
```

OPTIMIZE Statement
See https://clickhouse.com/docs/ru/sql-reference/statements/optimize/
```php
MyTable::optimize($final = false, $partition = null);
```
## Deletions
See https://clickhouse.com/docs/en/sql-reference/statements/alter/delete/
```php
MyTable::where('field_one', 123)->delete();
```
Using buffer engine and performing OPTIMIZE or ALTER TABLE DELETE
```php
<?php

namespace App\Models\Clickhouse;

use Xtwoend\HyperfClickhouse\Model;

class MyTable extends Model
{
    // All SELECT's and INSERT's on $table
    protected $table = 'my_table_buffer';
    // OPTIMIZE and DELETE on $tableSources
    protected $tableSources = 'my_table';
}
```


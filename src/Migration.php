<?php

namespace Xtwoend\HyperfClickhouse;

use Hyperf\Database\Migrations\Migration as BaseMigration;

class Migration extends BaseMigration
{
    /***
     * @param string $sql
     * @param string $connection
     * @return \ClickHouseDB\Statement
     */
    protected static function write(string $sql, string $connection = 'clickhouse')
    {
        /** @var \ClickHouseDB\Client $client */
        $client = Clickhouse::connection($connection)->getClient();
        return $client->writeOne($sql);
    }
}
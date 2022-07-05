<?php

namespace App\Models;

use App\Config\DBConfig;

abstract class BaseModel
{
    static string $table;

    public function getConnection(): \MongoDB\Client
    {
        return new \MongoDB\Client(DBConfig::HOST, ['username' => DBConfig::USER, 'password' => DBConfig::PASSWORD]);
    }

    public function getCollection(): \MongoDB\Collection
    {
        return static::getConnection()->selectDatabase(DBConfig::DATABASE)->selectCollection(static::$table);
    }
}

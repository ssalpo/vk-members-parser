<?php

namespace App\Config;

interface DBConfig
{
    const HOST = 'mongodb://localhost:27017';
    const DATABASE = 'vk_members_app';
    const USER = 'admin';
    const PASSWORD = 'admin';
}

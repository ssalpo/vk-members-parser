<?php

namespace App\Models;

class MemberModel extends BaseModel
{
    public static string $table = 'members';

    public function saveMembers(array $data): \MongoDB\InsertManyResult
    {
        return $this->getCollection()->insertMany($data);
    }
}

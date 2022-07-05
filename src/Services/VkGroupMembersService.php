<?php

namespace App\Services;

use App\Helpers\ArrHelper;
use App\Models\MemberModel;
use GuzzleHttp\Client;

class VkGroupMembersService
{
    const API_VERSION = 5.131;
    const API_BASE_URL = 'https://api.vk.com/';
    const MAX_RECORDS_LIMIT = 25000;

    private string $token;
    private int $groupId;
    private Client $client;
    private MemberModel $memberModel;

    public int $loadMembersCount = 0;
    public int $groupMembersCount = 0;

    public function __construct(string $token, int $groupId)
    {
        $this->client = new Client(['base_uri' => self::API_BASE_URL]);
        $this->memberModel = new MemberModel;

        $this->token = $token;
        $this->groupId = $groupId;
        $this->groupMembersCount = $this->getMembersCount();
    }

    /**
     * Возвращает кол-во участников группы
     *
     * @return int
     * @throws \VK\Exceptions\Api\VKApiParamGroupIdException
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getMembersCount()
    {
        return json_decode(
            $this->client->post('/method/groups.getMembers', [
                'form_params' => [
                    'group_id' => $this->groupId,
                    'access_token' => $this->token,
                    'count' => 0,
                    'v' => self::API_VERSION
                ]
            ])->getBody()->getContents(),
            true
        )['response']['count'];
    }

    /**
     * Парсит список участников группы
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parseMembers(): void
    {
        $requestContent = $this->client->post('/method/execute', [
            'form_params' => [
                'code' => $this->getCodeTemplate($this->loadMembersCount, $this->groupMembersCount),
                'access_token' => $this->token,
                'v' => self::API_VERSION
            ]
        ])->getBody()->getContents();

        $result = json_decode($requestContent, true);

        if (isset($result['response']['execute_errors'])) return;

        $members = ArrHelper::flatten($result['response']);
        $countMembers = count($members);

        $this->loadMembersCount += $countMembers;

        if ($countMembers) {
            $this->saveGroupMembers($members);
            echo sprintf('Сохранено %s участников...', $countMembers) . PHP_EOL;
        };

        if ($this->loadMembersCount < $this->groupMembersCount && $countMembers > 0) {
            unset($result);
            unset($members);

            sleep(1);
            $this->parseMembers();
        }
    }

    /**
     * Сохраняет список участников
     *
     * @param array $members
     */
    public function saveGroupMembers(array $members): \Generator
    {
        yield $this->memberModel->saveMembers(
            array_map(
                function ($item) {
                    $bdate = $item['bdate'] ?? "";
                    $age = count(explode(".", $bdate)) === 3 ? $this->ageFromDate($bdate) : null;

                    return [
                        'first_name' => $item['first_name'],
                        'last_name' => $item['last_name'],
                        'city' => $item['city'] ?? null,
                        'country' => $item['country'] ?? null,
                        'bdate' => $item['bdate'] ?? null,
                        'age' => $age,
                    ];
                },
                $members
            )
        );
    }

    /**
     * Шаблон VSScript для отправки запросов внутри ВК
     *
     * @param int $offset
     * @param int $membersCount
     *
     * @return string
     */
    public function getCodeTemplate(int $offset, int $membersCount = 0): string
    {
        $offsetLimit = $membersCount >= self::MAX_RECORDS_LIMIT ? self::MAX_RECORDS_LIMIT : $membersCount;
        $maxCount = $membersCount > 1000 ? 1000 : $membersCount;

        return <<<CODE
var members = [];
var offset = 0;

while (offset < $offsetLimit) {
    var params = {
        "group_id": {$this->groupId},
        "v": "5.131",
        "sort": "id_asc",
        "count": $maxCount,
        "fields": "country,city,bdate",
        "offset": $offset
    };

    members.push(API.groups.getMembers(params).items);
    offset = offset + 1000;
}

return members;
CODE;
    }

    /**
     * Вычисляет возвраст на основе даты рождения пользователя
     *
     * @param string $bdate
     * @return int
     */
    private function ageFromDate(string $bdate): ?int
    {
        return date_diff(date_create($bdate), date_create('now'))->y;
    }
}

<?php
declare(strict_types=1);

namespace App\Service\Impl;


use App\Service\Shared;
use App\Util\Str;
use GuzzleHttp\Client;
use Kernel\Annotation\Inject;
use Kernel\Exception\JSONException;

class SharedService implements Shared
{

    #[Inject]
    private Client $http;


    /**
     * @param string $url
     * @param string $appId
     * @param string $appKey
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Kernel\Exception\JSONException
     */
    private function post(string $url, string $appId, string $appKey, array $data = []): array
    {
        $data = array_merge($data, ["app_id" => $appId, "app_key" => $appKey]);
        $data['sign'] = Str::generateSignature($data, $appKey);
        try {
            $response = $this->http->post($url, ["form_params" => $data]);
        } catch (\Exception $e) {
            throw new JSONException("连接失败");
        }

        $contents = $response->getBody()->getContents();
        $result = json_decode($contents, true);
        if ($result['code'] != 200) {
            throw new JSONException($result['msg']);
        }
        return (array)$result['data'];
    }

    public function connect(string $domain, string $appId, string $appKey): ?array
    {
        return $this->post($domain . "/shared/authentication/connect", $appId, $appKey);
    }

    public function items(\App\Model\Shared $shared): ?array
    {
        return $this->post($shared->domain . "/shared/commodity/items", $shared->app_id, $shared->app_key);
    }

    public function inventoryState(\App\Model\Shared $shared, string $sharedCode, int $cardId, int $num): bool
    {
        $this->post($shared->domain . "/shared/commodity/inventoryState", $shared->app_id, $shared->app_key, [
            "shared_code" => $sharedCode,
            "card_id" => $cardId,
            "num" => $num
        ]);

        return true;
    }

    public function trade(\App\Model\Shared $shared, string $sharedCode, string $contact, int $num, int $cardId, int $device, string $password): string
    {
        $trade = $this->post($shared->domain . "/shared/commodity/trade", $shared->app_id, $shared->app_key, [
            "shared_code" => $sharedCode,
            "contact" => $contact,
            "num" => $num,
            "card_id" => $cardId,
            "device" => $device,
            "password" => $password
        ]);
        return (string)$trade['secret'];
    }

    /**
     * @param \App\Model\Shared $shared
     * @param string $sharedCode
     * @param int $page
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Kernel\Exception\JSONException
     */
    public function draftCard(\App\Model\Shared $shared, string $sharedCode, int $page): array
    {
        $card = $this->post($shared->domain . "/shared/commodity/draftCard", $shared->app_id, $shared->app_key, [
            "sharedCode" => $sharedCode,
            "page" => $page
        ]);
        return (array)$card;
    }

    /**
     * @param \App\Model\Shared $shared
     * @param string $sharedCode
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Kernel\Exception\JSONException
     */
    public function inventory(\App\Model\Shared $shared, string $sharedCode): array
    {
        $inventory = $this->post($shared->domain . "/shared/commodity/inventory", $shared->app_id, $shared->app_key, [
            "sharedCode" => $sharedCode
        ]);
        return (array)$inventory;
    }
}
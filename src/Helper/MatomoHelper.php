<?php
namespace Joomla\Module\MatomoCounter\Site\Helper;

defined('_JEXEC') || die;

use Joomla\CMS\Http\HttpFactory;

class MatomoHelper
{
    public static function getStats(string $url, int $siteId, string $token)
    {
        // Базовые параметры для построения bulk-запроса
        $methods = [
            'online' => 'method=Live.getCounters&lastMinutes=3', // 3 минуты для статуса Онлайн
            'today'  => 'method=VisitsSummary.getUniqueVisitors&period=day&date=today',
            'week'   => 'method=VisitsSummary.getVisits&period=range&date=last7'
        ];

        // Собираем массив параметров для bulk-отправки
        $apiParams = [
            'module'     => 'API',
            'method'     => 'API.getBulkRequest',
            'idSite'     => $siteId,
            'token_auth' => $token,
            'format'     => 'json'
        ];

        $i = 0;
        foreach ($methods as $key => $methodStr) {
            $apiParams["urls[$i]"] = $methodStr;
            $i++;
        }

        try {
            $http = HttpFactory::getHttp();
            $response = $http->get($url . '?' . http_build_query($apiParams), [], 5); // Таймаут 5 сек.

            if ($response && $response->code === 200) {
                $data = json_decode($response->body, true);
                
                if (is_array($data) && isset($data[0])) {
                    return [
                        'online' => (int) ($data[0][0]['visitors'] ?? 0),
                        'today'  => (int) ($data[1]['value'] ?? 0),
                        'week'   => (int) ($data[2]['value'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            // В продакшене лучше логировать ошибки $e->getMessage()
        }

        return ['online' => 0, 'today' => 0, 'week' => 0];
    }
}
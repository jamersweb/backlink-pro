<?php

namespace App\Services\Google;

use Google_Service_SearchConsole;
use App\Models\ConnectedAccount;
use App\Services\Google\GoogleSeoClientFactory;
use Illuminate\Support\Facades\Log;

class SearchConsoleService
{
    protected $service;
    protected $connectedAccount;

    public function __construct(ConnectedAccount $connectedAccount)
    {
        $this->connectedAccount = $connectedAccount;
        $client = GoogleSeoClientFactory::create($connectedAccount);
        $this->service = new Google_Service_SearchConsole($client);
    }

    /**
     * List available sites (properties)
     */
    public function listSites(): array
    {
        try {
            $sites = $this->service->sites->listSites();
            $result = [];

            foreach ($sites->getSiteEntry() as $site) {
                $result[] = [
                    'siteUrl' => $site->getSiteUrl(),
                    'permissionLevel' => $site->getPermissionLevel(),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('GSC list sites failed', [
                'error' => $e->getMessage(),
                'account_id' => $this->connectedAccount->id,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch daily metrics
     */
    public function fetchDailyMetrics(string $siteUrl, \DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $request = new \Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate->format('Y-m-d'));
            $request->setEndDate($endDate->format('Y-m-d'));
            $request->setDimensions(['date']);

            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();

            $metrics = [];
            foreach ($rows as $row) {
                $keys = $row->getKeys();
                $metrics[] = [
                    'date' => $keys[0],
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition(),
                ];
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::error('GSC fetch daily metrics failed', [
                'error' => $e->getMessage(),
                'site_url' => $siteUrl,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch top pages
     */
    public function fetchTopPages(string $siteUrl, \DateTime $startDate, \DateTime $endDate, int $limit = 250): array
    {
        try {
            $request = new \Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate->format('Y-m-d'));
            $request->setEndDate($endDate->format('Y-m-d'));
            $request->setDimensions(['page']);
            $request->setRowLimit($limit);

            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();

            $pages = [];
            foreach ($rows as $row) {
                $keys = $row->getKeys();
                $pages[] = [
                    'page' => $keys[0],
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition(),
                ];
            }

            return $pages;
        } catch (\Exception $e) {
            Log::error('GSC fetch top pages failed', [
                'error' => $e->getMessage(),
                'site_url' => $siteUrl,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch top queries
     */
    public function fetchTopQueries(string $siteUrl, \DateTime $startDate, \DateTime $endDate, int $limit = 250): array
    {
        try {
            $request = new \Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate->format('Y-m-d'));
            $request->setEndDate($endDate->format('Y-m-d'));
            $request->setDimensions(['query']);
            $request->setRowLimit($limit);

            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();

            $queries = [];
            foreach ($rows as $row) {
                $keys = $row->getKeys();
                $queries[] = [
                    'query' => $keys[0],
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition(),
                ];
            }

            return $queries;
        } catch (\Exception $e) {
            Log::error('GSC fetch top queries failed', [
                'error' => $e->getMessage(),
                'site_url' => $siteUrl,
            ]);
            throw $e;
        }
    }
}



<?php

namespace App\Services\Google;

use Google_Service_AnalyticsData;
use Google_Service_AnalyticsAdmin;
use App\Models\ConnectedAccount;
use App\Services\Google\GoogleSeoClientFactory;
use Illuminate\Support\Facades\Log;

class Ga4Service
{
    protected $dataService;
    protected $adminService;
    protected $connectedAccount;

    public function __construct(ConnectedAccount $connectedAccount)
    {
        $this->connectedAccount = $connectedAccount;
        $client = GoogleSeoClientFactory::create($connectedAccount);
        $this->dataService = new Google_Service_AnalyticsData($client);
        $this->adminService = new Google_Service_AnalyticsAdmin($client);
    }

    /**
     * List available GA4 properties
     */
    public function listProperties(): array
    {
        try {
            $accounts = $this->adminService->accounts->listAccounts();
            $properties = [];

            foreach ($accounts->getAccounts() as $account) {
                $accountProperties = $this->adminService->properties->listProperties([
                    'filter' => 'parent:"accounts/' . $account->getName() . '"',
                ]);

                foreach ($accountProperties->getProperties() as $property) {
                    $properties[] = [
                        'propertyName' => $property->getName(), // e.g. "properties/123456789"
                        'displayName' => $property->getDisplayName(),
                    ];
                }
            }

            return $properties;
        } catch (\Exception $e) {
            Log::error('GA4 list properties failed', [
                'error' => $e->getMessage(),
                'account_id' => $this->connectedAccount->id,
            ]);
            throw $e;
        }
    }

    /**
     * Run daily report
     */
    public function runDailyReport(string $propertyId, \DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $request = new \Google_Service_AnalyticsData_RunReportRequest();
            $request->setDateRanges([
                new \Google_Service_AnalyticsData_DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ]);
            $request->setDimensions([
                new \Google_Service_AnalyticsData_Dimension(['name' => 'date']),
            ]);
            $request->setMetrics([
                new \Google_Service_AnalyticsData_Metric(['name' => 'sessions']),
                new \Google_Service_AnalyticsData_Metric(['name' => 'totalUsers']),
                new \Google_Service_AnalyticsData_Metric(['name' => 'engagedSessions']),
                new \Google_Service_AnalyticsData_Metric(['name' => 'engagementRate']),
            ]);

            $response = $this->dataService->properties->runReport($propertyId, $request);
            $rows = $response->getRows();

            $metrics = [];
            foreach ($rows as $row) {
                $dimensionValues = $row->getDimensionValues();
                $metricValues = $row->getMetricValues();

                $metrics[] = [
                    'date' => $dimensionValues[0]->getValue(),
                    'sessions' => (int) $metricValues[0]->getValue(),
                    'total_users' => (int) $metricValues[1]->getValue(),
                    'engaged_sessions' => (int) $metricValues[2]->getValue(),
                    'engagement_rate' => (float) $metricValues[3]->getValue(),
                ];
            }

            return $metrics;
        } catch (\Exception $e) {
            Log::error('GA4 run daily report failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
            ]);
            throw $e;
        }
    }

    /**
     * Run landing pages report
     */
    public function runLandingPagesReport(string $propertyId, \DateTime $startDate, \DateTime $endDate, int $limit = 250): array
    {
        try {
            $request = new \Google_Service_AnalyticsData_RunReportRequest();
            $request->setDateRanges([
                new \Google_Service_AnalyticsData_DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ]);
            $request->setDimensions([
                new \Google_Service_AnalyticsData_Dimension(['name' => 'landingPagePlusQueryString']),
            ]);
            $request->setMetrics([
                new \Google_Service_AnalyticsData_Metric(['name' => 'sessions']),
                new \Google_Service_AnalyticsData_Metric(['name' => 'totalUsers']),
            ]);
            $request->setLimit($limit);

            $response = $this->dataService->properties->runReport($propertyId, $request);
            $rows = $response->getRows();

            $pages = [];
            foreach ($rows as $row) {
                $dimensionValues = $row->getDimensionValues();
                $metricValues = $row->getMetricValues();

                $pages[] = [
                    'landing_page' => $dimensionValues[0]->getValue(),
                    'sessions' => (int) $metricValues[0]->getValue(),
                    'total_users' => (int) $metricValues[1]->getValue(),
                ];
            }

            return $pages;
        } catch (\Exception $e) {
            Log::error('GA4 run landing pages report failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
            ]);
            throw $e;
        }
    }
}



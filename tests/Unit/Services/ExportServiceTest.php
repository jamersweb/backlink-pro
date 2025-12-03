<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_be_initialized()
    {
        // Service uses static methods, so we just verify the class exists
        $this->assertTrue(class_exists(ExportService::class));
    }

    public function test_export_csv_generates_valid_csv()
    {
        $data = [
            ['John Doe', 'john@example.com', 'Active'],
            ['Jane Smith', 'jane@example.com', 'Inactive'],
        ];
        $headers = ['Name', 'Email', 'Status'];

        $response = ExportService::exportCsv($data, $headers, 'test-export.csv');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test-export.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_export_json_generates_valid_json()
    {
        $data = [
            ['id' => 1, 'name' => 'Test'],
            ['id' => 2, 'name' => 'Test 2'],
        ];

        $response = ExportService::exportJson($data, 'test-export.json');

        $this->assertEquals(200, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('Test', $decoded[0]['name']);
    }
}


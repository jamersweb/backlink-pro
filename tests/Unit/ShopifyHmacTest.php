<?php

namespace Tests\Unit;

use App\Http\Controllers\ShopifyOAuthController;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ShopifyHmacTest extends TestCase
{
    public function test_verify_hmac_returns_false_when_no_secret(): void
    {
        Config::set('shopify.api_secret', '');
        $params = ['code' => 'abc', 'hmac' => 'def', 'shop' => 'x.myshopify.com'];
        $this->assertFalse(ShopifyOAuthController::verifyHmac($params));
    }

    public function test_verify_hmac_returns_false_for_tampered_params(): void
    {
        Config::set('shopify.api_secret', 'my-secret');
        $params = [
            'code' => 'abc',
            'shop' => 'x.myshopify.com',
            'state' => 's1',
            'hmac' => 'wrong-hmac-value',
        ];
        $this->assertFalse(ShopifyOAuthController::verifyHmac($params));
    }

    public function test_verify_hmac_returns_true_for_valid_hmac(): void
    {
        $secret = 'hush';
        Config::set('shopify.api_secret', $secret);

        $params = [
            'code' => '0907a61c0c8d55e99db179b68161bc00',
            'shop' => 'example.myshopify.com',
            'state' => '0.6784241404160823',
            'timestamp' => '1337178173',
        ];
        $str = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $hmac = hash_hmac('sha256', $str, $secret, false);
        $params['hmac'] = $hmac;

        $this->assertTrue(ShopifyOAuthController::verifyHmac($params));
    }
}

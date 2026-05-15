<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SmsApiClientService
{
    public function sendDistribution(array $payload): Response
    {
        $baseUrl = rtrim((string) config('services.sms_api.base_url'), '/');
        $token = (string) config('services.sms_api.token');

        return Http::timeout(60)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-SMS-API-KEY' => $token,
            ])
            ->post($baseUrl . '/api/distributions/manual', $payload);
    }
}
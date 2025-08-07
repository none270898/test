<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private ?string $appId;
    private ?string $restApiKey;
    private string $baseUrl = 'https://onesignal.com/api/v1';

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->restApiKey = config('services.onesignal.rest_api_key');
        
        // Debug logging
        Log::info('OneSignal Service initialized', [
            'app_id_set' => !empty($this->appId),
            'rest_api_key_set' => !empty($this->restApiKey),
            'app_id_length' => $this->appId ? strlen($this->appId) : 0,
        ]);
    }

    public function sendNotification(array $data): bool
    {
        Log::info('OneSignal sendNotification called', ['data' => $data]);
        
        if (!$this->appId || !$this->restApiKey) {
            Log::warning('OneSignal not configured properly', [
                'app_id_set' => !empty($this->appId),
                'rest_api_key_set' => !empty($this->restApiKey)
            ]);
            return false;
        }

        try {
            $payload = array_merge($data, [
                'app_id' => $this->appId,
            ]);
            
            Log::info('OneSignal API request payload', ['payload' => $payload]);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->restApiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/notifications', $payload);

            Log::info('OneSignal API response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                Log::info('OneSignal notification sent successfully', ['response' => $response->json()]);
                return true;
            }

            Log::error('OneSignal notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('OneSignal notification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function sendToUser(string $userId, array $notification): bool
    {
        Log::info('OneSignal sendToUser called', [
            'user_id' => $userId,
            'notification' => $notification
        ]);
        
        return $this->sendNotification(array_merge($notification, [
            'filters' => [
                ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => $userId]
            ]
        ]));
    }
    
    public function sendToAll(array $notification): bool
    {
        Log::info('OneSignal sendToAll called', ['notification' => $notification]);
        
        return $this->sendNotification(array_merge($notification, [
            'included_segments' => ['All']
        ]));
    }
    
    // Test method
    public function sendTestNotification(): bool
    {
        Log::info('OneSignal test notification triggered');
        
        $testNotification = [
            'contents' => [
                'en' => 'Test notification from CryptoNote.pl',
                'pl' => 'Testowa notyfikacja z CryptoNote.pl'
            ],
            'headings' => [
                'en' => 'Test Notification',
                'pl' => 'Testowa Notyfikacja'
            ],
            'included_segments' => ['All'],
            'data' => [
                'type' => 'test',
                'timestamp' => now()->toISOString(),
            ],
        ];
        
        return $this->sendNotification($testNotification);
    }
}
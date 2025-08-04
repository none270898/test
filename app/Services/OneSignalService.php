<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private $appId;
    private $apiKey;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.api_key');
    }

    public function sendToUser($userId, $title, $message, $data = [])
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user || !$user->onesignal_player_id || !$user->push_notifications) {
            return false;
        }

        return $this->sendNotification([
            'include_player_ids' => [$user->onesignal_player_id],
            'headings' => ['en' => $title, 'pl' => $title],
            'contents' => ['en' => $message, 'pl' => $message],
            'data' => $data,
            'url' => config('app.url') . '/dashboard',
        ]);
    }

    public function sendToUsers($userIds, $title, $message, $data = [])
    {
        $users = \App\Models\User::whereIn('id', $userIds)
            ->whereNotNull('onesignal_player_id')
            ->where('push_notifications', true)
            ->get();

        if ($users->isEmpty()) {
            return false;
        }

        $playerIds = $users->pluck('onesignal_player_id')->toArray();

        return $this->sendNotification([
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title, 'pl' => $title],
            'contents' => ['en' => $message, 'pl' => $message],
            'data' => $data,
            'url' => config('app.url') . '/dashboard',
        ]);
    }

    public function sendToSegment($segment, $title, $message, $data = [])
    {
        return $this->sendNotification([
            'included_segments' => [$segment],
            'headings' => ['en' => $title, 'pl' => $title],
            'contents' => ['en' => $message, 'pl' => $message],
            'data' => $data,
            'url' => config('app.url') . '/dashboard',
        ]);
    }

    private function sendNotification($params)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', array_merge([
                'app_id' => $this->appId,
            ], $params));

            if ($response->successful()) {
                Log::info('OneSignal notification sent successfully', $response->json());
                return true;
            } else {
                Log::error('OneSignal notification failed', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('OneSignal service error: ' . $e->getMessage());
            return false;
        }
    }

    public function createUser($email, $userId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/players', [
                'app_id' => $this->appId,
                'device_type' => 5, // Web Push
                'identifier' => $email,
                'external_user_id' => $userId,
            ]);

            return $response->successful() ? $response->json() : null;

        } catch (\Exception $e) {
            Log::error('OneSignal create user error: ' . $e->getMessage());
            return null;
        }
    }
}
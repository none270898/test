<?php
namespace App\Channels;

use App\Services\OneSignalService;
use Illuminate\Notifications\Notification;

class OneSignalChannel
{
    public function __construct(private OneSignalService $oneSignalService)
    {
    }

    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toOneSignal')) {
            return;
        }

        $data = $notification->toOneSignal($notifiable);
        
        return $this->oneSignalService->sendToUser(
            (string) $notifiable->id,
            $data
        );
    }
}
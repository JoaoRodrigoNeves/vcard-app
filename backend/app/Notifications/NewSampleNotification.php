<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use YieldStudio\LaravelExpoNotifier\ExpoNotificationsChannel;
use YieldStudio\LaravelExpoNotifier\Dto\ExpoMessage;

class NewSampleNotification extends Notification
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return [ExpoNotificationsChannel::class];
    }

    public function toExpoNotification($notifiable): ExpoMessage
    {
        $customData = json_decode($notifiable->custom_data)->expoTokens;
        return (new ExpoMessage())
            ->to($customData)
            ->title($this->data["title"])
            ->body($this->data["message"]);
    }
}

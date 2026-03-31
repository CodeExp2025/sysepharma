<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockRequestedNotification extends Notification
{
    use Queueable;

    public $stockRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(StockRequest $stockRequest)
    {
        $this->stockRequest = $stockRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $requester = $this->stockRequest->user->name;
        $depot = $this->stockRequest->depot ? $this->stockRequest->depot->name : 'Aucun';

        return [
            'stock_request_id' => $this->stockRequest->id,
            'requester' => $requester,
            'depot' => $depot,
            'message' => "Nouvelle demande de stock de {$requester} (Dépôt: {$depot}).",
        ];
    }
}

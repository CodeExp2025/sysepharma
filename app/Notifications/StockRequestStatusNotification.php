<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockRequestStatusNotification extends Notification
{
    use Queueable;

    public $stockRequest;
    public $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(StockRequest $stockRequest, string $status)
    {
        $this->stockRequest = $stockRequest;
        $this->status = $status;
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
        $statusLabel = match($this->status) {
            'approved' => 'approuvée',
            'rejected' => 'rejetée',
            'completed' => 'complétée',
            default => $this->status,
        };

        return [
            'stock_request_id' => $this->stockRequest->id,
            'status' => $this->status,
            'message' => "Votre demande de stock #{$this->stockRequest->id} a été {$statusLabel}.",
        ];
    }
}

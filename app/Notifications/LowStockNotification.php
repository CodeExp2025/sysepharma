<?php

namespace App\Notifications;

use App\Models\Drug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public $drug;
    public $currentStock;

    /**
     * Create a new notification instance.
     */
    public function __construct(Drug $drug, int $currentStock)
    {
        $this->drug = $drug;
        $this->currentStock = $currentStock;
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
        return [
            'drug_id' => $this->drug->id,
            'drug_name' => $this->drug->name,
            'current_stock' => $this->currentStock,
            'min_stock' => $this->drug->min_stock,
            'message' => "Attention: Stock faible pour {$this->drug->name}. Actuel: {$this->currentStock} (Min: {$this->drug->min_stock})",
        ];
    }
}

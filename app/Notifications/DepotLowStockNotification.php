<?php

namespace App\Notifications;

use App\Models\Depot;
use App\Models\Drug;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DepotLowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Depot $depot,
        public Drug $drug,
        public int $remaining,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'depot_id' => $this->depot->id,
            'drug_id' => $this->drug->id,
            'remaining' => $this->remaining,
            'message' => "Stock faible: {$this->drug->name} au dépôt {$this->depot->name} (reste {$this->remaining}).",
        ];
    }
}


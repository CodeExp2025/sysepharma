<?php

namespace App\Notifications;

use App\Models\Depot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Depot $depot,
        public int $count,
        public User $performedBy,
        public int $transferId,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transfer_id' => $this->transferId,
            'transfer_depot_id' => $this->depot->id,
            'transfer_depot_name' => $this->depot->name,
            'transfer_count' => $this->count,
            'performed_by_id' => $this->performedBy->id,
            'performed_by_name' => $this->performedBy->name,
            'message' => "Transfert effectué vers le dépôt {$this->depot->name} ({$this->count} unité(s)).",
        ];
    }
}

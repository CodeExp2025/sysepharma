<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Sale $sale,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $drugName = $this->sale->drugUnit?->drug?->name ?? 'Médicament';
        $depotName = $this->sale->depot?->name ?? 'Dépôt';
        $barcode = $this->sale->drugUnit?->barcode ?? '';
        $sellerName = $this->sale->seller?->name ?? 'Utilisateur';

        return [
            'sale_id' => $this->sale->id,
            'depot_id' => $this->sale->depot_id,
            'drug_id' => $this->sale->drugUnit?->drug_id,
            'message' => "Vente enregistrée: {$drugName} ({$barcode}) vendu à {$depotName} par {$sellerName}.",
        ];
    }
}


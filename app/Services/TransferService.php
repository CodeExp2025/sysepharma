<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\DrugUnit;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferService
{
    /**
     * Transfer units (with partial quantity support) from Pharmacy to Depot.
     *
     * @param array $items  Array of ['drug_unit_id' => int, 'quantity' => int]
     * @param Depot $targetDepot
     * @param User $user
     * @return Transfer
     */
    public function transferToDepot(array $items, Depot $targetDepot, User $user): Transfer
    {
        return DB::transaction(function () use ($items, $targetDepot, $user) {
            $unitIds = collect($items)->pluck('drug_unit_id')->unique()->values()->all();
            $units   = DrugUnit::whereIn('id', $unitIds)->lockForUpdate()->get()->keyBy('id');

            if ($units->count() !== count($unitIds)) {
                throw new Exception('Certaines unités sont introuvables.');
            }

            foreach ($items as $item) {
                $unit     = $units[$item['drug_unit_id']];
                $quantity = (int) ($item['quantity'] ?? 1);

                if ($unit->status !== 'en_stock') {
                    throw new Exception("L'unité {$unit->barcode} n'est pas en stock (Statut: {$unit->status}).");
                }

                if ($unit->current_location_type !== 'pharmacy') {
                    throw new Exception("L'unité {$unit->barcode} ne se trouve pas à la pharmacie.");
                }

                if ($quantity < 1 || $quantity > $unit->quantite_actuelle) {
                    throw new Exception("Quantité invalide ({$quantity}) pour l'unité {$unit->barcode} (disponible: {$unit->quantite_actuelle}).");
                }
            }

            $fromPharmacyId = $units->first()?->current_location_id ?? ($user->pharmacy_id ?? null);

            $transfer = Transfer::create([
                'from_pharmacy_id' => $fromPharmacyId,
                'to_depot_id'      => $targetDepot->id,
                'performed_by'     => $user->id,
                'status'           => 'completed',
                'items_count'      => count($items),
                'performed_at'     => now(),
            ]);

            foreach ($items as $item) {
                $unit     = $units[$item['drug_unit_id']];
                $quantity = (int) ($item['quantity'] ?? 1);

                if ($quantity >= $unit->quantite_actuelle) {
                    // Full transfer: move the whole unit to depot
                    TransferItem::create([
                        'transfer_id'  => $transfer->id,
                        'drug_unit_id' => $unit->id,
                        'quantity'     => $unit->quantite_actuelle,
                    ]);

                    StockMovement::create([
                        'drug_unit_id' => $unit->id,
                        'transfer_id'  => $transfer->id,
                        'from_type'    => 'pharmacy',
                        'from_id'      => $unit->current_location_id,
                        'to_type'      => 'depot',
                        'to_id'        => $targetDepot->id,
                        'action'       => 'transfer',
                        'performed_by' => $user->id,
                        'performed_at' => now(),
                    ]);

                    $unit->update([
                        'current_location_type' => 'depot',
                        'current_location_id'   => $targetDepot->id,
                    ]);
                } else {
                    // Partial transfer: reduce source, create new unit at depot
                    $unit->decrement('quantite_actuelle', $quantity);

                    $depotUnit = DrugUnit::create([
                        'drug_id'               => $unit->drug_id,
                        'price'                 => $unit->price,
                        'barcode'               => $unit->barcode . '-' . strtoupper(substr(uniqid(), -6)),
                        'quantite_contenu'      => $quantity,
                        'quantite_actuelle'     => $quantity,
                        'expiration_date'       => $unit->expiration_date,
                        'status'                => 'en_stock',
                        'current_location_type' => 'depot',
                        'current_location_id'   => $targetDepot->id,
                        'created_by'            => $user->id,
                    ]);

                    TransferItem::create([
                        'transfer_id'  => $transfer->id,
                        'drug_unit_id' => $depotUnit->id,
                        'quantity'     => $quantity,
                    ]);

                    StockMovement::create([
                        'drug_unit_id' => $depotUnit->id,
                        'transfer_id'  => $transfer->id,
                        'from_type'    => 'pharmacy',
                        'from_id'      => $unit->current_location_id,
                        'to_type'      => 'depot',
                        'to_id'        => $targetDepot->id,
                        'action'       => 'transfer',
                        'performed_by' => $user->id,
                        'performed_at' => now(),
                    ]);
                }
            }

            return $transfer->fresh(['depot', 'performer']);
        });
    }
}

<?php

namespace App\Services;

use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockService
{
    /**
     * Create a new drug unit in the central pharmacy.
     */
    public function createDrugUnit(Drug $drug, array $data, User $user): DrugUnit
    {
        return DB::transaction(function () use ($drug, $data, $user) {
            // Check if user has permission
            // Assuming this check is done in Request/Policy, but good to be safe.

            // Ensure unique barcode
            // Handled by DB constraint, but can add pre-check if needed.

            $quantiteContenu = (int) ($data['quantite_contenu'] ?? 1);
            if ($quantiteContenu < 1) {
                $quantiteContenu = 1;
            }

            $drugUnit = DrugUnit::create([
                'drug_id' => $drug->id,
                'price' => $data['price'],
                'barcode' => $data['barcode'],
                'quantite_contenu' => $quantiteContenu,
                'quantite_actuelle' => $quantiteContenu,
                'expiration_date' => $data['expiration_date'],
                'status' => 'en_stock',
                'current_location_type' => 'pharmacy',
                'current_location_id' => $user->pharmacy_id ?? 1,
                'created_by' => $user->id,
            ]);

            // Log movement: Creation
            StockMovement::create([
                'drug_unit_id' => $drugUnit->id,
                'from_type' => null,
                'from_id' => null,
                'to_type' => 'pharmacy',
                'to_id' => $drugUnit->current_location_id,
                'action' => 'creation',
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            return $drugUnit;
        });
    }

    /**
     * Mark a unit as expired or retired.
     */
    public function updateStatus(DrugUnit $drugUnit, string $status, User $user): DrugUnit
    {
        return DB::transaction(function () use ($drugUnit, $status, $user) {
            $oldStatus = $drugUnit->status;
            $drugUnit->update(['status' => $status]);

            // Log movement if necessary, or just audit log?
            // "StockMovement" handles physical moves usually, but status change is also a "movement" in lifecycle.
            // Let's record it as 'destruction' or 'return' if applicable, or just rely on AuditLog for status changes.
            // Objectives: "Historique complet des mouvements de chaque unité (création → transfert → vente)"
            // "destruction" is in ENUM.

            if ($status === 'retiree' || $status === 'perimee') {
                StockMovement::create([
                    'drug_unit_id' => $drugUnit->id,
                    'from_type' => $drugUnit->current_location_type,
                    'from_id' => $drugUnit->current_location_id,
                    'to_type' => null,
                    'to_id' => null,
                    'action' => 'destruction', // Or add 'expiration' to enum if needed
                    'performed_by' => $user->id,
                    'performed_at' => now(),
                ]);
            }

            return $drugUnit;
        });
    }
}

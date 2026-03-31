<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class SaleService
{
    /**
     * Sell a quantity from a depot DrugUnit.
     */
    public function sellUnit(DrugUnit $unit, User $user, int $quantity = 1, ?string $transactionId = null): Sale
    {
        return DB::transaction(function () use ($unit, $user, $quantity, $transactionId) {
            $unit = DrugUnit::where('id', $unit->id)->lockForUpdate()->firstOrFail();

            if ($user->depot_id && ($unit->current_location_type !== 'depot' || $unit->current_location_id !== $user->depot_id)) {
                throw new Exception("Impossible de vendre depuis cet emplacement.");
            }

            if ($unit->status !== 'en_stock') {
                throw new Exception("L'unité {$unit->barcode} n'est pas disponible à la vente (Statut: {$unit->status}).");
            }

            if ($unit->expiration_date < now()->startOfDay()) {
                throw new Exception("L'unité {$unit->barcode} est périmée.");
            }

            if ($quantity < 1 || $quantity > $unit->quantite_actuelle) {
                throw new Exception("Quantité invalide ({$quantity}). Disponible: {$unit->quantite_actuelle}.");
            }

            $totalPrice = $unit->price * $quantity;

            $sale = Sale::create([
                'transaction_id' => $transactionId,
                'depot_id'       => $unit->current_location_id,
                'drug_unit_id'   => $unit->id,
                'sold_by'        => $user->id,
                'sold_at'        => now(),
                'price'          => $totalPrice,
                'quantity'       => $quantity,
            ]);

            StockMovement::create([
                'drug_unit_id' => $unit->id,
                'from_type'    => 'depot',
                'from_id'      => $unit->current_location_id,
                'to_type'      => 'client',
                'to_id'        => null,
                'action'       => 'sale',
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            if ($quantity >= $unit->quantite_actuelle) {
                $unit->update(['status' => 'vendue', 'quantite_actuelle' => 0]);
            } else {
                $unit->decrement('quantite_actuelle', $quantity);
            }

            return $sale;
        });
    }

    /**
     * Process a cart of items (multiple drug units with quantities).
     *
     * @param array $cartItems  Array of ['drug_unit_id' => int, 'quantity' => int]
     * @param User $user
     * @return array  Array of Sale models
     */
    public function processSaleCart(array $cartItems, User $user): array
    {
        $transactionId = (string) Str::uuid();

        return DB::transaction(function () use ($cartItems, $user, $transactionId) {
            $sales = [];
            foreach ($cartItems as $item) {
                $unit    = DrugUnit::findOrFail($item['drug_unit_id']);
                $sales[] = $this->sellUnit($unit, $user, (int) ($item['quantity'] ?? 1), $transactionId);
            }
            return $sales;
        });
    }
}

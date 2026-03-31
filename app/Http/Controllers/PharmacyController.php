<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy;
use App\Models\Drug;
use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\Transfer;
use App\Models\StockRequest;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PharmacyController extends Controller
{
    public function edit(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasAnyRole(['super_admin', 'pharmacy_staff']); // fixed: was pharmacy_staff

        // Active pharmacy for the settings form (admins only)
        $pharmacy = $isAdmin ? $user->pharmacy : null;

        // User's pharmacies (for the switcher)
        $userPharmacies = $user->pharmacies()->get(['pharmacies.id', 'pharmacies.name']);

        // Pharmacies the user can still join
        $joinable = Pharmacy::whereNotIn('id', $userPharmacies->pluck('id'))->get(['id', 'name']);

        return Inertia::render('Pharmacy/Edit', [
            'pharmacy'           => $pharmacy,
            'isAdmin'            => $isAdmin,
            'isSuperAdmin'       => $user->hasRole('super_admin'),
            'userPharmacies'     => $userPharmacies,
            'activePharmacyId'   => $user->pharmacy_id,
            'joinablePharmacies' => $joinable,
        ]);
    }

    public function update(Request $request)
    {
        $user     = $request->user();
        $pharmacy = $user->pharmacy ?? Pharmacy::firstOrFail();

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
            'nif'     => 'nullable|string|max:50',
            'stat'    => 'nullable|string|max:50',
            'rcs'     => 'nullable|string|max:50',
        ]);

        $pharmacy->update($validated);

        return redirect()->back()->with('success', 'Informations de la pharmacie mises à jour.');
    }

    /**
     * Create a brand-new pharmacy and make it the user's active pharmacy (super_admin only).
     */
    public function store(Request $request)
    {
        if (! $request->user()->hasRole('super_admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $pharmacy = Pharmacy::create($validated);

        $user = $request->user();
        $user->pharmacies()->syncWithoutDetaching([$pharmacy->id]);

        // Set as active if the admin had none
        if (! $user->pharmacy_id) {
            $user->update(['pharmacy_id' => $pharmacy->id]);
        }

        return redirect()->back()->with('success', "Pharmacie « {$pharmacy->name} » créée avec succès.");
    }

    /**
     * Associate the current user with an existing pharmacy.
     */
    public function join(Request $request)
    {
        $validated = $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $user = $request->user();

        if (! $user->pharmacies()->where('pharmacies.id', $validated['pharmacy_id'])->exists()) {
            $user->pharmacies()->attach($validated['pharmacy_id']);

            if (! $user->pharmacy_id) {
                $user->update(['pharmacy_id' => $validated['pharmacy_id']]);
            }
        }

        return redirect()->back()->with('success', 'Pharmacie ajoutée à votre profil.');
    }

    /**
     * Delete a pharmacy and all related data (cascade).
     */
    public function destroy(Request $request, Pharmacy $pharmacy)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }

        // pharmacy_admin can only delete their own pharmacy
        if ($user->hasRole('pharmacy_admin') && $user->pharmacy_id !== $pharmacy->id) {
            abort(403);
        }

        $request->validate([
            'confirm_name' => ['required', 'string', function ($attr, $value, $fail) use ($pharmacy) {
                if ($value !== $pharmacy->name) {
                    $fail('Le nom saisi ne correspond pas au nom de la pharmacie.');
                }
            }],
        ]);

        DB::transaction(function () use ($pharmacy) {
            $depotIds = $pharmacy->depots()->pluck('id')->toArray();

            // 1. Drug units located in this pharmacy or its depots
            $drugUnitIds = DrugUnit::withTrashed()
                ->where(function ($q) use ($pharmacy, $depotIds) {
                    $q->where(function ($q2) use ($pharmacy) {
                        $q2->where('current_location_type', 'pharmacy')
                           ->where('current_location_id', $pharmacy->id);
                    })->orWhere(function ($q2) use ($depotIds) {
                        $q2->where('current_location_type', 'depot')
                           ->whereIn('current_location_id', $depotIds);
                    });
                })->pluck('id')->toArray();

            // 2. Delete stock movements for these drug units
            StockMovement::whereIn('drug_unit_id', $drugUnitIds)->delete();

            // 3. Delete sales in these depots
            Sale::whereIn('depot_id', $depotIds)->delete();

            // 4. Delete transfers from/to this pharmacy
            Transfer::where('from_pharmacy_id', $pharmacy->id)
                ->orWhereIn('to_depot_id', $depotIds)
                ->each(fn ($t) => $t->items()->delete());
            Transfer::where('from_pharmacy_id', $pharmacy->id)
                ->orWhereIn('to_depot_id', $depotIds)
                ->delete();

            // 5. Delete stock requests for these depots
            StockRequest::withTrashed()->whereIn('depot_id', $depotIds)
                ->each(fn ($sr) => $sr->items()->delete());
            StockRequest::withTrashed()->whereIn('depot_id', $depotIds)->forceDelete();

            // 6. Delete drug units
            DrugUnit::withTrashed()->whereIn('id', $drugUnitIds)->forceDelete();

            // 7. Find drugs created by users of this pharmacy that have no remaining units
            $pharmacyUserIds = $pharmacy->members()->pluck('users.id')
                ->merge($pharmacy->users()->pluck('id'))
                ->unique()->toArray();

            Drug::withTrashed()
                ->whereIn('created_by', $pharmacyUserIds)
                ->whereDoesntHave('drugUnits')
                ->forceDelete();

            // 8. Detach users from pharmacy (don't delete their accounts)
            foreach ($pharmacy->users as $u) {
                $u->update(['pharmacy_id' => null, 'depot_id' => null]);
            }
            $pharmacy->members()->detach();

            // 9. Delete depots (cascade will handle remaining FKs)
            $pharmacy->depots()->delete();

            // 10. Delete the pharmacy
            $pharmacy->delete();
        });

        return redirect()->route('pharmacy.edit')->with('success', "La pharmacie « {$pharmacy->name} » et toutes ses données ont été supprimées.");
    }

    /**
     * Export/backup all pharmacy data as JSON.
     */
    public function backup(Request $request, Pharmacy $pharmacy)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }
        if ($user->hasRole('pharmacy_admin') && $user->pharmacy_id !== $pharmacy->id) {
            abort(403);
        }

        $depotIds = $pharmacy->depots()->pluck('id')->toArray();

        $drugUnitIds = DrugUnit::withTrashed()
            ->where(function ($q) use ($pharmacy, $depotIds) {
                $q->where(function ($q2) use ($pharmacy) {
                    $q2->where('current_location_type', 'pharmacy')
                       ->where('current_location_id', $pharmacy->id);
                })->orWhere(function ($q2) use ($depotIds) {
                    $q2->where('current_location_type', 'depot')
                       ->whereIn('current_location_id', $depotIds);
                });
            })->pluck('id')->toArray();

        $pharmacyUserIds = $pharmacy->members()->pluck('users.id')
            ->merge($pharmacy->users()->pluck('id'))
            ->unique()->toArray();

        $drugIds = DrugUnit::withTrashed()->whereIn('id', $drugUnitIds)->pluck('drug_id')->unique()->toArray();

        $data = [
            'exported_at'  => now()->toIso8601String(),
            'version'      => '1.0',
            'pharmacy'     => $pharmacy->toArray(),
            'depots'       => $pharmacy->depots()->get()->toArray(),
            'users'        => $pharmacy->users()->with('roles')->get()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'roles' => $u->roles->pluck('name')->toArray(),
                'depot_id' => $u->depot_id,
            ])->toArray(),
            'drugs'        => Drug::withTrashed()->whereIn('id', $drugIds)->get()->toArray(),
            'drug_units'   => DrugUnit::withTrashed()->whereIn('id', $drugUnitIds)->get()->toArray(),
            'sales'        => Sale::whereIn('depot_id', $depotIds)->get()->toArray(),
            'transfers'    => Transfer::with('items')
                ->where('from_pharmacy_id', $pharmacy->id)
                ->orWhereIn('to_depot_id', $depotIds)
                ->get()->toArray(),
            'stock_requests' => StockRequest::withTrashed()->with('items')
                ->whereIn('depot_id', $depotIds)
                ->get()->toArray(),
            'stock_movements' => StockMovement::whereIn('drug_unit_id', $drugUnitIds)->get()->toArray(),
        ];

        $filename = 'backup_' . str_replace(' ', '_', $pharmacy->name) . '_' . now()->format('Y-m-d_His') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Type', 'application/json');
    }

    /**
     * Restore pharmacy data from a JSON backup.
     */
    public function restore(Request $request)
    {
        if (! $request->user()->hasRole('super_admin')) {
            abort(403);
        }

        $request->validate([
            'backup_file' => 'required|file|mimetypes:application/json,text/plain',
        ]);

        $data = json_decode($request->file('backup_file')->get(), true);

        if (! $data || ! isset($data['pharmacy'], $data['version'])) {
            return back()->withErrors(['backup_file' => 'Fichier de sauvegarde invalide.']);
        }

        DB::transaction(function () use ($data) {
            // 1. Create or find pharmacy
            $pharmaData = collect($data['pharmacy'])->only(['name', 'address', 'phone', 'email', 'nif', 'stat', 'rcs'])->toArray();
            $pharmacy = Pharmacy::create($pharmaData);
            $oldPharmacyId = $data['pharmacy']['id'];

            // 2. Create depots — map old IDs to new IDs
            $depotMap = [];
            foreach ($data['depots'] ?? [] as $depot) {
                $newDepot = $pharmacy->depots()->create(
                    collect($depot)->only(['name', 'address', 'stock_alert_threshold', 'show_receipts', 'show_stats'])->toArray()
                );
                $depotMap[$depot['id']] = $newDepot->id;
            }

            // 3. Map old drug IDs to new (or existing) drug IDs
            $drugMap = [];
            foreach ($data['drugs'] ?? [] as $drug) {
                $existing = Drug::withTrashed()->where('name', $drug['name'])->first();
                if ($existing) {
                    $drugMap[$drug['id']] = $existing->id;
                } else {
                    $newDrug = Drug::create(
                        collect($drug)->only(['category_id', 'name', 'effet_s_med', 'dosage_med', 'form_med', 'prix_med', 'is_authorized', 'description', 'min_stock'])
                            ->merge(['created_by' => auth()->id()])
                            ->toArray()
                    );
                    $drugMap[$drug['id']] = $newDrug->id;
                }
            }

            // 4. Create drug units — map old IDs to new IDs
            $unitMap = [];
            foreach ($data['drug_units'] ?? [] as $unit) {
                $locType = $unit['current_location_type'];
                $locId   = $locType === 'pharmacy'
                    ? $pharmacy->id
                    : ($depotMap[$unit['current_location_id']] ?? null);

                if (! $locId) continue;

                $newUnit = DrugUnit::create([
                    'drug_id'               => $drugMap[$unit['drug_id']] ?? $unit['drug_id'],
                    'price'                 => $unit['price'],
                    'barcode'               => $unit['barcode'] . '_R' . $pharmacy->id,
                    'quantite_contenu'      => $unit['quantite_contenu'],
                    'quantite_actuelle'     => $unit['quantite_actuelle'],
                    'expiration_date'       => $unit['expiration_date'],
                    'status'                => $unit['status'],
                    'current_location_type' => $locType,
                    'current_location_id'   => $locId,
                    'created_by'            => auth()->id(),
                ]);
                $unitMap[$unit['id']] = $newUnit->id;
            }

            // 5. Restore sales
            foreach ($data['sales'] ?? [] as $sale) {
                $newDepotId  = $depotMap[$sale['depot_id']] ?? null;
                $newUnitId   = $unitMap[$sale['drug_unit_id']] ?? null;
                if (! $newDepotId || ! $newUnitId) continue;

                Sale::create([
                    'transaction_id' => $sale['transaction_id'],
                    'depot_id'       => $newDepotId,
                    'drug_unit_id'   => $newUnitId,
                    'sold_by'        => auth()->id(),
                    'sold_at'        => $sale['sold_at'],
                    'price'          => $sale['price'],
                    'quantity'       => $sale['quantity'],
                ]);
            }

            // 6. Restore transfers
            foreach ($data['transfers'] ?? [] as $transfer) {
                $newTransfer = Transfer::create([
                    'from_pharmacy_id' => $pharmacy->id,
                    'to_depot_id'      => $depotMap[$transfer['to_depot_id']] ?? array_values($depotMap)[0] ?? null,
                    'performed_by'     => auth()->id(),
                    'status'           => $transfer['status'],
                    'items_count'      => $transfer['items_count'],
                    'performed_at'     => $transfer['performed_at'],
                ]);
                foreach ($transfer['items'] ?? [] as $item) {
                    $newUnitId = $unitMap[$item['drug_unit_id']] ?? null;
                    if ($newUnitId) {
                        $newTransfer->items()->create([
                            'drug_unit_id' => $newUnitId,
                            'quantity'     => $item['quantity'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('pharmacy.edit')->with('success', 'Sauvegarde restaurée avec succès. Une nouvelle pharmacie a été créée.');
    }
}

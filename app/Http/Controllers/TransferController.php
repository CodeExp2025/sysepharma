<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Depot;
use App\Models\DrugUnit;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Notifications\TransferCompletedNotification;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $search       = $request->query('search');
        $sort         = $request->query('sort', 'performed_at');
        $direction    = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $fromDate     = $request->query('from_date');
        $toDate       = $request->query('to_date');
        $depotId      = $request->query('depot_id');

        $allowedSorts = ['performed_at', 'status', 'id'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'performed_at';
        }

        $baseQuery = Transfer::with(['depot', 'performer', 'items.drugUnit.drug'])
            ->when(! $isSuperAdmin && $user->pharmacy_id, function ($q) use ($user) {
                $q->whereHas('depot', fn ($dq) => $dq->where('pharmacy_id', $user->pharmacy_id));
            })
            ->when($depotId, fn ($q) => $q->where('to_depot_id', $depotId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('depot', fn ($d) => $d->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
                          ->orWhereHas('performer', fn ($u) => $u->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
                          ->orWhereRaw('status COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($fromDate, fn ($q) => $q->whereDate('performed_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->whereDate('performed_at', '<=', $toDate))
            ->orderBy($sort, $direction);

        // Helper: compute stats for a transfer's items collection
        $computeStats = function (Transfer $t): array {
            $items          = $t->items;
            $products       = $items->map(fn ($i) => $i->drugUnit?->drug_id)->filter()->unique()->count();
            $boxes          = $items->count();
            // quantity in transfer_items is the actual units transferred (not boxes)
            // For full transfer: quantity = quantite_actuelle (all units in box)
            // For partial transfer: quantity = user-specified amount
            $qtyTransferred = $items->sum(fn ($i) => $i->quantity ?? 0);
            // qtyRemaining = what was originally in boxes minus what was transferred
            // This represents what stayed at the pharmacy after partial transfers
            $qtyRemaining   = $items->sum(fn ($i) => ($i->drugUnit?->quantite_contenu ?? 0) - ($i->quantity ?? 0));
            // Average units per box for display purposes
            $unitsPerBox    = $boxes > 0 ? round($qtyTransferred / $boxes, 1) : 0;
            return compact('products', 'boxes', 'unitsPerBox', 'qtyTransferred', 'qtyRemaining');
        };

        // Full transfer data for period print (only when both dates are set)
        $transfersForPrint = null;
        if ($fromDate && $toDate) {
            $transfersForPrint = (clone $baseQuery)
                ->get()
                ->map(fn (Transfer $t) => array_merge([
                    'id'           => $t->id,
                    'status'       => $t->status,
                    'performed_at' => $t->performed_at,
                    'user'         => $t->performer,
                    'depot'        => $t->depot,
                    'items'        => $t->items->map(fn ($item) => [
                        'quantity'          => $item->quantity,
                        'quantite_contenu'  => $item->drugUnit?->quantite_contenu,
                        'quantite_actuelle' => $item->drugUnit?->quantite_actuelle,
                        'drug'              => $item->drugUnit?->drug ? [
                            'name'       => $item->drugUnit->drug->name,
                            'form_med'   => $item->drugUnit->drug->form_med,
                            'dosage_med' => $item->drugUnit->drug->dosage_med,
                        ] : null,
                        'barcode'           => $item->drugUnit?->barcode,
                        'price'             => $item->drugUnit?->price,
                    ])->values(),
                ], $computeStats($t)));
        }

        // Available depots for filter dropdown
        $depots = Depot::query()
            ->when(! $isSuperAdmin && $user->pharmacy_id, fn ($q) => $q->where('pharmacy_id', $user->pharmacy_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Transfers/Index', [
            'transfers' => $baseQuery->paginate(10)->through(function (Transfer $transfer) use ($computeStats) {
                $stats = $computeStats($transfer);
                return [
                    'id'             => $transfer->id,
                    'uuid'           => $transfer->uuid,
                    'status'         => $transfer->status,
                    'created_at'     => $transfer->performed_at,
                    'user'           => $transfer->performer,
                    'depot'          => $transfer->depot,
                    'items_count'    => $transfer->items_count,
                    'products'       => $stats['products'],
                    'boxes'          => $stats['boxes'],
                    'unitsPerBox'    => $stats['unitsPerBox'],
                    'qtyTransferred' => $stats['qtyTransferred'],
                    'qtyRemaining'   => $stats['qtyRemaining'],
                ];
            }),
            'transfersForPrint' => $transfersForPrint,
            'depots'  => $depots,
            'filters' => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
                'from_date' => $fromDate,
                'to_date'   => $toDate,
                'depot_id'  => $depotId,
            ],
        ]);
    }

    public function cancel(Transfer $transfer, Request $request)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }

        if ($transfer->status !== 'pending') {
            return redirect()->back()->with('error', 'Seuls les transferts en attente peuvent être annulés.');
        }

        $transfer->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Transfert annulé avec succès.');
    }

    public function destroy(Transfer $transfer, Request $request)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['super_admin', 'pharmacy_admin'])) {
            abort(403);
        }

        // Load relationships needed for restoration
        $transfer->load(['items.drugUnit', 'depot', 'stockMovements']);

        // Log the deletion before removing
        try {
            $restoredItems = [];
            foreach ($transfer->items as $item) {
                $unit = $item->drugUnit;
                if ($unit) {
                    $restoredItems[] = [
                        'drug_unit_id' => $unit->id,
                        'barcode' => $unit->barcode,
                        'quantity' => $item->quantity,
                        'from_location' => $transfer->to_depot_id,
                        'to_location' => $transfer->from_pharmacy_id,
                    ];
                }
            }

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'transfer_deleted',
                'entity_type' => 'Transfer',
                'entity_id'   => $transfer->id,
                'old_values'  => [
                    'transfer_id' => $transfer->id,
                    'depot_id' => $transfer->to_depot_id,
                    'items_count' => $transfer->items_count,
                    'restored_items' => $restoredItems,
                ],
                'new_values'  => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('audit_log.transfer_delete_failed', ['error' => $e->getMessage()]);
        }

        // Restore stocks to original location (pharmacy)
        $restoredCount = 0;
        foreach ($transfer->items as $item) {
            $unit = $item->drugUnit;
            if (! $unit) {
                continue;
            }

            // Check if this was a partial transfer
            // Partial transfer indicators:
            // 1. Unit is at depot with status 'en_stock'
            // 2. Unit's current quantity equals the transferred quantity (unit was created for this transfer)
            // 3. Unit barcode has a suffix (format: ORIGINAL-XXXXXX)
            $barcodeParts = explode('-', $unit->barcode);
            $hasBarcodeSuffix = count($barcodeParts) > 1 && strlen(end($barcodeParts)) >= 6;
            $isPartialTransfer = $unit->current_location_type === 'depot'
                && $unit->quantite_actuelle == $item->quantity
                && $hasBarcodeSuffix;

            if ($isPartialTransfer) {
                // This is a partial transfer - find the source unit at pharmacy
                $originalBarcode = $barcodeParts[0];

                // Find the source unit at pharmacy by matching drug_id and original barcode
                $sourceUnit = DrugUnit::where('drug_id', $unit->drug_id)
                    ->where('current_location_type', 'pharmacy')
                    ->where(function ($q) use ($originalBarcode, $unit) {
                        $q->where('barcode', $originalBarcode)
                          ->orWhere('barcode', 'like', $originalBarcode . '-%');
                    })
                    ->where('id', '!=', $unit->id)
                    ->first();

                if ($sourceUnit) {
                    // Restore quantity to source unit
                    $sourceUnit->increment('quantite_actuelle', $item->quantity);

                    // Log the restoration
                    StockMovement::create([
                        'drug_unit_id' => $sourceUnit->id,
                        'transfer_id'  => $transfer->id,
                        'from_type'    => 'depot',
                        'from_id'      => $transfer->to_depot_id,
                        'to_type'      => 'pharmacy',
                        'to_id'        => $transfer->from_pharmacy_id,
                        'action'       => 'return_quantity',
                        'quantity'     => $item->quantity,
                        'performed_by' => $user->id,
                        'performed_at' => now(),
                    ]);

                    // Delete the depot unit (it was created for this partial transfer)
                    $unit->delete();
                    $restoredCount++;
                    continue;
                }

                // If source unit not found, fallback to moving the unit back to pharmacy
                Log::warning('transfer.destroy.partial_source_not_found', [
                    'transfer_id' => $transfer->id,
                    'unit_id' => $unit->id,
                    'barcode' => $unit->barcode,
                    'original_barcode' => $originalBarcode,
                ]);
            }

            // For full transfers or when source unit not found: move unit back to pharmacy
            $unit->update([
                'current_location_type' => 'pharmacy',
                'current_location_id'   => $transfer->from_pharmacy_id,
                'status'                => 'en_stock',
            ]);

            // Log the stock restoration movement
            StockMovement::create([
                'drug_unit_id' => $unit->id,
                'transfer_id'  => $transfer->id,
                'from_type'    => 'depot',
                'from_id'      => $transfer->to_depot_id,
                'to_type'      => 'pharmacy',
                'to_id'        => $transfer->from_pharmacy_id,
                'action'       => 'return',
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            $restoredCount++;
        }

        // Delete the transfer (items will be deleted via cascade or foreign key)
        $transfer->delete();

        $message = "Transfert supprimé avec succès. {$restoredCount} article(s) retourné(s) à la pharmacie.";

        return redirect()->route('transfers.index')->with('success', $message);
    }

    public function show(Transfer $transfer)
    {
        $transfer->load(['depot.pharmacy', 'performer', 'items.drugUnit.drug.category']);

        return Inertia::render('Transfers/Show', [
            'transfer' => [
                'id'         => $transfer->id,
                'uuid'       => $transfer->uuid,
                'status'     => $transfer->status,
                'created_at' => $transfer->performed_at,
                'user'       => $transfer->performer,
                'depot'      => $transfer->depot,
                'items_count' => $transfer->items_count,
                'items'      => $transfer->items->map(function ($item) {
                    return [
                        'id'        => $item->id,
                        'quantity'  => $item->quantity,
                        'drug_unit' => $item->drugUnit ? array_merge($item->drugUnit->toArray(), [
                            'drug' => $item->drugUnit->drug,
                        ]) : null,
                    ];
                })->values(),
            ],
        ]);
    }

    public function print(Transfer $transfer)
    {
        $transfer->load(['depot.pharmacy', 'performer', 'items.drugUnit.drug.category']);

        return Inertia::render('Transfers/Print', [
            'transfer' => [
                'id'          => $transfer->id,
                'uuid'        => $transfer->uuid,
                'status'      => $transfer->status,
                'performed_at' => $transfer->performed_at,
                'user'        => $transfer->performer,
                'depot'       => $transfer->depot,
                'items_count' => $transfer->items_count,
                'items'       => $transfer->items->map(function ($item) {
                    return [
                        'id'        => $item->id,
                        'quantity'  => $item->quantity,
                        'drug_unit' => $item->drugUnit ? [
                            'id'                => $item->drugUnit->id,
                            'barcode'           => $item->drugUnit->barcode,
                            'price'             => $item->drugUnit->price,
                            'quantite_contenu'  => $item->drugUnit->quantite_contenu,
                            'expiration_date'   => $item->drugUnit->expiration_date,
                            'drug'              => $item->drugUnit->drug ? [
                                'name'     => $item->drugUnit->drug->name,
                                'form_med' => $item->drugUnit->drug->form_med,
                                'dosage_med' => $item->drugUnit->drug->dosage_med,
                                'category' => $item->drugUnit->drug->category,
                            ] : null,
                        ] : null,
                    ];
                })->values(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        // Show only depots belonging to the user's active pharmacy
        $depots = Depot::query()
            ->when($user->pharmacy_id, fn($q) => $q->where('pharmacy_id', $user->pharmacy_id))
            ->get(['id', 'name', 'address', 'pharmacy_id']);

        // Available pharmacy stock (for preview when scanning)
        return Inertia::render('Transfers/Create', [
            'depots' => $depots,
        ]);
    }

    /**
     * Lookup a DrugUnit by barcode (for the transfer scan UI).
     */
    public function lookup(Request $request)
    {
        $barcode = trim((string) $request->query('barcode', ''));
        if ($barcode === '') {
            return response()->json(['error' => 'Code-barres requis.'], 422);
        }

        $unit = DrugUnit::with('drug.category')
            ->where('barcode', $barcode)
            ->first();

        if (! $unit) {
            return response()->json(['error' => "Unité introuvable: {$barcode}"], 404);
        }

        // Ensure the unit belongs to the user's active pharmacy
        $user = $request->user();
        if (! $user->hasRole('super_admin') && $user->pharmacy_id) {
            if ($unit->current_location_type !== 'pharmacy' || (int) $unit->current_location_id !== (int) $user->pharmacy_id) {
                return response()->json(['error' => "Cette unité n'est pas dans votre pharmacie."], 422);
            }
        }

        return response()->json([
            'id'                => $unit->id,
            'barcode'           => $unit->barcode,
            'quantite_contenu'  => $unit->quantite_contenu,
            'quantite_actuelle' => $unit->quantite_actuelle,
            'status'            => $unit->status,
            'expiration_date'   => $unit->expiration_date,
            'price'             => $unit->price,
            'current_location_type' => $unit->current_location_type,
            'current_location_id'   => $unit->current_location_id,
            'drug'              => $unit->drug ? [
                'id'       => $unit->drug->id,
                'name'     => $unit->drug->name,
                'form_med' => $unit->drug->form_med,
                'dosage_med' => $unit->drug->dosage_med,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'depot_id' => 'required|exists:depots,id',
            'items'    => 'required|array|min:1',
            'items.*.drug_unit_id' => 'required|integer',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        $depot = Depot::findOrFail($validated['depot_id']);

        // Resolve drug_unit_ids — support barcode string or numeric ID
        // Check for expired units and log warnings
        $expiredWarnings = [];
        $resolvedItems = collect($validated['items'])->map(function ($item) use (&$expiredWarnings) {
            $value    = $item['drug_unit_id'];
            $quantity = (int) $item['quantity'];

            $unit = DrugUnit::whereKey((int) $value)->first()
                ?? DrugUnit::where('barcode', (string) $value)->first();

            if (! $unit) {
                throw ValidationException::withMessages([
                    'items' => ["Unité introuvable: {$value}"],
                ]);
            }

            // Check if unit is expired and log warning
            if ($unit->expiration_date && $unit->expiration_date->isPast()) {
                $warning = "ALERTE: L'unité {$unit->barcode} ({$unit->drug?->name}) est périmée depuis {$unit->expiration_date->format('d/m/Y')}. Elle sera marquée comme 'à détruire'.";
                $expiredWarnings[] = $warning;
                Log::warning('transfer.expired_unit', [
                    'barcode' => $unit->barcode,
                    'drug_name' => $unit->drug?->name,
                    'expiration_date' => $unit->expiration_date->toDateString(),
                    'depot_id' => $depot->id,
                ]);
            }

            return ['drug_unit_id' => $unit->id, 'quantity' => $quantity, 'is_expired' => ($unit->expiration_date && $unit->expiration_date->isPast())];
        })->all();

        try {
            $transfer = $this->transferService->transferToDepot($resolvedItems, $depot, $request->user());
            $count    = $transfer->items_count;
            $notificationsSent = null;
            $auditLogged       = false;

            try {
                AuditLog::create([
                    'user_id'     => $request->user()->id,
                    'action'      => 'transfer_completed',
                    'entity_type' => 'Transfer',
                    'entity_id'   => $transfer->id,
                    'old_values'  => null,
                    'new_values'  => [
                        'items'       => $resolvedItems,
                        'count'       => $count,
                        'to_depot_id' => $depot->id,
                    ],
                ]);
                $auditLogged = true;
            } catch (\Throwable $e) {
                Log::warning('audit_log.transfer_failed', ['error' => $e->getMessage()]);
            }

            try {
                $recipients = User::query()
                    ->where('depot_id', $depot->id)
                    ->whereKeyNot($request->user()->id)
                    ->get();

                foreach ($recipients as $recipient) {
                    $recipient->notify(new TransferCompletedNotification($depot, $count, $request->user(), $transfer->id));
                }

                $notificationsSent = $recipients->count();
            } catch (\Throwable $e) {
                Log::warning('notifications.transfer_failed', ['error' => $e->getMessage()]);
            }

            $message = "Transfert effectué : {$count} article(s) vers {$depot->name}.";
            
            // Add expired warnings to message if any
            if (!empty($expiredWarnings)) {
                $message .= " ATTENTION: " . count($expiredWarnings) . " unité(s) périmée(s) marquée(s) comme 'à détruire'.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success'     => true,
                    'message'     => $message,
                    'transfer_id' => $transfer->id,
                    'transfer_uuid' => $transfer->uuid,
                    'warnings'    => $expiredWarnings,
                ]);
            }

            return redirect()->route('transfers.show', $transfer->uuid)->with('success', $message)->with('warnings', $expiredWarnings);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('transfer.store_failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DrugUnit;
use App\Models\Sale;
use App\Models\User;
use App\Models\Depot;
use App\Notifications\SaleRecordedNotification;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $userDepotStat = $user->depot?->show_stats;
        $search = $request->query('search');

        // Fetch all sale line-items matching scope
        $salesQuery = Sale::with(['drugUnit.drug.category', 'seller', 'depot'])
            ->when(! $isSuperAdmin && $user->pharmacy_id, function ($q) use ($user) {
                $q->whereHas('depot', fn ($dq) => $dq->where('pharmacy_id', $user->pharmacy_id));
            })
            ->when($user->depot_id, fn ($q) => $q->where('depot_id', $user->depot_id))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('drugUnit.drug', fn ($d) => $d->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('seller', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('depot', fn ($d) => $d->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        // Group into transactions (one receipt = one transaction_id)
        $allSales    = $salesQuery->get();
        $transactions = $allSales
            ->groupBy(fn ($s) => $s->transaction_id ?? 'solo_' . $s->id)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'transaction_id' => $first->transaction_id ?? $first->id,
                    'created_at'     => $first->created_at,
                    'depot'          => $first->depot,
                    'seller'         => $first->seller,
                    'items'          => $items->values(),
                    'total'          => $items->sum('price'),
                    'items_count'    => $items->count(),
                    'total_qty'      => $items->sum('quantity'),
                ];
            })
            ->values()
            ->sortByDesc('created_at')
            ->values();

        // Manual pagination
        $perPage = 15;
        $page    = $request->input('page', 1);
        $paged   = new \Illuminate\Pagination\LengthAwarePaginator(
            $transactions->forPage($page, $perPage),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Sales/Index', [
            'transactions'  => $paged,
            'userDepotStat' => $userDepotStat,
            'filters'       => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Sales/Create');
    }

    /**
     * Scan a barcode and return drug unit info for POS cart preview.
     */
    public function scan(Request $request)
    {
        $barcode = trim((string) $request->query('barcode', ''));

        if ($barcode === '') {
            return response()->json(['error' => 'Code-barres requis.'], 422);
        }

        $unit = DrugUnit::with('drug.category')->where('barcode', $barcode)->first();

        if (! $unit) {
            return response()->json(['error' => "Médicament introuvable: {$barcode}"], 404);
        }

        if ($unit->current_location_type !== 'depot') {
            return response()->json(['error' => "Ce médicament ne se trouve pas au dépôt."], 422);
        }

        if ($request->user()->depot_id && $unit->current_location_id !== $request->user()->depot_id) {
            return response()->json(['error' => "Ce médicament n'est pas dans votre dépôt."], 422);
        }

        if ($unit->status !== 'en_stock') {
            return response()->json(['error' => "Ce médicament n'est plus en stock (statut: {$unit->status})."], 422);
        }

        if ($unit->expiration_date < now()->startOfDay()) {
            return response()->json(['error' => "Ce médicament est périmé (exp: {$unit->expiration_date->format('d/m/Y')})."], 422);
        }

        return response()->json([
            'id'                => $unit->id,
            'barcode'           => $unit->barcode,
            'price'             => $unit->price,
            'quantite_actuelle' => $unit->quantite_actuelle,
            'quantite_contenu'  => $unit->quantite_contenu,
            'expiration_date'   => $unit->expiration_date->format('Y-m-d'),
            'drug'              => $unit->drug ? [
                'id'       => $unit->drug->id,
                'name'     => $unit->drug->name,
                'form_med' => $unit->drug->form_med,
                'dosage_med' => $unit->drug->dosage_med,
            ] : null,
        ]);
    }

    /**
     * Process a full POS cart (multiple items with quantities).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.drug_unit_id' => 'required|integer|exists:drug_units,id',
            'items.*.quantity'    => 'required|integer|min:1',
        ]);

        try {
            $sales = $this->saleService->processSaleCart($validated['items'], $request->user());

            // Send notifications for last sale (batch notification)
            try {
                $firstSale = $sales[0] ?? null;
                if ($firstSale) {
                    $firstSale->load(['drugUnit.drug', 'depot', 'seller']);
                    $pharmacyId = $firstSale->depot?->pharmacy_id;
                    $recipients = collect(User::role('super_admin')->get());

                    if ($pharmacyId) {
                        $recipients = $recipients->merge(
                            User::role(['pharmacy_admin', 'pharmacy_staff'])
                                ->where('pharmacy_id', $pharmacyId)
                                ->get()
                        );
                    }

                    $recipients = $recipients
                        ->unique('id')
                        ->where('id', '!=', $request->user()->id)
                        ->values();

                    foreach ($recipients as $recipient) {
                        $recipient->notify(new SaleRecordedNotification($firstSale));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('notifications.sale_failed', ['error' => $e->getMessage()]);
            }

            $total         = collect($sales)->sum('price');
            $transactionId = $sales[0]->transaction_id ?? null;

            if ($request->expectsJson()) {
                return response()->json([
                    'success'        => true,
                    'transaction_id' => $transactionId,
                    'total'          => $total,
                    'count'          => count($sales),
                ]);
            }

            return redirect()->route('sales.index')->with('success', 'Vente enregistrée — ' . count($sales) . ' article(s).');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::warning('sale.store_failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

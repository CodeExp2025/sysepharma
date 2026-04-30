<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DisbursementController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isPharmacyAdmin = $user->hasRole('pharmacy_admin');
        $search       = $request->query('search');
        $sort         = $request->query('sort', 'performed_at');
        $direction    = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $fromDate     = $request->query('from_date');
        $toDate       = $request->query('to_date');

        $allowedSorts = ['performed_at', 'id'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'performed_at';
        }

        $query = Disbursement::with(['initiator', 'items'])
            // Staff roles (depot_staff, pharmacy_staff) can only see their own disbursements
            ->when(! $isSuperAdmin && ! $isPharmacyAdmin, function ($q) use ($user) {
                $q->where('initiated_by', $user->id);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('initiator', fn ($u) => $u->whereRaw('name COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
                          ->orWhereHas('items', fn ($i) => $i->whereRaw('designation COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]))
                          ->orWhereRaw('notes COLLATE utf8mb4_general_ci LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($fromDate, fn ($q) => $q->whereDate('performed_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->whereDate('performed_at', '<=', $toDate))
            ->orderBy($sort, $direction);

        // Period print data
        $disbursementsForPrint = null;
        if ($fromDate && $toDate) {
            $disbursementsForPrint = (clone $query)->get()->map(fn (Disbursement $d) => $this->formatForPrint($d));
        }

        return Inertia::render('Disbursements/Index', [
            'disbursements' => $query->paginate(15)->through(fn (Disbursement $d) => [
                'id'           => $d->id,
                'uuid'         => $d->uuid,
                'performed_at' => $d->performed_at,
                'initiator'    => $d->initiator ? ['name' => $d->initiator->name] : null,
                'items_count'  => $d->items->count(),
                'total'        => $d->items->sum(fn ($i) => $i->quantite * $i->prix_unitaire),
                'notes'        => $d->notes,
            ]),
            'disbursementsForPrint' => $disbursementsForPrint,
            'filters' => compact('search', 'sort', 'direction', 'fromDate', 'toDate'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Disbursements/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes'                  => 'nullable|string|max:1000',
            'performed_at'           => 'nullable|date',
            'items'                  => 'required|array|min:1',
            'items.*.designation'    => 'required|string|max:255',
            'items.*.quantite'       => 'required|numeric|min:0.01',
            'items.*.prix_unitaire'  => 'required|numeric|min:0',
        ], [
            'items.required'               => 'Ajoutez au moins une ligne de décaissement.',
            'items.*.designation.required' => 'La désignation est obligatoire.',
            'items.*.quantite.required'    => 'La quantité est obligatoire.',
            'items.*.quantite.min'         => 'La quantité doit être supérieure à 0.',
            'items.*.prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
        ]);

        $disbursement = Disbursement::create([
            'uuid'         => (string) Str::uuid(),
            'initiated_by' => $request->user()->id,
            'notes'        => $validated['notes'] ?? null,
            'performed_at' => $validated['performed_at'] ?? now(),
        ]);

        foreach ($validated['items'] as $item) {
            $disbursement->items()->create([
                'designation'   => $item['designation'],
                'quantite'      => $item['quantite'],
                'prix_unitaire' => $item['prix_unitaire'],
            ]);
        }

        return redirect()->route('disbursements.show', $disbursement->uuid)
            ->with('success', 'Décaissement enregistré avec succès.');
    }

    public function show(Request $request, Disbursement $disbursement)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isPharmacyAdmin = $user->hasRole('pharmacy_admin');

        // Staff roles can only view their own disbursements
        if (! $isSuperAdmin && ! $isPharmacyAdmin && $disbursement->initiated_by !== $user->id) {
            abort(403, 'Vous ne pouvez voir que vos propres décaissements.');
        }

        $disbursement->load('initiator', 'items');

        return Inertia::render('Disbursements/Show', [
            'disbursement' => $this->formatForPrint($disbursement),
        ]);
    }

    public function destroy(Disbursement $disbursement, Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isPharmacyAdmin = $user->hasRole('pharmacy_admin');

        // Staff roles can only delete their own disbursements
        if (! $isSuperAdmin && ! $isPharmacyAdmin && $disbursement->initiated_by !== $user->id) {
            abort(403, 'Vous ne pouvez supprimer que vos propres décaissements.');
        }

        $disbursement->delete();

        return redirect()->route('disbursements.index')->with('success', 'Décaissement supprimé.');
    }

    private function formatForPrint(Disbursement $d): array
    {
        $items = $d->items->map(fn ($item) => [
            'id'            => $item->id,
            'designation'   => $item->designation,
            'quantite'      => $item->quantite,
            'prix_unitaire' => $item->prix_unitaire,
            'montant'       => $item->quantite * $item->prix_unitaire,
        ])->values()->all();

        return [
            'id'           => $d->id,
            'uuid'         => $d->uuid,
            'performed_at' => $d->performed_at,
            'notes'        => $d->notes,
            'initiator'    => $d->initiator ? ['name' => $d->initiator->name] : null,
            'items'        => $items,
            'total'        => array_sum(array_column($items, 'montant')),
        ];
    }
}

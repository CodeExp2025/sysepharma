<?php

namespace App\Console\Commands;

use App\Models\Drug;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckStockLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les niveaux de stock et notifie les administrateurs si des médicaments sont en rupture ou stock faible.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des niveaux de stock...');

        // Récupérer les médicaments avec leur stock actuel
        $lowStockDrugs = Drug::withCount(['drugUnits as current_stock' => function ($query) {
            $query->where('status', 'en_stock');
        }])
        ->whereRaw('(select count(*) from drug_units where drug_units.drug_id = drugs.id and status = "en_stock" and deleted_at is null) <= min_stock')
        ->get();

        if ($lowStockDrugs->isEmpty()) {
            $this->info('Tous les niveaux de stock sont suffisants.');
            return;
        }

        $this->warn("Trouvé {$lowStockDrugs->count()} médicaments avec stock faible.");

        // Notifier les administrateurs (Super Admin et Pharmacy Admin)
        $admins = User::role(['super_admin', 'pharmacy_admin'])->get();

        if ($admins->isEmpty()) {
            $this->error('Aucun administrateur trouvé pour la notification.');
            return;
        }

        foreach ($lowStockDrugs as $drug) {
            $this->info("Traitement de {$drug->name} (Actuel: {$drug->current_stock}, Min: {$drug->min_stock})");
            
            foreach ($admins as $admin) {
                // Vérifier si une notification non lue existe déjà pour ce médicament
                $alreadyNotified = $admin->unreadNotifications()
                    ->where('type', LowStockNotification::class)
                    ->where('data->drug_id', $drug->id)
                    ->exists();

                if (!$alreadyNotified) {
                    $admin->notify(new LowStockNotification($drug, $drug->current_stock));
                    $this->info("  - Notification envoyée à {$admin->name}");
                } else {
                    $this->line("  - {$admin->name} a déjà une notification non lue pour ce médicament.");
                }
            }
        }
        
        $this->info('Vérification terminée.');
    }
}

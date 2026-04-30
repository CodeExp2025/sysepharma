<?php

namespace Database\Seeders;

use App\Models\DrugForm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DrugFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user or any user for created_by
        $adminUser = User::first();
        $createdBy = $adminUser ? $adminUser->id : 1;

        $forms = [
            ['name' => 'Comprimé', 'description' => 'Forme solide orale, généralement ronde ou ovale'],
            ['name' => 'Gélule', 'description' => 'Enveloppe contenant une poudre, des granulés ou un liquide'],
            ['name' => 'Sirop', 'description' => 'Solution aqueuse sucrée, généralement pour la pédiatrie'],
            ['name' => 'Suspension', 'description' => 'Liquide contenant des particules solides en suspension'],
            ['name' => 'Solution buvable', 'description' => 'Liquide homogène prêt à boire'],
            ['name' => 'Injection IV', 'description' => 'Administration intraveineuse directe ou en perfusion'],
            ['name' => 'Injection IM', 'description' => 'Administration intramusculaire'],
            ['name' => 'Injection SC', 'description' => 'Administration sous-cutanée'],
            ['name' => 'Crème', 'description' => 'Préparation émulsionnée pour application cutanée'],
            ['name' => 'Pommade', 'description' => 'Préparation grasse pour application cutanée ou muqueuse'],
            ['name' => 'Gouttes orales', 'description' => 'Solution à prendre par voie orale en gouttes'],
            ['name' => 'Collyre', 'description' => 'Solution pour instillation oculaire'],
            ['name' => 'Gouttes nasales', 'description' => 'Solution pour instillation nasale'],
            ['name' => 'Gouttes auriculaires', 'description' => 'Solution pour instillation dans l\'oreille'],
            ['name' => 'Suppositoire', 'description' => 'Forme solide pour administration rectale'],
            ['name' => 'Ovule', 'description' => 'Forme solide pour administration vaginale'],
            ['name' => 'Sachet', 'description' => 'Poudre ou granulés dans un sachet individuel'],
            ['name' => 'Ampoule', 'description' => 'Contenant en verre pour solution injectable'],
            ['name' => 'Flacon', 'description' => 'Contenant pour solutions multidoses'],
            ['name' => 'Inhalateur', 'description' => 'Dispositif pour administration par inhalation'],
            ['name' => 'Aérosol', 'description' => 'Vaporisateur pour administration pulmonaire'],
            ['name' => 'Poudre', 'description' => 'Forme sèche pour reconstitution ou inhalation'],
            ['name' => 'Liniment', 'description' => 'Préparation liquide ou semi-liquide pour friction'],
            ['name' => 'Lotion', 'description' => 'Préparation liquide pour application cutanée'],
            ['name' => 'Patch/Dispositif transdermique', 'description' => 'Système adhésif pour libération prolongée'],
            ['name' => 'Comprimé effervescent', 'description' => 'Comprimé qui se dissout en solution effervescente'],
            ['name' => 'Granulés', 'description' => 'Petites particules solides pour suspension orale'],
            ['name' => 'Émulsion', 'description' => 'Préparation contenant deux phases liquides immiscibles'],
        ];

        foreach ($forms as $form) {
            DrugForm::firstOrCreate(
                ['slug' => Str::slug($form['name'])],
                [
                    'name' => $form['name'],
                    'description' => $form['description'],
                    'is_active' => true,
                    'created_by' => $createdBy,
                ]
            );
        }
    }
}

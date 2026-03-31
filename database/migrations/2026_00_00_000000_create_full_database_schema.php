<?php

/**
 * MIGRATION COMPLÈTE — Sys E-Dépôt Pharma
 *
 * Ce fichier représente le schéma complet de la base de données.
 * À utiliser pour une installation fraîche (fresh install).
 * Ne pas exécuter sur une base existante (les tables existent déjà).
 *
 * Usage : php artisan migrate --path=database/migrations/2026_00_00_000000_create_full_database_schema.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Cache ─────────────────────────────────────────────────────────
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // ── 2. Jobs ──────────────────────────────────────────────────────────
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // ── 3. Pharmacies ────────────────────────────────────────────────────
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('nif')->nullable();
            $table->string('stat')->nullable();
            $table->string('rcs')->nullable();
            $table->timestamps();
        });

        // ── 4. Users ─────────────────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('pharmacy_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('depot_id')->nullable(); // FK added after depots table
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ── 5. Depots ────────────────────────────────────────────────────────
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedInteger('stock_alert_threshold')->default(10);
            $table->boolean('show_receipts')->default(true);
            $table->boolean('show_stats')->default(true);
            $table->timestamps();
        });

        // Add depot_id FK on users now that depots table exists
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('depot_id')->references('id')->on('depots')->nullOnDelete();
        });

        // ── 6. Pharmacy ↔ User pivot ─────────────────────────────────────────
        Schema::create('pharmacy_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'pharmacy_id']);
        });

        // ── 7. Spatie permissions ────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        // ── 8. Categories ────────────────────────────────────────────────────
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 9. Drugs ─────────────────────────────────────────────────────────
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('effet_s_med')->nullable();
            $table->string('dosage_med')->nullable();
            $table->string('form_med')->nullable();
            $table->decimal('prix_med', 10, 2)->nullable();
            $table->boolean('is_authorized')->default(true);
            $table->text('description')->nullable();
            $table->unsignedInteger('min_stock')->default(10);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 10. Drug Units ───────────────────────────────────────────────────
        Schema::create('drug_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('barcode')->unique();
            $table->unsignedInteger('quantite_contenu')->default(1);
            $table->unsignedInteger('quantite_actuelle')->default(1);
            $table->date('expiration_date');
            $table->enum('status', ['en_stock', 'vendue', 'perimee', 'retiree'])->default('en_stock');
            $table->enum('current_location_type', ['pharmacy', 'depot']);
            $table->unsignedBigInteger('current_location_id');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['current_location_type', 'current_location_id']);
        });

        // ── 11. Transfers ────────────────────────────────────────────────────
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_pharmacy_id')->nullable()->constrained('pharmacies')->nullOnDelete();
            $table->foreignId('to_depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('completed');
            $table->unsignedInteger('items_count')->default(0);
            $table->timestamp('performed_at');
            $table->timestamps();
        });

        // ── 12. Transfer Items ───────────────────────────────────────────────
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['transfer_id', 'drug_unit_id']);
            $table->index('drug_unit_id');
        });

        // ── 13. Stock Movements ──────────────────────────────────────────────
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_type')->nullable();
            $table->unsignedBigInteger('from_id')->nullable();
            $table->string('to_type')->nullable();
            $table->unsignedBigInteger('to_id')->nullable();
            $table->enum('action', ['creation', 'transfer', 'sale', 'return', 'destruction']);
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index('transfer_id');
        });

        // ── 14. Sales ────────────────────────────────────────────────────────
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 36)->nullable()->index();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sold_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        // ── 15. Stock Requests ───────────────────────────────────────────────
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('depot_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        // ── 16. Notifications ────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // ── 17. Audit Logs ───────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop in reverse order (respecting FK constraints)
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('stock_request_items');
        Schema::dropIfExists('stock_requests');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('drug_units');
        Schema::dropIfExists('drugs');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('pharmacy_user');
        Schema::dropIfExists('depots');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('pharmacies');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Pharmacy;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load('pharmacies');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail'   => $user instanceof MustVerifyEmail,
            'status'            => session('status'),
            'userPharmacies'    => $user->pharmacies->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
            'activePharmacyId'  => $user->pharmacy_id,
        ]);
    }

    /**
     * Switch the user's active pharmacy.
     */
    public function switchPharmacy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $user = $request->user();

        // Ensure the user belongs to that pharmacy
        if (! $user->pharmacies()->where('pharmacies.id', $validated['pharmacy_id'])->exists()) {
            abort(403, 'Vous n\'avez pas accès à cette pharmacie.');
        }

        $user->update(['pharmacy_id' => $validated['pharmacy_id']]);

        return Redirect::route('profile.edit')->with('status', 'Pharmacie active changée avec succès.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Export the entire database as SQL dump (super_admin only).
     */
    public function exportDatabaseSql(Request $request)
    {
        if (! $request->user()->hasRole('super_admin')) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        $driver = $dbConfig['driver'] ?? 'sqlite';

        $filename = 'database_export_' . now()->format('Y-m-d_His') . '.sql';

        if ($driver === 'sqlite') {
            $databasePath = $dbConfig['database'] ?? database_path('database.sqlite');

            if (! file_exists($databasePath)) {
                abort(404, 'Base de données introuvable.');
            }

            // Use sqlite3 command to dump the database
            $command = escapeshellcmd("sqlite3 \"{$databasePath}\" .dump");
            $sqlDump = shell_exec($command);

            if ($sqlDump === null || $sqlDump === '') {
                // Fallback: read and dump manually
                $sqlDump = $this->generateSqliteDump($databasePath);
            }
        } else {
            // For MySQL/PostgreSQL, use mysqldump/pg_dump
            $sqlDump = $this->generateGenericDump($dbConfig, $driver);
        }

        return response($sqlDump, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate SQL dump for SQLite manually (fallback).
     */
    private function generateSqliteDump(string $databasePath): string
    {
        $pdo = new \PDO("sqlite:{$databasePath}");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $sql = "-- Database Export\n";
        $sql .= "-- Generated: " . now()->toIso8601String() . "\n";
        $sql .= "-- Source: {$databasePath}\n\n";
        $sql .= "PRAGMA foreign_keys=OFF;\n\n";

        // Get all tables
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            ->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $create = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table}'")
                ->fetchColumn();
            $sql .= $create . ";\n\n";

            // Get all data
            $rows = $pdo->query("SELECT * FROM \"{$table}\"")->fetchAll(\PDO::FETCH_ASSOC);

            if (! empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));

                foreach ($rows as $row) {
                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($value);
                    }, array_values($row));

                    $valueList = implode(', ', $values);
                    $sql .= "INSERT INTO \"{$table}\" ({$columnList}) VALUES ({$valueList});\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "PRAGMA foreign_keys=ON;\n";

        return $sql;
    }

    /**
     * Generate SQL dump for MySQL/PostgreSQL.
     */
    private function generateGenericDump(array $dbConfig, string $driver): string
    {
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? ($driver === 'mysql' ? '3306' : '5432');
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        if ($driver === 'mysql') {
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s %s %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password ? '-p' . escapeshellarg($password) : '',
                escapeshellarg($database)
            );
        } elseif ($driver === 'pgsql') {
            $env = $password ? "PGPASSWORD=" . escapeshellarg($password) . " " : "";
            $command = sprintf(
                '%spg_dump -h %s -p %s -U %s %s',
                $env,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database)
            );
        } else {
            throw new \Exception("Driver non supporté: {$driver}");
        }

        $output = shell_exec($command . ' 2>&1');

        if ($output === null || $output === '') {
            abort(500, 'Erreur lors de l\'export de la base de données.');
        }

        return $output;
    }
}

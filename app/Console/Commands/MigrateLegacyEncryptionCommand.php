<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EncryptionService;
use App\Services\FinancialRecordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyEncryptionCommand extends Command
{
    protected $signature = 'encryption:migrate-legacy {--user= : ID de usuario específico}';

    protected $description = 'Cifra registros legacy en trips y expenses usando la Llave Maestra';

    public function handle(EncryptionService $encryption, FinancialRecordService $financialRecords): int
    {
        if (! $encryption->masterKeyConfigured()) {
            $this->error('MASTER_ENCRYPTION_KEY no está configurada.');

            return self::FAILURE;
        }

        $query = User::query()->whereNotNull('admin_wrapped_dek');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $this->info("Migrando usuario {$user->id} ({$user->email})");

            try {
                $dek = $encryption->unwrapUserDekWithMasterKey($user);
            } catch (\Throwable $exception) {
                $this->warn("  Omitido: {$exception->getMessage()}");

                continue;
            }

            DB::table('trips')
                ->where('user_id', $user->id)
                ->where('encryption_version', 0)
                ->orderBy('fecha')
                ->chunk(100, function ($rows) use ($financialRecords, $dek) {
                    foreach ($rows as $row) {
                        DB::table('trips')
                            ->where('user_id', $row->user_id)
                            ->where('anio', $row->anio)
                            ->where('trip_number', $row->trip_number)
                            ->where('uuid', $row->uuid)
                            ->where('fecha', $row->fecha)
                            ->update($financialRecords->migrateTripRow($row, $dek));
                    }
                });

            DB::table('expenses')
                ->where('user_id', $user->id)
                ->where('encryption_version', 0)
                ->orderBy('fecha')
                ->chunk(100, function ($rows) use ($financialRecords, $dek) {
                    foreach ($rows as $row) {
                        DB::table('expenses')
                            ->where('user_id', $row->user_id)
                            ->where('anio', $row->anio)
                            ->where('expense_number', $row->expense_number)
                            ->where('uuid', $row->uuid)
                            ->where('fecha', $row->fecha)
                            ->update($financialRecords->migrateExpenseRow($row, $dek));
                    }
                });
        }

        $this->info('Migración legacy completada.');

        return self::SUCCESS;
    }
}

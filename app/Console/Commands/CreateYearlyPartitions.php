<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateYearlyPartitions extends Command
{
    protected $signature = 'app:create-yearly-partitions {--year=}';

    protected $description = 'Crea particiones anuales de trips y expenses en PostgreSQL';

    public function handle(): int
    {
        $targetYear = (int) ($this->option('year') ?: now()->year + 1);

        if (now()->month !== 12 && ! $this->option('year')) {
            $this->info('Fuera de diciembre: se verificará si faltan particiones del año subsiguiente.');
        }

        $years = $this->option('year') ? [$targetYear] : [$targetYear];

        foreach ($years as $year) {
            $this->createPartitionIfMissing('trips', $year);
            $this->createPartitionIfMissing('expenses', $year);
        }

        $this->info('Proceso de particiones completado.');

        return self::SUCCESS;
    }

    private function createPartitionIfMissing(string $parentTable, int $year): void
    {
        $partitionName = "{$parentTable}_{$year}";
        $from = "{$year}-01-01";
        $to = ($year + 1).'-01-01';

        $exists = DB::selectOne(
            'SELECT 1 FROM pg_class WHERE relname = ?',
            [$partitionName]
        );

        if ($exists) {
            $this->line("Partición {$partitionName} ya existe.");

            return;
        }

        DB::statement("CREATE TABLE {$partitionName} PARTITION OF {$parentTable} FOR VALUES FROM ('{$from}') TO ('{$to}')");
        $this->info("Partición {$partitionName} creada.");
    }
}

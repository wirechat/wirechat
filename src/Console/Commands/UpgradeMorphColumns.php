<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Facades\Wirechat;

class UpgradeMorphColumns extends Command
{
    protected $signature = 'wirechat:upgrade-morph-columns {--dry-run : Show what would change without applying}';

    protected $description = 'Convert Wirechat polymorphic *_id/*_type columns to strings (UUID/int/ULID-safe).';

    // Fixed lengths
    private const ID_LEN = 64;   // fits UUID/ULID/Bigint-as-string

    private const TYPE_LEN = 100;  // morph type (class alias)

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $driver = Schema::getConnection()->getDriverName();

        $this->line("<info>Driver:</info> {$driver}");
        $this->line('<info>Dry run:</info> '.($dry ? 'yes' : 'no'));

        // Exactly these two tables
        $plans = [
            Wirechat::actionModelTable() => [
                ['col' => 'actionable_id',   'is_id' => true],
                ['col' => 'actionable_type', 'is_id' => false],
                ['col' => 'actor_id',        'is_id' => true],
                ['col' => 'actor_type',      'is_id' => false],
            ],
            Wirechat::attachmentModelTable() => [
                ['col' => 'attachable_id',   'is_id' => true],
                ['col' => 'attachable_type', 'is_id' => false],
            ],
        ];

        foreach ($plans as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $this->warn("Skipping: {$table} does not exist.");

                continue;
            }

            $this->info("Table: {$table}");

            foreach ($columns as $step) {
                $col = $step['col'];
                $isId = $step['is_id'];
                $to = 'varchar('.($isId ? self::ID_LEN : self::TYPE_LEN).')';

                if (! Schema::hasColumn($table, $col)) {
                    $this->warn("  - {$col} missing, skip.");

                    continue;
                }

                // If NOT already string/text → change it.
                $current = Schema::getColumnType($table, $col); // integer, bigint, uuid, string, text, char, ...
                $needs = ! in_array($current, ['string', 'text'], true);

                $this->line("  - {$col}: {$current} → {$to}".($needs ? '' : ' (ok)'));
                if (! $needs || $dry) {
                    continue;
                }

                switch ($driver) {
                    case 'pgsql':
                        // Cast to text first, then tighten to varchar(N)
                        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} TYPE text USING {$col}::text");
                        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} TYPE {$to}");
                        break;

                    case 'mysql':
                    case 'mariadb':
                        // For *_id columns, force ASCII for smaller indexes (optional but nice)
                        $charset = $isId ? ' CHARACTER SET ascii COLLATE ascii_bin' : '';
                        DB::statement("ALTER TABLE {$table} MODIFY {$col} {$to}{$charset}");
                        break;

                    case 'sqlite':
                        $this->warn('    SQLite: in-place ALTER not supported; consider a table rebuild or migrate:fresh.');
                        break;

                    default:
                        // Fallback (requires doctrine/dbal)
                        Schema::table($table, function (Blueprint $t) use ($col, $isId) {
                            $t->string($col, $isId ? self::ID_LEN : self::TYPE_LEN)->change();
                        });
                        break;
                }

                $this->info("    Updated {$col}.");
            }

            // Ensure helpful composite indexes (idempotent)
            try {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if ($table === Wirechat::actionModelTable()) {
                        $t->index(['actionable_id', 'actionable_type'], 'actions_actionable_idx');
                        $t->index(['actor_id', 'actor_type'], 'actions_actor_idx');
                    }
                    if ($table === Wirechat::attachmentModelTable()) {
                        $t->index(['attachable_id', 'attachable_type'], 'attachments_attachable_idx');
                    }
                });
            } catch (\Throwable $e) {
                // ignore "already exists"
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CustomField;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeCustomFieldTranslations extends Command
{
    protected $signature = 'customfields:normalize-translations
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Normalize CustomField "value" translations to single JSON-encoded arrays (fixes double-encoded legacy rows)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $type = CustomField::class;

        $rows = Translation::where('translatable_type', $type)
            ->where('key', 'value')
            ->get(['id', 'value']);

        $this->info("Scanning {$rows->count()} translation rows for type {$type}");

        $fixed = 0;
        $skipped = 0;
        $bad = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $original = $row->getRawOriginal('value');
                $normalized = $this->normalize($original);

                if ($normalized === null) {
                    $bad++;
                    $this->warn("  id={$row->id}: could not normalize -> ".mb_strimwidth((string) $original, 0, 80, '...'));
                    continue;
                }

                if ($normalized === $original) {
                    $skipped++;
                    continue;
                }

                $fixed++;
                $this->line("  id={$row->id}: fixed");

                if (!$dry) {
                    DB::table('translations')->where('id', $row->id)->update([
                        'value' => $normalized,
                        'updated_at' => now(),
                    ]);
                }
            }

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info("Done. fixed={$fixed} skipped={$skipped} bad={$bad}".($dry ? ' (dry-run, no writes)' : ''));
        return self::SUCCESS;
    }

    private function normalize(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return $raw;
        }

        $current = $raw;
        for ($i = 0; $i < 6; $i++) {
            if (!is_string($current)) {
                break;
            }
            $decoded = json_decode($current, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            $current = $decoded;
            if (is_array($current)) {
                break;
            }
        }

        if (!is_array($current)) {
            return null;
        }

        return json_encode(array_values($current), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

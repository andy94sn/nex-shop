<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Category;

class SyncCategoryParentIdCommand extends Command
{
    protected $signature = 'catalog:sync-category-parent-id
                            {--dry-run : Preview changes without writing to the database}
                            {--chunk=500 : Number of categories to process per chunk}';

    protected $description = 'Populate parent_id on categories by resolving parent_code to the corresponding category id.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk  = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('DRY-RUN mode — no changes will be saved.');
        }

        $this->info('Building code → id lookup table…');

        // Load the full code → id map in one query to avoid N+1 updates.
        $codeToId = Category::withTrashed()
            ->whereNotNull('code')
            ->pluck('id', 'code');

        $this->info("Found {$codeToId->count()} categories with a code.");

        $updated  = 0;
        $skipped  = 0;
        $orphaned = 0;

        $this->info('Processing categories with a parent_code…');

        Category::withTrashed()
            ->whereNotNull('parent_code')
            ->select(['id', 'parent_code', 'parent_id'])
            ->chunkById($chunk, function ($categories) use ($codeToId, $dryRun, &$updated, &$skipped, &$orphaned) {
                $rows = [];

                foreach ($categories as $category) {
                    $resolvedId = $codeToId->get($category->parent_code);

                    if ($resolvedId === null) {
                        $this->warn("  Category id={$category->id} has parent_code='{$category->parent_code}' but no matching category was found — skipping.");
                        $orphaned++;
                        continue;
                    }

                    if ((int) $category->parent_id === (int) $resolvedId) {
                        $skipped++;
                        continue;
                    }

                    $rows[$category->id] = $resolvedId;
                }

                if (empty($rows)) {
                    return;
                }

                if (! $dryRun) {
                    // Bulk-update in a single query using a CASE expression.
                    $ids        = array_keys($rows);
                    $cases      = '';
                    $bindings   = [];

                    foreach ($rows as $id => $parentId) {
                        $cases      .= "WHEN ? THEN ? ";
                        $bindings[] = $id;
                        $bindings[] = $parentId;
                    }

                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $bindings     = array_merge($bindings, $ids);

                    DB::statement(
                        "UPDATE categories SET parent_id = CASE id {$cases}END WHERE id IN ({$placeholders})",
                        $bindings
                    );
                } else {
                    foreach ($rows as $id => $parentId) {
                        $this->line("  [DRY-RUN] Would set categories.id={$id} parent_id={$parentId}");
                    }
                }

                $updated += count($rows);
            });

        $this->newLine();
        $this->table(
            ['Stat', 'Count'],
            [
                ['Updated', $updated],
                ['Already correct (skipped)', $skipped],
                ['Orphaned parent_code (skipped)', $orphaned],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY-RUN complete — nothing was written.');
        } else {
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}

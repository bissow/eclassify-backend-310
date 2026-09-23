<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class BackfillCategoryPaths extends Command
{
    protected $signature = 'categories:backfill-paths';
    protected $description = 'Backfill the path column for existing categories.';

    public function handle(): int
    {
        $total = Category::count();
        if ($total === 0) {
            $this->info('No categories found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $roots = Category::whereNull('parent_category_id')->orderBy('id')->get();
        foreach ($roots as $root) {
            $this->process($root, null, $bar);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Category paths backfilled.');

        return self::SUCCESS;
    }

    protected function process(Category $category, ?string $parentPath, $bar): void
    {
        $prefix = $parentPath ?: '/ads';
        $path = rtrim($prefix, '/') . '/' . $category->slug;

        Category::withoutEvents(function () use ($category, $path) {
            $category->forceFill(['path' => $path])->saveQuietly();
        });

        $bar->advance();

        $children = Category::where('parent_category_id', $category->id)->orderBy('id')->get();
        foreach ($children as $child) {
            $this->process($child, $path, $bar);
        }
    }
}

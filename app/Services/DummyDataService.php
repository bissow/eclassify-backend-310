<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CustomField;
use App\Models\CustomFieldCategory;
use App\Models\Item;
use App\Models\ItemCustomFieldValue;
use App\Models\ItemImages;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dummy data is fully reversible: every seeded row carries is_dummy = 1 so it can
 * be removed at any time without touching real data. The category / custom field
 * catalog is imported once (idempotent); dummy items can be generated repeatedly.
 */
class DummyDataService
{
    public const PLACEHOLDER_IMAGE = 'dummy/placeholder.png';

    private const LOCATIONS = [
        ['city' => 'New York', 'state' => 'New York', 'country' => 'United States', 'latitude' => 40.7128, 'longitude' => -74.0060],
        ['city' => 'Los Angeles', 'state' => 'California', 'country' => 'United States', 'latitude' => 34.0522, 'longitude' => -118.2437],
        ['city' => 'London', 'state' => 'England', 'country' => 'United Kingdom', 'latitude' => 51.5074, 'longitude' => -0.1278],
        ['city' => 'Dubai', 'state' => 'Dubai', 'country' => 'United Arab Emirates', 'latitude' => 25.2048, 'longitude' => 55.2708],
        ['city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'latitude' => 19.0760, 'longitude' => 72.8777],
        ['city' => 'Ahmedabad', 'state' => 'Gujarat', 'country' => 'India', 'latitude' => 23.0225, 'longitude' => 72.5714],
        ['city' => 'Sydney', 'state' => 'New South Wales', 'country' => 'Australia', 'latitude' => -33.8688, 'longitude' => 151.2093],
        ['city' => 'Toronto', 'state' => 'Ontario', 'country' => 'Canada', 'latitude' => 43.6532, 'longitude' => -79.3832],
        ['city' => 'Singapore', 'state' => 'Singapore', 'country' => 'Singapore', 'latitude' => 1.3521, 'longitude' => 103.8198],
        ['city' => 'Berlin', 'state' => 'Berlin', 'country' => 'Germany', 'latitude' => 52.5200, 'longitude' => 13.4050],
    ];

    private const ADJECTIVES = [
        'Brand New', 'Barely Used', 'Premium', 'Stylish', 'Reliable', 'Affordable',
        'Top Quality', 'Well Maintained', 'Classic', 'Modern', 'Imported', 'Genuine',
    ];

    private const DESCRIPTION_LINES = [
        'In excellent condition and works perfectly.',
        'Used only for a few months, kept with great care.',
        'Selling because of relocation, price slightly negotiable.',
        'Comes with original packaging and accessories.',
        'No scratches or damage, looks as good as new.',
        'Perfect for daily use, very well maintained.',
        'First owner, all documents available.',
        'Urgent sale, serious buyers only.',
    ];

    private const TEXT_SAMPLES = ['Standard', 'Premium', 'Original', 'Universal', 'Classic', 'Custom'];

    /* ---------------------------------------------------------------------
     | Status
     * --------------------------------------------------------------------*/

    public function counts(): array
    {
        return [
            'categories'    => Category::where('is_dummy', 1)->count(),
            'custom_fields' => CustomField::where('is_dummy', 1)->count(),
            'items'         => Item::withTrashed()->where('is_dummy', 1)->count(),
        ];
    }

    /* ---------------------------------------------------------------------
     | Populate
     * --------------------------------------------------------------------*/

    /**
     * Populate dummy data. Catalog (categories + custom fields) is imported only
     * once; items are added on every call ($itemCount per run).
     */
    public function populate(int $itemCount = 20, ?int $ownerUserId = null): array
    {
        $catalogImported = $this->importCatalog();
        $itemsCreated = $itemCount > 0 ? $this->generateItems($itemCount, $ownerUserId) : 0;

        return [
            'catalog_imported' => $catalogImported,
            'items_created'    => $itemsCreated,
            'counts'           => $this->counts(),
        ];
    }

    /**
     * Import dummy categories + custom fields from the bundled SQL dump without
     * touching existing rows: IDs are remapped to freshly generated ones and
     * slugs are de-duplicated against existing categories.
     *
     * @return bool true when imported, false when dummy catalog already exists
     */
    public function importCatalog(): bool
    {
        if (Category::where('is_dummy', 1)->exists()) {
            return false;
        }

        $sqlFilePath = public_path('categories_and_sub_custom_field_demo.sql');

        if (!file_exists($sqlFilePath)) {
            throw new \RuntimeException("Dummy data SQL file not found at: {$sqlFilePath}");
        }

        $sql = file_get_contents($sqlFilePath);
        $categoryRows = $this->parseInsertRows($sql, 'categories');
        $customFieldRows = $this->parseInsertRows($sql, 'custom_fields');
        $pivotRows = $this->parseInsertRows($sql, 'custom_field_categories');

        if (empty($categoryRows)) {
            throw new \RuntimeException('No category rows could be parsed from the dummy data SQL file.');
        }

        $this->ensurePlaceholderImage();

        DB::transaction(function () use ($categoryRows, $customFieldRows, $pivotRows) {
            $categoryIdMap = $this->insertCategories($categoryRows);
            $customFieldIdMap = $this->insertCustomFields($customFieldRows);
            $this->insertCustomFieldCategories($pivotRows, $categoryIdMap, $customFieldIdMap);
        });

        return true;
    }

    /**
     * No real image assets are bundled — every dummy record points at a single
     * placeholder copied into the dummy storage folder.
     */
    private function ensurePlaceholderImage(): void
    {
        if (Storage::disk('public')->exists(self::PLACEHOLDER_IMAGE)) {
            return;
        }

        $source = public_path('assets/images/logo/placeholder.png');
        if (!file_exists($source)) {
            throw new \RuntimeException("Placeholder image not found at: {$source}");
        }

        Storage::disk('public')->put(self::PLACEHOLDER_IMAGE, file_get_contents($source));
    }

    /**
     * Insert categories parent-first, remapping old IDs to new ones.
     *
     * @return array<int, int> old ID => new ID
     */
    private function insertCategories(array $rows): array
    {
        $idMap = [];
        $pending = $rows;

        while (!empty($pending)) {
            $progressed = false;

            foreach ($pending as $key => $row) {
                $oldParentId = $row['parent_category_id'];
                if ($oldParentId !== null && !isset($idMap[(int) $oldParentId])) {
                    continue; // parent not inserted yet
                }

                $category = new Category();
                $category->forceFill([
                    'name'               => $row['name'],
                    'parent_category_id' => $oldParentId !== null ? $idMap[(int) $oldParentId] : null,
                    'sequence'           => $row['sequence'] !== null ? (int) $row['sequence'] : null,
                    'image'              => self::PLACEHOLDER_IMAGE,
                    'description'        => $row['description'],
                    'status'             => (int) $row['status'],
                    'slug'               => HelperService::generateUniqueSlug(new Category(), (string) $row['slug']),
                    'is_job_category'    => (int) ($row['is_job_category'] ?? 0),
                    'price_optional'     => (int) ($row['price_optional'] ?? 0),
                    'is_dummy'           => 1,
                ]);
                $category->save();

                $idMap[(int) $row['id']] = $category->id;
                unset($pending[$key]);
                $progressed = true;
            }

            if (!$progressed) {
                // Orphan rows whose parent is missing from the dump — import as root.
                foreach ($pending as $key => $row) {
                    $pending[$key]['parent_category_id'] = null;
                }
                Log::warning('DummyDataService: ' . count($pending) . ' category rows had missing parents; importing as root categories.');
            }
        }

        return $idMap;
    }

    /**
     * @return array<int, int> old ID => new ID
     */
    private function insertCustomFields(array $rows): array
    {
        $idMap = [];

        foreach ($rows as $row) {
            $customField = new CustomField();
            $customField->forceFill([
                'name'       => $row['name'],
                'type'       => $row['type'],
                'image'      => self::PLACEHOLDER_IMAGE,
                'required'   => (int) ($row['required'] ?? 0),
                'values'     => $row['values'],
                'min_length' => $row['min_length'] !== null ? (int) $row['min_length'] : null,
                'max_length' => $row['max_length'] !== null ? (int) $row['max_length'] : null,
                'status'     => (int) $row['status'],
                'is_dummy'   => 1,
            ]);
            $customField->save();

            $idMap[(int) $row['id']] = $customField->id;
        }

        return $idMap;
    }

    private function insertCustomFieldCategories(array $rows, array $categoryIdMap, array $customFieldIdMap): void
    {
        $inserts = [];
        foreach ($rows as $row) {
            $categoryId = $categoryIdMap[(int) $row['category_id']] ?? null;
            $customFieldId = $customFieldIdMap[(int) $row['custom_field_id']] ?? null;
            if ($categoryId === null || $customFieldId === null) {
                continue;
            }
            $inserts[] = [
                'category_id'     => $categoryId,
                'custom_field_id' => $customFieldId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            CustomFieldCategory::insert($chunk);
        }
    }

    /* ---------------------------------------------------------------------
     | Items
     * --------------------------------------------------------------------*/

    /**
     * Generate dummy items under dummy leaf categories. Safe to call repeatedly —
     * every run adds $count new items.
     */
    public function generateItems(int $count, ?int $ownerUserId = null): int
    {
        $leafCategories = Category::where('is_dummy', 1)
            ->where('status', 1)
            ->whereDoesntHave('subcategories')
            ->get();

        if ($leafCategories->isEmpty()) {
            throw new \RuntimeException('No dummy categories found. Import the dummy catalog first.');
        }

        $user = $this->ownerUser($ownerUserId);
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            /** @var Category $category */
            $category = $leafCategories->random();
            $categoryChainIds = $this->categoryChainIds($category);
            $location = self::LOCATIONS[array_rand(self::LOCATIONS)];
            $name = self::ADJECTIVES[array_rand(self::ADJECTIVES)] . ' ' . $category->getRawOriginal('name');

            $descriptionLines = (array) array_rand(array_flip(self::DESCRIPTION_LINES), 3);

            $item = new Item();
            $item->forceFill([
                'name'             => $name,
                'slug'             => HelperService::generateUniqueSlug(new Item(), $name . '-' . Str::lower(Str::random(6))),
                'description'      => implode(' ', $descriptionLines),
                'category_id'      => $category->id,
                'all_category_ids' => implode(',', $categoryChainIds),
                'user_id'          => $user->id,
                'price'            => $category->is_job_category ? null : random_int(10, 4999),
                'min_salary'       => $category->is_job_category ? random_int(2, 8) * 1000 : null,
                'max_salary'       => $category->is_job_category ? random_int(9, 20) * 1000 : null,
                'latitude'         => $location['latitude'],
                'longitude'        => $location['longitude'],
                'address'          => $location['city'] . ', ' . $location['state'] . ', ' . $location['country'],
                'city'             => $location['city'],
                'state'            => $location['state'],
                'country'          => $location['country'],
                'contact'          => '1234567890',
                'status'           => 'approved',
                'expiry_date'      => null,
                'created_at'       => now()->subDays(random_int(0, 45))->subMinutes(random_int(0, 1440)),
                'is_dummy'         => 1,
            ]);
            $item->save();

            ItemImages::insert([
                'item_id'    => $item->id,
                'image'      => self::PLACEHOLDER_IMAGE,
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertCustomFieldValues($item, $categoryChainIds);
            $created++;
        }

        return $created;
    }

    /**
     * Dummy items belong to an admin account — app users live in Firebase, so
     * no synthetic seller account is ever created.
     */
    private function ownerUser(?int $ownerUserId): User
    {
        if ($ownerUserId !== null) {
            $user = User::find($ownerUserId);
            if ($user) {
                return $user;
            }
        }

        $admin = User::role('Super Admin')->first();
        if (!$admin) {
            throw new \RuntimeException('No admin user found to own the dummy items.');
        }

        return $admin;
    }

    /** @return array<int> leaf-first chain of category IDs (leaf, parent, ..., root) */
    private function categoryChainIds(Category $category): array
    {
        $ids = [$category->id];
        $current = $category;
        while ($current->parent_category_id !== null) {
            $current = Category::find($current->parent_category_id);
            if ($current === null) {
                break;
            }
            $ids[] = $current->id;
        }

        return $ids;
    }

    /**
     * Custom fields are attached anywhere along the category chain (usually on
     * parents), so collect them for every category the item belongs to.
     *
     * @param array<int> $categoryChainIds
     */
    private function insertCustomFieldValues(Item $item, array $categoryChainIds): void
    {
        $customFields = CustomField::where('status', 1)
            ->whereHas('custom_field_category', static function ($q) use ($categoryChainIds) {
                $q->whereIn('category_id', $categoryChainIds);
            })
            ->get()
            ->unique('id');

        $inserts = [];

        foreach ($customFields as $customField) {
            $value = $this->randomCustomFieldValue($customField);
            if ($value === null) {
                continue;
            }
            $inserts[] = [
                'item_id'         => $item->id,
                'custom_field_id' => $customField->id,
                'language_id'     => 1,
                'value'           => json_encode($value),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if (!empty($inserts)) {
            ItemCustomFieldValue::insert($inserts);
        }
    }

    private function randomCustomFieldValue(CustomField $customField): ?array
    {
        $options = json_decode($customField->getRawOriginal('values') ?? '', true);
        $options = is_array($options) ? array_values(array_filter($options, 'is_string')) : [];

        switch ($customField->type) {
            case 'dropdown':
            case 'radio':
                return !empty($options) ? [$options[array_rand($options)]] : null;
            case 'checkbox':
                if (empty($options)) {
                    return null;
                }
                $picked = (array) array_rand(array_flip($options), min(2, count($options)));
                return array_values($picked);
            case 'number':
                return [(string) random_int(1, 99)];
            case 'textbox':
            case 'textarea':
                return [self::TEXT_SAMPLES[array_rand(self::TEXT_SAMPLES)]];
            default: // fileinput and anything unknown
                return null;
        }
    }

    /* ---------------------------------------------------------------------
     | Delete
     * --------------------------------------------------------------------*/

    /**
     * Remove all dummy data while leaving real data untouched. Dummy categories
     * or custom fields that real records depend on are kept and reported.
     */
    public function delete(): array
    {
        $itemsDeleted = $this->deleteItems();
        [$customFieldsDeleted, $customFieldsKept] = $this->deleteCustomFields();
        [$categoriesDeleted, $categoriesKept] = $this->deleteCategories();

        $counts = $this->counts();
        if (array_sum($counts) === 0) {
            Storage::disk('public')->deleteDirectory('dummy');
        }

        return [
            'items_deleted'         => $itemsDeleted,
            'custom_fields_deleted' => $customFieldsDeleted,
            'custom_fields_kept'    => $customFieldsKept,
            'categories_deleted'    => $categoriesDeleted,
            'categories_kept'       => $categoriesKept,
            'counts'                => $this->counts(),
        ];
    }

    private function deleteItems(): int
    {
        $deleted = 0;

        Item::withTrashed()->where('is_dummy', 1)->chunkById(50, function ($items) use (&$deleted) {
            foreach ($items as $item) {
                DB::table('translations')
                    ->where('translatable_type', Item::class)
                    ->where('translatable_id', $item->id)
                    ->delete();
                // forceDelete cascades item_images, item_custom_field_values,
                // favourites etc. via foreign keys and fires model events.
                $item->forceDelete();
                $deleted++;
            }
        });

        return $deleted;
    }

    /** @return array{0: int, 1: int} [deleted, kept] */
    private function deleteCustomFields(): array
    {
        $dummyFieldIds = CustomField::where('is_dummy', 1)->pluck('id');
        if ($dummyFieldIds->isEmpty()) {
            return [0, 0];
        }

        // After dummy items are gone, any remaining value belongs to a real item.
        $inUseIds = ItemCustomFieldValue::whereIn('custom_field_id', $dummyFieldIds)
            ->distinct()
            ->pluck('custom_field_id');

        $deletableIds = $dummyFieldIds->diff($inUseIds);
        $deleted = 0;

        foreach (CustomField::whereIn('id', $deletableIds)->get() as $customField) {
            $customField->delete(); // cascades custom_field_categories

            DB::table('translations')
                ->where('translatable_type', CustomField::class)
                ->where('translatable_id', $customField->id)
                ->delete();

            $deleted++;
        }

        return [$deleted, $inUseIds->count()];
    }

    /** @return array{0: int, 1: int} [deleted, kept] */
    private function deleteCategories(): array
    {
        $deleted = 0;

        // Delete leaves first; repeat until nothing changes. Categories that real
        // items (or kept children) reference fail the checks and remain.
        do {
            $progressed = false;

            $deletable = Category::where('is_dummy', 1)
                ->whereDoesntHave('subcategories')
                ->get();

            foreach ($deletable as $category) {
                $hasItems = DB::table('items')->where('category_id', $category->id)->exists();
                if ($hasItems) {
                    continue;
                }

                try {
                    $category->delete();
                } catch (\Throwable $th) {
                    // Referenced by something else (packages, sliders, ...) — keep it.
                    Log::info("DummyDataService: keeping dummy category #{$category->id} ({$th->getMessage()})");
                    continue;
                }

                DB::table('translations')
                    ->where('translatable_type', Category::class)
                    ->where('translatable_id', $category->id)
                    ->delete();

                $deleted++;
                $progressed = true;
            }
        } while ($progressed);

        return [$deleted, Category::where('is_dummy', 1)->count()];
    }

    /* ---------------------------------------------------------------------
     | SQL dump parsing
     * --------------------------------------------------------------------*/

    /**
     * Parse all INSERT statements for the given table from a MySQL dump and
     * return rows as column => value maps.
     */
    private function parseInsertRows(string $sql, string $table): array
    {
        $rows = [];
        $offset = 0;
        $pattern = '/INSERT INTO `' . preg_quote($table, '/') . '`\s*\(([^)]+)\)\s*VALUES/i';

        while (preg_match($pattern, $sql, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $columns = array_map(
                static fn ($column) => trim($column, " `\r\n\t"),
                explode(',', $match[1][0])
            );

            $position = $match[0][1] + strlen($match[0][0]);
            [$tuples, $position] = $this->parseValueTuples($sql, $position);

            foreach ($tuples as $tuple) {
                if (count($tuple) === count($columns)) {
                    $rows[] = array_combine($columns, $tuple);
                }
            }

            $offset = $position;
        }

        return $rows;
    }

    /**
     * Parse `(v, v, ...), (v, v, ...);` tuples starting at $position, honoring
     * quoted strings and escape sequences.
     *
     * @return array{0: array<array>, 1: int} [tuples, position after the statement]
     */
    private function parseValueTuples(string $sql, int $position): array
    {
        $tuples = [];
        $length = strlen($sql);

        while ($position < $length) {
            while ($position < $length && (ctype_space($sql[$position]) || $sql[$position] === ',')) {
                $position++;
            }
            if ($position >= $length || $sql[$position] !== '(') {
                if ($position < $length && $sql[$position] === ';') {
                    $position++;
                }
                break;
            }

            $position++; // consume '('
            $tuple = [];
            $buffer = '';
            $isQuoted = false;
            $inString = false;

            while ($position < $length) {
                $char = $sql[$position];

                if ($inString) {
                    if ($char === '\\' && $position + 1 < $length) {
                        $buffer .= $this->unescapeChar($sql[$position + 1]);
                        $position += 2;
                        continue;
                    }
                    if ($char === "'") {
                        if ($position + 1 < $length && $sql[$position + 1] === "'") {
                            $buffer .= "'";
                            $position += 2;
                            continue;
                        }
                        $inString = false;
                        $position++;
                        continue;
                    }
                    $buffer .= $char;
                    $position++;
                    continue;
                }

                if ($char === "'") {
                    $inString = true;
                    $isQuoted = true;
                    $position++;
                    continue;
                }

                if ($char === ',' || $char === ')') {
                    $tuple[] = $this->castSqlValue(trim($buffer), $isQuoted);
                    $buffer = '';
                    $isQuoted = false;
                    $position++;
                    if ($char === ')') {
                        break;
                    }
                    continue;
                }

                $buffer .= $char;
                $position++;
            }

            $tuples[] = $tuple;
        }

        return [$tuples, $position];
    }

    private function castSqlValue(string $raw, bool $isQuoted)
    {
        if ($isQuoted) {
            return $raw;
        }
        if (strcasecmp($raw, 'NULL') === 0 || $raw === '') {
            return null;
        }

        return $raw;
    }

    private function unescapeChar(string $char): string
    {
        return match ($char) {
            'n'     => "\n",
            'r'     => "\r",
            't'     => "\t",
            '0'     => "\0",
            'Z'     => "\x1a",
            default => $char, // covers \' \" \\ and any literal
        };
    }
}

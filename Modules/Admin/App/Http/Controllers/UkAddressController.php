<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Http\Requests\ImportUkAddressCsvRequest;
use Modules\Admin\Http\Requests\StoreUkAddressRequest;
use Modules\Admin\Http\Requests\UpdateUkAddressRequest;
use Modules\Admin\Models\UkAddress;
use SplFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UkAddressController extends Controller
{
    /**
     * Display the UK Address Management dashboard and table.
     */
    public function index(): View
    {
        $stats = [
            'total' => UkAddress::count(),
            'active' => UkAddress::where('is_active', true)->count(),
            'postcodes' => UkAddress::distinct('postcode_compact')->count('postcode_compact'),
            'demo' => UkAddress::where('is_demo', true)->count(),
        ];

        $postcodeCounts = UkAddress::query()
            ->select('postcode', DB::raw('COUNT(*) as total_count'), DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count'))
            ->groupBy('postcode')
            ->orderByDesc('total_count')
            ->limit(25)
            ->get();

        return view('admin::uk-addresses.index', compact('stats', 'postcodeCounts'));
    }

    /**
     * Return JSON data of UK addresses for AJAX DataTable with filtering.
     */
    public function data(Request $request): JsonResponse
    {
        $query = UkAddress::query()->latest('id');

        if ($request->filled('postcode')) {
            $compact = UkAddress::compactPostcode((string) $request->input('postcode'));
            if ($compact !== '') {
                $query->where('postcode_compact', 'like', '%' . $compact . '%');
            }
        }

        if ($request->filled('search_text')) {
            $term = trim((string) $request->input('search_text'));
            $query->where(function ($q) use ($term) {
                $q->where('street', 'like', '%' . $term . '%')
                    ->orWhere('city', 'like', '%' . $term . '%')
                    ->orWhere('address_line_1', 'like', '%' . $term . '%')
                    ->orWhere('building_name', 'like', '%' . $term . '%')
                    ->orWhere('postcode', 'like', '%' . $term . '%');
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $addresses = $query->get();

        // Precompute counts per postcode
        $countsByPostcode = UkAddress::query()
            ->select('postcode_compact', DB::raw('COUNT(*) as cnt'))
            ->groupBy('postcode_compact')
            ->pluck('cnt', 'postcode_compact');

        $data = $addresses->map(function (UkAddress $addr) use ($countsByPostcode) {
            return [
                'id' => $addr->id,
                'postcode' => $addr->postcode,
                'postcode_count' => (int) ($countsByPostcode[$addr->postcode_compact] ?? 1),
                'address_line_1' => $addr->address_line_1,
                'address_line_2' => $addr->address_line_2 ?? '',
                'building_name' => $addr->building_name ?? '',
                'building_number' => $addr->building_number ?? '',
                'street' => $addr->street ?? '',
                'city' => $addr->city,
                'county' => $addr->county ?? '',
                'country' => $addr->country,
                'latitude' => $addr->latitude,
                'longitude' => $addr->longitude,
                'full_address' => $addr->full_address,
                'source' => $addr->source ?? 'manual',
                'is_active' => (bool) $addr->is_active,
                'is_demo' => (bool) $addr->is_demo,
                'created_at' => $addr->created_at ? $addr->created_at->format('M d, Y') : '-',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created UK address record.
     */
    public function store(StoreUkAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['source'] = 'admin_manual';
        $hash = UkAddress::generateDedupHash($validated);

        if (UkAddress::where('dedup_hash', $hash)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This UK address already exists for the specified postcode.',
                'errors' => [
                    'address_line_1' => ['Duplicate address detected for postcode ' . $validated['postcode'] . '.'],
                ],
            ], 422);
        }

        $address = UkAddress::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Address '{$address->full_address}' added successfully.",
            'address' => $address,
        ], 201);
    }

    /**
     * Update an existing UK address record.
     */
    public function update(UpdateUkAddressRequest $request, UkAddress $ukAddress): JsonResponse
    {
        $validated = $request->validated();
        $hash = UkAddress::generateDedupHash($validated);

        if (UkAddress::where('dedup_hash', $hash)->where('id', '!=', $ukAddress->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Another address record with these exact details already exists.',
                'errors' => [
                    'address_line_1' => ['Duplicate address detected for postcode ' . $validated['postcode'] . '.'],
                ],
            ], 422);
        }

        $ukAddress->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Address updated to '{$ukAddress->full_address}'.",
            'address' => $ukAddress->fresh(),
        ]);
    }

    /**
     * Toggle active/inactive status of a UK address.
     */
    public function toggleStatus(UkAddress $ukAddress): JsonResponse
    {
        $ukAddress->update([
            'is_active' => ! $ukAddress->is_active,
        ]);

        $status = $ukAddress->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Address '{$ukAddress->postcode}' {$status} successfully.",
            'is_active' => (bool) $ukAddress->is_active,
        ]);
    }

    /**
     * Safely delete a UK address record.
     * Existing bookings store immutable address snapshots and are unaffected.
     */
    public function destroy(UkAddress $ukAddress): JsonResponse
    {
        $label = $ukAddress->full_address;
        $ukAddress->delete();

        return response()->json([
            'success' => true,
            'message' => "Address '{$label}' deleted safely.",
        ]);
    }

    /**
     * Stream UK addresses export as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $fileName = 'uk-addresses-' . now()->format('Y-m-d-His') . '.csv';

        $query = UkAddress::query()->orderBy('postcode')->orderBy('address_line_1');
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'postcode',
                'address_line_1',
                'address_line_2',
                'building_name',
                'building_number',
                'street',
                'city',
                'county',
                'country',
                'latitude',
                'longitude',
                'is_active',
                'is_demo',
                'source',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->postcode,
                        $row->address_line_1,
                        $row->address_line_2,
                        $row->building_name,
                        $row->building_number,
                        $row->street,
                        $row->city,
                        $row->county,
                        $row->country,
                        $row->latitude,
                        $row->longitude,
                        $row->is_active ? 1 : 0,
                        $row->is_demo ? 1 : 0,
                        $row->source,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Step 1 of CSV Import: Upload, validate, and preview first 5 rows without loading entire file into memory.
     */
    public function previewCsv(ImportUkAddressCsvRequest $request): JsonResponse
    {
        $file = $request->file('csv_file');
        $token = Str::uuid()->toString();
        $relativePath = "uk_address_imports/{$token}.csv";
        Storage::disk('local')->putFileAs('uk_address_imports', $file, "{$token}.csv");
        $fullPath = Storage::disk('local')->path($relativePath);

        try {
            $csv = new SplFileObject($fullPath, 'r');
            $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

            $headers = $csv->fgetcsv();
            if (! is_array($headers) || count(array_filter($headers, fn ($h) => trim((string) $h) !== '')) === 0) {
                Storage::disk('local')->delete($relativePath);

                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded CSV file is empty or missing a header row.',
                ], 422);
            }

            // Strip UTF-8 BOM from first header if present
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($h) => trim((string) $h), $headers);

            $sampleRows = [];
            $count = 0;
            while (! $csv->eof() && $count < 5) {
                $row = $csv->fgetcsv();
                if (is_array($row) && count(array_filter($row, fn ($v) => trim((string) $v) !== '')) > 0) {
                    $sampleRows[] = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
                    $count++;
                }
            }

            if (empty($sampleRows)) {
                Storage::disk('local')->delete($relativePath);

                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded CSV contains headers but no data rows.',
                ], 422);
            }

            $suggestedMapping = $this->suggestCsvMapping($headers);

            return response()->json([
                'success' => true,
                'import_token' => $token,
                'headers' => $headers,
                'sample_rows' => $sampleRows,
                'suggested_mapping' => $suggestedMapping,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($relativePath);

            return response()->json([
                'success' => false,
                'message' => 'Unable to parse the CSV file. Please verify it is valid UTF-8 CSV.',
            ], 422);
        }
    }

    /**
     * Step 2 of CSV Import: Stream-process mapped CSV in batches of 500 rows.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_token' => ['required', 'uuid'],
            'duplicate_strategy' => ['required', Rule::in(['skip', 'update'])],
            'mark_as_demo' => ['nullable', 'boolean'],
            'mapping' => ['required', 'array'],
            'mapping.postcode' => ['required', 'integer', 'min:0'],
            'mapping.address_line_1' => ['required', 'integer', 'min:0'],
            'mapping.city' => ['required', 'integer', 'min:0'],
            'mapping.address_line_2' => ['nullable', 'integer', 'min:0'],
            'mapping.building_name' => ['nullable', 'integer', 'min:0'],
            'mapping.building_number' => ['nullable', 'integer', 'min:0'],
            'mapping.street' => ['nullable', 'integer', 'min:0'],
            'mapping.county' => ['nullable', 'integer', 'min:0'],
            'mapping.country' => ['nullable', 'integer', 'min:0'],
            'mapping.latitude' => ['nullable', 'integer', 'min:0'],
            'mapping.longitude' => ['nullable', 'integer', 'min:0'],
            'mapping.is_active' => ['nullable', 'integer', 'min:0'],
        ]);

        $relativePath = "uk_address_imports/{$validated['import_token']}.csv";
        if (! Storage::disk('local')->exists($relativePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Import session expired or file not found. Please upload the CSV again.',
            ], 404);
        }

        $fullPath = Storage::disk('local')->path($relativePath);
        $mapping = $validated['mapping'];
        $strategy = $validated['duplicate_strategy'];
        $isDemo = $request->boolean('mark_as_demo', false);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $csv = new SplFileObject($fullPath, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $csv->fgetcsv(); // Skip header row

        $rowNumber = 1;
        $batch = [];

        $flushBatch = function () use (&$batch, $strategy, &$imported, &$updated, &$skipped) {
            if (empty($batch)) {
                return;
            }

            $hashes = array_keys($batch);
            $existing = UkAddress::whereIn('dedup_hash', $hashes)->pluck('id', 'dedup_hash')->all();

            $toInsert = [];
            foreach ($batch as $hash => $record) {
                if (isset($existing[$hash])) {
                    if ($strategy === 'update') {
                        UkAddress::where('id', $existing[$hash])->update($record);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $toInsert[] = $record;
                }
            }

            if (! empty($toInsert)) {
                UkAddress::insert($toInsert);
                $imported += count($toInsert);
            }

            $batch = [];
        };

        while (! $csv->eof()) {
            $row = $csv->fgetcsv();
            $rowNumber++;

            if (! is_array($row) || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $get = fn (string $field): string => isset($mapping[$field]) && $mapping[$field] !== null && isset($row[$mapping[$field]])
                ? trim((string) $row[$mapping[$field]])
                : '';

            $rawPostcode = $get('postcode');
            $normalizedPostcode = UkAddress::normalizePostcode($rawPostcode);
            $line1 = $get('address_line_1');
            $city = $get('city');

            if (! $normalizedPostcode || $line1 === '' || $city === '') {
                $failed++;
                if (count($errors) < 100) {
                    $reasons = [];
                    if (! $normalizedPostcode) {
                        $reasons[] = "Invalid UK postcode '{$rawPostcode}'";
                    }
                    if ($line1 === '') {
                        $reasons[] = 'Missing address_line_1';
                    }
                    if ($city === '') {
                        $reasons[] = 'Missing city';
                    }
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => implode('; ', $reasons),
                    ];
                }
                continue;
            }

            $latRaw = $get('latitude');
            $lonRaw = $get('longitude');
            $activeRaw = strtolower($get('is_active'));
            $isActive = $activeRaw === '' ? true : ! in_array($activeRaw, ['0', 'false', 'no', 'inactive'], true);

            $record = [
                'postcode' => $normalizedPostcode,
                'postcode_compact' => UkAddress::compactPostcode($normalizedPostcode),
                'address_line_1' => $line1,
                'address_line_2' => $get('address_line_2') ?: null,
                'building_name' => $get('building_name') ?: null,
                'building_number' => $get('building_number') ?: null,
                'street' => $get('street') ?: null,
                'city' => $city,
                'county' => $get('county') ?: null,
                'country' => $get('country') ?: 'United Kingdom',
                'latitude' => is_numeric($latRaw) ? (float) $latRaw : null,
                'longitude' => is_numeric($lonRaw) ? (float) $lonRaw : null,
                'source' => 'csv_import',
                'is_active' => $isActive,
                'is_demo' => $isDemo,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $hash = UkAddress::generateDedupHash($record);
            $record['dedup_hash'] = $hash;

            // Prevent duplicate inside the same batch
            if (isset($batch[$hash])) {
                if ($strategy === 'update') {
                    $batch[$hash] = $record;
                } else {
                    $skipped++;
                }
            } else {
                $batch[$hash] = $record;
            }

            if (count($batch) >= 500) {
                $flushBatch();
            }
        }

        $flushBatch();
        Storage::disk('local')->delete($relativePath);

        return response()->json([
            'success' => true,
            'message' => "CSV import completed: {$imported} imported, {$updated} updated, {$skipped} skipped, {$failed} failed.",
            'summary' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Auto-suggest CSV column mapping based on header names.
     */
    protected function suggestCsvMapping(array $headers): array
    {
        $fields = [
            'postcode' => ['postcode', 'post_code', 'postal_code', 'zip'],
            'address_line_1' => ['address_line_1', 'line_1', 'address1', 'address'],
            'address_line_2' => ['address_line_2', 'line_2', 'address2'],
            'building_name' => ['building_name', 'building'],
            'building_number' => ['building_number', 'house_number', 'number'],
            'street' => ['street', 'road', 'thoroughfare'],
            'city' => ['city', 'town', 'post_town'],
            'county' => ['county', 'region'],
            'country' => ['country'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'lon', 'lng'],
            'is_active' => ['is_active', 'active', 'status'],
        ];

        $mapping = [];
        foreach ($fields as $dbField => $aliases) {
            $mapping[$dbField] = null;
            foreach ($headers as $idx => $header) {
                $norm = strtolower(str_replace([' ', '-'], '_', $header));
                if (in_array($norm, $aliases, true)) {
                    $mapping[$dbField] = $idx;
                    break;
                }
            }
        }

        return $mapping;
    }
}

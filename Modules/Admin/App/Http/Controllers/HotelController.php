<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Http\Requests\ImportHotelCsvRequest;
use Modules\Admin\Http\Requests\StoreHotelRequest;
use Modules\Admin\Http\Requests\UpdateHotelRequest;
use Modules\Admin\Models\Hotel;
use Modules\Admin\Models\UkAddress;
use SplFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HotelController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total' => Hotel::count(),
            'active' => Hotel::where('is_active', true)->count(),
            'cities' => Hotel::distinct('city')->count('city'),
            'demo' => Hotel::where('is_demo', true)->count(),
        ];

        return view('admin::hotels.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Hotel::query()->latest('id');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim((string) $request->input('name')) . '%');
        }

        if ($request->filled('postcode_or_city')) {
            $term = trim((string) $request->input('postcode_or_city'));
            $compact = UkAddress::compactPostcode($term);
            $query->where(function ($q) use ($term, $compact) {
                $q->where('city', 'like', '%' . $term . '%')
                    ->orWhere('postcode', 'like', '%' . $term . '%');
                if ($compact !== '') {
                    $q->orWhere('postcode_compact', 'like', '%' . $compact . '%');
                }
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $hotels = $query->get()->map(fn (Hotel $hotel) => [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'address_line_1' => $hotel->address_line_1,
            'address_line_2' => $hotel->address_line_2 ?? '',
            'city' => $hotel->city,
            'county' => $hotel->county ?? '',
            'postcode' => $hotel->postcode,
            'country' => $hotel->country,
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'phone' => $hotel->phone ?? '',
            'website' => $hotel->website ?? '',
            'formatted_address' => $hotel->formatted_address,
            'source' => $hotel->source ?? 'admin_manual',
            'is_active' => (bool) $hotel->is_active,
            'is_demo' => (bool) $hotel->is_demo,
            'created_at' => $hotel->created_at ? $hotel->created_at->format('M d, Y') : '-',
        ]);

        return response()->json(['data' => $hotels]);
    }

    public function store(StoreHotelRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['source'] = 'admin_manual';
        $hash = Hotel::generateDedupHash($validated);

        if (Hotel::where('dedup_hash', $hash)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A hotel with the same name, address, and postcode already exists.',
                'errors' => ['name' => ['Duplicate hotel record detected.']],
            ], 422);
        }

        $hotel = Hotel::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Hotel '{$hotel->name}' added successfully.",
            'hotel' => $hotel,
        ], 201);
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validated();
        $hash = Hotel::generateDedupHash($validated);

        if (Hotel::where('dedup_hash', $hash)->where('id', '!=', $hotel->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Another hotel with the same name, address, and postcode already exists.',
                'errors' => ['name' => ['Duplicate hotel record detected.']],
            ], 422);
        }

        $hotel->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Hotel '{$hotel->name}' updated successfully.",
            'hotel' => $hotel->fresh(),
        ]);
    }

    public function toggleStatus(Hotel $hotel): JsonResponse
    {
        $hotel->update([
            'is_active' => ! $hotel->is_active,
        ]);

        $status = $hotel->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Hotel '{$hotel->name}' {$status} successfully.",
            'is_active' => (bool) $hotel->is_active,
        ]);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $name = $hotel->name;
        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => "Hotel '{$name}' deleted safely.",
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'hotels-' . now()->format('Y-m-d-His') . '.csv';

        $query = Hotel::query()->orderBy('name');
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'name',
                'address_line_1',
                'address_line_2',
                'city',
                'county',
                'postcode',
                'country',
                'latitude',
                'longitude',
                'phone',
                'is_active',
                'is_demo',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->name,
                        $row->address_line_1,
                        $row->address_line_2,
                        $row->city,
                        $row->county,
                        $row->postcode,
                        $row->country,
                        $row->latitude,
                        $row->longitude,
                        $row->phone,
                        $row->is_active ? 1 : 0,
                        $row->is_demo ? 1 : 0,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function previewCsv(ImportHotelCsvRequest $request): JsonResponse
    {
        $file = $request->file('csv_file');
        $token = Str::uuid()->toString();
        $relativePath = "hotel_imports/{$token}.csv";
        Storage::disk('local')->putFileAs('hotel_imports', $file, "{$token}.csv");
        $fullPath = Storage::disk('local')->path($relativePath);

        try {
            $csv = new SplFileObject($fullPath, 'r');
            $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

            $headers = $csv->fgetcsv();
            if (! is_array($headers) || count(array_filter($headers, fn ($h) => trim((string) $h) !== '')) === 0) {
                Storage::disk('local')->delete($relativePath);

                return response()->json(['success' => false, 'message' => 'CSV file is empty or missing headers.'], 422);
            }

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

                return response()->json(['success' => false, 'message' => 'CSV contains headers but no data rows.'], 422);
            }

            $fields = ['name', 'address_line_1', 'address_line_2', 'city', 'county', 'postcode', 'country', 'latitude', 'longitude', 'phone', 'is_active'];
            $suggested = [];
            foreach ($fields as $field) {
                $suggested[$field] = null;
                foreach ($headers as $idx => $h) {
                    if (strtolower(str_replace([' ', '-'], '_', $h)) === $field) {
                        $suggested[$field] = $idx;
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'import_token' => $token,
                'headers' => $headers,
                'sample_rows' => $sampleRows,
                'suggested_mapping' => $suggested,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($relativePath);

            return response()->json(['success' => false, 'message' => 'Unable to parse CSV file.'], 422);
        }
    }

    public function importCsv(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_token' => ['required', 'uuid'],
            'duplicate_strategy' => ['required', Rule::in(['skip', 'update'])],
            'mark_as_demo' => ['nullable', 'boolean'],
            'mapping' => ['required', 'array'],
            'mapping.name' => ['required', 'integer', 'min:0'],
            'mapping.address_line_1' => ['required', 'integer', 'min:0'],
            'mapping.city' => ['required', 'integer', 'min:0'],
            'mapping.postcode' => ['required', 'integer', 'min:0'],
            'mapping.address_line_2' => ['nullable', 'integer', 'min:0'],
            'mapping.county' => ['nullable', 'integer', 'min:0'],
            'mapping.country' => ['nullable', 'integer', 'min:0'],
            'mapping.latitude' => ['nullable', 'integer', 'min:0'],
            'mapping.longitude' => ['nullable', 'integer', 'min:0'],
            'mapping.phone' => ['nullable', 'integer', 'min:0'],
            'mapping.is_active' => ['nullable', 'integer', 'min:0'],
        ]);

        $relativePath = "hotel_imports/{$validated['import_token']}.csv";
        if (! Storage::disk('local')->exists($relativePath)) {
            return response()->json(['success' => false, 'message' => 'Import session expired or file not found.'], 404);
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
        $csv->fgetcsv();

        $rowNumber = 1;
        $batch = [];

        $flushBatch = function () use (&$batch, $strategy, &$imported, &$updated, &$skipped) {
            if (empty($batch)) {
                return;
            }

            $existing = Hotel::whereIn('dedup_hash', array_keys($batch))->pluck('id', 'dedup_hash')->all();
            $toInsert = [];

            foreach ($batch as $hash => $record) {
                if (isset($existing[$hash])) {
                    if ($strategy === 'update') {
                        Hotel::where('id', $existing[$hash])->update($record);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $toInsert[] = $record;
                }
            }

            if (! empty($toInsert)) {
                Hotel::insert($toInsert);
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

            $name = $get('name');
            $line1 = $get('address_line_1');
            $city = $get('city');
            $rawPostcode = $get('postcode');
            $normalizedPostcode = UkAddress::normalizePostcode($rawPostcode);

            if ($name === '' || $line1 === '' || $city === '' || ! $normalizedPostcode) {
                $failed++;
                if (count($errors) < 100) {
                    $errors[] = ['row' => $rowNumber, 'error' => "Invalid or missing required hotel fields (name, address_line_1, city, postcode='{$rawPostcode}')."];
                }
                continue;
            }

            $latRaw = $get('latitude');
            $lonRaw = $get('longitude');
            $activeRaw = strtolower($get('is_active'));
            $isActive = $activeRaw === '' ? true : ! in_array($activeRaw, ['0', 'false', 'no', 'inactive'], true);

            $record = [
                'name' => $name,
                'address_line_1' => $line1,
                'address_line_2' => $get('address_line_2') ?: null,
                'city' => $city,
                'county' => $get('county') ?: null,
                'postcode' => $normalizedPostcode,
                'postcode_compact' => UkAddress::compactPostcode($normalizedPostcode),
                'country' => $get('country') ?: 'United Kingdom',
                'latitude' => is_numeric($latRaw) ? (float) $latRaw : null,
                'longitude' => is_numeric($lonRaw) ? (float) $lonRaw : null,
                'phone' => $get('phone') ?: null,
                'source' => 'csv_import',
                'is_active' => $isActive,
                'is_demo' => $isDemo,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $hash = Hotel::generateDedupHash($record);
            $record['dedup_hash'] = $hash;

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

        if ($failed > 0) {
            Log::warning('Hotel CSV import completed with row errors', ['failed' => $failed, 'errors' => array_slice($errors, 0, 20)]);
        }

        return response()->json([
            'success' => true,
            'message' => "Hotel CSV import completed: {$imported} imported, {$updated} updated, {$skipped} skipped, {$failed} failed.",
            'summary' => compact('imported', 'updated', 'skipped', 'failed', 'errors'),
        ]);
    }
}

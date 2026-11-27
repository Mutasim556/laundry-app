<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LaundryTag;
use App\Models\LaundryTagAllocation;
use App\Models\LaundryTagBatch;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\LaundryTagPrintJob;
use App\Models\TagPrinterProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class LaundryTagService
{
    public const MAX_GENERATION_QUANTITY = 500;

    public function __construct(
        private readonly LaundryTagQrService $qrService,
    ) {}

    /**
     * Ensure default physical printer presets exist (idempotent).
     */
    public function ensureDefaultPrinterProfiles(?int $adminId = null): void
    {
        if (TagPrinterProfile::query()->exists()) {
            return;
        }

        $defaults = [
            [
                'name' => 'A4 Sheet — 8 Hanging Tags (40×125mm)',
                'is_default' => true,
                'preset' => TagPrinterProfile::PRESET_A4,
                'orientation' => 'portrait',
                'paper_width_mm' => 210,
                'paper_height_mm' => 297,
                'tag_width_mm' => 40,
                'tag_height_mm' => 125,
                'margin_top_mm' => 10,
                'margin_bottom_mm' => 10,
                'margin_left_mm' => 14,
                'margin_right_mm' => 14,
                'gap_horizontal_mm' => 6,
                'gap_vertical_mm' => 12,
                'tags_per_row' => 4,
                'tags_per_column' => 2,
                'qr_size_mm' => 24,
                'logo_size_mm' => 14,
                'font_size_pt' => 7.5,
                'code_font_size_pt' => 8.5,
                'show_brand_name' => true,
                'show_booking_field' => true,
                'show_package_field' => true,
                'show_cut_guides' => true,
                'show_hole_guide' => true,
                'background_printing' => true,
                'created_by_admin_id' => $adminId,
            ],
            [
                'name' => 'A4 Compact — 10 Hanging Tags (36×120mm)',
                'is_default' => false,
                'preset' => TagPrinterProfile::PRESET_A4,
                'orientation' => 'portrait',
                'paper_width_mm' => 210,
                'paper_height_mm' => 297,
                'tag_width_mm' => 36,
                'tag_height_mm' => 120,
                'margin_top_mm' => 12,
                'margin_bottom_mm' => 12,
                'margin_left_mm' => 8,
                'margin_right_mm' => 8,
                'gap_horizontal_mm' => 3.5,
                'gap_vertical_mm' => 14,
                'tags_per_row' => 5,
                'tags_per_column' => 2,
                'qr_size_mm' => 22,
                'logo_size_mm' => 13,
                'font_size_pt' => 7.0,
                'code_font_size_pt' => 8.0,
                'show_brand_name' => true,
                'show_booking_field' => true,
                'show_package_field' => true,
                'show_cut_guides' => true,
                'show_hole_guide' => true,
                'background_printing' => true,
                'created_by_admin_id' => $adminId,
            ],
            [
                'name' => 'Letter Sheet — 8 Hanging Tags (40×120mm)',
                'is_default' => false,
                'preset' => TagPrinterProfile::PRESET_LETTER,
                'orientation' => 'portrait',
                'paper_width_mm' => 215.9,
                'paper_height_mm' => 279.4,
                'tag_width_mm' => 40,
                'tag_height_mm' => 120,
                'margin_top_mm' => 10,
                'margin_bottom_mm' => 10,
                'margin_left_mm' => 14,
                'margin_right_mm' => 14,
                'gap_horizontal_mm' => 6,
                'gap_vertical_mm' => 12,
                'tags_per_row' => 4,
                'tags_per_column' => 2,
                'qr_size_mm' => 24,
                'logo_size_mm' => 14,
                'font_size_pt' => 7.5,
                'code_font_size_pt' => 8.5,
                'show_brand_name' => true,
                'show_booking_field' => true,
                'show_package_field' => true,
                'show_cut_guides' => true,
                'show_hole_guide' => true,
                'background_printing' => true,
                'created_by_admin_id' => $adminId,
            ],
            [
                'name' => 'Thermal Roll — Single Hanging Tag (45×130mm)',
                'is_default' => false,
                'preset' => TagPrinterProfile::PRESET_THERMAL,
                'orientation' => 'portrait',
                'paper_width_mm' => 45,
                'paper_height_mm' => 130,
                'tag_width_mm' => 43,
                'tag_height_mm' => 126,
                'margin_top_mm' => 2,
                'margin_bottom_mm' => 2,
                'margin_left_mm' => 1,
                'margin_right_mm' => 1,
                'gap_horizontal_mm' => 0,
                'gap_vertical_mm' => 0,
                'tags_per_row' => 1,
                'tags_per_column' => 1,
                'qr_size_mm' => 26,
                'logo_size_mm' => 15,
                'font_size_pt' => 8.0,
                'code_font_size_pt' => 9.0,
                'show_brand_name' => true,
                'show_booking_field' => true,
                'show_package_field' => true,
                'show_cut_guides' => false,
                'show_hole_guide' => true,
                'background_printing' => true,
                'created_by_admin_id' => $adminId,
            ],
        ];

        foreach ($defaults as $row) {
            TagPrinterProfile::create($row);
        }
    }

    public function getActivePrinterProfile(?int $profileId = null): TagPrinterProfile
    {
        $this->ensureDefaultPrinterProfiles();

        if ($profileId) {
            $profile = TagPrinterProfile::find($profileId);
            if ($profile) {
                return $profile;
            }
        }

        return TagPrinterProfile::where('is_default', true)->first()
            ?? TagPrinterProfile::orderBy('id')->firstOrFail();
    }

    /**
     * Save or update a Printer Profile and optionally mark it default.
     */
    public function savePrinterProfile(array $data, Admin $admin, ?TagPrinterProfile $profile = null): TagPrinterProfile
    {
        return DB::transaction(function () use ($data, $admin, $profile) {
            $isDefault = (bool) ($data['is_default'] ?? false);
            if ($isDefault) {
                TagPrinterProfile::query()->update(['is_default' => false]);
            }

            $payload = [
                'name' => trim((string) $data['name']),
                'is_default' => $isDefault || ! TagPrinterProfile::query()->where('is_default', true)->exists(),
                'preset' => (string) $data['preset'],
                'orientation' => (string) $data['orientation'],
                'paper_width_mm' => (float) $data['paper_width_mm'],
                'paper_height_mm' => (float) $data['paper_height_mm'],
                'tag_width_mm' => (float) $data['tag_width_mm'],
                'tag_height_mm' => (float) $data['tag_height_mm'],
                'margin_top_mm' => (float) $data['margin_top_mm'],
                'margin_bottom_mm' => (float) $data['margin_bottom_mm'],
                'margin_left_mm' => (float) $data['margin_left_mm'],
                'margin_right_mm' => (float) $data['margin_right_mm'],
                'gap_horizontal_mm' => (float) $data['gap_horizontal_mm'],
                'gap_vertical_mm' => (float) $data['gap_vertical_mm'],
                'tags_per_row' => (int) $data['tags_per_row'],
                'tags_per_column' => (int) $data['tags_per_column'],
                'qr_size_mm' => (float) $data['qr_size_mm'],
                'logo_size_mm' => (float) $data['logo_size_mm'],
                'font_size_pt' => (float) $data['font_size_pt'],
                'code_font_size_pt' => (float) $data['code_font_size_pt'],
                'show_brand_name' => (bool) ($data['show_brand_name'] ?? false),
                'show_booking_field' => (bool) ($data['show_booking_field'] ?? false),
                'show_package_field' => (bool) ($data['show_package_field'] ?? false),
                'show_cut_guides' => (bool) ($data['show_cut_guides'] ?? false),
                'show_hole_guide' => (bool) ($data['show_hole_guide'] ?? false),
                'background_printing' => (bool) ($data['background_printing'] ?? false),
            ];

            if ($profile) {
                $profile->update($payload);
                return $profile->fresh();
            }

            $payload['created_by_admin_id'] = $admin->id;

            return TagPrinterProfile::create($payload);
        });
    }

    /**
     * Permanently generate a sequential batch of unique QR laundry tags inside a DB transaction.
     */
    public function generateBatch(int $quantity, Admin $admin, array $options = [], ?string $idempotencyKey = null): LaundryTagBatch
    {
        if ($quantity < 1 || $quantity > self::MAX_GENERATION_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be between 1 and ' . self::MAX_GENERATION_QUANTITY . ' per batch.',
            ]);
        }

        $this->ensureDefaultPrinterProfiles($admin->id);

        return DB::transaction(function () use ($quantity, $admin, $options, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = LaundryTagBatch::where('idempotency_key', $idempotencyKey)->with('tags')->first();
                if ($existing) {
                    return $existing;
                }
            }

            $seqRow = DB::table('tag_sequences')->where('key', 'laundry_tag')->lockForUpdate()->first();
            if (! $seqRow) {
                $maxExisting = (int) (DB::table('laundry_tags')->max('id') ?? 0);
                DB::table('tag_sequences')->insert([
                    'key' => 'laundry_tag',
                    'last_number' => $maxExisting,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $lastNumber = $maxExisting;
            } else {
                $lastNumber = (int) $seqRow->last_number;
            }

            $profileId = ! empty($options['printer_profile_id']) ? (int) $options['printer_profile_id'] : null;
            $profile = $this->getActivePrinterProfile($profileId);

            $startSeq = $lastNumber + 1;
            $endSeq = $lastNumber + $quantity;
            $codeFrom = $this->qrService->formatTagCode($startSeq);
            $codeTo = $this->qrService->formatTagCode($endSeq);

            $batch = LaundryTagBatch::create([
                'batch_reference' => 'TB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'label' => ! empty($options['label']) ? trim((string) $options['label']) : null,
                'template' => (string) ($options['template'] ?? 'fresh_go_hanger_v1'),
                'printer_profile_id' => $profile->id,
                'quantity' => $quantity,
                'code_from' => $codeFrom,
                'code_to' => $codeTo,
                'idempotency_key' => $idempotencyKey ?: null,
                'generated_by_admin_id' => $admin->id,
                'generated_at' => now(),
            ]);

            $now = now();
            for ($seq = $startSeq; $seq <= $endSeq; $seq++) {
                $tagCode = $this->qrService->formatTagCode($seq);
                $sig = $this->qrService->signTagCode($tagCode);

                $tag = LaundryTag::create([
                    'tag_code' => $tagCode,
                    'qr_signature' => $sig,
                    'batch_id' => $batch->id,
                    'status' => LaundryTag::STATUS_AVAILABLE,
                    'print_status' => LaundryTag::PRINT_STATUS_UNPRINTED,
                    'print_count' => 0,
                    'created_by_admin_id' => $admin->id,
                    'is_reusable' => (bool) ($options['is_reusable'] ?? false),
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_GENERATED,
                    'previous_status' => null,
                    'new_status' => LaundryTag::STATUS_AVAILABLE,
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'actor_name' => $admin->name,
                    'reason' => "Generated in batch {$batch->batch_reference}",
                    'metadata' => [
                        'batch_id' => $batch->id,
                        'batch_reference' => $batch->batch_reference,
                        'printer_profile_id' => $profile->id,
                    ],
                    'occurred_at' => $now,
                ]);
            }

            DB::table('tag_sequences')
                ->where('key', 'laundry_tag')
                ->update(['last_number' => $endSeq, 'updated_at' => $now]);

            return $batch->load(['tags', 'printerProfile', 'generatedByAdmin']);
        });
    }

    /**
     * Prepare or record a print/reprint job for existing tags without creating new tags.
     *
     * @return array{print_job: LaundryTagPrintJob, warnings: array<int, string>}
     */
    public function recordPrintJob(
        array $tagIds,
        ?int $batchId,
        ?int $profileId,
        int $copies,
        int $startOffset,
        Admin $admin,
        string $stage = LaundryTagPrintJob::STATUS_PREPARED
    ): array {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        if (empty($uniqueIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => 'Select at least one valid laundry tag to print.',
            ]);
        }

        $profile = $this->getActivePrinterProfile($profileId);
        $copies = max(1, min(10, $copies));
        $startOffset = max(0, min(50, $startOffset));

        return DB::transaction(function () use ($uniqueIds, $batchId, $profile, $copies, $startOffset, $admin, $stage) {
            $tags = LaundryTag::whereIn('id', $uniqueIds)->orderBy('id')->lockForUpdate()->get();
            if ($tags->isEmpty()) {
                throw ValidationException::withMessages([
                    'tag_ids' => 'No matching tags found for printing.',
                ]);
            }

            $warnings = [];
            $isReprint = $tags->contains(fn (LaundryTag $t) => $t->print_count > 0);
            foreach ($tags as $tag) {
                if (in_array($tag->status, [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED, LaundryTag::STATUS_VOIDED], true)) {
                    $warnings[] = "{$tag->tag_code} is marked {$tag->status_label}.";
                } elseif (in_array($tag->status, [LaundryTag::STATUS_ALLOCATED, LaundryTag::STATUS_ASSIGNED, LaundryTag::STATUS_AT_STORE, LaundryTag::STATUS_IN_FINAL_DELIVERY], true)) {
                    $warnings[] = "{$tag->tag_code} is currently {$tag->status_label}. Reprinting should only be used to replace a worn label.";
                }
            }

            $now = now();
            $printJob = LaundryTagPrintJob::create([
                'print_reference' => 'TPJ-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
                'batch_id' => $batchId,
                'printer_profile_id' => $profile->id,
                'tag_ids' => $tags->pluck('id')->all(),
                'tag_count' => $tags->count(),
                'copies' => $copies,
                'start_offset' => $startOffset,
                'is_reprint' => $isReprint,
                'status' => $stage,
                'requested_by_admin_id' => $admin->id,
                'prepared_at' => $now,
                'requested_at' => in_array($stage, [LaundryTagPrintJob::STATUS_REQUESTED, LaundryTagPrintJob::STATUS_CONFIRMED], true) ? $now : null,
                'confirmed_at' => $stage === LaundryTagPrintJob::STATUS_CONFIRMED ? $now : null,
            ]);

            $targetPrintStatus = match ($stage) {
                LaundryTagPrintJob::STATUS_CONFIRMED => LaundryTag::PRINT_STATUS_PRINTED_CONFIRMED,
                LaundryTagPrintJob::STATUS_REQUESTED => LaundryTag::PRINT_STATUS_REQUESTED,
                default => LaundryTag::PRINT_STATUS_PREPARED,
            };

            $eventType = match ($stage) {
                LaundryTagPrintJob::STATUS_CONFIRMED => LaundryTagEvent::EVENT_PRINT_CONFIRMED,
                LaundryTagPrintJob::STATUS_REQUESTED => LaundryTagEvent::EVENT_PRINT_REQUESTED,
                default => LaundryTagEvent::EVENT_PRINT_PREPARED,
            };

            foreach ($tags as $tag) {
                $updates = [];
                if ($tag->print_status !== LaundryTag::PRINT_STATUS_PRINTED_CONFIRMED || $stage === LaundryTagPrintJob::STATUS_CONFIRMED) {
                    $updates['print_status'] = $targetPrintStatus;
                }
                if (in_array($stage, [LaundryTagPrintJob::STATUS_REQUESTED, LaundryTagPrintJob::STATUS_CONFIRMED], true)) {
                    $updates['print_count'] = ((int) $tag->print_count) + 1;
                    $updates['last_printed_at'] = $now;
                }
                if (! empty($updates)) {
                    $tag->update($updates);
                }

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => $eventType,
                    'previous_status' => $tag->status,
                    'new_status' => $tag->status,
                    'print_job_id' => $printJob->id,
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'actor_name' => $admin->name,
                    'reason' => ($isReprint ? 'Reprint ' : 'Print ') . "{$stage} ({$profile->name}, copies: {$copies})",
                    'metadata' => [
                        'print_reference' => $printJob->print_reference,
                        'printer_profile_id' => $profile->id,
                        'copies' => $copies,
                        'start_offset' => $startOffset,
                        'is_reprint' => $isReprint,
                    ],
                    'occurred_at' => $now,
                ]);
            }

            return [
                'print_job' => $printJob->load(['printerProfile', 'batch', 'requestedByAdmin']),
                'warnings' => $warnings,
            ];
        });
    }

    public function confirmPrintJob(LaundryTagPrintJob $printJob, Admin $admin): LaundryTagPrintJob
    {
        return DB::transaction(function () use ($printJob, $admin) {
            $locked = LaundryTagPrintJob::where('id', $printJob->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === LaundryTagPrintJob::STATUS_CONFIRMED) {
                return $locked;
            }

            $now = now();
            $locked->update([
                'status' => LaundryTagPrintJob::STATUS_CONFIRMED,
                'confirmed_at' => $now,
            ]);

            $tags = LaundryTag::whereIn('id', $locked->tag_ids ?? [])->lockForUpdate()->get();
            foreach ($tags as $tag) {
                $shouldIncrement = $locked->requested_at === null;
                $tag->update([
                    'print_status' => LaundryTag::PRINT_STATUS_PRINTED_CONFIRMED,
                    'print_count' => $shouldIncrement ? (((int) $tag->print_count) + 1) : $tag->print_count,
                    'last_printed_at' => $tag->last_printed_at ?? $now,
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_PRINT_CONFIRMED,
                    'previous_status' => $tag->status,
                    'new_status' => $tag->status,
                    'print_job_id' => $locked->id,
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'actor_name' => $admin->name,
                    'reason' => "Admin confirmed physical print ({$locked->print_reference})",
                    'occurred_at' => $now,
                ]);
            }

            return $locked->fresh(['printerProfile', 'batch']);
        });
    }

    /**
     * Allocate exact available laundry tags to an active Delivery Person.
     */
    public function allocateToDeliveryPerson(
        DeliveryPerson $deliveryPerson,
        int $quantity,
        Admin $admin,
        ?int $batchId = null,
        ?string $notes = null,
        ?array $specificTagIds = null
    ): LaundryTagAllocation {
        if (! $deliveryPerson->isEligibleForAssignment()) {
            throw ValidationException::withMessages([
                'delivery_person_id' => 'Selected Delivery Person is inactive or archived and cannot receive tag inventory.',
            ]);
        }

        if ($quantity < 1 && empty($specificTagIds)) {
            throw ValidationException::withMessages([
                'quantity' => 'Select a valid quantity (minimum 1 tag) or specific tags to allocate.',
            ]);
        }

        return DB::transaction(function () use ($deliveryPerson, $quantity, $admin, $batchId, $notes, $specificTagIds) {
            $query = LaundryTag::query()
                ->where('status', LaundryTag::STATUS_AVAILABLE)
                ->whereNull('current_delivery_person_id')
                ->whereNull('current_booking_id');

            if (! empty($specificTagIds)) {
                $ids = array_values(array_unique(array_filter(array_map('intval', $specificTagIds))));
                $tags = $query->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if ($tags->count() !== count($ids)) {
                    throw ValidationException::withMessages([
                        'tag_ids' => 'One or more selected tags are no longer available in Admin inventory.',
                    ]);
                }
            } else {
                if ($batchId) {
                    $query->where('batch_id', $batchId);
                }
                $tags = $query->orderBy('id')->limit($quantity)->lockForUpdate()->get();
                if ($tags->count() < $quantity) {
                    $availableCount = $tags->count();
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient available tags. Requested {$quantity}, only {$availableCount} available" . ($batchId ? ' in selected batch.' : '.'),
                    ]);
                }
            }

            $now = now();
            $allocation = LaundryTagAllocation::create([
                'allocation_reference' => 'TA-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $deliveryPerson->id,
                'quantity' => $tags->count(),
                'status' => 'completed',
                'notes' => $notes ? trim($notes) : null,
                'allocated_by_admin_id' => $admin->id,
                'allocated_at' => $now,
            ]);

            $allocation->tags()->attach($tags->pluck('id')->all());

            foreach ($tags as $tag) {
                $prevStatus = $tag->status;
                $tag->update([
                    'status' => LaundryTag::STATUS_ALLOCATED,
                    'current_delivery_person_id' => $deliveryPerson->id,
                    'allocated_at' => $now,
                    'retired_at' => null,
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_ALLOCATED_TO_DP,
                    'previous_status' => $prevStatus,
                    'new_status' => LaundryTag::STATUS_ALLOCATED,
                    'delivery_person_id' => $deliveryPerson->id,
                    'allocation_id' => $allocation->id,
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'actor_name' => $admin->name,
                    'reason' => "Allocated to {$deliveryPerson->full_name} ({$allocation->allocation_reference})" . ($notes ? " — {$notes}" : ''),
                    'metadata' => [
                        'allocation_reference' => $allocation->allocation_reference,
                    ],
                    'occurred_at' => $now,
                ]);
            }

            return $allocation->load(['deliveryPerson', 'tags', 'allocatedByAdmin']);
        });
    }

    /**
     * Mark a tag as Lost, Damaged, Voided, or Retired (preserving immutable audit trail).
     */
    public function transitionExceptionOrRetire(
        LaundryTag $tag,
        string $targetStatus,
        string $reason,
        string $actorType,
        ?int $actorId,
        string $actorName,
        bool $allowActiveBookingRelease = false
    ): LaundryTag {
        if (! in_array($targetStatus, LaundryTag::EXCEPTION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid target exception/retirement status.',
            ]);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to record this status change.',
            ]);
        }

        return DB::transaction(function () use ($tag, $targetStatus, $reason, $actorType, $actorId, $actorName, $allowActiveBookingRelease) {
            $locked = LaundryTag::where('id', $tag->id)->lockForUpdate()->firstOrFail();
            $activeAssignment = LaundryTagBookingAssignment::where('tag_id', $locked->id)
                ->where('active_lock', 1)
                ->lockForUpdate()
                ->first();

            if ($activeAssignment && ! $allowActiveBookingRelease && $targetStatus === LaundryTag::STATUS_VOIDED) {
                throw ValidationException::withMessages([
                    'status' => "Tag {$locked->tag_code} is actively attached to booking {$activeAssignment->booking?->reference}. Unassign or replace it first.",
                ]);
            }

            $now = now();
            $prevStatus = $locked->status;
            $bookingId = $locked->current_booking_id;
            $dpId = $locked->current_delivery_person_id;

            if ($activeAssignment) {
                $activeAssignment->update([
                    'active_lock' => null,
                    'status' => LaundryTagBookingAssignment::STATUS_CANCELLED,
                    'released_at' => $now,
                    'release_reason' => "Tag marked {$targetStatus}: {$reason}",
                ]);
            }

            $updates = [
                'status' => $targetStatus,
                'status_reason' => $reason,
                'current_booking_id' => null,
            ];

            if (in_array($targetStatus, [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED], true)) {
                $updates['lost_or_damaged_at'] = $now;
                $updates['current_delivery_person_id'] = null;
            } elseif ($targetStatus === LaundryTag::STATUS_RETIRED) {
                $updates['retired_at'] = $now;
                $updates['current_delivery_person_id'] = null;
            } elseif ($targetStatus === LaundryTag::STATUS_VOIDED) {
                $updates['current_delivery_person_id'] = null;
            }

            $locked->update($updates);

            $eventType = match ($targetStatus) {
                LaundryTag::STATUS_LOST => LaundryTagEvent::EVENT_REPORTED_LOST,
                LaundryTag::STATUS_DAMAGED => LaundryTagEvent::EVENT_REPORTED_DAMAGED,
                LaundryTag::STATUS_VOIDED => LaundryTagEvent::EVENT_VOIDED,
                default => LaundryTagEvent::EVENT_RETIRED,
            };

            LaundryTagEvent::create([
                'tag_id' => $locked->id,
                'event_type' => $eventType,
                'previous_status' => $prevStatus,
                'new_status' => $targetStatus,
                'booking_id' => $bookingId,
                'delivery_person_id' => $dpId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'reason' => $reason,
                'occurred_at' => $now,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Recover an undamaged allocated/retired tag back into Available Admin Inventory.
     * Lost, Damaged, and Voided tags can NEVER be recycled back to available stock.
     */
    public function returnTagToAvailableStock(LaundryTag $tag, Admin $admin, string $reason): LaundryTag
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Please provide a reason for returning this tag to inventory.',
            ]);
        }

        return DB::transaction(function () use ($tag, $admin, $reason) {
            $locked = LaundryTag::where('id', $tag->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED, LaundryTag::STATUS_VOIDED], true)) {
                throw ValidationException::withMessages([
                    'status' => "Tags marked {$locked->status_label} cannot be recycled into active stock.",
                ]);
            }

            $activeAssignment = LaundryTagBookingAssignment::where('tag_id', $locked->id)
                ->where('active_lock', 1)
                ->lockForUpdate()
                ->first();

            if ($activeAssignment) {
                $booking = $activeAssignment->booking;
                if ($booking && ! in_array($booking->booking_status, [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED], true)) {
                    throw ValidationException::withMessages([
                        'status' => "Tag {$locked->tag_code} is attached to ongoing booking {$booking->reference}. Unassign or wait for completion first.",
                    ]);
                }

                $activeAssignment->update([
                    'active_lock' => null,
                    'status' => $booking && $booking->booking_status === Booking::STATUS_COMPLETED
                        ? LaundryTagBookingAssignment::STATUS_COMPLETED
                        : LaundryTagBookingAssignment::STATUS_UNASSIGNED,
                    'released_at' => now(),
                    'release_reason' => "Returned to admin stock: {$reason}",
                ]);
            }

            $prevStatus = $locked->status;
            $dpId = $locked->current_delivery_person_id;
            $bookingId = $locked->current_booking_id;

            $locked->update([
                'status' => LaundryTag::STATUS_AVAILABLE,
                'current_delivery_person_id' => null,
                'current_booking_id' => null,
                'allocated_at' => null,
                'assigned_at' => null,
                'at_store_at' => null,
                'status_reason' => $reason,
            ]);

            LaundryTagEvent::create([
                'tag_id' => $locked->id,
                'event_type' => LaundryTagEvent::EVENT_RETURNED_TO_STOCK,
                'previous_status' => $prevStatus,
                'new_status' => LaundryTag::STATUS_AVAILABLE,
                'booking_id' => $bookingId,
                'delivery_person_id' => $dpId,
                'actor_type' => 'admin',
                'actor_id' => $admin->id,
                'actor_name' => $admin->name,
                'reason' => $reason,
                'occurred_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Inventory summary counts for Admin cards.
     *
     * @return array<string, int>
     */
    public function getInventorySummary(): array
    {
        $counts = LaundryTag::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (int) $counts->sum();
        $available = (int) ($counts[LaundryTag::STATUS_AVAILABLE] ?? 0);
        $allocated = (int) ($counts[LaundryTag::STATUS_ALLOCATED] ?? 0);
        $inUse = (int) ($counts[LaundryTag::STATUS_ASSIGNED] ?? 0)
            + (int) ($counts[LaundryTag::STATUS_AT_STORE] ?? 0)
            + (int) ($counts[LaundryTag::STATUS_IN_FINAL_DELIVERY] ?? 0);
        $retired = (int) ($counts[LaundryTag::STATUS_RETIRED] ?? 0);
        $lostDamaged = (int) ($counts[LaundryTag::STATUS_LOST] ?? 0)
            + (int) ($counts[LaundryTag::STATUS_DAMAGED] ?? 0)
            + (int) ($counts[LaundryTag::STATUS_VOIDED] ?? 0);

        return [
            'total' => $total,
            'available' => $available,
            'allocated' => $allocated,
            'in_use' => $inUse,
            'retired' => $retired,
            'lost_damaged' => $lostDamaged,
        ];
    }

    public function buildFilteredTagsQuery(array $filters): Builder
    {
        $query = LaundryTag::query()
            ->with(['batch', 'currentDeliveryPerson', 'currentBooking']);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $upper = strtoupper($search);
            $query->where(function (Builder $q) use ($upper, $search) {
                $q->where('tag_code', 'like', '%' . $upper . '%')
                    ->orWhereHas('currentBooking', fn (Builder $bq) => $bq->where('reference', 'like', '%' . $search . '%'))
                    ->orWhereHas('currentDeliveryPerson', fn (Builder $dq) => $dq->where('full_name', 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%'));
            });
        }

        if ($status = trim((string) ($filters['status'] ?? ''))) {
            if ($status === 'in_use') {
                $query->whereIn('status', LaundryTag::ACTIVE_BOOKING_STATUSES);
            } elseif ($status === 'lost_damaged') {
                $query->whereIn('status', [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED, LaundryTag::STATUS_VOIDED]);
            } else {
                $query->where('status', $status);
            }
        }

        if ($batchId = (int) ($filters['batch_id'] ?? 0)) {
            $query->where('batch_id', $batchId);
        }

        if ($dpId = (int) ($filters['delivery_person_id'] ?? 0)) {
            $query->where('current_delivery_person_id', $dpId);
        }

        if ($printStatus = trim((string) ($filters['print_status'] ?? ''))) {
            $query->where('print_status', $printStatus);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'code_asc' => $query->orderBy('tag_code'),
            'code_desc' => $query->orderByDesc('tag_code'),
            'oldest' => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        return $query;
    }

    public function buildFilteredEventsQuery(array $filters): Builder
    {
        $query = LaundryTagEvent::query()
            ->with(['tag', 'booking', 'deliveryPerson', 'store', 'allocation', 'printJob']);

        if ($tagCode = trim((string) ($filters['tag_code'] ?? ''))) {
            $query->whereHas('tag', fn (Builder $q) => $q->where('tag_code', 'like', '%' . strtoupper($tagCode) . '%'));
        }

        if ($bookingRef = trim((string) ($filters['booking_reference'] ?? ''))) {
            $query->whereHas('booking', fn (Builder $q) => $q->where('reference', 'like', '%' . $bookingRef . '%'));
        }

        if ($dpId = (int) ($filters['delivery_person_id'] ?? 0)) {
            $query->where('delivery_person_id', $dpId);
        }

        if ($storeId = (int) ($filters['store_id'] ?? 0)) {
            $query->where('store_id', $storeId);
        }

        if ($eventType = trim((string) ($filters['event_type'] ?? ''))) {
            $query->where('event_type', $eventType);
        }

        if (! empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query->orderByDesc('occurred_at')->orderByDesc('id');
    }
}

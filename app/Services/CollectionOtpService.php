<?php

namespace App\Services;

use App\Mail\CollectionOtpMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCollectionOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class CollectionOtpService
{
    public const EXPIRY_MINUTES = 5;

    public function sendOtp(Booking $booking, DeliveryPerson $person): BookingCollectionOtp
    {
        $recipient = trim((string) ($booking->customer_email ?: $booking->user?->email ?: ''));
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Customer has no verified email address on this booking. Request an admin exception.');
        }

        $sendKey = "dp-otp-send:{$booking->id}:{$person->id}";
        if (RateLimiter::tooManyAttempts($sendKey, 3)) {
            $seconds = RateLimiter::availableIn($sendKey);
            throw new RuntimeException("Too many OTP requests. Please wait {$seconds} seconds before resending.");
        }
        RateLimiter::hit($sendKey, 300);

        $code = (string) random_int(100000, 999999);

        $otpRecord = DB::transaction(function () use ($booking, $person, $recipient, $code) {
            BookingCollectionOtp::query()
                ->where('booking_id', $booking->id)
                ->where('purpose', BookingCollectionOtp::PURPOSE_COLLECTION_CONFIRMATION)
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            return BookingCollectionOtp::query()->create([
                'booking_id' => $booking->id,
                'delivery_person_id' => $person->id,
                'purpose' => BookingCollectionOtp::PURPOSE_COLLECTION_CONFIRMATION,
                'channel' => 'email',
                'recipient' => $recipient,
                'otp_hash' => Hash::make($code),
                'attempts' => 0,
                'max_attempts' => 5,
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            ]);
        });

        Mail::to($recipient)->send(new CollectionOtpMail($booking, $code, self::EXPIRY_MINUTES));

        BookingAuditLog::create([
            'booking_id' => $booking->id,
            'actor_type' => 'delivery_person',
            'actor_id' => $person->id,
            'actor_name' => $person->full_name,
            'action' => 'collection_otp_sent',
            'summary' => "Collection OTP sent to customer email by Delivery Person {$person->full_name} ({$person->code}).",
            'changes' => [
                'delivery_person_id' => $person->id,
                'channel' => 'email',
                'expires_at' => $otpRecord->expires_at->toIso8601String(),
            ],
        ]);

        return $otpRecord;
    }

    public function verifyOtp(Booking $booking, DeliveryPerson $person, string $rawCode): BookingCollectionOtp
    {
        $code = preg_replace('/\D+/', '', trim($rawCode)) ?? '';
        if (strlen($code) !== 6) {
            throw new RuntimeException('Please enter the 6-digit verification code.');
        }

        $verifyKey = "dp-otp-verify:{$booking->id}:{$person->id}";
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            throw new RuntimeException("Too many invalid OTP attempts. Try again in {$seconds} seconds.");
        }

        return DB::transaction(function () use ($booking, $person, $code, $verifyKey) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->collection_otp_verified_at !== null) {
                throw new RuntimeException('Collection OTP has already been verified for this booking.');
            }

            /** @var BookingCollectionOtp|null $otp */
            $otp = BookingCollectionOtp::query()
                ->where('booking_id', $lockedBooking->id)
                ->where('purpose', BookingCollectionOtp::PURPOSE_COLLECTION_CONFIRMATION)
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                RateLimiter::hit($verifyKey, 300);
                throw new RuntimeException('No active OTP found. Please send a new collection OTP.');
            }

            if ($otp->expires_at->isPast()) {
                $otp->update(['invalidated_at' => now()]);
                RateLimiter::hit($verifyKey, 300);
                throw new RuntimeException('Collection OTP has expired. Please request a new code.');
            }

            if ($otp->attempts >= $otp->max_attempts) {
                $otp->update(['invalidated_at' => now()]);
                throw new RuntimeException('Maximum OTP attempts exceeded. Please request a new code.');
            }

            if (! Hash::check($code, $otp->otp_hash)) {
                $otp->increment('attempts');
                RateLimiter::hit($verifyKey, 300);
                throw new RuntimeException('Invalid collection OTP code.');
            }

            RateLimiter::clear($verifyKey);

            $now = now();
            $otp->update([
                'verified_at' => $now,
                'verified_by_delivery_person_id' => $person->id,
            ]);

            $lockedBooking->forceFill([
                'collection_otp_verified_at' => $now,
                'collection_otp_verified_by_dp_id' => $person->id,
            ])->save();

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'collection_otp_verified',
                'summary' => "Collection OTP verified by Delivery Person {$person->full_name} ({$person->code}).",
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'verified_at' => $now->toIso8601String(),
                ],
            ]);

            return $otp;
        });
    }
}

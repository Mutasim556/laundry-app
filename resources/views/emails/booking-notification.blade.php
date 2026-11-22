<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #FAFAF7; color: #171717; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 16px; border: 1.5px solid #FFC629; padding: 28px;">
        <h2 style="margin: 0 0 8px; color: #171717;">{{ $title }}</h2>
        <p style="margin: 0 0 16px; color: #555555; font-size: 14px;">
            Booking Reference: <strong>{{ $booking->reference }}</strong>
        </p>
        <p style="margin: 0 0 18px; color: #171717; font-size: 14px; line-height: 1.5;">
            {{ $messageBody }}
        </p>
        <div style="background: #FFF8E5; border-radius: 12px; padding: 14px; font-size: 13px;">
            <div><strong>Order Status:</strong> {{ ucwords(str_replace('_', ' ', (string) $booking->booking_status)) }}</div>
            <div><strong>Payment Status:</strong> {{ ucwords(str_replace('_', ' ', (string) $booking->payment_status)) }}</div>
            @if ($booking->final_amount_minor !== null)
                <div><strong>Final Amount:</strong> {{ formatMoney($booking->final_amount_minor / 100, $booking->currency) }}</div>
            @endif
        </div>
    </div>
</body>
</html>

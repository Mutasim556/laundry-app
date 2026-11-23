<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Collection Confirmation Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #171717; background-color: #F8FAFC; padding: 24px;">
    <div style="max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; padding: 24px; border: 1px solid #E2E8F0;">
        <h2 style="margin-top: 0; color: #171717;">FreshGo Laundry &amp; Dry Cleaning</h2>
        <p>Hello {{ $booking->customer_name }},</p>
        <p>Please provide this 6-digit verification code to your Delivery Person to confirm collection for booking <strong>{{ $booking->reference }}</strong>:</p>
        <div style="background: #FFF8E5; border: 2px dashed #FFC629; border-radius: 12px; text-align: center; padding: 16px; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #171717;">{{ $otpCode }}</span>
        </div>
        <p style="font-size: 13px; color: #475569;">This code expires in {{ $expiresInMinutes }} minutes and can only be used once.</p>
    </div>
</body>
</html>

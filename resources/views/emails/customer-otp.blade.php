<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FreshGo Laundry Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background: #FAFAF7; color: #171717; padding: 24px;">
    <div style="max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 16px; border: 1.5px solid #FFC629; padding: 28px;">
        <h2 style="margin: 0 0 10px; color: #171717;">Verify Your Email</h2>
        <p style="margin: 0 0 18px; color: #555555; font-size: 14px;">
            Use the 6-digit verification code below to sign in to your FreshGo Laundry account. This code expires in {{ $expiresInMinutes }} minutes.
        </p>
        <div style="background: #FFF8E5; border: 1.5px solid #FFC629; border-radius: 12px; padding: 16px; text-align: center; margin-bottom: 18px;">
            <span style="font-size: 30px; font-weight: 800; letter-spacing: 8px; color: #171717;">{{ $otpCode }}</span>
        </div>
        <p style="margin: 0; color: #777777; font-size: 12px;">
            If you did not request this code, you can safely ignore this email.
        </p>
    </div>
</body>
</html>

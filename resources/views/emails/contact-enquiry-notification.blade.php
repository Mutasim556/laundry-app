<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #171717; line-height: 1.5;">
    <h2 style="margin-bottom: 8px;">New Customer Enquiry ({{ $enquiry->reference }})</h2>
    <p><strong>From:</strong> {{ $enquiry->name }} ({{ $enquiry->email }})</p>
    @if($enquiry->phone)
        <p><strong>Phone:</strong> {{ $enquiry->phone }}</p>
    @endif
    @if($enquiry->booking_reference)
        <p><strong>Booking Reference:</strong> {{ $enquiry->booking_reference }}</p>
    @endif
    <p><strong>Subject:</strong> {{ $enquiry->subject }}</p>
    <hr>
    <p style="white-space: pre-line;">{{ $enquiry->message }}</p>
    <hr>
    <p style="font-size: 12px; color: #64748B;">Submitted at {{ $enquiry->submitted_at?->format('d M Y, H:i') }}</p>
</body>
</html>

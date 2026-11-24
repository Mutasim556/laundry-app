<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }} - {{ $booking->reference }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #FFFFFF;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @include('admin::bookings.invoices._print_styles')

        @if($format === 'thermal')
            @page {
                size: {{ $paperSize === '58mm' ? '58mm' : '80mm' }} auto;
                margin: 0;
            }
            body {
                padding: 2mm 0;
            }
        @else
            @page {
                size: A4 portrait;
                margin: 12mm 14mm;
            }
        @endif
    </style>
</head>
<body>
    @if($format === 'a4')
        @include('admin::bookings.invoices._a4')
    @else
        @include('admin::bookings.invoices._thermal')
    @endif

    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.focus();
                    window.print();
                }, 150);
            });
        </script>
    @endif
</body>
</html>

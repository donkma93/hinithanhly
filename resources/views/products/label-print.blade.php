<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In Mã vạch</title>
    <style>
        @page {
            size: auto;
            margin: 4mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6mm;
            padding: 4mm;
        }

        .barcode {
            width: 80mm;
            height: 34mm;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-inside: avoid;
            break-inside: avoid;
            margin: 0 auto;
        }

        .barcode-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .barcode-top,
        .barcode-bottom {
            font-size: 10pt;
            line-height: 1;
            color: #000;
            text-align: center;
            margin: 0 0 2mm 0;
            white-space: nowrap;
        }

        .barcode-bottom {
            margin-top: 2mm;
        }

        .barcode svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        @foreach ($products as $product)
            <div class="barcode-block">
                <div class="barcode-top">{{ $product->id }} - {{ $product->supplier_id }} - {{ $product->send_round }}</div>
                <div class="barcode">{!! $product->barcode_svg !!}</div>
                <div class="barcode-bottom">{{ number_format($product->sale_price ?? 0, 0, ',', '.') }} đ</div>
            </div>
        @endforeach
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
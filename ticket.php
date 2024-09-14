<?php
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

// A számlázási adatok
$userid = 12345;
$invoiceNumber = bin2hex(random_bytes(8));
$date = date('Y-m-d');
$dueDate = date('Y-m-d', strtotime('+14 days'));
$clientName = 'Teszt Ügyfél';
$clientTel = '123456789';
$clientCity = 'Budapest';
$clientAddress = 'Fő utca 1.';
$clientEmail = 'teszt@ugyfel.hu';

// Képek base64 kódolása
$logoPath = __DIR__ . '/assets/img/brand/logo.png';
$logoData = base64_encode(file_get_contents($logoPath));
$logoSrc = 'data:image/png;base64,' . $logoData;

$partnerLogoPath = __DIR__ . '/assets/img/logo.png';
$partnerLogoData = base64_encode(file_get_contents($partnerLogoPath));
$partnerLogoSrc = 'data:image/png;base64,' . $partnerLogoData;

// Számla HTML tartalom táblázatos elrendezéssel
$invoiceHtml = "
<!doctype html>
<html lang='hu'>
<head>
    <meta charset='utf-8'>
    <title>Számla</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 100%; max-width: 800px; margin: auto; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table, .table th, .table td { border: 1px solid black; }
        .table th, .table td { padding: 8px; text-align: center; }
        .hr { border-top: 1px solid black; margin-top: 20px; margin-bottom: 20px; }
        .img-fluid { max-width: 100%; height: auto; }
        .row { width: 100%; display: table; }
        .col-6 { display: table-cell; width: 50%; vertical-align: middle; }
        .col-12 { width: 100%; }
        .align-center { vertical-align: middle; }
        .mb-0 { margin-bottom: 0; }
        .mt-2 { margin-top: 20px; }
        .me-2 { margin-right: 8px; }
        .d-flex { display: flex; }
        .justify-content-center { justify-content: center; }
        .align-items-center { align-items: center; }
        .blue{color:#0950dc;}
    </style>
</head>

<body>
    <div class='container mt-2'>
        <!-- Fejléc -->
        <table class='row text-center'>
            <tr>
                <td class='col-6 align-center'>
                    <img src='$logoSrc' class='img-fluid' alt='Logo'>
                </td>
                <td class='col-6 align-center'>
                    <h1 class='blue'>SZÁMLA</h1>
                </td>
            </tr>
        </table>
        <hr class='hr' />
        <!-- Cég és számla adatok -->
        <table class='row text-left'>
            <tr>
                <td class='col-6 align-center'>
                    <h4 class='blue'>TEST GYM</h4>
                    <small>sajt@echo.php</small>
                </td>
                <td class='col-6 align-center text-right'>
                    <p><b class='blue'>Dátum:</b> $date</p>
                    <p><b class='blue'>Számla száma:</b> $invoiceNumber</p>
                    <p><b class='blue'>Vevőazonosító:</b> $userid</p>
                </td>
            </tr>
        </table>
        <hr class='hr' />
        <!-- Vevő adatai -->
        <div class='text-left'>
            <p><strong>Címzett:</strong></p>
            <p>&emsp;<strong>$clientName</strong></p>
            <p>&emsp;$clientTel</p>
            <p>&emsp;$clientCity</p>
            <p>&emsp;$clientAddress</p>
            <p>&emsp;$clientEmail</p>
        </div>
        <hr class='hr' />
        <!-- Kiállítás adatai -->
        <table class='table'>
            <thead>
                <tr>
                    <th>Kiállította</th>
                    <th>Fizetési mód</th>
                    <th>Dátum</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mark</td>
                    <td>Készpénz</td>
                    <td>$date</td>
                </tr>
            </tbody>
        </table>
        <hr class='hr' />
        <!-- Számla tételei -->
        <table class='table'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Leírás</th>
                    <th>Összeg</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>333</td>
                    <td>Szolgáltatás</td>
                    <td>1000 HUF</td>
                </tr>
                <tr>
                    <td colspan='2' class='text-right'><strong>Összesen:</strong></td>
                    <td><strong>1000 HUF</strong></td>
                </tr>
            </tbody>
        </table>
        <!-- Lábléc -->
        <table class='row' style='margin-top: 20px;'>
            <tr>
                <td class='col-6 align-center'>
                    <img src='$partnerLogoSrc' width='100' class='img-fluid' alt='Partner Logo'>
                </td>
                <td class='col-6 align-center text-left'>
                    <p class='mb-0'>Partner - © 2024 GYM One</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
";

// PDF generálása és mentése
$mpdf = new Mpdf();
$mpdf->WriteHTML($invoiceHtml);

$invoicePath = __DIR__ . "/assets/docs/invoices/{$userid}-{$invoiceNumber}.pdf";
$mpdf->Output($invoicePath, \Mpdf\Output\Destination::FILE);

echo "A számla sikeresen létrehozva: $invoicePath";

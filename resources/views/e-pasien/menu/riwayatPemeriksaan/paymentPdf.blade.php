<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Nota Pembayaran RS ARSY</title>
    <style>
        @page {
            margin: 16mm 14mm 20mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #253247;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.4pt;
            line-height: 1.42;
            margin: 0;
        }

        .page-accent {
            border-collapse: collapse;
            height: 4mm;
            left: -14mm;
            position: fixed;
            top: -16mm;
            width: 210mm;
        }

        .page-accent td {
            padding: 0;
        }

        .accent-red {
            background: #df241c;
        }

        .accent-purple {
            background: #2c2172;
        }

        .accent-green {
            background: #079646;
        }

        .document-header,
        .statement-band,
        .data-grid,
        .information-grid,
        .billing-table,
        .summary-layout,
        .totals-table,
        .digital-proof,
        .page-footer {
            border-collapse: collapse;
            width: 100%;
        }

        .document-header {
            border-bottom: 1.5pt solid #2c2172;
            margin-bottom: 5mm;
        }

        .document-header td {
            padding: 0 0 5mm;
            vertical-align: middle;
        }

        .brand-cell {
            width: 56%;
        }

        .brand-logo {
            display: inline-block;
            height: 18mm;
            margin-right: 4mm;
            object-fit: contain;
            vertical-align: middle;
            width: 18mm;
        }

        .brand-copy {
            display: inline-block;
            vertical-align: middle;
        }

        .brand-copy small,
        .document-copy small,
        .field-label,
        .section-kicker,
        .print-meta small {
            color: #778197;
            display: block;
            font-size: 6.7pt;
            font-weight: bold;
            letter-spacing: .6pt;
            text-transform: uppercase;
        }

        .brand-copy strong {
            color: #2c2172;
            display: block;
            font-size: 17pt;
            line-height: 1.1;
        }

        .brand-copy span {
            color: #596579;
            display: block;
            font-size: 7.5pt;
            margin-top: 1.2mm;
        }

        .document-copy {
            text-align: right;
            width: 44%;
        }

        .document-copy small {
            color: #079646;
        }

        .document-copy h1 {
            color: #202a3b;
            font-size: 16pt;
            line-height: 1.2;
            margin: 1mm 0 0;
        }

        .document-copy p {
            color: #7c8697;
            font-size: 7.4pt;
            margin: 1.2mm 0 0;
        }

        .statement-band {
            background: #2c2172;
            color: #ffffff;
            margin-bottom: 5mm;
        }

        .statement-band td {
            border-right: .5pt solid #564e91;
            padding: 3.2mm 3.6mm;
            vertical-align: top;
        }

        .statement-band td:last-child {
            border-right: 0;
        }

        .statement-band small {
            color: #c9c5e1;
            display: block;
            font-size: 6.3pt;
            font-weight: bold;
            letter-spacing: .4pt;
            margin-bottom: .8mm;
            text-transform: uppercase;
        }

        .statement-band strong {
            display: block;
            font-size: 8.4pt;
            overflow-wrap: anywhere;
        }

        .status-badge {
            background: #eaf7ef;
            border: .6pt solid #b7dec8;
            color: #08743b;
            display: inline-block;
            font-size: 6.8pt;
            font-weight: bold;
            padding: 1.1mm 2.2mm;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fff5df;
            border-color: #efd49a;
            color: #8d6214;
        }

        .section-heading {
            border-left: 3pt solid #df241c;
            margin: 0 0 2.5mm;
            padding-left: 2.5mm;
            page-break-after: avoid;
        }

        .section-heading.with-green {
            border-left-color: #079646;
        }

        .section-kicker {
            color: #7c8697;
            margin-bottom: .3mm;
        }

        .section-heading h2 {
            color: #202a3b;
            font-size: 10.5pt;
            line-height: 1.2;
            margin: 0;
        }

        .section-block {
            margin-bottom: 5mm;
        }

        .data-grid,
        .information-grid {
            table-layout: fixed;
        }

        .data-grid td,
        .information-grid td {
            border: .65pt solid #dde2ea;
            padding: 3mm;
            vertical-align: top;
            width: 50%;
        }

        .data-grid strong,
        .information-grid strong {
            color: #222e42;
            display: block;
            font-size: 8.5pt;
            line-height: 1.35;
            margin-top: .8mm;
            overflow-wrap: anywhere;
        }

        .data-grid p {
            color: #737e91;
            font-size: 7.1pt;
            margin: .8mm 0 0;
            overflow-wrap: anywhere;
        }

        .information-grid td {
            background: #f8fafc;
        }

        .billing-table {
            border: .65pt solid #d8dee7;
            table-layout: fixed;
        }

        .billing-table thead {
            display: table-header-group;
        }

        .billing-table th {
            background: #202a3b;
            color: #ffffff;
            font-size: 6.7pt;
            letter-spacing: .25pt;
            padding: 2.5mm 2.2mm;
            text-align: left;
            text-transform: uppercase;
        }

        .billing-table td {
            border-bottom: .45pt solid #e2e6ed;
            color: #3b475a;
            font-size: 7.4pt;
            padding: 2.2mm;
            vertical-align: top;
        }

        .billing-table tr {
            page-break-inside: avoid;
        }

        .billing-table tr:last-child td {
            border-bottom: 0;
        }

        .billing-table .description {
            color: #222e42;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .billing-table .category {
            color: #59677a;
            font-size: 6.8pt;
            text-transform: uppercase;
        }

        .billing-table .calculation {
            color: #657084;
            white-space: nowrap;
        }

        .billing-table .amount {
            color: #222e42;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        .billing-table .negative {
            color: #b23e38;
        }

        .billing-table .group-row td {
            background: #eeedf7;
            border-bottom-color: #d4d1e9;
            color: #2c2172;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: .25pt;
            padding-bottom: 2mm;
            padding-top: 2mm;
            text-transform: uppercase;
        }

        .billing-table .subtotal-row td {
            background: #f1f8f4;
            color: #17633d;
            font-weight: bold;
        }

        .billing-table .note-row td {
            background: #fafbfc;
            color: #657084;
            font-style: italic;
        }

        .summary-layout {
            margin-top: 5mm;
            page-break-inside: avoid;
        }

        .summary-layout > tbody > tr > td {
            vertical-align: top;
        }

        .summary-note-cell {
            padding-right: 5mm;
            width: 54%;
        }

        .summary-note {
            background: #f8fafc;
            border-left: 3pt solid #079646;
            color: #6d788a;
            font-size: 7.1pt;
            line-height: 1.55;
            padding: 3.2mm;
        }

        .summary-note strong {
            color: #243044;
            display: block;
            font-size: 8.1pt;
            margin-bottom: 1mm;
        }

        .totals-cell {
            width: 46%;
        }

        .totals-table {
            border: .65pt solid #d9dfe8;
        }

        .totals-table td {
            border-bottom: .45pt solid #e4e8ee;
            font-size: 7.5pt;
            padding: 2.2mm 2.5mm;
        }

        .totals-table td:last-child {
            color: #222e42;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        .totals-table .deduction td:last-child {
            color: #b23e38;
        }

        .totals-table .grand-total td {
            background: #2c2172;
            border: 0;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            padding-bottom: 3mm;
            padding-top: 3mm;
        }

        .totals-table .grand-total td:last-child {
            color: #ffffff;
            font-size: 11pt;
        }

        .digital-proof {
            border: .65pt solid #dce2ea;
            margin-top: 6mm;
            page-break-inside: avoid;
        }

        .digital-proof td {
            padding: 3mm;
            vertical-align: middle;
        }

        .proof-mark {
            background: #eaf7ef;
            color: #08743b;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            width: 12mm;
        }

        .proof-copy strong {
            color: #243044;
            display: block;
            font-size: 8pt;
        }

        .proof-copy p {
            color: #737e91;
            font-size: 6.9pt;
            margin: .6mm 0 0;
        }

        .print-meta {
            border-left: .45pt solid #e0e5ec;
            text-align: right;
            width: 45mm;
        }

        .print-meta strong {
            color: #344054;
            display: block;
            font-size: 7pt;
            margin-top: .7mm;
        }

        .page-footer {
            border-top: .55pt solid #d9dfe7;
            bottom: -13mm;
            color: #7c8696;
            font-size: 6.5pt;
            left: 0;
            position: fixed;
        }

        .page-footer td {
            padding-top: 2.3mm;
            vertical-align: top;
        }

        .page-footer strong {
            color: #2c2172;
        }

        .page-footer .footer-right {
            padding-right: 26mm;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rows = collect($payment['rows'] ?? []);
        $informationRows = $rows->where('type', 'information')->values();
        $statementRows = $rows->where('type', '!=', 'information')->values();
        $summary = $payment['summary'] ?? [];
        $statusText = trim((string) ($payment['status_bayar'] ?? '-')) ?: '-';
        $isPaid = str_contains(mb_strtolower($statusText), 'sudah');
        $money = static function ($value): string {
            $amount = (float) $value;
            $formatted = number_format(abs($amount), $amount == floor($amount) ? 0 : 2, ',', '.');

            return ($amount < 0 ? '- ' : '').'Rp'.$formatted;
        };
        $number = static function ($value): string {
            $amount = (float) $value;

            return number_format($amount, $amount == floor($amount) ? 0 : 2, ',', '.');
        };
        $rowDescription = static function (array $row): string {
            return collect([$row['label'] ?? '', $row['description'] ?? ''])
                ->map(static fn ($value): string => trim((string) $value))
                ->filter()
                ->unique()
                ->implode(' - ') ?: '-';
        };
    @endphp

    <table class="page-accent" aria-hidden="true">
        <tr>
            <td class="accent-red" style="width: 30%"></td>
            <td class="accent-purple" style="width: 44%"></td>
            <td class="accent-green" style="width: 26%"></td>
        </tr>
    </table>

    <table class="page-footer">
        <tr>
            <td><strong>RS ARSY</strong> &nbsp;|&nbsp; E-Pasien - Nota pembayaran pasien</td>
            <td class="footer-right">Dokumen bersifat pribadi dan rahasia</td>
        </tr>
    </table>

    <table class="document-header">
        <tr>
            <td class="brand-cell">
                @if ($logoDataUri)
                    <img class="brand-logo" src="{{ $logoDataUri }}" alt="Logo RS ARSY">
                @endif
                <span class="brand-copy">
                    <small>Rumah Sakit</small>
                    <strong>RS ARSY</strong>
                    <span>Portal layanan pasien digital</span>
                </span>
            </td>
            <td class="document-copy">
                <small>Nota resmi</small>
                <h1>Bukti Pembayaran</h1>
                <p>Patient financial statement</p>
            </td>
        </tr>
    </table>

    <table class="statement-band">
        <tr>
            <td style="width: 32%">
                <small>Nomor nota</small>
                <strong>{{ $payment['nomor_nota'] ?: '-' }}</strong>
            </td>
            <td style="width: 25%">
                <small>Tanggal pembayaran</small>
                <strong>{{ $payment['tanggal_bayar_lengkap'] ?? '-' }}</strong>
            </td>
            <td style="width: 25%">
                <small>Nomor rawat</small>
                <strong>{{ $payment['no_rawat'] ?: '-' }}</strong>
            </td>
            <td style="width: 18%">
                <small>Status</small>
                <span class="status-badge{{ $isPaid ? '' : ' pending' }}">{{ $statusText }}</span>
            </td>
        </tr>
    </table>

    <section class="section-block">
        <div class="section-heading">
            <span class="section-kicker">Informasi kunjungan</span>
            <h2>Data pasien dan layanan</h2>
        </div>

        <table class="data-grid">
            <tr>
                <td>
                    <span class="field-label">Pasien</span>
                    <strong>{{ $payment['pasien'] ?? '-' }}</strong>
                    <p>No. Rekam Medis: {{ $payment['no_rkm_medis'] ?? '-' }}</p>
                </td>
                <td>
                    <span class="field-label">Layanan</span>
                    <strong>{{ $payment['jenis_layanan'] ?? '-' }}</strong>
                    <p>{{ $payment['poli'] ?? '-' }}</p>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">Dokter penanggung jawab</span>
                    <strong>{{ $payment['dokter'] ?? '-' }}</strong>
                    <p>Jam registrasi: {{ $payment['jam_registrasi'] ?? '-' }}</p>
                </td>
                <td>
                    <span class="field-label">Penjamin</span>
                    <strong>{{ $payment['penjamin'] ?? '-' }}</strong>
                    <p>Status layanan: {{ $payment['status_lanjut'] ?? '-' }}</p>
                </td>
            </tr>
        </table>
    </section>

    @if ($informationRows->isNotEmpty())
        <section class="section-block">
            <div class="section-heading with-green">
                <span class="section-kicker">Informasi transaksi</span>
                <h2>Detail pada nota</h2>
            </div>

            <table class="information-grid">
                @foreach ($informationRows->chunk(2) as $chunk)
                    <tr>
                        @foreach ($chunk as $row)
                            <td>
                                <span class="field-label">{{ $row['label'] ?? '-' }}</span>
                                <strong>{{ $row['description'] ?: ($row['nm_perawatan'] ?? '-') }}</strong>
                            </td>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </section>
    @endif

    <section class="section-block">
        <div class="section-heading">
            <span class="section-kicker">Billing statement</span>
            <h2>Rincian biaya - {{ (int) ($summary['jumlah_item'] ?? 0) }} rincian</h2>
        </div>

        <table class="billing-table">
            <thead>
                <tr>
                    <th style="width: 40%">Uraian</th>
                    <th style="width: 16%">Kategori</th>
                    <th style="width: 25%">Perhitungan</th>
                    <th style="width: 19%; text-align: right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($statementRows as $row)
                    @php
                        $type = $row['type'] ?? 'note';
                        $total = (float) ($row['totalbiaya'] ?? 0);
                        $price = (float) ($row['biaya'] ?? 0);
                        $quantity = (float) ($row['jumlah'] ?? 0);
                        $additional = (float) ($row['tambahan'] ?? 0);
                        $description = $rowDescription($row);
                    @endphp

                    @if ($type === 'heading')
                        <tr class="group-row">
                            <td colspan="4">{{ $description }}</td>
                        </tr>
                    @elseif ($type === 'subtotal')
                        <tr class="subtotal-row">
                            <td colspan="3">{{ $description }}</td>
                            <td class="amount{{ $total < 0 ? ' negative' : '' }}">{{ $money($total) }}</td>
                        </tr>
                    @elseif ($type === 'detail')
                        <tr>
                            <td class="description">{{ $description }}</td>
                            <td class="category">{{ $row['status'] ?? '-' }}</td>
                            <td class="calculation">
                                @if ($price == 0.0 && $quantity == 0.0 && $additional == 0.0)
                                    -
                                @else
                                    {{ $total < 0 ? '- ' : '' }}{{ $money(abs($price)) }} x {{ $number($quantity) }}
                                    @if ($additional != 0.0)
                                        + {{ $money($additional) }}
                                    @endif
                                @endif
                            </td>
                            <td class="amount{{ $total < 0 ? ' negative' : '' }}">{{ $money($total) }}</td>
                        </tr>
                    @else
                        <tr class="note-row">
                            <td colspan="3">{{ $description }}{{ !empty($row['status']) ? ' - '.$row['status'] : '' }}</td>
                            <td class="amount{{ $total < 0 ? ' negative' : '' }}">{{ $total != 0.0 ? $money($total) : '-' }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="4" style="padding: 6mm; text-align: center">Rincian biaya belum tersedia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-layout">
            <tr>
                <td class="summary-note-cell">
                    <div class="summary-note">
                        <strong>Ringkasan pembayaran</strong>
                        Total akhir merupakan akumulasi layanan dan biaya tambahan setelah dikurangi potongan atau
                        retur yang tercatat pada sistem billing.
                    </div>
                </td>
                <td class="totals-cell">
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal layanan</td>
                            <td>{{ $money($summary['subtotal'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td>Tambahan biaya</td>
                            <td>{{ $money($summary['tambahan'] ?? 0) }}</td>
                        </tr>
                        <tr class="deduction">
                            <td>Potongan / retur</td>
                            <td>{{ ((float) ($summary['pengurang'] ?? 0)) > 0 ? '- '.$money($summary['pengurang']) : $money(0) }}</td>
                        </tr>
                        <tr class="grand-total">
                            <td>Total tagihan</td>
                            <td>{{ $money($summary['total'] ?? 0) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </section>

    <table class="digital-proof">
        <tr>
            <td class="proof-mark">&#10003;</td>
            <td class="proof-copy">
                <strong>Bukti transaksi elektronik E-Pasien RS ARSY</strong>
                <p>Nota ini dibuat dari data billing kunjungan milik pasien yang terautentikasi.</p>
            </td>
            <td class="print-meta">
                <small>Waktu cetak</small>
                <strong>{{ $printedAt }}</strong>
            </td>
        </tr>
    </table>

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $pdf->page_text(484, 806, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 7, array(0.48, 0.52, 0.60));
        }
    </script>
</body>

</html>

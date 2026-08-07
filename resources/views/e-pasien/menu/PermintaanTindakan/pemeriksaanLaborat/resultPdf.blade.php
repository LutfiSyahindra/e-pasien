<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Hasil Laboratorium RS ARSY</title>
    <style>
        @page {
            margin: 15mm 13mm 20mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.5pt;
            line-height: 1.45;
        }

        .page-accent {
            border-collapse: collapse;
            left: -13mm;
            position: fixed;
            right: -13mm;
            top: -15mm;
            width: calc(100% + 26mm);
        }

        .page-accent td {
            height: 3.2mm;
            padding: 0;
        }

        .accent-red {
            background: #e32620;
        }

        .accent-purple {
            background: #27166f;
        }

        .accent-green {
            background: #00964a;
        }

        .page-footer {
            border-collapse: collapse;
            bottom: -11mm;
            color: #788194;
            font-size: 6.8pt;
            left: 0;
            position: fixed;
            right: 0;
            width: 100%;
        }

        .page-footer td {
            border-top: 0.25mm solid #dfe4ec;
            padding-top: 2.2mm;
        }

        .page-footer strong {
            color: #27166f;
        }

        .footer-right {
            text-align: right;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .document-header {
            border-bottom: 0.35mm solid #e0e4eb;
            margin-bottom: 5mm;
            padding-bottom: 4mm;
        }

        .document-header td {
            padding: 0 0 4mm;
            vertical-align: middle;
        }

        .brand-cell {
            width: 55%;
        }

        .brand-logo {
            height: 18mm;
            object-fit: contain;
            vertical-align: middle;
            width: 18mm;
        }

        .brand-copy {
            display: inline-block;
            margin-left: 3mm;
            vertical-align: middle;
        }

        .brand-copy small,
        .brand-copy span {
            color: #6c7588;
            display: block;
        }

        .brand-copy small {
            font-size: 6.6pt;
            font-weight: 700;
            letter-spacing: 0.9pt;
            text-transform: uppercase;
        }

        .brand-copy strong {
            color: #27166f;
            display: block;
            font-size: 17pt;
            letter-spacing: -0.35pt;
            line-height: 1.05;
        }

        .brand-copy span {
            font-size: 6.8pt;
            margin-top: 0.7mm;
        }

        .document-copy {
            text-align: right;
            width: 45%;
        }

        .document-copy small {
            color: #00964a;
            display: block;
            font-size: 6.6pt;
            font-weight: 700;
            letter-spacing: 1.2pt;
            text-transform: uppercase;
        }

        .document-copy h1 {
            color: #172033;
            font-size: 17.5pt;
            margin: 0.4mm 0 0;
        }

        .document-copy p {
            color: #7a8291;
            font-size: 7pt;
            margin: 0.5mm 0 0;
            text-transform: uppercase;
        }

        .report-band {
            background: #27166f;
            border-radius: 2.4mm;
            color: #ffffff;
            margin-bottom: 5mm;
            overflow: hidden;
        }

        .report-band td {
            border-left: 0.2mm solid rgba(255, 255, 255, 0.17);
            padding: 3.2mm 3.5mm;
            vertical-align: top;
        }

        .report-band td:first-child {
            border-left: 0;
        }

        .report-band small,
        .report-band strong {
            display: block;
        }

        .report-band small {
            color: #cfc9ea;
            font-size: 6.3pt;
            letter-spacing: 0.45pt;
            margin-bottom: 0.8mm;
            text-transform: uppercase;
        }

        .report-band strong {
            font-size: 8.2pt;
            line-height: 1.35;
        }

        .section-block {
            margin-bottom: 4.5mm;
        }

        .section-heading {
            border-left: 1mm solid #e32620;
            margin-bottom: 2.2mm;
            padding-left: 2.3mm;
        }

        .section-heading.green {
            border-left-color: #00964a;
        }

        .section-heading small {
            color: #8a92a2;
            display: block;
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.85pt;
            text-transform: uppercase;
        }

        .section-heading h2 {
            color: #27166f;
            font-size: 10pt;
            margin: 0.15mm 0 0;
        }

        .patient-grid,
        .clinical-grid {
            border: 0.25mm solid #e0e5ed;
            border-radius: 2mm;
            overflow: hidden;
        }

        .patient-grid td,
        .clinical-grid td {
            border-left: 0.25mm solid #e8ebf1;
            border-top: 0.25mm solid #e8ebf1;
            padding: 2.6mm 3.2mm;
            vertical-align: top;
        }

        .patient-grid tr:first-child td,
        .clinical-grid tr:first-child td {
            border-top: 0;
        }

        .patient-grid td:first-child,
        .clinical-grid td:first-child {
            border-left: 0;
        }

        .field-label {
            color: #7c8596;
            display: block;
            font-size: 6.2pt;
            font-weight: 700;
            letter-spacing: 0.35pt;
            margin-bottom: 0.7mm;
            text-transform: uppercase;
        }

        .patient-grid strong,
        .clinical-grid strong {
            color: #172033;
            display: block;
            font-size: 8.1pt;
        }

        .patient-grid .patient-name {
            color: #27166f;
            font-size: 10pt;
        }

        .clinical-grid td {
            background: #fafbfc;
        }

        .summary-strip {
            margin: 4mm 0 5mm;
        }

        .summary-strip td {
            background: #f4f6f9;
            border-left: 1.5mm solid #ffffff;
            padding: 2.4mm 3mm;
            vertical-align: middle;
        }

        .summary-strip td:first-child {
            border-left: 0;
        }

        .summary-strip small {
            color: #7b8495;
            display: block;
            font-size: 6.2pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .summary-strip strong {
            color: #27166f;
            display: block;
            font-size: 12pt;
            line-height: 1.25;
        }

        .summary-strip .attention strong {
            color: #c9342e;
        }

        .result-group {
            margin: 0 0 4.5mm;
        }

        .group-heading {
            background: #eff7f2;
            border-left: 1.1mm solid #00964a;
            page-break-after: avoid;
        }

        .group-heading td {
            padding: 2.2mm 3mm;
        }

        .group-heading h3 {
            color: #174a36;
            font-size: 9pt;
            margin: 0;
        }

        .group-heading span {
            color: #638071;
            display: block;
            font-size: 6.3pt;
            margin-top: 0.3mm;
        }

        .group-count {
            color: #287c58;
            font-size: 7pt;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
            width: 26%;
        }

        .result-table {
            table-layout: fixed;
        }

        .result-table thead {
            display: table-header-group;
        }

        .result-table tr {
            page-break-inside: avoid;
        }

        .result-table th {
            background: #27166f;
            border-left: 0.2mm solid #4a3b8a;
            color: #ffffff;
            font-size: 6.2pt;
            letter-spacing: 0.25pt;
            padding: 2mm 1.8mm;
            text-align: left;
            text-transform: uppercase;
        }

        .result-table th:first-child {
            border-left: 0;
            text-align: center;
        }

        .result-table td {
            border-bottom: 0.2mm solid #e2e6ed;
            color: #3a4252;
            padding: 2.2mm 1.8mm;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .result-table tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

        .result-table .number {
            color: #9299a7;
            text-align: center;
        }

        .result-table .parameter {
            color: #20283a;
            font-weight: 700;
        }

        .result-table .value {
            color: #27166f;
            font-size: 9pt;
            font-weight: 700;
        }

        .result-table .unit,
        .result-table .reference {
            color: #5f6879;
        }

        .note-badge {
            background: #eef7f2;
            border-radius: 5mm;
            color: #257350;
            display: inline-block;
            font-size: 6.4pt;
            font-weight: 700;
            padding: 0.8mm 1.8mm;
        }

        .note-badge.has-note {
            background: #fff0ee;
            color: #bd302b;
        }

        .patient-note {
            background: #fff8ec;
            border: 0.25mm solid #f0d6a5;
            border-left: 1.1mm solid #e7a52b;
            border-radius: 1.5mm;
            color: #6e552b;
            margin-top: 4.5mm;
            padding: 3mm 3.5mm;
            page-break-inside: avoid;
        }

        .patient-note strong {
            color: #5b421b;
            display: block;
            font-size: 7.3pt;
            margin-bottom: 0.6mm;
            text-transform: uppercase;
        }

        .digital-proof {
            border-top: 0.25mm solid #e0e5ed;
            margin-top: 4.5mm;
            page-break-inside: avoid;
        }

        .digital-proof td {
            padding-top: 3mm;
            vertical-align: middle;
        }

        .proof-mark {
            background: #27166f;
            border-radius: 50%;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 700;
            height: 9mm;
            text-align: center;
            width: 9mm;
        }

        .proof-copy {
            padding-left: 2.5mm;
        }

        .proof-copy strong {
            color: #27166f;
            display: block;
            font-size: 7.5pt;
        }

        .proof-copy p {
            color: #7b8393;
            font-size: 6.3pt;
            margin: 0.4mm 0 0;
        }

        .print-meta {
            text-align: right;
            width: 31%;
        }

        .print-meta small,
        .print-meta strong {
            display: block;
        }

        .print-meta small {
            color: #8c94a2;
            font-size: 6pt;
            text-transform: uppercase;
        }

        .print-meta strong {
            color: #3a4251;
            font-size: 6.8pt;
        }
    </style>
</head>

<body>
    @php
        $request = $result['permintaan'] ?? [];
        $summary = $result['ringkasan'] ?? [];
        $groups = $result['kelompok_hasil'] ?? [];
        $patientName = trim((string) data_get($patient, 'nm_pasien')) ?: '-';
        $medicalRecordNumber = trim((string) data_get($patient, 'no_rkm_medis')) ?: '-';
        $genderCode = mb_strtoupper(trim((string) data_get($patient, 'jk')));
        $gender = match ($genderCode) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
        $birthDateValue = trim((string) data_get($patient, 'tgl_lahir'));
        $birthDate = '-';

        if ($birthDateValue !== '' && $birthDateValue !== '0000-00-00') {
            try {
                $birthDate = \Illuminate\Support\Carbon::parse($birthDateValue)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                $birthDate = $birthDateValue;
            }
        }
    @endphp

    <table class="page-accent" aria-hidden="true">
        <tr>
            <td class="accent-red" style="width: 29%"></td>
            <td class="accent-purple" style="width: 45%"></td>
            <td class="accent-green" style="width: 26%"></td>
        </tr>
    </table>

    <table class="page-footer">
        <tr>
            <td><strong>RS ARSY</strong> &nbsp;|&nbsp; E-Pasien - Hasil laboratorium</td>
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
                <small>Dokumen hasil</small>
                <h1>Hasil Laboratorium</h1>
                <p>Laboratory examination report</p>
            </td>
        </tr>
    </table>

    <table class="report-band">
        <tr>
            <td style="width: 27%">
                <small>No. permintaan</small>
                <strong>{{ $request['noorder'] ?? '-' }}</strong>
            </td>
            <td style="width: 31%">
                <small>Tanggal hasil</small>
                <strong>{{ $request['tanggal_hasil_lengkap'] ?? '-' }}</strong>
            </td>
            <td style="width: 19%">
                <small>Waktu hasil</small>
                <strong>{{ $request['jam_hasil_aktual'] ?? ($request['jam_hasil'] ?? '-') }} WIB</strong>
            </td>
            <td style="width: 23%">
                <small>Jenis layanan</small>
                <strong>{{ $request['jenis_layanan'] ?? '-' }}</strong>
            </td>
        </tr>
    </table>

    <section class="section-block">
        <div class="section-heading">
            <small>Identitas pasien</small>
            <h2>Data pasien dan permintaan</h2>
        </div>

        <table class="patient-grid">
            <tr>
                <td style="width: 38%">
                    <span class="field-label">Nama pasien</span>
                    <strong class="patient-name">{{ $patientName }}</strong>
                </td>
                <td style="width: 20%">
                    <span class="field-label">No. rekam medis</span>
                    <strong>{{ $medicalRecordNumber }}</strong>
                </td>
                <td style="width: 19%">
                    <span class="field-label">Jenis kelamin</span>
                    <strong>{{ $gender }}</strong>
                </td>
                <td style="width: 23%">
                    <span class="field-label">Tanggal lahir</span>
                    <strong>{{ $birthDate }}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">Dokter perujuk</span>
                    <strong>{{ $request['dokter_perujuk'] ?? '-' }}</strong>
                </td>
                <td colspan="2">
                    <span class="field-label">Unit pelayanan</span>
                    <strong>{{ $request['poli'] ?? '-' }}</strong>
                </td>
                <td>
                    <span class="field-label">No. rawat</span>
                    <strong>{{ $request['no_rawat'] ?? '-' }}</strong>
                </td>
            </tr>
        </table>
    </section>

    <section class="section-block">
        <div class="section-heading green">
            <small>Informasi klinis</small>
            <h2>Keterangan pemeriksaan</h2>
        </div>

        <table class="clinical-grid">
            <tr>
                <td style="width: 50%">
                    <span class="field-label">Diagnosa klinis</span>
                    <strong>{{ $request['diagnosa_klinis'] ?? '-' }}</strong>
                </td>
                <td style="width: 50%">
                    <span class="field-label">Informasi tambahan</span>
                    <strong>{{ $request['informasi_tambahan'] ?? '-' }}</strong>
                </td>
            </tr>
        </table>
    </section>

    <table class="summary-strip">
        <tr>
            <td style="width: 33.33%">
                <small>Jenis pemeriksaan</small>
                <strong>{{ (int) ($summary['jumlah_jenis'] ?? 0) }}</strong>
            </td>
            <td style="width: 33.33%">
                <small>Parameter hasil</small>
                <strong>{{ (int) ($summary['jumlah_parameter'] ?? 0) }}</strong>
            </td>
            <td class="attention" style="width: 33.33%">
                <small>Dengan catatan lab</small>
                <strong>{{ (int) ($summary['jumlah_catatan'] ?? 0) }}</strong>
            </td>
        </tr>
    </table>

    @foreach ($groups as $group)
        @php($parameters = $group['parameter'] ?? [])
        <section class="result-group">
            <table class="group-heading">
                <tr>
                    <td>
                        <h3>{{ $group['nama'] ?? '-' }}</h3>
                        <span>Kode pemeriksaan: {{ $group['kode'] ?? '-' }}</span>
                    </td>
                    <td class="group-count">{{ count($parameters) }} parameter</td>
                </tr>
            </table>

            <table class="result-table">
                <thead>
                    <tr>
                        <th style="width: 6%">No.</th>
                        <th style="width: 27%">Parameter</th>
                        <th style="width: 15%">Hasil</th>
                        <th style="width: 13%">Satuan</th>
                        <th style="width: 20%">Nilai rujukan</th>
                        <th style="width: 19%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parameters as $parameter)
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td class="parameter">{{ $parameter['nama'] ?? '-' }}</td>
                            <td class="value">{{ $parameter['nilai'] ?? '-' }}</td>
                            <td class="unit">{{ $parameter['satuan'] ?? '-' }}</td>
                            <td class="reference">{{ $parameter['nilai_rujukan'] ?? '-' }}</td>
                            <td>
                                <span class="note-badge{{ !empty($parameter['memiliki_catatan']) ? ' has-note' : '' }}">
                                    {{ $parameter['keterangan'] ?? '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 4mm; text-align: center">
                                Parameter hasil belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach

    <div class="patient-note">
        <strong>Catatan penting untuk pasien</strong>
        Nilai rujukan dapat berbeda menurut metode pemeriksaan, usia, jenis kelamin, dan kondisi klinis.
        Hasil ini bukan diagnosis mandiri. Konsultasikan hasil kepada dokter atau tenaga kesehatan yang merawat Anda.
    </div>

    <table class="digital-proof">
        <tr>
            <td class="proof-mark">E</td>
            <td class="proof-copy">
                <strong>Dokumen elektronik E-Pasien RS ARSY</strong>
                <p>Disusun dari data hasil laboratorium milik pasien yang terautentikasi.</p>
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
            $pdf->page_text(484, 806, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 7, array(0.47, 0.51, 0.59));
        }
    </script>
</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Hasil Radiologi RS ARSY</title>
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
            color: #1b2932;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.4pt;
            line-height: 1.48;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .page-accent {
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
            bottom: -11mm;
            color: #788391;
            font-size: 6.7pt;
            left: 0;
            position: fixed;
            right: 0;
        }

        .page-footer td {
            border-top: 0.25mm solid #dce4e7;
            padding-top: 2.2mm;
        }

        .page-footer strong {
            color: #125f68;
        }

        .footer-right {
            padding-right: 27mm;
            text-align: right;
        }

        .document-header {
            border-bottom: 0.35mm solid #dce4e7;
            margin-bottom: 5mm;
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
            color: #6e7985;
            display: block;
        }

        .brand-copy small {
            font-size: 6.5pt;
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
            color: #0b7a81;
            display: block;
            font-size: 6.5pt;
            font-weight: 700;
            letter-spacing: 1.2pt;
            text-transform: uppercase;
        }

        .document-copy h1 {
            color: #172831;
            font-size: 17.5pt;
            margin: 0.4mm 0 0;
        }

        .document-copy p {
            color: #7a8690;
            font-size: 7pt;
            margin: 0.5mm 0 0;
            text-transform: uppercase;
        }

        .report-band {
            background: #12313a;
            border-radius: 2.4mm;
            color: #ffffff;
            margin-bottom: 5mm;
            overflow: hidden;
        }

        .report-band td {
            border-left: 0.2mm solid rgba(255, 255, 255, 0.17);
            padding: 3.1mm 3.3mm;
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
            color: #afd0d3;
            font-size: 6.2pt;
            letter-spacing: 0.45pt;
            margin-bottom: 0.8mm;
            text-transform: uppercase;
        }

        .report-band strong {
            font-size: 8.2pt;
            line-height: 1.35;
        }

        .status-final {
            background: #dff4e8;
            border-radius: 5mm;
            color: #176b47;
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 700;
            margin-top: 0.4mm;
            padding: 0.7mm 2mm;
            text-transform: uppercase;
        }

        .section-block {
            margin-bottom: 4.5mm;
        }

        .section-heading {
            border-left: 1mm solid #0b7a81;
            margin-bottom: 2.2mm;
            padding-left: 2.3mm;
            page-break-after: avoid;
        }

        .section-heading.purple {
            border-left-color: #27166f;
        }

        .section-heading.green {
            border-left-color: #00964a;
        }

        .section-heading small {
            color: #89949d;
            display: block;
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.85pt;
            text-transform: uppercase;
        }

        .section-heading h2 {
            color: #16313a;
            font-size: 10pt;
            margin: 0.15mm 0 0;
        }

        .patient-grid,
        .clinical-grid {
            border: 0.25mm solid #dce5e8;
            border-radius: 2mm;
            overflow: hidden;
        }

        .patient-grid td,
        .clinical-grid td {
            border-left: 0.25mm solid #e6ecee;
            border-top: 0.25mm solid #e6ecee;
            padding: 2.6mm 3.1mm;
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
            color: #7b8791;
            display: block;
            font-size: 6.2pt;
            font-weight: 700;
            letter-spacing: 0.35pt;
            margin-bottom: 0.7mm;
            text-transform: uppercase;
        }

        .patient-grid strong,
        .clinical-grid strong {
            color: #1b2932;
            display: block;
            font-size: 8.1pt;
            overflow-wrap: break-word;
        }

        .patient-grid .patient-name {
            color: #125f68;
            font-size: 10pt;
        }

        .clinical-grid td {
            background: #f8fbfb;
        }

        .summary-strip {
            margin: 4mm 0 5mm;
        }

        .summary-strip td {
            background: #f2f6f7;
            border-left: 1.5mm solid #ffffff;
            padding: 2.4mm 3mm;
            vertical-align: middle;
        }

        .summary-strip td:first-child {
            border-left: 0;
        }

        .summary-strip small {
            color: #7b8790;
            display: block;
            font-size: 6.1pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .summary-strip strong {
            color: #125f68;
            display: block;
            font-size: 12pt;
            line-height: 1.25;
        }

        .examination-table {
            table-layout: fixed;
        }

        .examination-table thead {
            display: table-header-group;
        }

        .examination-table tr {
            page-break-inside: avoid;
        }

        .examination-table th {
            background: #12313a;
            border-left: 0.2mm solid #34515a;
            color: #ffffff;
            font-size: 6.2pt;
            letter-spacing: 0.25pt;
            padding: 2mm 2.1mm;
            text-align: left;
            text-transform: uppercase;
        }

        .examination-table th:first-child {
            border-left: 0;
            text-align: center;
        }

        .examination-table td {
            border-bottom: 0.2mm solid #e0e7e9;
            color: #42525a;
            padding: 2.3mm 2.1mm;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .examination-table tbody tr:nth-child(even) td {
            background: #f9fbfb;
        }

        .examination-table .number {
            color: #8b969d;
            text-align: center;
        }

        .examination-table .examination-name {
            color: #1b2932;
            font-weight: 700;
        }

        .payment-badge {
            background: #eaf5f2;
            border-radius: 5mm;
            color: #2c6c59;
            display: inline-block;
            font-size: 6.3pt;
            font-weight: 700;
            padding: 0.8mm 1.8mm;
        }

        .finding-block {
            border: 0.25mm solid #d9e3e6;
            border-radius: 2mm;
            margin-bottom: 3.5mm;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .finding-cluster {
            page-break-inside: avoid;
        }

        .finding-heading {
            background: #eaf5f5;
            border-left: 1.1mm solid #0b7a81;
            page-break-after: avoid;
        }

        .finding-heading td {
            padding: 2.3mm 3mm;
            vertical-align: middle;
        }

        .finding-heading strong {
            color: #125f68;
            display: block;
            font-size: 8.7pt;
        }

        .finding-heading small {
            color: #668087;
            display: block;
            font-size: 6.3pt;
            margin-top: 0.35mm;
        }

        .finding-index {
            color: #438087;
            font-size: 7pt;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
            width: 24%;
        }

        .finding-copy {
            color: #263941;
            font-size: 8.5pt;
            line-height: 1.65;
            padding: 3.5mm 3.8mm 4mm;
            word-wrap: break-word;
        }

        .attachment-table {
            table-layout: fixed;
        }

        .attachment-table tr {
            page-break-inside: avoid;
        }

        .attachment-table td {
            border-bottom: 0.2mm solid #e1e7e9;
            padding: 2.2mm 2.4mm;
            vertical-align: middle;
        }

        .attachment-table tr:first-child td {
            border-top: 0.25mm solid #dce4e7;
        }

        .attachment-number {
            color: #7a8790;
            text-align: center;
            width: 8%;
        }

        .attachment-name {
            color: #23363e;
            font-weight: 700;
            width: 55%;
            word-wrap: break-word;
        }

        .attachment-date {
            color: #64727b;
            font-size: 7.2pt;
            text-align: right;
            width: 37%;
        }

        .image-guidance {
            background: #eff7f7;
            border-left: 1mm solid #0b7a81;
            color: #4e686e;
            font-size: 6.8pt;
            margin-top: 2.5mm;
            padding: 2.5mm 3mm;
            page-break-inside: avoid;
        }

        .image-guidance strong {
            color: #125f68;
        }

        .patient-note {
            background: #fff8ea;
            border: 0.25mm solid #efd7aa;
            border-left: 1.1mm solid #e6a329;
            border-radius: 1.5mm;
            color: #6c552b;
            margin-top: 4.5mm;
            padding: 3mm 3.5mm;
            page-break-inside: avoid;
        }

        .patient-note strong {
            color: #59431d;
            display: block;
            font-size: 7.3pt;
            margin-bottom: 0.6mm;
            text-transform: uppercase;
        }

    </style>
</head>

<body>
    @php
        $request = $result['permintaan'] ?? [];
        $summary = $result['ringkasan'] ?? [];
        $examinations = $result['pemeriksaan'] ?? [];
        $reports = $result['hasil'] ?? [];
        $images = $result['gambar'] ?? [];
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
            <td><strong>RS ARSY</strong> &nbsp;|&nbsp; Hasil radiologi - Dokumen pribadi dan rahasia</td>
            <td class="footer-right">Dicetak: {{ $printedAt }}</td>
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
                <small>Laporan klinis</small>
                <h1>Hasil Radiologi</h1>
                <p>Radiology examination report</p>
            </td>
        </tr>
    </table>

    <table class="report-band">
        <tr>
            <td style="width: 27%">
                <small>No. permintaan</small>
                <strong>{{ $request['noorder'] ?? '-' }}</strong>
            </td>
            <td style="width: 30%">
                <small>Tanggal hasil</small>
                <strong>{{ $request['tanggal_hasil_lengkap'] ?? '-' }}</strong>
            </td>
            <td style="width: 18%">
                <small>Waktu hasil</small>
                <strong>{{ $request['jam_hasil'] ?? '-' }} WIB</strong>
            </td>
            <td style="width: 25%">
                <small>Status laporan</small>
                <span class="status-final">Hasil tersedia</span>
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
            <h2>Indikasi dan konteks pemeriksaan</h2>
        </div>

        <table class="clinical-grid">
            <tr>
                <td style="width: 42%">
                    <span class="field-label">Diagnosa klinis</span>
                    <strong>{{ $request['diagnosa_klinis'] ?? '-' }}</strong>
                </td>
                <td style="width: 38%">
                    <span class="field-label">Informasi tambahan</span>
                    <strong>{{ $request['informasi_tambahan'] ?? '-' }}</strong>
                </td>
                <td style="width: 20%">
                    <span class="field-label">Jenis layanan</span>
                    <strong>{{ $request['jenis_layanan'] ?? '-' }}</strong>
                </td>
            </tr>
        </table>
    </section>

    <table class="summary-strip">
        <tr>
            <td style="width: 33.33%">
                <small>Pemeriksaan</small>
                <strong>{{ (int) ($summary['jumlah_pemeriksaan'] ?? count($examinations)) }}</strong>
            </td>
            <td style="width: 33.33%">
                <small>Narasi hasil</small>
                <strong>{{ (int) ($summary['jumlah_hasil'] ?? count($reports)) }}</strong>
            </td>
            <td style="width: 33.33%">
                <small>Lampiran gambar</small>
                <strong>{{ (int) ($summary['jumlah_gambar'] ?? count($images)) }}</strong>
            </td>
        </tr>
    </table>

    @if (!empty($examinations))
        <section class="section-block">
            <div class="section-heading purple">
                <small>Rincian tindakan</small>
                <h2>Pemeriksaan yang diminta</h2>
            </div>

            <table class="examination-table">
                <thead>
                    <tr>
                        <th style="width: 7%">No.</th>
                        <th style="width: 54%">Pemeriksaan</th>
                        <th style="width: 19%">Kode</th>
                        <th style="width: 20%">Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($examinations as $examination)
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td class="examination-name">{{ $examination['nama'] ?? '-' }}</td>
                            <td>{{ $examination['kode'] ?? '-' }}</td>
                            <td>
                                <span class="payment-badge">{{ $examination['status_bayar'] ?? '-' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <div class="patient-note">
        <strong>Catatan penting untuk pasien</strong>
        Hasil radiologi perlu dinilai bersama kondisi klinis, riwayat penyakit, dan pemeriksaan lain.
        Dokumen ini bukan diagnosis mandiri. Konsultasikan hasil kepada dokter atau tenaga kesehatan yang merawat Anda.
    </div>

    @if (!empty($reports))
        <section class="section-block">
            @foreach ($reports as $report)
                @if ($loop->first)
                    <div class="finding-cluster">
                        <div class="section-heading">
                            <small>Interpretasi pemeriksaan</small>
                            <h2>Hasil pembacaan radiologi</h2>
                        </div>
                @endif

                <div class="finding-block">
                    <table class="finding-heading">
                        <tr>
                            <td>
                                <strong>Hasil pembacaan {{ $loop->iteration }}</strong>
                                <small>{{ $report['tanggal_lengkap'] ?? '-' }} - {{ $report['jam'] ?? '-' }} WIB</small>
                            </td>
                            <td class="finding-index">Laporan {{ $loop->iteration }} / {{ count($reports) }}</td>
                        </tr>
                    </table>
                    <div class="finding-copy">{!! nl2br(e($report['narasi'] ?? '-')) !!}</div>
                </div>

                @if ($loop->first)
                    </div>
                @endif
            @endforeach
        </section>
    @endif

    @if (!empty($images))
        <section class="section-block">
            <div class="section-heading green">
                <small>Lampiran digital</small>
                <h2>Indeks gambar radiologi</h2>
            </div>

            <table class="attachment-table">
                @foreach ($images as $image)
                    <tr>
                        <td class="attachment-number">{{ $loop->iteration }}</td>
                        <td class="attachment-name">{{ $image['nama_file'] ?? 'Gambar radiologi' }}</td>
                        <td class="attachment-date">
                            {{ $image['tanggal_lengkap'] ?? '-' }} - {{ $image['jam'] ?? '-' }} WIB
                        </td>
                    </tr>
                @endforeach
            </table>

            <div class="image-guidance">
                <strong>Gambar resolusi penuh tersedia di E-Pasien.</strong>
                Dokumen cetak ini memuat indeks lampiran. Gunakan penampil gambar pada portal untuk melihat citra asli;
                hasil cetak biasa tidak ditujukan untuk interpretasi diagnostik.
            </div>
        </section>
    @endif

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $pdf->page_text(484, 806, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 7, array(0.47, 0.51, 0.56));
        }
    </script>
</body>

</html>

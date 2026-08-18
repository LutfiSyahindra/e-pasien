@extends("template.epasien.appPasien")

@section("title", "Pemantauan Penggunaan Web & PWA | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/user-access-monitoring.css") }}" rel="stylesheet" />
@endpush

@section("content")
    <main class="usage-monitor-page">
        <nav class="usage-monitor-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Pengaturan</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Penggunaan Web &amp; PWA</span>
        </nav>

        <section class="usage-monitor-hero">
            <div class="usage-monitor-heading">
                <span class="usage-monitor-heading__icon"><i class="bi bi-graph-up-arrow"></i></span>
                <div>
                    <span class="usage-monitor-eyebrow">Analitik internal E-Pasien</span>
                    <h1>Pemantauan Penggunaan Web &amp; PWA</h1>
                    <p>Pantau pengguna aktif dari browser, aplikasi PWA, dan jumlah perangkat yang memasang E-Pasien.</p>
                </div>
            </div>
            <div class="usage-monitor-period">
                <span><i class="bi bi-calendar3"></i> Periode laporan</span>
                <strong>{{ $periodLabel }}</strong>
                <small>Diperbarui otomatis saat pengguna membuka E-Pasien.</small>
            </div>
        </section>

        <section class="usage-monitor-stats" aria-label="Ringkasan penggunaan E-Pasien">
            <article class="is-indigo">
                <span><i class="bi bi-person-check"></i></span>
                <div><small>Akun aktif terdaftar</small><strong>{{ number_format($summary["registered_users"], 0, ",", ".") }}</strong><em>akun dapat menggunakan E-Pasien</em></div>
            </article>
            <article class="is-cyan">
                <span><i class="bi bi-activity"></i></span>
                <div><small>Pengguna aktif</small><strong>{{ number_format($summary["active_users"], 0, ",", ".") }}</strong><em>dalam {{ $filters["period"] }} hari terakhir</em></div>
            </article>
            <article class="is-blue">
                <span><i class="bi bi-globe2"></i></span>
                <div><small>Pengguna melalui web</small><strong>{{ number_format($summary["web_users"], 0, ",", ".") }}</strong><em>browser dalam periode ini</em></div>
            </article>
            <article class="is-emerald">
                <span><i class="bi bi-phone"></i></span>
                <div><small>Pengguna melalui PWA</small><strong>{{ number_format($summary["pwa_users"], 0, ",", ".") }}</strong><em>aplikasi dalam periode ini</em></div>
            </article>
            <article class="is-orange">
                <span><i class="bi bi-download"></i></span>
                <div>
                    <small>Pengguna memasang PWA</small>
                    <strong>{{ number_format($summary["installed_users"], 0, ",", ".") }}</strong>
                    <em>{{ number_format($summary["installed_devices"], 0, ",", ".") }} perangkat · {{ number_format($summary["conversion_rate"], 1, ",", ".") }}% pengguna terlacak</em>
                </div>
            </article>
        </section>

        <aside class="usage-monitor-note">
            <span><i class="bi bi-info-circle"></i></span>
            <p><strong>Cara membaca angka:</strong> pengguna web dan PWA dapat tumpang tindih karena satu akun bisa memakai keduanya. Data instalasi mulai terkumpul setelah fitur ini aktif; instalasi iPhone/iPad dikenali saat PWA pertama kali dibuka. Browser tidak mengirim peristiwa uninstall, sehingga angka pemasangan berarti pernah terdeteksi terpasang.</p>
        </aside>

        <div class="usage-monitor-insights">
            <section class="usage-monitor-panel usage-monitor-chart-panel" aria-labelledby="usage-chart-title">
                <header>
                    <div>
                        <span class="usage-monitor-kicker"><i class="bi bi-bar-chart"></i> Tren harian</span>
                        <h2 id="usage-chart-title">Pengguna aktif per kanal</h2>
                        <p>Jumlah akun unik yang terdeteksi setiap hari.</p>
                    </div>
                    <form method="GET" action="{{ route("userAccessMonitoring.index") }}" class="usage-monitor-period-filter">
                        <input type="hidden" name="channel" value="{{ $filters["channel"] }}">
                        @if ($filters["q"] !== "")<input type="hidden" name="q" value="{{ $filters["q"] }}">@endif
                        <label for="usagePeriod">Periode</label>
                        <select id="usagePeriod" name="period" onchange="this.form.submit()">
                            <option value="7" @selected($filters["period"] === 7)>7 hari</option>
                            <option value="30" @selected($filters["period"] === 30)>30 hari</option>
                            <option value="90" @selected($filters["period"] === 90)>90 hari</option>
                        </select>
                    </form>
                </header>

                <div class="usage-monitor-legend" aria-hidden="true">
                    <span><i class="web"></i> Web</span>
                    <span><i class="pwa"></i> PWA</span>
                </div>

                <div class="usage-monitor-chart-scroll">
                    <div class="usage-monitor-chart" role="img" aria-label="Grafik pengguna web dan PWA harian">
                        @foreach ($series as $day)
                            @php
                                $webHeight = $day["web"] > 0 ? max(5, round(($day["web"] / $seriesPeak) * 100)) : 0;
                                $pwaHeight = $day["pwa"] > 0 ? max(5, round(($day["pwa"] / $seriesPeak) * 100)) : 0;
                                $showLabel = $filters["period"] <= 7 || $loop->first || $loop->last || $loop->iteration % 5 === 0;
                            @endphp
                            <div class="usage-monitor-chart-day" title="{{ $day["label"] }}: Web {{ $day["web"] }}, PWA {{ $day["pwa"] }}">
                                <div class="usage-monitor-chart-bars">
                                    <i class="web" style="--bar-height: {{ $webHeight }}%"><span>{{ $day["web"] }}</span></i>
                                    <i class="pwa" style="--bar-height: {{ $pwaHeight }}%"><span>{{ $day["pwa"] }}</span></i>
                                </div>
                                <small>{{ $showLabel ? $day["label"] : "" }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <aside class="usage-monitor-panel usage-monitor-platforms" aria-labelledby="usage-platform-title">
                <header>
                    <div>
                        <span class="usage-monitor-kicker"><i class="bi bi-device-ssd"></i> Perangkat</span>
                        <h2 id="usage-platform-title">Platform aktif</h2>
                        <p>Perangkat yang mengakses dalam periode ini.</p>
                    </div>
                </header>
                <div class="usage-monitor-platform-list">
                    @forelse ($platforms as $platform)
                        <div>
                            <span><strong>{{ $platform["label"] }}</strong><em>{{ number_format($platform["devices"], 0, ",", ".") }} perangkat</em></span>
                            <i><span style="width: {{ round(($platform["devices"] / $platformPeak) * 100) }}%"></span></i>
                        </div>
                    @empty
                        <div class="usage-monitor-empty compact"><i class="bi bi-phone"></i><span>Belum ada platform yang terlacak.</span></div>
                    @endforelse
                </div>
            </aside>
        </div>

        <section class="usage-monitor-panel usage-monitor-devices" aria-labelledby="usage-device-title">
            <header>
                <div>
                    <span class="usage-monitor-kicker"><i class="bi bi-clock-history"></i> Aktivitas terbaru</span>
                    <h2 id="usage-device-title">Perangkat pengguna</h2>
                    <p>Rincian perangkat aktif pada periode yang dipilih.</p>
                </div>
            </header>

            <form class="usage-monitor-toolbar" method="GET" action="{{ route("userAccessMonitoring.index") }}">
                <input type="hidden" name="period" value="{{ $filters["period"] }}">
                <label class="usage-monitor-search" for="usageSearch">
                    <i class="bi bi-search"></i>
                    <input id="usageSearch" name="q" value="{{ $filters["q"] }}" type="search" maxlength="100" placeholder="Cari nama, username, atau email...">
                </label>
                <label class="usage-monitor-channel" for="usageChannel">
                    <span>Kanal</span>
                    <select id="usageChannel" name="channel">
                        <option value="all" @selected($filters["channel"] === "all")>Semua aktivitas</option>
                        <option value="web" @selected($filters["channel"] === "web")>Web</option>
                        <option value="pwa" @selected($filters["channel"] === "pwa")>PWA</option>
                        <option value="installed" @selected($filters["channel"] === "installed")>PWA terpasang</option>
                    </select>
                </label>
                <button type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
                @if ($filters["q"] !== "" || $filters["channel"] !== "all")
                    <a href="{{ route("userAccessMonitoring.index", ["period" => $filters["period"]]) }}">Reset</a>
                @endif
            </form>

            <div class="usage-monitor-table-wrap">
                <table>
                    <thead><tr><th>Pengguna</th><th>Perangkat</th><th>Kanal terakhir</th><th>Status PWA</th><th>Aktivitas terakhir</th></tr></thead>
                    <tbody>
                        @forelse ($devices as $device)
                            <tr>
                                <td>
                                    <span class="usage-monitor-user">
                                        <img src="{{ $device->user->profile_photo_url }}" alt="" loading="lazy" decoding="async">
                                        <span><strong>{{ $device->user->name }}</strong><small>{{ $device->user->username ?: $device->user->email }}</small></span>
                                    </span>
                                </td>
                                <td><strong>{{ $device->platform ?: "Lainnya" }}</strong><small>{{ $device->browser ?: "Browser tidak dikenal" }}</small></td>
                                <td><span class="usage-monitor-badge is-{{ $device->last_mode }}"><i class="bi {{ $device->last_mode === "pwa" ? "bi-phone" : "bi-globe2" }}"></i>{{ strtoupper($device->last_mode) }}</span></td>
                                <td>
                                    @if ($device->is_pwa_installed)
                                        <span class="usage-monitor-install is-installed"><i class="bi bi-check-circle-fill"></i> Pernah terpasang</span>
                                        <small>{{ $device->installed_at?->locale("id")->translatedFormat("d M Y") }}</small>
                                    @else
                                        <span class="usage-monitor-install"><i class="bi bi-dash-circle"></i> Belum terdeteksi</span>
                                    @endif
                                </td>
                                <td><strong>{{ $device->last_seen_at->locale("id")->translatedFormat("d M Y, H.i") }}</strong><small>{{ $device->last_seen_at->locale("id")->diffForHumans() }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="usage-monitor-empty"><i class="bi bi-bar-chart"></i><strong>Belum ada aktivitas yang sesuai</strong><span>Data akan muncul setelah pengguna membuka E-Pasien.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($devices->hasPages())
                <footer class="usage-monitor-pagination">
                    <span>Menampilkan {{ $devices->firstItem() }}–{{ $devices->lastItem() }} dari {{ number_format($devices->total(), 0, ",", ".") }} perangkat</span>
                    {{ $devices->links() }}
                </footer>
            @endif
        </section>
    </main>
@endsection

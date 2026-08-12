@extends("template.epasien.appPasien")

@section("title", "Pasien Service - E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/patient-service.css") }}" rel="stylesheet">
@endpush

@section("content")
    @php
        $waitingAdmin = $conversations->where("status", "waiting_admin")->count();
        $openConversations = $conversations->where("status", "!=", "closed")->count();
        $activeId = $activeConversation?->id;
    @endphp

    <div class="ps-shell"
        data-patient-service
        data-user-id="{{ auth()->id() }}"
        data-can-manage="{{ $canManage ? "1" : "0" }}"
        data-active-conversation="{{ $activeId }}"
        data-list-url="{{ route("patientService.index") }}"
        data-conversations-url="{{ route("patientService.conversations.store") }}"
        @if ($activeConversation)
            data-messages-url="{{ route("patientService.messages.index", $activeConversation) }}"
            data-send-url="{{ route("patientService.messages.store", $activeConversation) }}"
            data-read-url="{{ route("patientService.read", $activeConversation) }}"
            data-status-url="{{ route("patientService.status", $activeConversation) }}"
        @endif
    >
        <section class="ps-hero" aria-labelledby="patient-service-title">
            <div class="ps-hero__copy">
                <span class="ps-eyebrow"><i class="bi bi-headset"></i> Pusat bantuan RS Arsy</span>
                <h1 id="patient-service-title">
                    @if ($canManage)
                        Hubungkan pasien dengan <em>solusi terbaik.</em>
                    @else
                        Kami siap membantu <em>secara langsung.</em>
                    @endif
                </h1>
                <p>
                    {{ $canManage
                        ? "Terima pertanyaan dan laporan pasien, lalu tangani percakapan dari satu ruang kerja realtime."
                        : "Tanyakan informasi layanan atau laporkan kendala Anda. Tim kami akan merespons melalui percakapan ini." }}
                </p>
                <div class="ps-hero__assurances" aria-label="Keunggulan Pasien Service">
                    <span><i class="bi bi-shield-check"></i> Pribadi &amp; aman</span>
                    <span><i class="bi bi-lightning-charge-fill"></i> Respons realtime</span>
                    <span><i class="bi bi-check2-all"></i> Status pesan lengkap</span>
                </div>
            </div>
            <div class="ps-hero__status" aria-label="Status layanan">
                <span class="ps-live-dot" aria-hidden="true"></span>
                <div><strong>Pasien Service aktif</strong><small data-realtime-label>Menghubungkan layanan realtime...</small></div>
            </div>
            <div class="ps-hero__art" aria-hidden="true">
                <span class="ps-art-orbit ps-art-orbit--one"><i class="bi bi-chat-dots-fill"></i></span>
                <span class="ps-art-orbit ps-art-orbit--two"><i class="bi bi-heart-pulse-fill"></i></span>
                <span class="ps-art-headset"><i class="bi bi-headset"></i></span>
            </div>
        </section>

        @if ($canManage)
            <section class="ps-summary" aria-label="Ringkasan Pasien Service">
                <article><span class="is-coral"><i class="bi bi-inbox-fill"></i></span><div><strong>{{ $waitingAdmin }}</strong><small>Menunggu respons</small></div></article>
                <article><span class="is-teal"><i class="bi bi-chat-square-dots-fill"></i></span><div><strong>{{ $openConversations }}</strong><small>Percakapan aktif</small></div></article>
                <article><span class="is-indigo"><i class="bi bi-collection-fill"></i></span><div><strong>{{ $conversations->count() }}</strong><small>Ditampilkan</small></div></article>
            </section>
        @endif

        <section
            class="ps-workspace {{ $activeConversation ? "has-active" : "is-empty" }} {{ request()->filled("conversation") ? "mobile-chat-open" : "mobile-inbox-open" }}"
            aria-label="Ruang percakapan Pasien Service"
            data-service-workspace
        >
            <aside class="ps-inbox" aria-label="Daftar percakapan">
                <div class="ps-inbox__header">
                    <div>
                        <span>{{ $canManage ? "Kotak masuk" : "Layanan bantuan" }}</span>
                        <h2>{{ $canManage ? "Percakapan pasien" : "Percakapan saya" }}</h2>
                    </div>
                    @unless ($canManage)
                        <button class="ps-icon-button" type="button" data-bs-toggle="modal" data-bs-target="#newPatientServiceConversation" aria-label="Buat percakapan baru" title="Percakapan baru">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    @endunless
                </div>

                @if ($canManage)
                    <form class="ps-filters" method="GET" action="{{ route("patientService.index") }}">
                        <label><i class="bi bi-search"></i><input type="search" name="q" value="{{ $filters["q"] }}" placeholder="Cari pasien atau topik..."></label>
                        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
                            @foreach (["all" => "Semua status", "open" => "Percakapan aktif", "waiting_admin" => "Menunggu Tim Pasien Service", "waiting_patient" => "Menunggu pasien", "closed" => "Selesai"] as $value => $label)
                                <option value="{{ $value }}" @selected($filters["status"] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <button class="ps-new-conversation" type="button" data-bs-toggle="modal" data-bs-target="#newPatientServiceConversation">
                        <i class="bi bi-chat-heart-fill"></i><span><strong>Mulai percakapan baru</strong><small>Tanyakan atau laporkan kendala</small></span><i class="bi bi-arrow-right"></i>
                    </button>
                @endif

                <div class="ps-conversation-list" data-conversation-list>
                    @forelse ($conversations as $conversation)
                        @php
                            $conversationUrl = route("patientService.index", array_filter([
                                "conversation" => $conversation->id,
                                "q" => $canManage ? $filters["q"] : null,
                                "status" => $canManage && $filters["status"] !== "all" ? $filters["status"] : null,
                            ], fn ($value) => $value !== null && $value !== ""));
                        @endphp
                        <a class="ps-conversation {{ $activeId === $conversation->id ? "is-active" : "" }}"
                            href="{{ $conversationUrl }}"
                            @if ($activeId === $conversation->id) aria-current="page" @endif
                            data-conversation-id="{{ $conversation->id }}">
                            <span class="ps-conversation__avatar">{{ $conversation->patient?->initials() ?? "PS" }}</span>
                            <span class="ps-conversation__body">
                                <span class="ps-conversation__top">
                                    <strong data-patient-name>{{ $canManage ? ($conversation->patient?->name ?? "Pasien") : $conversation->category_label }}</strong>
                                    <small data-conversation-time>{{ $conversation->last_message_at?->diffForHumans(short: true) }}</small>
                                </span>
                                <span class="ps-conversation__subject" data-conversation-subject>{{ $conversation->subject }}</span>
                                <span class="ps-conversation__preview" data-conversation-preview>{{ Str::limit($conversation->latestMessage?->body ?? "Belum ada pesan", 58) }}</span>
                                <span class="ps-conversation__footer">
                                    <em class="is-{{ $conversation->status }}" data-conversation-status>{{ $conversation->status_label }}</em>
                                    @if ($conversation->unread_count > 0)
                                        <b data-unread-count>{{ $conversation->unread_count > 9 ? "9+" : $conversation->unread_count }}</b>
                                    @endif
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="ps-inbox-empty" data-inbox-empty>
                            <i class="bi bi-chat-square-heart"></i>
                            <strong>{{ $canManage ? "Belum ada percakapan" : "Belum ada riwayat" }}</strong>
                            <span>{{ $canManage ? "Percakapan pasien akan muncul di sini." : "Mulai percakapan saat Anda membutuhkan bantuan." }}</span>
                        </div>
                    @endforelse
                </div>
            </aside>

            <div class="ps-chat">
                @if ($activeConversation)
                    <header class="ps-chat__header">
                        <button class="ps-mobile-back" type="button" data-mobile-inbox-toggle aria-label="Kembali ke daftar percakapan">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <div class="ps-chat__identity">
                            <span>{{ $activeConversation->patient?->initials() ?? "PS" }}</span>
                            <div>
                                <small>{{ $canManage ? "Percakapan dengan" : $activeConversation->category_label }}</small>
                                <h2>{{ $canManage ? ($activeConversation->patient?->name ?? "Pasien") : $activeConversation->subject }}</h2>
                                @if ($canManage)<p>{{ $activeConversation->subject }}</p>@endif
                            </div>
                        </div>
                        <div class="ps-chat__actions">
                            <span class="ps-status is-{{ $activeConversation->status }}" data-active-status><i class="bi bi-circle-fill"></i><span>{{ $activeConversation->status_label }}</span></span>
                            <button
                                class="ps-status-button"
                                type="button"
                                data-status-toggle
                                data-next-status="{{ $activeConversation->status === "closed" ? "open" : "closed" }}"
                                aria-label="{{ $activeConversation->status === "closed" ? "Buka kembali percakapan" : "Tandai percakapan selesai" }}"
                                title="{{ $activeConversation->status === "closed" ? "Buka kembali percakapan" : "Tandai percakapan selesai" }}"
                            >
                                <i class="bi {{ $activeConversation->status === "closed" ? "bi-arrow-counterclockwise" : "bi-check2-circle" }}"></i>
                                <span>{{ $activeConversation->status === "closed" ? "Buka kembali" : "Tandai selesai" }}</span>
                            </button>
                        </div>
                    </header>

                    <div class="ps-messages" data-message-list aria-live="polite" aria-label="Isi percakapan">
                        <div class="ps-chat-start">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Percakapan dimulai {{ $activeConversation->created_at->translatedFormat("d F Y, H:i") }}</span>
                            <small>Pesan hanya dapat dilihat oleh Anda dan tim Pasien Service.</small>
                        </div>
                        @foreach ($messages as $message)
                            @php($isMine = $message->sender_id === auth()->id())
                            <article class="ps-message {{ $isMine ? "is-mine" : "is-theirs" }}" data-message-id="{{ $message->id }}">
                                <span class="ps-message__avatar">{{ $message->sender?->initials() ?? "PG" }}</span>
                                <div class="ps-message__content">
                                    <span class="ps-message__meta"><strong>{{ $isMine ? "Anda" : ($message->sender?->name ?? "Pengguna") }}</strong><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->translatedFormat("H:i") }}</time></span>
                                    <p>{{ $message->body }}</p>
                                    @if ($isMine)
                                        <span class="ps-message__receipt is-{{ $message->delivery_status }}" data-message-receipt data-status="{{ $message->delivery_status }}">
                                            <i class="bi {{ $message->delivery_status === "sent" ? "bi-check2" : "bi-check2-all" }}"></i>
                                            <span>{{ $message->delivery_status_label }}</span>
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <footer class="ps-composer {{ $activeConversation->status === "closed" ? "is-closed" : "" }}" data-composer>
                        <div class="ps-closed-notice" data-closed-notice @if ($activeConversation->status !== "closed") hidden @endif>
                            <i class="bi bi-check-circle-fill"></i><span><strong>Percakapan telah selesai</strong><small>Buka kembali jika masih ada yang perlu dibicarakan.</small></span>
                        </div>
                        <form data-message-form @if ($activeConversation->status === "closed") hidden @endif>
                            <label for="patient-service-message" class="visually-hidden">Tulis pesan</label>
                            <textarea id="patient-service-message" name="message" rows="1" maxlength="2000" placeholder="Tulis pesan Anda..." required data-message-input></textarea>
                            <button type="submit" aria-label="Kirim pesan" title="Kirim pesan"><i class="bi bi-send-fill"></i></button>
                        </form>
                        <div class="ps-composer__hint"><span><i class="bi bi-lightning-charge-fill"></i> Pesan dikirim realtime</span><small>Enter untuk kirim · Shift+Enter untuk baris baru</small></div>
                    </footer>
                @else
                    <div class="ps-chat-empty">
                        <span><i class="bi bi-chat-heart-fill"></i></span>
                        <small>Pasien Service</small>
                        <h2>{{ $canManage ? "Pilih percakapan untuk mulai membantu" : "Ada yang bisa kami bantu?" }}</h2>
                        <p>{{ $canManage ? "Buka salah satu percakapan pasien dari kotak masuk di sebelah kiri." : "Buat percakapan baru untuk bertanya seputar layanan atau melaporkan kendala." }}</p>
                        @unless ($canManage)
                            <button type="button" data-bs-toggle="modal" data-bs-target="#newPatientServiceConversation"><i class="bi bi-plus-lg"></i>Mulai percakapan</button>
                        @endunless
                    </div>
                @endif
            </div>
        </section>

        <div class="ps-toast" role="status" aria-live="polite" data-service-toast hidden>
            <i class="bi bi-check-circle-fill"></i><span></span>
        </div>
    </div>

    @unless ($canManage)
        <div class="modal fade ps-modal" id="newPatientServiceConversation" tabindex="-1" aria-labelledby="newConversationTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form data-new-conversation-form>
                        <div class="ps-modal__head">
                            <span><i class="bi bi-chat-heart-fill"></i></span>
                            <div><small>PASIEN SERVICE</small><h2 id="newConversationTitle">Mulai percakapan baru</h2><p>Ceritakan kebutuhan Anda agar tim kami dapat membantu lebih cepat.</p></div>
                            <button type="button" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div class="ps-modal__body">
                            <label class="ps-field"><span>Jenis kebutuhan</span><select name="category" required><option value="question">Pertanyaan layanan</option><option value="complaint">Laporan kendala</option><option value="technical">Kendala teknis aplikasi</option><option value="suggestion">Saran &amp; masukan</option></select></label>
                            <label class="ps-field"><span>Topik percakapan</span><input type="text" name="subject" maxlength="120" placeholder="Contoh: Kendala pendaftaran online" required></label>
                            <label class="ps-field"><span>Ceritakan kebutuhan Anda</span><textarea name="message" rows="5" maxlength="2000" placeholder="Tuliskan pertanyaan atau kendala secara lengkap..." required></textarea><small>Maksimal 2.000 karakter</small></label>
                            <div class="ps-form-error" data-new-conversation-error hidden></div>
                        </div>
                        <div class="ps-modal__footer">
                            <button type="button" class="is-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="is-primary"><span>Mulai percakapan</span><i class="bi bi-arrow-right"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endunless
@endsection

@push("script")
    <script src="{{ versioned_asset("epasien/assets/js/patient-service.js") }}"></script>
@endpush

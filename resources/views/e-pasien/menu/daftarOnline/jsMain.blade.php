<script>
    $(document).ready(function() {
        const config = window.daftarOnlineConfig || {};
        const state = {
            schedules: [],
            selectedSchedule: null,
            selectedGuarantor: null,
            controlLetterDetails: {},
            activeControlLetterNumber: null,
            availableBpjsDocuments: [],
            pendingBpjsDocument: null,
            selectedBpjsDocument: null,
            antrolPreview: null,
            submitting: false,
        };

        const elements = {
            form: $('#onlineRegistrationForm'),
            date: $('#tgl_registrasi'),
            openDatePicker: $('#openDatePicker'),
            scheduleList: $('#scheduleList'),
            scheduleCount: $('#scheduleCountLabel'),
            dayLabel: $('#selectedDayLabel'),
            submit: $('#submitOnlineRegistration'),
            reset: $('#resetOnlineRegistration'),
            doctorCode: $('#kd_dokter'),
            clinicCode: $('#kd_poli'),
            guarantorCode: $('#kd_pj'),
            cardNumberField: $('#bpjsCardNumberField'),
            cardNumber: $('#no_peserta'),
            summaryState: $('#summaryState'),
            summaryDate: $('#summaryDate'),
            summaryClinic: $('#summaryClinic'),
            summaryDoctor: $('#summaryDoctor'),
            summaryTime: $('#summaryTime'),
            summaryGuarantor: $('#summaryGuarantor'),
            summaryQueue: $('#summaryQueue'),
            mobileProgress: $('#onlineMobileProgress'),
            mobileProgressLabel: $('#onlineMobileProgressLabel'),
            mobileProgressBar: $('#onlineMobileProgressBar'),
            summaryPanel: $('#onlineVisitSummary'),
            sidePanel: $('#onlineSidePanel'),
            formActions: $('#onlineRegistrationForm > .online-actions'),
            controlLetterList: $('#bpjsControlLetterList'),
            controlLetterDetail: $('#bpjsControlLetterDetail'),
            controlLetterTitle: $('#bpjsControlLetterTitle'),
            controlLetterPeriod: $('#bpjsControlLetterPeriod'),
            controlLetterModal: document.getElementById('bpjsControlLetterModal'),
            mjknModal: document.getElementById('mjknRegistrationWizardModal'),
            mjknPanels: $('[data-mjkn-panel]'),
            mjknSteps: $('[data-mjkn-step]'),
            searchJourney: $('#bpjsSearchJourney'),
            documentChoiceHint: $('#bpjsDocumentChoiceHint'),
            confirmDocumentChoice: $('#confirmBpjsDocumentChoice'),
            antrolPayloadJson: $('#antrolPayloadJson'),
            submitMjknRegistration: $('#submitMjknRegistration'),
            showPendingResult: $('#showPendingRegistrationModal'),
            cancelPendingRegistration: $('#cancelPendingRegistration'),
            noticeModal: document.getElementById('onlineRegistrationNoticeModal'),
        };

        const mobileLayoutMedia = window.matchMedia('(max-width: 767.98px)');

        function syncMobileSummaryPlacement() {
            if (!elements.summaryPanel.length || !elements.formActions.length) {
                return;
            }

            if (mobileLayoutMedia.matches) {
                elements.summaryPanel
                    .addClass('is-mobile-inline')
                    .insertBefore(elements.formActions);
                return;
            }

            if (elements.sidePanel.length) {
                elements.summaryPanel
                    .removeClass('is-mobile-inline')
                    .appendTo(elements.sidePanel);
            }
        }

        if (typeof mobileLayoutMedia.addEventListener === 'function') {
            mobileLayoutMedia.addEventListener('change', syncMobileSummaryPlacement);
        } else {
            mobileLayoutMedia.addListener(syncMobileSummaryPlacement);
        }

        syncMobileSummaryPlacement();

        function alertAction(options) {
            if (window.Swal) {
                return Swal.fire(options);
            }

            window.alert(options.text || options.title || 'Selesai');
            return Promise.resolve();
        }

        function confirmAction(options) {
            if (window.Swal) {
                return Swal.fire(options);
            }

            return Promise.resolve({
                isConfirmed: window.confirm(options.title || 'Lanjutkan?')
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function fillResultModal(registration) {
            $('#resultNoReg').text(registration.no_reg || '-');
            $('#resultNoRawat').text(registration.no_rawat || '-');
            $('#resultDate').text(
                `${registration.tanggal_label || registration.tanggal || '-'} ${registration.jam || ''}`.trim()
            );
            $('#resultClinic').text(registration.poli || '-');
            $('#resultDoctor').text(registration.dokter || '-');
            $('#resultGuarantor').text(registration.penjamin || '-');
            $('#resultStatus').text(`${registration.status || '-'} / ${registration.status_bayar || '-'}`);
        }

        function showBootstrapModal(element) {
            if (!element) {
                return;
            }

            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(element).show();
                return;
            }

            $(element).modal('show');
        }

        function hideBootstrapModal(element) {
            if (!element) {
                return;
            }

            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(element).hide();
                return;
            }

            $(element).modal('hide');
        }

        function hasSelect2() {
            return Boolean($.fn.select2);
        }

        function refreshSelect($select) {
            if (hasSelect2()) {
                $select.trigger('change.select2');
            }
        }

        function clearSelect($select) {
            $select.val('');
            refreshSelect($select);
        }

        function setSelectEnabled($select, enabled) {
            $select.prop('disabled', !enabled);
            refreshSelect($select);
        }

        function hasGuarantorOptions() {
            return elements.guarantorCode.find('option[value!=""]').length > 0;
        }

        function selectedOptionName($select) {
            const selected = $select.find('option:selected');

            return selected.data('name') || $.trim(selected.text()) || '-';
        }

        function isBpjsSelected() {
            return String(state.selectedGuarantor?.kd_pj || '').toUpperCase() === 'BPJ';
        }

        function isIrmSelected() {
            const clinicCode = state.selectedSchedule?.kd_poli || elements.clinicCode.val() || '';

            return String(clinicCode).trim().toUpperCase() === 'IRM';
        }

        function isBpjsIrmCombination() {
            return isBpjsSelected() && isIrmSelected();
        }

        function usesMjknFlow() {
            return Boolean(config.isRegistrationStaff) && isBpjsSelected();
        }

        function hasRequiredCardNumber() {
            return !isBpjsSelected() || Boolean($.trim(elements.cardNumber.val()));
        }

        function setMjknStep(step) {
            const order = ['search', 'document', 'preview'];
            const activeIndex = order.indexOf(step);

            if (activeIndex < 0) {
                return;
            }

            elements.mjknPanels
                .prop('hidden', true)
                .removeClass('active');
            elements.mjknPanels
                .filter(`[data-mjkn-panel="${step === 'preview' ? 'preview' : 'document'}"]`)
                .prop('hidden', false)
                .addClass('active');

            elements.mjknSteps.each(function() {
                const modalStep = String($(this).data('mjkn-step') || '');
                const stepIndex = order.indexOf(modalStep);

                $(this)
                    .toggleClass('active', modalStep === step)
                    .toggleClass('done', stepIndex >= 0 && stepIndex < activeIndex);
            });
        }

        function documentAntrolData(document) {
            const response = document?.response || {};

            if (document?.type === 'surat_kontrol') {
                return {
                    date: response.tgl_rencana_kontrol || '',
                    cardNumber: String(response.no_kartu || '').replace(/\D/g, ''),
                    nik: '',
                    phone: '',
                    medicalRecord: '',
                    clinicCode: response.poli_tujuan || '',
                    clinicName: response.nama_poli_tujuan || '',
                    doctorCode: response.kode_dokter || '',
                    doctorName: response.nama_dokter || '',
                };
            }

            const participant = response.peserta || {};
            const clinic = response.poli_rujukan || {};

            return {
                date: response.tgl_kunjungan || '',
                cardNumber: String(participant.no_kartu || '').replace(/\D/g, ''),
                nik: String(participant.nik || '').replace(/\D/g, ''),
                phone: String(participant.no_telepon || '').replace(/\D/g, ''),
                medicalRecord: participant.no_mr || '',
                clinicCode: clinic.kode || '',
                clinicName: clinic.nama || '',
                doctorCode: '',
                doctorName: '',
            };
        }

        function bpjsDocumentTypeLabel(document) {
            if (document?.type === 'surat_kontrol') {
                return 'Surat Kontrol';
            }

            return document?.source === 'rujukan_pcare'
                ? 'Rujukan PCare'
                : 'Rujukan Rumah Sakit';
        }

        function clearBpjsDocumentSelection() {
            state.availableBpjsDocuments = [];
            state.pendingBpjsDocument = null;
            state.selectedBpjsDocument = null;
            state.antrolPreview = null;
            syncPendingBpjsDocument();
            syncSubmitPresentation();
        }

        function syncPendingBpjsDocument() {
            const document = state.pendingBpjsDocument;

            elements.controlLetterList
                .find('.online-control-card')
                .removeClass('selected');
            elements.controlLetterList
                .find('input[name="bpjs_document_choice"]')
                .prop('checked', false);

            if (document) {
                elements.controlLetterList
                    .find('input[name="bpjs_document_choice"]')
                    .filter(function() {
                        return String($(this).val()) === document.key;
                    })
                    .prop('checked', true)
                    .closest('.online-control-card')
                    .addClass('selected');
            }

            elements.confirmDocumentChoice.prop('disabled', !document);

            if (document) {
                elements.documentChoiceHint.html(
                    `<i class="bi bi-check-circle"></i> ${escapeHtml(bpjsDocumentTypeLabel(document))} ${escapeHtml(document.number)} dipilih.`
                );
            } else if (state.availableBpjsDocuments.length > 1) {
                elements.documentChoiceHint.html(
                    '<i class="bi bi-info-circle"></i> Pilih salah satu dokumen BPJS yang akan digunakan.'
                );
            } else if (state.availableBpjsDocuments.length === 1) {
                elements.documentChoiceHint.html(
                    '<i class="bi bi-info-circle"></i> Satu dokumen BPJS ditemukan.'
                );
            } else {
                elements.documentChoiceHint.html(
                    '<i class="bi bi-info-circle"></i> Tidak ada dokumen yang dapat dipilih.'
                );
            }
        }

        function prepareBpjsDocumentChoices(documents) {
            state.availableBpjsDocuments = documents;

            const selectedKey = state.selectedBpjsDocument?.key || '';
            const matchingSelection = documents.find(function(document) {
                return document.key === selectedKey;
            }) || null;

            if (state.selectedBpjsDocument && !matchingSelection) {
                state.selectedBpjsDocument = null;
                syncSubmitPresentation();
                updateSummary();
            }

            state.pendingBpjsDocument = matchingSelection
                || (documents.length === 1 ? documents[0] : null);

            syncPendingBpjsDocument();
        }

        function bpjsDocumentChoice(document) {
            return `
                <label class="online-document-choice">
                    <input type="radio" name="bpjs_document_choice"
                        value="${escapeHtml(document.key)}"
                        aria-label="Pilih ${escapeHtml(bpjsDocumentTypeLabel(document))} ${escapeHtml(document.number)}">
                    <span><i class="bi bi-check2-circle"></i> Pilih Dokumen</span>
                </label>
            `;
        }

        function syncSubmitPresentation() {
            if (!state.submitting) {
                elements.submit.html(
                    usesMjknFlow()
                        ? '<i class="bi bi-phone"></i><span>Proses Daftar MJKN</span>'
                        : '<i class="bi bi-send-check"></i><span>Simpan Pendaftaran</span>'
                );
            }
        }

        function syncBpjsCardNumberField() {
            const showCardNumber = isBpjsSelected();

            elements.cardNumberField.prop('hidden', !showCardNumber);
            elements.cardNumber
                .prop('disabled', !showCardNumber || !config.patientReady)
                .prop('required', showCardNumber);

            if (!showCardNumber) {
                elements.cardNumber.removeClass('is-invalid');
                $('#error-no_peserta').text('');
            }

            syncSubmitPresentation();
        }

        function initializeSelects() {
            if (!hasSelect2()) {
                return;
            }

            $('.online-select').each(function() {
                const select = $(this);
                const field = select.closest('.online-field-control');

                select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownAutoWidth: false,
                    dropdownParent: field,
                    placeholder: select.data('placeholder') || 'Pilih data',
                    allowClear: true,
                    language: {
                        noResults: function() {
                            return 'Data tidak ditemukan';
                        }
                    }
                });
            });
        }

        function clearValidation() {
            $('.invalid-feedback').text('');
            $('.online-field-control .form-control, .online-field-control .form-select').removeClass('is-invalid');
            $('.select2-container').removeClass('is-invalid');
        }

        function markInvalid(key) {
            const field = $(`#${key}`);

            field.addClass('is-invalid');

            if (field.hasClass('select2-hidden-accessible')) {
                field.next('.select2-container').addClass('is-invalid');
            }
        }

        function showValidationErrors(errors) {
            Object.keys(errors || {}).forEach(function(key) {
                const normalizedKey = key.split('.')[0];
                const message = errors[key][0] || 'Data belum valid.';

                markInvalid(normalizedKey);
                $(`#error-${normalizedKey}`).text(message);
            });
        }

        function setLoadingSchedules() {
            elements.scheduleList.html(`
                <div class="online-empty-state loading">
                    <span class="online-loader"></span>
                    <strong>Memuat jadwal</strong>
                    <small>Mohon tunggu sebentar.</small>
                </div>
            `);
            elements.scheduleCount.text('Memuat poli tersedia.');
        }

        function emptySchedule(message, detail, icon) {
            elements.scheduleList.html(`
                <div class="online-empty-state compact">
                    <i class="bi ${escapeHtml(icon || 'bi-calendar-x')}"></i>
                    <strong>${escapeHtml(message)}</strong>
                    <small>${escapeHtml(detail)}</small>
                </div>
            `);
        }

        function quotaMeta(schedule) {
            const quota = Number(schedule.kuota || 0);
            const registered = Number(schedule.terdaftar || 0);

            if (quota <= 0) {
                return {
                    label: `${registered} terdaftar`,
                    percent: 0,
                    tone: 'neutral'
                };
            }

            const percent = Math.min(100, Math.round((registered / quota) * 100));
            const tone = percent >= 90 ? 'danger' : percent >= 70 ? 'warning' : 'safe';

            return {
                label: `${registered}/${quota} terisi`,
                percent,
                tone
            };
        }

        function scheduleTime(schedule) {
            return `${schedule?.jam_mulai || '-'} - ${schedule?.jam_selesai || '-'}`;
        }

        function clinicDisplayName(schedule) {
            const mappedName = String(schedule?.nm_poli_bpjs || '')
                .trim()
                .replace(/^poliklinik\s+/i, '');

            return mappedName ? `Poliklinik ${mappedName}` : '';
        }

        function clinicOptions() {
            const clinics = new Map();

            state.schedules.forEach(function(schedule) {
                const displayName = clinicDisplayName(schedule);
                const bpjsCode = String(schedule.kd_poli_bpjs || '').trim();

                if (!displayName || !bpjsCode) {
                    return;
                }

                if (!clinics.has(schedule.kd_poli)) {
                    clinics.set(schedule.kd_poli, {
                        value: schedule.kd_poli,
                        name: displayName,
                        label: `${displayName} (${bpjsCode})`,
                    });
                }
            });

            return Array.from(clinics.values());
        }

        function doctorOptions(clinicCode) {
            const doctors = new Map();

            state.schedules
                .filter(function(schedule) {
                    return schedule.kd_poli === clinicCode;
                })
                .forEach(function(schedule) {
                    if (!doctors.has(schedule.kd_dokter)) {
                        doctors.set(schedule.kd_dokter, {
                            value: schedule.kd_dokter,
                            name: schedule.nm_dokter,
                            label: `${schedule.nm_dokter} - ${scheduleTime(schedule)}`,
                        });
                    }
                });

            return Array.from(doctors.values());
        }

        function rebuildSelect($select, options) {
            $select.empty().append(new Option('', '', false, false));

            options.forEach(function(option) {
                const element = new Option(option.label, option.value, false, false);
                $(element).attr('data-name', option.name);
                $select.append(element);
            });

            clearSelect($select);
        }

        function resetScheduleChoice() {
            state.selectedSchedule = null;
            state.selectedGuarantor = null;
            rebuildSelect(elements.clinicCode, []);
            rebuildSelect(elements.doctorCode, []);
            clearSelect(elements.guarantorCode);
            setSelectEnabled(elements.clinicCode, false);
            setSelectEnabled(elements.doctorCode, false);
            setSelectEnabled(elements.guarantorCode, false);
            syncBpjsCardNumberField();
        }

        function renderSelectedSchedule(schedule) {
            const quota = quotaMeta(schedule);

            elements.scheduleList.html(`
                <div class="online-selected-schedule">
                    <div>
                        <span>Jadwal dipilih</span>
                        <strong>${escapeHtml(clinicDisplayName(schedule))}</strong>
                        <small>${escapeHtml(schedule.nm_dokter)}</small>
                    </div>
                    <div class="online-selected-meta">
                        <span><i class="bi bi-clock"></i>${escapeHtml(scheduleTime(schedule))}</span>
                        <span><i class="bi bi-people"></i>${escapeHtml(quota.label)}</span>
                        <span><i class="bi bi-ticket-perforated"></i>No. ${escapeHtml(schedule.estimasi_no_reg)}</span>
                    </div>
                    <span class="online-quota-track">
                        <span class="online-quota-fill ${quota.tone}" style="width: ${quota.percent}%"></span>
                    </span>
                </div>
            `);
        }

        function renderSchedules(payload) {
            state.schedules = (payload.schedules || []).filter(function(schedule) {
                return Number(schedule.kuota || 0) > 0;
            });
            resetScheduleChoice();

            elements.dayLabel.text(`${payload.hari || '-'}, ${payload.tanggal_label || payload.tanggal || '-'}`);

            const clinics = clinicOptions();
            rebuildSelect(elements.clinicCode, clinics);
            setSelectEnabled(elements.clinicCode, config.patientReady && clinics.length > 0);

            if (!state.schedules.length) {
                elements.scheduleCount.text('Tidak ada poli tersedia pada tanggal ini.');
                emptySchedule('Jadwal tidak tersedia', 'Tidak ada dokter dan poli pada tanggal ini.', 'bi-calendar-x');
                updateSummary();
                return;
            }

            elements.scheduleCount.text(`${clinics.length} poli tersedia. Pilih poli untuk melihat dokter.`);
            emptySchedule('Pilih poli tujuan', 'Dokter akan muncul setelah poli dipilih.', 'bi-hospital');

            updateSummary();
        }

        function loadSchedules() {
            const date = elements.date.val();

            clearValidation();
            resetScheduleChoice();
            setSelectEnabled(elements.clinicCode, false);
            updateSummary();

            if (!date || !config.patientReady) {
                elements.scheduleCount.text('Pilih tanggal untuk memuat poli tersedia.');
                elements.dayLabel.text('Hari layanan akan menyesuaikan tanggal.');
                emptySchedule('Pilih tanggal kunjungan', 'Poli dan dokter akan difilter otomatis.', 'bi-calendar2-check');
                return;
            }

            setLoadingSchedules();

            $.ajax({
                url: config.schedulesUrl,
                method: 'GET',
                data: {
                    tgl_registrasi: date
                },
                success: function(response) {
                    renderSchedules(response.data || {});
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        showValidationErrors(xhr.responseJSON?.errors || {});
                        elements.scheduleCount.text('Tanggal belum valid.');
                        emptySchedule('Tanggal belum valid', xhr.responseJSON?.message || 'Periksa tanggal kunjungan.', 'bi-exclamation-circle');
                        return;
                    }

                    elements.scheduleCount.text('Jadwal belum dapat dimuat.');
                    emptySchedule('Jadwal belum dapat dimuat', xhr.responseJSON?.message || 'Koneksi data Khanza belum tersedia.', 'bi-wifi-off');
                    alertAction({
                        icon: 'error',
                        title: 'Gagal memuat jadwal',
                        text: xhr.responseJSON?.message || 'Koneksi data Khanza belum tersedia.'
                    });
                }
            });
        }

        function updateChoiceProgress() {
            const hasDate = Boolean(elements.date.val());
            const hasClinic = Boolean(elements.clinicCode.val());
            const hasDoctor = Boolean(state.selectedSchedule);
            const hasGuarantor = Boolean(state.selectedGuarantor);
            const hasGuarantorDetails = hasGuarantor && hasRequiredCardNumber();
            let activeStep = 1;
            let activeLabel = 'Tanggal kunjungan';

            $('.online-choice-card').removeClass('active done');
            $('.online-choice-card[data-choice="date"]').toggleClass('done', hasDate).toggleClass('active', !hasDate);
            $('.online-choice-card[data-choice="clinic"]').toggleClass('done', hasClinic).toggleClass('active', hasDate && !hasClinic);
            $('.online-choice-card[data-choice="doctor"]').toggleClass('done', hasDoctor).toggleClass('active', hasClinic && !hasDoctor);
            $('.online-choice-card[data-choice="guarantor"]').toggleClass('done', hasGuarantorDetails).toggleClass('active', hasDoctor && !hasGuarantorDetails);

            if (hasGuarantorDetails) {
                activeStep = 4;
                activeLabel = 'Siap simpan pendaftaran';
            } else if (hasDoctor) {
                activeStep = 4;
                activeLabel = isBpjsSelected() ? 'Penjamin dan nomor BPJS' : 'Penjamin';
            } else if (hasClinic) {
                activeStep = 3;
                activeLabel = 'Dokter';
            } else if (hasDate) {
                activeStep = 2;
                activeLabel = 'Poli tujuan';
            }

            elements.mobileProgressLabel.text(
                hasGuarantorDetails
                    ? 'Semua data lengkap · Siap disimpan'
                    : `Langkah ${activeStep} dari 4 · ${activeLabel}`
            );
            elements.mobileProgressBar.css(
                'width',
                `${hasGuarantorDetails ? 100 : activeStep * 25}%`
            );
            elements.mobileProgress.toggleClass('complete', hasGuarantorDetails);
        }

        function updateSubmitState() {
            const ready = config.patientReady &&
                Boolean(elements.date.val()) &&
                Boolean(state.selectedSchedule) &&
                Boolean(state.selectedGuarantor) &&
                hasRequiredCardNumber() &&
                !isBpjsIrmCombination() &&
                !state.submitting;

            elements.submit.prop('disabled', !ready);
        }

        function updateSummary() {
            const schedule = state.selectedSchedule;
            const hasClinic = Boolean(elements.clinicCode.val());
            const clinicName = schedule
                ? clinicDisplayName(schedule)
                : (hasClinic ? selectedOptionName(elements.clinicCode) : '-');
            const guarantor = state.selectedGuarantor;

            elements.summaryDate.text(elements.dayLabel.text() || '-');
            elements.summaryClinic.text(clinicName || '-');
            elements.summaryDoctor.text(schedule?.nm_dokter || '-');
            elements.summaryTime.text(schedule ? scheduleTime(schedule) : '-');
            elements.summaryGuarantor.text(guarantor?.name || '-');
            elements.summaryQueue.text(schedule?.estimasi_no_reg ? `No. ${schedule.estimasi_no_reg}` : '-');

            if (schedule && guarantor && isBpjsIrmCombination()) {
                elements.summaryState.text('Penjamin BPJ tidak tersedia untuk poli IRM.');
            } else if (schedule && guarantor && !hasRequiredCardNumber()) {
                elements.summaryState.text('Lengkapi no. kartu BPJS.');
            } else if (schedule && guarantor) {
                elements.summaryState.text(
                    usesMjknFlow()
                        ? 'Siap diproses melalui modal daftar MJKN.'
                        : 'Siap disimpan.'
                );
            } else if (schedule) {
                elements.summaryState.text('Menunggu penjamin.');
            } else if (hasClinic) {
                elements.summaryState.text('Menunggu dokter.');
            } else if (elements.date.val()) {
                elements.summaryState.text('Menunggu poli.');
            } else {
                elements.summaryState.text('Menunggu tanggal.');
            }

            updateChoiceProgress();
            updateSubmitState();
        }

        elements.openDatePicker.on('click', function() {
            const input = elements.date[0];

            if (!input || input.disabled) {
                return;
            }

            if (typeof input.showPicker === 'function') {
                input.showPicker();
                return;
            }

            input.focus();
        });

        elements.clinicCode.on('change', function() {
            const clinicCode = elements.clinicCode.val();
            const doctors = doctorOptions(clinicCode);

            clearBpjsDocumentSelection();
            state.selectedSchedule = null;
            state.selectedGuarantor = null;
            rebuildSelect(elements.doctorCode, doctors);
            clearSelect(elements.guarantorCode);
            setSelectEnabled(elements.doctorCode, Boolean(clinicCode) && doctors.length > 0);
            setSelectEnabled(elements.guarantorCode, false);
            syncBpjsCardNumberField();
            clearValidation();

            if (!clinicCode) {
                emptySchedule('Pilih poli tujuan', 'Dokter akan muncul setelah poli dipilih.', 'bi-hospital');
            } else if (!doctors.length) {
                emptySchedule('Dokter belum tersedia', 'Tidak ada dokter pada poli ini untuk tanggal tersebut.', 'bi-person-x');
            } else {
                emptySchedule('Pilih dokter', 'Penjamin akan aktif setelah dokter dipilih.', 'bi-person-heart');
            }

            updateSummary();
        });

        elements.doctorCode.on('change', function() {
            const clinicCode = elements.clinicCode.val();
            const doctorCode = elements.doctorCode.val();

            state.selectedSchedule = state.schedules.find(function(schedule) {
                return schedule.kd_poli === clinicCode && schedule.kd_dokter === doctorCode;
            }) || null;
            clearBpjsDocumentSelection();
            state.selectedGuarantor = null;
            clearSelect(elements.guarantorCode);
            setSelectEnabled(elements.guarantorCode, Boolean(state.selectedSchedule) && hasGuarantorOptions());
            syncBpjsCardNumberField();
            clearValidation();

            if (state.selectedSchedule) {
                renderSelectedSchedule(state.selectedSchedule);
            } else if (clinicCode) {
                emptySchedule('Pilih dokter', 'Penjamin akan aktif setelah dokter dipilih.', 'bi-person-heart');
            }

            updateSummary();
        });

        elements.guarantorCode.on('change', function() {
            const guarantorCode = elements.guarantorCode.val();

            clearBpjsDocumentSelection();
            state.selectedGuarantor = guarantorCode ? {
                kd_pj: guarantorCode,
                name: selectedOptionName(elements.guarantorCode),
            } : null;

            clearValidation();
            syncBpjsCardNumberField();
            updateSummary();
        });

        elements.cardNumber.on('input', function() {
            clearBpjsDocumentSelection();
            elements.cardNumber.removeClass('is-invalid');
            $('#error-no_peserta').text('');
            updateSummary();
        });

        elements.date.on('change', function() {
            clearBpjsDocumentSelection();
            loadSchedules();
        });

        elements.reset.on('click', function() {
            elements.form[0].reset();
            elements.date.val(config.today || '');
            clearBpjsDocumentSelection();
            state.selectedSchedule = null;
            state.selectedGuarantor = null;
            clearSelect(elements.clinicCode);
            rebuildSelect(elements.doctorCode, []);
            clearSelect(elements.guarantorCode);
            clearValidation();
            syncBpjsCardNumberField();
            updateSummary();
            loadSchedules();
        });

        function formatControlDate(value) {
            const parts = String(value || '').split('-');

            if (parts.length !== 3) {
                return value || '-';
            }

            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }

        function controlDetailItem(label, value, options = {}) {
            const displayValue = value === null || value === undefined || String(value).trim() === ''
                ? '-'
                : value;

            return `
                <div class="${options.wide ? 'wide' : ''} ${options.emphasis ? 'emphasis' : ''}">
                    <dt>${escapeHtml(label)}</dt>
                    <dd>${escapeHtml(displayValue)}</dd>
                </div>
            `;
        }

        function controlSummaryCard(icon, label, value, caption) {
            return `
                <div class="online-control-summary-card">
                    <span class="online-control-summary-icon"><i class="bi ${escapeHtml(icon)}"></i></span>
                    <div>
                        <small>${escapeHtml(label)}</small>
                        <strong>${escapeHtml(value || '-')}</strong>
                        <span>${escapeHtml(caption || '')}</span>
                    </div>
                </div>
            `;
        }

        function controlDetailSectionHeading(kicker, title, subtitle, icon, badge = '') {
            return `
                <div class="online-control-section-heading">
                    <span class="online-control-section-icon"><i class="bi ${escapeHtml(icon)}"></i></span>
                    <div>
                        <small>${escapeHtml(kicker)}</small>
                        <h6>${escapeHtml(title)}</h6>
                        <p>${escapeHtml(subtitle)}</p>
                    </div>
                    ${badge ? `<span class="online-control-section-badge">${escapeHtml(badge)}</span>` : ''}
                </div>
            `;
        }

        function controlProviderCard(icon, label, code, name) {
            return `
                <article class="online-control-provider-card">
                    <span><i class="bi ${escapeHtml(icon)}"></i></span>
                    <div>
                        <small>${escapeHtml(label)}</small>
                        <strong>${escapeHtml(name || '-')}</strong>
                        <p>${escapeHtml(code ? `Kode ${code}` : 'Kode provider tidak tersedia')}</p>
                    </div>
                </article>
            `;
        }

        function combineControlCodeAndName(code, name) {
            const normalizedCode = String(code || '').trim();
            const normalizedName = String(name || '').trim();

            if (normalizedCode && normalizedName) {
                return `${normalizedCode} - ${normalizedName}`;
            }

            return normalizedName || normalizedCode || '-';
        }

        function controlLetterSepStatus(value) {
            const status = String(value || '').trim().toLowerCase();

            if (status === 'sudah') {
                return 'SEP Sudah Terbit';
            }

            if (status === 'belum') {
                return 'SEP Belum Terbit';
            }

            return value ? `SEP ${value}` : 'Status SEP -';
        }

        function controlParticipantGender(value) {
            const gender = String(value || '').toUpperCase();

            if (gender === 'L') {
                return 'Laki-laki';
            }

            if (gender === 'P') {
                return 'Perempuan';
            }

            return value || '-';
        }

        function prbFieldLabel(key) {
            const labels = {
                HBA1C: 'HbA1c',
                GDP: 'Gula Darah Puasa',
                GD2JPP: 'GD 2 Jam Post Prandial',
                eGFR: 'eGFR',
                TD_Sistolik: 'Tekanan Darah Sistolik',
                TD_Diastolik: 'Tekanan Darah Diastolik',
                LDL: 'LDL',
                Rata_TD_Sistolik: 'Rata-rata TD Sistolik',
                Rata_TD_Diastolik: 'Rata-rata TD Diastolik',
                JantungKoroner: 'Jantung Koroner',
                Stroke: 'Stroke',
                VaskularPerifer: 'Vaskular Perifer',
                Aritmia: 'Aritmia',
                AtrialFibrilasi: 'Atrial Fibrilasi',
                SesakNapas3Bulan: 'Sesak Napas 3 Bulan',
                NyeriDada3Bulan: 'Nyeri Dada 3 Bulan',
                Terkontrol: 'Terkontrol',
                Gejala2xMinggu: 'Gejala ≥ 2x/Minggu',
                BangunMalam: 'Bangun Malam',
                KeterbatasanFisik: 'Keterbatasan Fisik',
                FungsiParu: 'Fungsi Paru',
                SkorMMRC: 'Skor MMRC',
                Eksaserbasi1Tahun: 'Eksaserbasi 1 Tahun',
                MampuAktivitas: 'Mampu Beraktivitas',
                Epileptik6Bulan: 'Epileptik 6 Bulan',
                EfekSampingOAB: 'Efek Samping OAB',
                HamilMenyusui: 'Hamil/Menyusui',
                Remisi: 'Remisi',
                TerapiRumatan: 'Terapi Rumatan',
                Usia: 'Usia',
                AsamUrat: 'Asam Urat',
                RemisiSLE: 'Remisi SLE',
                Hamil: 'Hamil',
                NadiIstirahat: 'Nadi Istirahat',
                SesakNapasAktivitas: 'Sesak Napas saat Aktivitas',
                NyeriDadaAktivitas: 'Nyeri Dada saat Aktivitas',
            };

            return labels[key] || String(key || '').replace(/_/g, ' ');
        }

        function showControlLetterListView() {
            state.activeControlLetterNumber = null;
            hideBootstrapModal(elements.controlLetterModal);
            setMjknStep('document');
        }

        function showControlLetterDetailView(controlLetterNumber) {
            state.activeControlLetterNumber = controlLetterNumber;
            showBootstrapModal(elements.controlLetterModal);
        }

        function renderControlLetterDetail(controlLetter) {
            const sep = controlLetter.sep && typeof controlLetter.sep === 'object'
                ? controlLetter.sep
                : null;
            const participant = sep?.peserta || null;
            const generalProvider = sep?.provider_umum || null;
            const referringProvider = sep?.provider_perujuk || null;
            const prbForm = controlLetter.form_prb && typeof controlLetter.form_prb === 'object'
                ? controlLetter.form_prb
                : null;
            const prbData = prbForm?.data && typeof prbForm.data === 'object'
                ? prbForm.data
                : {};
            const populatedPrbFields = Object.entries(prbData).filter(function(entry) {
                return entry[1] !== null
                    && entry[1] !== undefined
                    && String(entry[1]).trim() !== '';
            });
            const isSpri = String(controlLetter.jenis_kontrol || '') === '1';
            const controlType = controlLetter.nama_jenis_kontrol
                || (isSpri ? 'SPRI' : 'Surat Kontrol');
            const destinationName = controlLetter.nama_poli_tujuan
                || controlLetter.poli_tujuan
                || '-';
            const destinationCode = controlLetter.poli_tujuan
                ? `Kode poli ${controlLetter.poli_tujuan}`
                : 'Poli tujuan';
            const doctorName = controlLetter.nama_dokter || '-';
            const doctorCode = controlLetter.kode_dokter
                ? `Kode dokter ${controlLetter.kode_dokter}`
                : 'Dokter tujuan';
            const sepStatus = sep
                ? 'SEP tersedia'
                : (isSpri ? 'Tanpa SEP asal' : 'SEP tidak tersedia');

            const sepContent = sep ? `
                <section class="online-control-detail-section tone-blue">
                    ${controlDetailSectionHeading(
                        'Episode pelayanan',
                        'Informasi SEP',
                        'Referensi pelayanan yang menjadi dasar surat kontrol.',
                        'bi-file-earmark-medical',
                        sep.jenis_pelayanan || 'SEP'
                    )}
                    <dl class="online-control-detail-grid">
                        ${controlDetailItem('No. SEP', sep.no_sep, { emphasis: true })}
                        ${controlDetailItem('Tanggal SEP', formatControlDate(sep.tgl_sep))}
                        ${controlDetailItem('Jenis Pelayanan', sep.jenis_pelayanan)}
                        ${controlDetailItem('Poli', sep.poli)}
                        ${controlDetailItem('Diagnosa', sep.diagnosa, { wide: true })}
                    </dl>
                </section>

                <section class="online-control-detail-section tone-mint">
                    ${controlDetailSectionHeading(
                        'Identitas kepesertaan',
                        'Peserta BPJS',
                        'Informasi peserta yang tercatat pada SEP.',
                        'bi-person-vcard',
                        participant?.hak_kelas ? `Hak kelas ${participant.hak_kelas}` : ''
                    )}
                    <div class="online-control-patient-card">
                        <span class="online-control-patient-avatar">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <div class="online-control-patient-identity">
                            <small>Nama Peserta</small>
                            <strong>${escapeHtml(participant?.nama || '-')}</strong>
                            <p><i class="bi bi-credit-card-2-front"></i> ${escapeHtml(participant?.no_kartu || '-')}</p>
                        </div>
                        <dl class="online-control-patient-meta">
                            <div>
                                <dt>Tanggal Lahir</dt>
                                <dd>${escapeHtml(formatControlDate(participant?.tgl_lahir))}</dd>
                            </div>
                            <div>
                                <dt>Jenis Kelamin</dt>
                                <dd>${escapeHtml(controlParticipantGender(participant?.kelamin))}</dd>
                            </div>
                            <div>
                                <dt>Hak Kelas</dt>
                                <dd>${escapeHtml(participant?.hak_kelas || '-')}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="online-control-detail-section tone-violet">
                    ${controlDetailSectionHeading(
                        'Jejaring pelayanan',
                        'Provider dan Rujukan',
                        'Fasilitas kesehatan umum serta asal rujukan peserta.',
                        'bi-hospital',
                        referringProvider?.asal_rujukan
                            ? `Asal rujukan ${referringProvider.asal_rujukan}`
                            : ''
                    )}
                    <div class="online-control-provider-grid">
                        ${controlProviderCard(
                            'bi-building-check',
                            'Provider Umum',
                            generalProvider?.kode_provider,
                            generalProvider?.nama_provider
                        )}
                        ${controlProviderCard(
                            'bi-hospital',
                            'Provider Perujuk',
                            referringProvider?.kode_provider,
                            referringProvider?.nama_provider
                        )}
                    </div>
                    <dl class="online-control-detail-grid compact">
                        ${controlDetailItem('Asal Rujukan', referringProvider?.asal_rujukan)}
                        ${controlDetailItem('No. Rujukan', referringProvider?.no_rujukan)}
                        ${controlDetailItem('Tanggal Rujukan', formatControlDate(referringProvider?.tgl_rujukan))}
                    </dl>
                </section>
            ` : `
                <section class="online-control-detail-section tone-blue">
                    ${controlDetailSectionHeading(
                        'Episode pelayanan',
                        'Informasi SEP',
                        'Referensi pelayanan yang menjadi dasar surat kontrol.',
                        'bi-file-earmark-medical',
                        isSpri ? 'SPRI' : ''
                    )}
                    <div class="online-control-null-note">
                        <span><i class="bi bi-info-circle"></i></span>
                        <div>
                            <strong>Referensi SEP tidak tersedia</strong>
                            <small>${isSpri
                                ? 'SPRI (jenis kontrol 1) tidak memiliki referensi nomor SEP asal.'
                                : 'Data SEP pada respons VClaim kosong atau tidak tersedia.'}</small>
                        </div>
                    </div>
                </section>
            `;

            const prbContent = populatedPrbFields.length || prbForm?.kode_status_prb ? `
                <section class="online-control-detail-section tone-amber">
                    ${controlDetailSectionHeading(
                        'Parameter klinis',
                        'Form PRB',
                        'Nilai pemeriksaan Program Rujuk Balik yang dikirim VClaim.',
                        'bi-clipboard2-pulse',
                        `${populatedPrbFields.length} parameter`
                    )}
                    <div class="online-control-prb-grid">
                        ${prbForm?.kode_status_prb ? `
                            <div class="online-control-prb-value featured">
                                <span>Kode Status PRB</span>
                                <strong>${escapeHtml(prbForm.kode_status_prb)}</strong>
                            </div>
                        ` : ''}
                        ${populatedPrbFields.map(function(entry) {
                            return `
                                <div class="online-control-prb-value">
                                    <span>${escapeHtml(prbFieldLabel(entry[0]))}</span>
                                    <strong>${escapeHtml(entry[1])}</strong>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </section>
            ` : `
                <section class="online-control-detail-section tone-amber">
                    ${controlDetailSectionHeading(
                        'Parameter klinis',
                        'Form PRB',
                        'Nilai pemeriksaan Program Rujuk Balik yang dikirim VClaim.',
                        'bi-clipboard2-pulse'
                    )}
                    <div class="online-control-null-note neutral">
                        <span><i class="bi bi-dash-circle"></i></span>
                        <div>
                            <strong>Belum ada data Form PRB</strong>
                            <small>VClaim tidak mengirim nilai pemeriksaan PRB untuk surat ini.</small>
                        </div>
                    </div>
                </section>
            `;

            elements.controlLetterDetail.html(`
                <article class="online-control-detail-card">
                    <header class="online-control-detail-hero">
                        <div class="online-control-detail-identity">
                            <span class="online-control-document-mark">
                                <i class="bi bi-file-earmark-medical-fill"></i>
                            </span>
                            <div>
                                <span class="online-control-detail-eyebrow">
                                    <i></i> Dokumen Rencana Kontrol BPJS
                                </span>
                                <strong>${escapeHtml(controlLetter.no_surat_kontrol || '-')}</strong>
                                <small>
                                    <i class="bi bi-shield-check"></i>
                                    Data terverifikasi dari VClaim
                                </small>
                            </div>
                        </div>
                        <div class="online-control-detail-badges">
                            <span class="online-control-type-badge">
                                ${escapeHtml(controlType)}
                            </span>
                            <span class="online-control-sep-badge ${sep ? 'available' : ''}">
                                <i class="bi ${sep ? 'bi-check-circle-fill' : 'bi-info-circle-fill'}"></i>
                                ${escapeHtml(sepStatus)}
                            </span>
                        </div>
                    </header>

                    <div class="online-control-summary-strip">
                        ${controlSummaryCard(
                            'bi-calendar2-check',
                            'Rencana Kontrol',
                            formatControlDate(controlLetter.tgl_rencana_kontrol),
                            `Terbit ${formatControlDate(controlLetter.tgl_terbit)}`
                        )}
                        ${controlSummaryCard(
                            'bi-signpost-2',
                            'Poli Tujuan',
                            destinationName,
                            destinationCode
                        )}
                        ${controlSummaryCard(
                            'bi-person-heart',
                            'Dokter Tujuan',
                            doctorName,
                            doctorCode
                        )}
                        ${controlSummaryCard(
                            'bi-bookmark-check',
                            'Jenis Kontrol',
                            controlType,
                            `Kode jenis ${controlLetter.jenis_kontrol || '-'}`
                        )}
                    </div>

                    <div class="online-control-detail-content">
                    <section class="online-control-detail-section tone-slate">
                        ${controlDetailSectionHeading(
                            'Informasi dokumen',
                            'Detail Surat Kontrol',
                            'Ringkasan penerbitan dan penanggung jawab surat.',
                            'bi-card-checklist',
                            controlLetter.flag_kontrol
                                ? `Flag ${controlLetter.flag_kontrol}`
                                : ''
                        )}
                        <dl class="online-control-detail-grid">
                            ${controlDetailItem('Tanggal Terbit', formatControlDate(controlLetter.tgl_terbit))}
                            ${controlDetailItem(
                                'Poli Tujuan',
                                combineControlCodeAndName(
                                    controlLetter.poli_tujuan,
                                    controlLetter.nama_poli_tujuan
                                )
                            )}
                            ${controlDetailItem(
                                'Dokter Tujuan',
                                combineControlCodeAndName(
                                    controlLetter.kode_dokter,
                                    controlLetter.nama_dokter
                                )
                            )}
                            ${controlDetailItem('Flag Kontrol', controlLetter.flag_kontrol)}
                            ${controlDetailItem(
                                'Dokter Pembuat',
                                combineControlCodeAndName(
                                    controlLetter.kode_dokter_pembuat,
                                    controlLetter.nama_dokter_pembuat
                                ),
                                { wide: true }
                            )}
                        </dl>
                    </section>

                    ${sepContent}
                    ${prbContent}
                    </div>
                </article>
            `);
        }

        function loadControlLetterDetail(controlLetterNumber) {
            const normalizedNumber = String(controlLetterNumber || '').trim();

            if (!normalizedNumber) {
                return;
            }

            showControlLetterDetailView(normalizedNumber);

            if (state.controlLetterDetails[normalizedNumber]) {
                renderControlLetterDetail(state.controlLetterDetails[normalizedNumber]);
                return;
            }

            elements.controlLetterDetail.html(`
                <div class="online-control-detail-loading">
                    <span class="online-loader"></span>
                    <strong>Memuat detail surat kontrol...</strong>
                    <small>Mengambil data terbaru dari VClaim.</small>
                </div>
            `);

            $.ajax({
                url: String(config.controlLetterDetailUrl || '')
                    .replace('__NUMBER__', encodeURIComponent(normalizedNumber)),
                method: 'GET',
                success: function(response) {
                    const controlLetter = response.data?.surat_kontrol;

                    if (!controlLetter || typeof controlLetter !== 'object') {
                        if (state.activeControlLetterNumber !== normalizedNumber) {
                            return;
                        }

                        showControlLetterListView();
                        alertAction({
                            icon: 'warning',
                            title: 'Detail tidak ditemukan',
                            text: response.message || 'Detail surat kontrol tidak ditemukan.'
                        });
                        return;
                    }

                    state.controlLetterDetails[normalizedNumber] = controlLetter;

                    if (state.activeControlLetterNumber === normalizedNumber) {
                        renderControlLetterDetail(controlLetter);
                    }
                },
                error: function(xhr) {
                    if (state.activeControlLetterNumber !== normalizedNumber) {
                        return;
                    }

                    showControlLetterListView();
                    alertAction({
                        icon: xhr.status === 404 ? 'warning' : 'error',
                        title: xhr.status === 404
                            ? 'Detail tidak ditemukan'
                            : 'Gagal memuat detail',
                        text: xhr.responseJSON?.message
                            || 'Detail surat kontrol belum dapat dimuat dari VClaim.'
                    });
                }
            });
        }

        function renderBpjsSearchJourney(payload) {
            const controlLetters = Array.isArray(payload.surat_kontrol)
                ? payload.surat_kontrol
                : [];
            const pcare = payload.pencarian_rujukan?.pcare || null;
            const hospital = payload.pencarian_rujukan?.rumah_sakit || null;
            const source = String(payload.sumber_dokumen || '');

            function updateSource(sourceName, status, detail) {
                const card = elements.searchJourney.find(`[data-search-source="${sourceName}"]`);

                card.removeClass('searching found not-found skipped').addClass(status);
                card.find('p').text(detail);
            }

            updateSource(
                'surat_kontrol',
                controlLetters.length ? 'found' : 'not-found',
                controlLetters.length
                    ? `${controlLetters.length} surat kontrol ditemukan.`
                    : 'Tidak ditemukan, pencarian dilanjutkan ke rujukan PCare.'
            );

            if (!pcare) {
                updateSource(
                    'rujukan_pcare',
                    'skipped',
                    controlLetters.length
                        ? 'Tidak dijalankan karena surat kontrol ditemukan.'
                        : 'Pencarian tidak dijalankan.'
                );
            } else {
                updateSource(
                    'rujukan_pcare',
                    source === 'rujukan_pcare' ? 'found' : 'not-found',
                    source === 'rujukan_pcare'
                        ? 'Rujukan PCare ditemukan.'
                        : `${pcare.message || 'Tidak ditemukan'}, pencarian dilanjutkan ke RS.`
                );
            }

            if (!hospital) {
                updateSource(
                    'rujukan_rumah_sakit',
                    'skipped',
                    source === 'rujukan_pcare'
                        ? 'Tidak dijalankan karena rujukan PCare ditemukan.'
                        : 'Tidak perlu dijalankan.'
                );
            } else {
                updateSource(
                    'rujukan_rumah_sakit',
                    source === 'rujukan_rumah_sakit' ? 'found' : 'not-found',
                    source === 'rujukan_rumah_sakit'
                        ? 'Rujukan rumah sakit ditemukan.'
                        : (hospital.message || 'Rujukan rumah sakit tidak ditemukan.')
                );
            }
        }

        function renderControlLetters(payload, message) {
            const controlLetters = Array.isArray(payload.surat_kontrol)
                ? payload.surat_kontrol
                : [];
            const referrals = Array.isArray(payload.daftar_rujukan) && payload.daftar_rujukan.length
                ? payload.daftar_rujukan
                : (payload.rujukan && typeof payload.rujukan === 'object'
                    ? [payload.rujukan]
                    : []);
            const documentSource = String(payload.sumber_dokumen || '');
            const period = payload.periode?.label || '-';

            renderBpjsSearchJourney(payload);
            showControlLetterListView();

            if (referrals.length) {
                const isPcare = documentSource === 'rujukan_pcare';
                const sourceLabel = isPcare ? 'PCare' : 'Rumah Sakit';
                const documents = referrals.map(function(referral, index) {
                    const clinic = referral.poli_rujukan || {};

                    return {
                        key: `${documentSource}:${referral.no_rujukan || index}`,
                        type: 'rujukan',
                        source: documentSource,
                        number: String(referral.no_rujukan || ''),
                        meta: [
                            formatControlDate(referral.tgl_kunjungan),
                            clinic.nama || clinic.kode || ''
                        ].filter(Boolean).join(' · '),
                        response: referral,
                    };
                });

                elements.controlLetterTitle.text(`Rujukan ${sourceLabel} BPJS`);
                elements.controlLetterPeriod.text(
                    `${message || `Rujukan ${sourceLabel} ditemukan`} · fallback setelah surat kontrol tidak ditemukan`
                );
                elements.controlLetterList.html(referrals.map(function(referral, index) {
                    const participant = referral.peserta || {};
                    const diagnosis = referral.diagnosa || {};
                    const service = referral.pelayanan || {};
                    const referredClinic = referral.poli_rujukan || {};
                    const referringProvider = referral.provider_perujuk || {};
                    const participantClass = participant.hak_kelas || {};
                    const participantStatus = participant.status || {};

                    return `
                        <article class="online-control-card">
                            <header>
                                <div>
                                    <span>Rujukan ${escapeHtml(sourceLabel)}</span>
                                    <strong>${escapeHtml(referral.no_rujukan || '-')}</strong>
                                </div>
                                <small>${escapeHtml(participantStatus.nama || 'Status peserta -')}</small>
                            </header>
                            <dl>
                                <div>
                                    <dt>Pasien</dt>
                                    <dd>${escapeHtml(participant.nama || '-')}</dd>
                                </div>
                                <div>
                                    <dt>No. Kartu</dt>
                                    <dd>${escapeHtml(participant.no_kartu || '-')}</dd>
                                </div>
                                <div>
                                    <dt>Tgl. Kunjungan</dt>
                                    <dd>${escapeHtml(formatControlDate(referral.tgl_kunjungan))}</dd>
                                </div>
                                <div>
                                    <dt>Jenis Pelayanan</dt>
                                    <dd>${escapeHtml(combineControlCodeAndName(service.kode, service.nama))}</dd>
                                </div>
                                <div>
                                    <dt>Poli Rujukan</dt>
                                    <dd>${escapeHtml(combineControlCodeAndName(referredClinic.kode, referredClinic.nama))}</dd>
                                </div>
                                <div>
                                    <dt>Provider Perujuk</dt>
                                    <dd>${escapeHtml(combineControlCodeAndName(referringProvider.kode, referringProvider.nama))}</dd>
                                </div>
                                <div>
                                    <dt>Diagnosa</dt>
                                    <dd>${escapeHtml(combineControlCodeAndName(diagnosis.kode, diagnosis.nama))}</dd>
                                </div>
                                <div>
                                    <dt>Hak Kelas</dt>
                                    <dd>${escapeHtml(combineControlCodeAndName(participantClass.kode, participantClass.nama))}</dd>
                                </div>
                                <div>
                                    <dt>No. Rekam Medis</dt>
                                    <dd>${escapeHtml(participant.no_mr || '-')}</dd>
                                </div>
                                <div>
                                    <dt>Keluhan</dt>
                                    <dd>${escapeHtml(referral.keluhan || '-')}</dd>
                                </div>
                            </dl>
                            <footer class="online-control-card-actions">
                                ${bpjsDocumentChoice(documents[index])}
                            </footer>
                        </article>
                    `;
                }).join(''));
                prepareBpjsDocumentChoices(documents);
                return;
            }

            if (!controlLetters.length) {
                elements.controlLetterTitle.text('Dokumen BPJS Tidak Ditemukan');
                elements.controlLetterPeriod.text(message || 'Hasil pencarian VClaim');
                elements.controlLetterList.html(`
                    <div class="online-empty-state compact">
                        <i class="bi bi-file-earmark-x"></i>
                        <strong>Surat kontrol dan rujukan tidak ditemukan</strong>
                        <small>Tidak ada surat kontrol pada periode ${escapeHtml(period)}, rujukan PCare, maupun rujukan rumah sakit untuk nomor kartu tersebut.</small>
                    </div>
                `);
                prepareBpjsDocumentChoices([]);
                return;
            }

            elements.controlLetterTitle.text('Surat Kontrol BPJS');
            elements.controlLetterPeriod.text(
                `${message || 'Surat kontrol ditemukan'} · Periode ${period}`
            );
            const documents = controlLetters.map(function(controlLetter, index) {
                return {
                    key: `surat_kontrol:${controlLetter.no_surat_kontrol || index}`,
                    type: 'surat_kontrol',
                    source: 'surat_kontrol',
                    number: String(controlLetter.no_surat_kontrol || ''),
                    meta: [
                        formatControlDate(controlLetter.tgl_rencana_kontrol),
                        controlLetter.nama_poli_tujuan || controlLetter.poli_tujuan || ''
                    ].filter(Boolean).join(' · '),
                    response: controlLetter,
                };
            });

            elements.controlLetterList.html(controlLetters.map(function(controlLetter, index) {
                const controlType = controlLetter.nama_jenis_kontrol || controlLetter.jenis_pelayanan || 'Surat Kontrol';
                const destinationClinic = controlLetter.nama_poli_tujuan || controlLetter.poli_tujuan || '-';
                const originClinic = controlLetter.nama_poli_asal || controlLetter.poli_asal || '-';

                return `
                    <article class="online-control-card">
                        <header>
                            <div>
                                <span>${escapeHtml(controlType)}</span>
                                <strong>${escapeHtml(controlLetter.no_surat_kontrol || '-')}</strong>
                            </div>
                            <small class="${String(controlLetter.terbit_sep || '').toLowerCase() === 'belum' ? 'pending' : ''}">
                                ${escapeHtml(controlLetterSepStatus(controlLetter.terbit_sep))}
                            </small>
                        </header>
                        <dl>
                            <div>
                                <dt>Pasien</dt>
                                <dd>${escapeHtml(controlLetter.nama || '-')}</dd>
                            </div>
                            <div>
                                <dt>Tgl. Rencana Kontrol</dt>
                                <dd>${escapeHtml(formatControlDate(controlLetter.tgl_rencana_kontrol))}</dd>
                            </div>
                            <div>
                                <dt>Poli Tujuan</dt>
                                <dd>${escapeHtml(destinationClinic)}</dd>
                            </div>
                            <div>
                                <dt>Dokter</dt>
                                <dd>${escapeHtml(controlLetter.nama_dokter || '-')}</dd>
                            </div>
                            <div>
                                <dt>Poli Asal</dt>
                                <dd>${escapeHtml(originClinic)}</dd>
                            </div>
                            <div>
                                <dt>SEP Asal</dt>
                                <dd>${escapeHtml(controlLetter.no_sep_asal_kontrol || '-')}</dd>
                            </div>
                        </dl>
                        <footer class="online-control-card-actions">
                            ${bpjsDocumentChoice(documents[index])}
                            <button type="button" class="online-control-detail-button"
                                data-control-letter-number="${escapeHtml(controlLetter.no_surat_kontrol || '')}">
                                <i class="bi bi-eye"></i>
                                <span>Lihat Detail</span>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </footer>
                    </article>
                `;
            }).join(''));
            prepareBpjsDocumentChoices(documents);
        }

        elements.controlLetterList.on('change', 'input[name="bpjs_document_choice"]', function() {
            const documentKey = String($(this).val() || '');

            state.pendingBpjsDocument = state.availableBpjsDocuments.find(function(document) {
                return document.key === documentKey;
            }) || null;
            syncPendingBpjsDocument();
        });

        elements.controlLetterList.on('click', '.online-control-detail-button', function() {
            loadControlLetterDetail($(this).data('control-letter-number'));
        });

        elements.confirmDocumentChoice.on('click', function() {
            if (!state.pendingBpjsDocument || state.submitting) {
                return;
            }

            state.selectedBpjsDocument = {
                ...state.pendingBpjsDocument
            };
            previewAntrolPayload();
        });

        $('#backToBpjsDocumentChoice').on('click', function() {
            setMjknStep('document');
            syncPendingBpjsDocument();
        });

        $('#retryBpjsDocumentSearch').on('click', searchBpjsControlLetters);

        function searchBpjsControlLetters() {
            clearBpjsDocumentSelection();
            state.submitting = true;
            setMjknStep('search');
            showBootstrapModal(elements.mjknModal);
            elements.searchJourney
                .find('article')
                .removeClass('searching found not-found skipped');
            elements.searchJourney
                .find('[data-search-source="surat_kontrol"]')
                .addClass('searching')
                .find('p')
                .text('Sedang mencari surat kontrol...');
            elements.controlLetterTitle.text('Mencari Dokumen BPJS');
            elements.controlLetterPeriod.text('Urutan: Surat Kontrol → PCare → Rumah Sakit');
            elements.controlLetterList.html(`
                <div class="online-empty-state loading">
                    <span class="online-loader"></span>
                    <strong>Mencari dokumen BPJS</strong>
                    <small>Fallback dijalankan otomatis sesuai urutan.</small>
                </div>
            `);
            $('#retryBpjsDocumentSearch').prop('disabled', true);
            elements.submit
                .prop('disabled', true)
                .html('<span class="online-loader small"></span><span>Mencari</span>');

            $.ajax({
                url: config.controlLettersUrl,
                method: 'GET',
                data: {
                    tgl_registrasi: elements.date.val(),
                    no_peserta: $.trim(elements.cardNumber.val()),
                },
                success: function(response) {
                    renderControlLetters(response.data || {}, response.message);
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        showValidationErrors(xhr.responseJSON?.errors || {});
                    }

                    elements.searchJourney
                        .find('article.searching')
                        .removeClass('searching')
                        .addClass('not-found')
                        .find('p')
                        .text('Pencarian gagal dijalankan.');
                    elements.controlLetterTitle.text('Pencarian Dokumen Gagal');
                    elements.controlLetterPeriod.text(
                        xhr.responseJSON?.message || 'Dokumen BPJS belum dapat dimuat dari VClaim.'
                    );
                    elements.controlLetterList.html(`
                        <div class="online-empty-state compact">
                            <i class="bi bi-wifi-off"></i>
                            <strong>Dokumen belum dapat dimuat</strong>
                            <small>Gunakan tombol Cari Ulang untuk mencoba kembali.</small>
                        </div>
                    `);
                    alertAction({
                        icon: xhr.status === 422 ? 'warning' : 'error',
                        title: xhr.status === 422 ? 'Pencarian belum valid' : 'Gagal mencari dokumen BPJS',
                        text: xhr.responseJSON?.message || 'Dokumen BPJS belum dapat dimuat dari VClaim.'
                    });
                },
                complete: function() {
                    state.submitting = false;
                    $('#retryBpjsDocumentSearch').prop('disabled', false);
                    syncSubmitPresentation();
                    updateSubmitState();
                }
            });
        }

        function antrolRequestData() {
            const documentData = documentAntrolData(state.selectedBpjsDocument);

            return {
                no_rkm_medis: config.selectedMedicalRecordNumber,
                tgl_registrasi: elements.date.val(),
                kd_dokter: elements.doctorCode.val(),
                kd_poli: elements.clinicCode.val(),
                kd_pj: elements.guarantorCode.val(),
                no_peserta: $.trim(elements.cardNumber.val()),
                bpjs_document_type: state.selectedBpjsDocument?.type || '',
                bpjs_document_source: state.selectedBpjsDocument?.source || '',
                bpjs_document_number: state.selectedBpjsDocument?.number || '',
                bpjs_document_date: documentData.date || null,
                bpjs_document_card_number: documentData.cardNumber || null,
                bpjs_document_nik: documentData.nik || null,
                bpjs_document_phone: documentData.phone || null,
                bpjs_document_medical_record: documentData.medicalRecord || null,
                bpjs_document_clinic_code: documentData.clinicCode || null,
                bpjs_document_clinic_name: documentData.clinicName || null,
                bpjs_document_doctor_code: documentData.doctorCode || null,
                bpjs_document_doctor_name: documentData.doctorName || null,
            };
        }

        function showAntrolPayloadPreview(preview) {
            const payload = preview?.payload || {};

            state.antrolPreview = preview;

            elements.antrolPayloadJson.text(JSON.stringify(payload, null, 3));
            elements.submitMjknRegistration.prop(
                'disabled',
                !preview?.preview_hash
            );
            setMjknStep('preview');
        }

        function previewAntrolPayload() {
            if (!state.selectedBpjsDocument) {
                searchBpjsControlLetters();
                return;
            }

            state.submitting = true;
            state.antrolPreview = null;
            elements.confirmDocumentChoice
                .prop('disabled', true)
                .html('<span class="online-loader small"></span><span>Menyiapkan Payload</span>');

            $.ajax({
                url: config.antrolPreviewUrl,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken
                },
                data: antrolRequestData(),
                success: function(response) {
                    showAntrolPayloadPreview(response.data || {});
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        showValidationErrors(xhr.responseJSON?.errors || {});
                    }

                    alertAction({
                        icon: xhr.status === 422 ? 'warning' : 'error',
                        title: xhr.status === 422 ? 'Payload belum valid' : 'Gagal membuat payload',
                        text: xhr.responseJSON?.message
                            || 'Preview payload tambah antrean Antrol belum dapat dibuat.'
                    });
                },
                complete: function() {
                    state.submitting = false;
                    syncSubmitPresentation();
                    elements.confirmDocumentChoice.html(
                        '<i class="bi bi-arrow-right"></i><span>Lanjut Lihat Data Final</span>'
                    );
                    syncPendingBpjsDocument();
                    updateSubmitState();
                }
            });
        }

        elements.submitMjknRegistration.on('click', function() {
            const preview = state.antrolPreview || {};
            const payload = preview.payload || {};
            const registration = preview.final_data?.registration || {};

            if (!preview.preview_hash || state.submitting) {
                return;
            }

            confirmAction({
                icon: 'question',
                title: 'Daftarkan data final MJKN?',
                html: `
                    <div class="online-confirmation-text">
                        <strong>${escapeHtml(payload.nomorantrean || '-')}</strong>
                        <span>${escapeHtml(registration.clinic || '-')} · ${escapeHtml(registration.doctor || '-')}</span>
                        <small>Setelah dikonfirmasi, data disimpan ke Khanza dan dikirim ke BPJS.</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, daftarkan & kirim',
                cancelButtonText: 'Periksa lagi',
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                let refreshFinalData = false;

                state.submitting = true;
                elements.submitMjknRegistration
                    .prop('disabled', true)
                    .html('<span class="online-loader small"></span><span>Memproses MJKN</span>');

                $.ajax({
                    url: config.antrolSubmitUrl,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    data: {
                        ...antrolRequestData(),
                        preview_hash: preview.preview_hash,
                    },
                    success: function(response) {
                        const savedRegistration = response.data?.registration || {};
                        const antrol = response.data?.antrol || {};

                        fillResultModal(savedRegistration);
                        $('#resultStatus').text(
                            `${savedRegistration.status || '-'} / ${savedRegistration.status_bayar || '-'} · Antrol ${antrol.delivery_status || '-'}`
                        );
                        $(elements.mjknModal).one('hidden.bs.modal', function() {
                            showBootstrapModal(document.getElementById('onlineRegistrationResultModal'));
                            $('#onlineRegistrationResultModal').one('hidden.bs.modal', function() {
                                window.location.reload();
                            });
                        });
                        hideBootstrapModal(elements.mjknModal);

                        alertAction({
                            icon: antrol.sent ? 'success' : 'warning',
                            title: response.message || (
                                antrol.sent
                                    ? 'Pendaftaran MJKN berhasil.'
                                    : 'Pendaftaran tersimpan, pengiriman BPJS masih menunggu.'
                            ),
                            confirmButtonText: 'Tutup',
                        });
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};

                        if (xhr.status === 422) {
                            showValidationErrors(errors);
                            refreshFinalData = Boolean(errors.preview);
                        }

                        alertAction({
                            icon: xhr.status === 422 ? 'warning' : 'error',
                            title: refreshFinalData
                                ? 'Data final berubah'
                                : (xhr.status === 422
                                    ? 'Pendaftaran belum valid'
                                    : 'Gagal memproses MJKN'),
                            text: xhr.responseJSON?.message
                                || 'Pendaftaran MJKN belum dapat diproses.'
                        });
                    },
                    complete: function() {
                        state.submitting = false;
                        elements.submitMjknRegistration
                            .prop('disabled', false)
                            .html('<i class="bi bi-send-check"></i><span>Daftarkan &amp; Kirim ke BPJS</span>');
                        syncSubmitPresentation();
                        updateSubmitState();

                        if (refreshFinalData) {
                            previewAntrolPayload();
                        }
                    }
                });
            });
        });

        $('#copyAntrolPayload').on('click', function() {
            const payloadText = elements.antrolPayloadJson.text();
            const copied = navigator.clipboard?.writeText
                ? navigator.clipboard.writeText(payloadText)
                : new Promise(function(resolve, reject) {
                    const temporaryField = $('<textarea>')
                        .val(payloadText)
                        .css({ position: 'fixed', opacity: 0 })
                        .appendTo('body')
                        .trigger('select');

                    try {
                        document.execCommand('copy') ? resolve() : reject();
                    } catch (error) {
                        reject(error);
                    } finally {
                        temporaryField.remove();
                    }
                });

            copied
                .then(function() {
                    alertAction({
                        icon: 'success',
                        title: 'Payload JSON disalin.',
                        toast: true,
                        position: 'top-end',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                })
                .catch(function() {
                    alertAction({
                        icon: 'warning',
                        title: 'Payload belum dapat disalin',
                        text: 'Blok teks JSON lalu salin secara manual.'
                    });
                });
        });

        elements.form.on('submit', function(event) {
            event.preventDefault();
            clearValidation();

            if (!state.selectedSchedule || !state.selectedGuarantor || !hasRequiredCardNumber()) {
                if (isBpjsSelected() && !hasRequiredCardNumber()) {
                    markInvalid('no_peserta');
                    $('#error-no_peserta').text('No. kartu wajib diisi untuk penjamin BPJ.');
                }

                alertAction({
                    icon: 'warning',
                    title: 'Lengkapi pilihan',
                    text: isBpjsSelected()
                        ? 'Tanggal, poli, dokter, penjamin, dan no. kartu harus diisi.'
                        : 'Tanggal, poli, dokter, dan penjamin harus dipilih.'
                });
                return;
            }

            if (isBpjsIrmCombination()) {
                markInvalid('kd_poli');
                $('#error-kd_poli').text('Penjamin BPJ tidak dapat digunakan untuk poli IRM.');
                alertAction({
                    icon: 'warning',
                    title: 'Penjamin tidak tersedia',
                    text: 'Pendaftaran online ke poli IRM tidak dapat menggunakan penjamin BPJ.'
                });
                return;
            }

            if (usesMjknFlow()) {
                searchBpjsControlLetters();
                return;
            }

            confirmAction({
                icon: 'question',
                title: 'Simpan pendaftaran?',
                html: `
                    <div class="online-confirmation-text">
                        <strong>${escapeHtml(state.selectedSchedule.nm_poli)}</strong>
                        <span>${escapeHtml(state.selectedSchedule.nm_dokter)}</span>
                        <small>${escapeHtml(state.selectedGuarantor.name)}</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                state.submitting = true;
                elements.submit.prop('disabled', true).html('<span class="online-loader small"></span><span>Menyimpan</span>');

                $.ajax({
                    url: config.storeUrl,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    data: {
                        no_rkm_medis: config.selectedMedicalRecordNumber,
                        tgl_registrasi: elements.date.val(),
                        kd_dokter: elements.doctorCode.val(),
                        kd_poli: elements.clinicCode.val(),
                        kd_pj: elements.guarantorCode.val(),
                        no_peserta: isBpjsSelected() ? $.trim(elements.cardNumber.val()) : null,
                    },
                    success: function(response) {
                        const registration = response.data?.registration || {};

                        fillResultModal(registration);
                        showBootstrapModal(document.getElementById('onlineRegistrationResultModal'));
                        $('#onlineRegistrationResultModal').one('hidden.bs.modal', function() {
                            window.location.reload();
                        });

                        alertAction({
                            icon: 'success',
                            title: response.message || 'Pendaftaran berhasil tersimpan.',
                            toast: true,
                            position: 'top-end',
                            timer: 2600,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });

                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors(xhr.responseJSON?.errors || {});
                            alertAction({
                                icon: 'warning',
                                title: 'Pendaftaran belum valid',
                                text: xhr.responseJSON?.message || 'Periksa kembali pilihan pendaftaran.'
                            });
                            return;
                        }

                        alertAction({
                            icon: 'error',
                            title: 'Gagal menyimpan',
                            text: xhr.responseJSON?.message || 'Pendaftaran belum dapat disimpan.'
                        });
                    },
                    complete: function() {
                        state.submitting = false;
                        syncSubmitPresentation();
                        updateSubmitState();
                    }
                });
            });
        });

        elements.showPendingResult.on('click', function() {
            fillResultModal(config.pendingRegistration || {});
            showBootstrapModal(document.getElementById('onlineRegistrationResultModal'));
        });

        elements.cancelPendingRegistration.on('click', function() {
            if (state.submitting) {
                return;
            }

            const registration = config.pendingRegistration || {};
            const defaultCancellationReason = config.isRegistrationStaff
                ? 'Pendaftaran dibatalkan oleh petugas melalui E-Pasien.'
                : 'Pendaftaran dibatalkan oleh pasien melalui E-Pasien.';

            confirmAction({
                icon: 'warning',
                title: 'Batalkan pendaftaran?',
                html: `
                    <div class="online-confirmation-text">
                        <strong>${escapeHtml(registration.poli || '-')}</strong>
                        <span>${escapeHtml(registration.dokter || '-')}</span>
                        <small>No. Rawat ${escapeHtml(registration.no_rawat || '-')}</small>
                    </div>
                `,
                input: 'textarea',
                inputLabel: 'Alasan pembatalan',
                inputValue: defaultCancellationReason,
                inputPlaceholder: 'Tuliskan alasan pembatalan',
                inputAttributes: {
                    maxlength: 255,
                    autocapitalize: 'sentences',
                },
                inputValidator: function(value) {
                    if ($.trim(value).length < 5) {
                        return 'Alasan pembatalan minimal 5 karakter.';
                    }
                },
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#c2413b',
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                state.submitting = true;
                elements.cancelPendingRegistration
                    .prop('disabled', true)
                    .html('<span class="online-loader small"></span><span>Membatalkan</span>');

                $.ajax({
                    url: config.cancelUrl,
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    data: {
                        no_rawat: registration.no_rawat,
                        no_rkm_medis: registration.no_rkm_medis,
                        keterangan: $.trim(result.value || defaultCancellationReason),
                    },
                    success: function(response) {
                        alertAction({
                            icon: 'success',
                            title: response.message || 'Pendaftaran berhasil dibatalkan.',
                            confirmButtonText: 'Tutup',
                        }).then(function() {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        alertAction({
                            icon: xhr.status === 422 ? 'warning' : 'error',
                            title: xhr.status === 422
                                ? 'Pendaftaran tidak dapat dibatalkan'
                                : 'Gagal membatalkan pendaftaran',
                            text: xhr.responseJSON?.message
                                || 'Pendaftaran belum dapat dibatalkan.'
                        });
                    },
                    complete: function() {
                        state.submitting = false;
                        elements.cancelPendingRegistration
                            .prop('disabled', false)
                            .html('<i class="bi bi-x-circle"></i><span>Batal Pendaftaran</span>');
                    }
                });
            });
        });

        if (config.showNotice && !config.hasPendingRegistration) {
            showBootstrapModal(elements.noticeModal);
        }

        if (config.hasPendingRegistration) {
            fillResultModal(config.pendingRegistration || {});
            return;
        }

        initializeSelects();
        setSelectEnabled(elements.clinicCode, false);
        setSelectEnabled(elements.doctorCode, false);
        setSelectEnabled(elements.guarantorCode, false);
        syncBpjsCardNumberField();
        updateSummary();

        if (config.patientReady && elements.date.val()) {
            loadSchedules();
        }
    });
</script>

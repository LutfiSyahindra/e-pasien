<script>
    $(document).ready(function() {
        const config = window.daftarOnlineConfig || {};
        const state = {
            schedules: [],
            selectedSchedule: null,
            selectedGuarantor: null,
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
            showPendingResult: $('#showPendingRegistrationModal'),
            noticeModal: document.getElementById('onlineRegistrationNoticeModal'),
        };

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

        function hasRequiredCardNumber() {
            return !isBpjsSelected() || Boolean($.trim(elements.cardNumber.val()));
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

        function clinicOptions() {
            const clinics = new Map();

            state.schedules.forEach(function(schedule) {
                if (!clinics.has(schedule.kd_poli)) {
                    clinics.set(schedule.kd_poli, {
                        value: schedule.kd_poli,
                        name: schedule.nm_poli,
                        label: `${schedule.nm_poli} (${schedule.kd_poli})`,
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
                        <strong>${escapeHtml(schedule.nm_poli)}</strong>
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
            state.schedules = payload.schedules || [];
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

        function updateStepper() {
            const hasDate = Boolean(elements.date.val());
            const hasClinic = Boolean(elements.clinicCode.val());
            const hasDoctor = Boolean(state.selectedSchedule);
            const hasGuarantor = Boolean(state.selectedGuarantor);
            const hasGuarantorDetails = hasGuarantor && hasRequiredCardNumber();

            $('.online-step').removeClass('active done');
            $('.online-step[data-step="date"]').toggleClass('active', !hasDate).toggleClass('done', hasDate);
            $('.online-step[data-step="clinic"]').toggleClass('active', hasDate && !hasClinic).toggleClass('done', hasClinic);
            $('.online-step[data-step="doctor"]').toggleClass('active', hasClinic && !hasDoctor).toggleClass('done', hasDoctor);
            $('.online-step[data-step="guarantor"]').toggleClass('active', hasDoctor && !hasGuarantorDetails).toggleClass('done', hasGuarantorDetails);
            $('.online-step[data-step="confirm"]').toggleClass('active', hasDoctor && hasGuarantorDetails);

            $('.online-choice-card').removeClass('active done');
            $('.online-choice-card[data-choice="date"]').toggleClass('done', hasDate).toggleClass('active', !hasDate);
            $('.online-choice-card[data-choice="clinic"]').toggleClass('done', hasClinic).toggleClass('active', hasDate && !hasClinic);
            $('.online-choice-card[data-choice="doctor"]').toggleClass('done', hasDoctor).toggleClass('active', hasClinic && !hasDoctor);
            $('.online-choice-card[data-choice="guarantor"]').toggleClass('done', hasGuarantorDetails).toggleClass('active', hasDoctor && !hasGuarantorDetails);
        }

        function updateSubmitState() {
            const ready = config.patientReady &&
                Boolean(elements.date.val()) &&
                Boolean(state.selectedSchedule) &&
                Boolean(state.selectedGuarantor) &&
                hasRequiredCardNumber() &&
                !state.submitting;

            elements.submit.prop('disabled', !ready);
        }

        function updateSummary() {
            const schedule = state.selectedSchedule;
            const hasClinic = Boolean(elements.clinicCode.val());
            const clinicName = schedule?.nm_poli || (hasClinic ? selectedOptionName(elements.clinicCode) : '-');
            const guarantor = state.selectedGuarantor;

            elements.summaryDate.text(elements.dayLabel.text() || '-');
            elements.summaryClinic.text(clinicName || '-');
            elements.summaryDoctor.text(schedule?.nm_dokter || '-');
            elements.summaryTime.text(schedule ? scheduleTime(schedule) : '-');
            elements.summaryGuarantor.text(guarantor?.name || '-');
            elements.summaryQueue.text(schedule?.estimasi_no_reg ? `No. ${schedule.estimasi_no_reg}` : '-');

            if (schedule && guarantor && !hasRequiredCardNumber()) {
                elements.summaryState.text('Lengkapi no. kartu BPJS.');
            } else if (schedule && guarantor) {
                elements.summaryState.text('Siap disimpan.');
            } else if (schedule) {
                elements.summaryState.text('Menunggu penjamin.');
            } else if (hasClinic) {
                elements.summaryState.text('Menunggu dokter.');
            } else if (elements.date.val()) {
                elements.summaryState.text('Menunggu poli.');
            } else {
                elements.summaryState.text('Menunggu tanggal.');
            }

            updateStepper();
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

            state.selectedGuarantor = guarantorCode ? {
                kd_pj: guarantorCode,
                name: selectedOptionName(elements.guarantorCode),
            } : null;

            clearValidation();
            syncBpjsCardNumberField();
            updateSummary();
        });

        elements.cardNumber.on('input', function() {
            elements.cardNumber.removeClass('is-invalid');
            $('#error-no_peserta').text('');
            updateSummary();
        });

        elements.date.on('change', loadSchedules);

        elements.reset.on('click', function() {
            elements.form[0].reset();
            elements.date.val(config.today || '');
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

            confirmAction({
                icon: 'question',
                title: 'Simpan pendaftaran?',
                html: `
                    <div class="online-confirmation-text">
                        <strong>${escapeHtml(state.selectedSchedule.nm_poli)}</strong>
                        <span>${escapeHtml(state.selectedSchedule.nm_dokter)}</span>
                        <small>${escapeHtml(state.selectedGuarantor.name)}</small>
                        ${isBpjsSelected()
                            ? `<small>No. Kartu: ${escapeHtml($.trim(elements.cardNumber.val()))}</small>`
                            : ''}
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
                        elements.submit.html('<i class="bi bi-send-check"></i><span>Simpan Pendaftaran</span>');
                        updateSubmitState();
                    }
                });
            });
        });

        elements.showPendingResult.on('click', function() {
            fillResultModal(config.pendingRegistration || {});
            showBootstrapModal(document.getElementById('onlineRegistrationResultModal'));
        });

        if (config.showNotice) {
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

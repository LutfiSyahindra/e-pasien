<script>
    $(document).ready(function() {
        function confirmAction(options) {
            if (window.Swal) {
                return Swal.fire(options);
            }

            return Promise.resolve({
                isConfirmed: window.confirm(options.title || 'Lanjutkan?')
            });
        }

        function alertAction(options) {
            if (window.Swal) {
                return Swal.fire(options);
            }

            window.alert(options.text || options.title || 'Selesai');
            return Promise.resolve();
        }

        function clearValidation(form) {
            form.find('.invalid-feedback').text('');
            form.find('.form-control, .form-select').removeClass('is-invalid');
        }

        function showValidationErrors(errors) {
            Object.keys(errors).forEach(function(key) {
                const normalizedKey = key.split('.')[0];
                const fieldMap = {
                    password_confirmation: '#confirm_password',
                    roles_id: '#rolesSelect'
                };
                const field = $(fieldMap[normalizedKey] || `#${normalizedKey}`);

                field.addClass('is-invalid');
                $(`#error-${normalizedKey}`).text(errors[key][0]);
            });
        }

        function dataTableLanguage(entity) {
            return {
                processing: '<i class="bi bi-arrow-repeat"></i><span>Loading</span>',
                emptyTable: `<div class="access-empty-table"><i class="bi bi-inbox"></i><strong>Belum ada ${entity}</strong><small>Data akan tampil di sini setelah dibuat.</small></div>`,
                zeroRecords: `<div class="access-empty-table"><i class="bi bi-search"></i><strong>Tidak ada hasil</strong><small>Coba kata kunci atau filter lain.</small></div>`,
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                infoFiltered: '(difilter dari _MAX_ data)',
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                }
            };
        }

        function updateStats(stats = {}) {
            $('#userStatTotal').text(stats.total ?? 0);
            $('#userStatActive').text(stats.active ?? 0);
            $('#userStatInactive').text(stats.inactive ?? 0);
            $('#userStatWithRoles').text(stats.with_roles ?? 0);
        }

        function updateSelectedRolesCount() {
            const selectedRoles = $('#rolesSelect').val() || [];
            $('#selectedRolesCount').text(`${selectedRoles.length} dipilih`);
        }

        $('#usersModal').on('show.bs.modal', function() {
            let form = $('#signupForm');
            $('#usersModalLabel').text('Tambah User');
            $('#submitForm').html('<i class="bi bi-check2"></i><span>Simpan User</span>');
            form.trigger('reset');
            clearValidation(form);
            $('#userId').val('');
        });

        let userTable = $('#tableUsers').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            dom: 'rt<"access-datatable-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3"ip>',
            language: dataTableLanguage('user'),
            ajax: {
                url: "{{ route("users.table") }}",
                type: "GET",
                data: function(d) {
                    d.status = $('#filterUserStatus').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'roles',
                    name: 'roles',
                    orderable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, row, meta) {
                        let isActive = data == 1;
                        let checked = isActive ? 'checked' : '';
                        let badge = isActive ?
                            '<span class="access-badge green"><i class="bi bi-check2-circle"></i>Aktif</span>' :
                            '<span class="access-badge orange"><i class="bi bi-slash-circle"></i>Nonaktif</span>';
                        return `
                        <div class="access-status-cell">
                            ${badge}
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input toggle-status" data-id="${row.id}" ${checked} id="switch${row.id}">
                                <label class="form-check-label" for="switch${row.id}"></label>
                            </div>
                        </div>
                    `;
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        userTable.on('draw.dt', function() {
            userTable.columns.adjust();
        });

        $(window).on('resize.accessUsersTable', function() {
            userTable.columns.adjust();
        });

        userTable.on('xhr.dt', function(e, settings, json) {
            updateStats(json?.stats || {});
        });

        $('#searchUser').on('keyup', function() {
            userTable.search(this.value).draw();
        });

        $('#filterUserStatus').on('change', function() {
            userTable.ajax.reload();
        });

        $('#resetUserFilter').on('click', function() {
            $('#searchUser').val('');
            $('#filterUserStatus').val('');
            userTable.search('').ajax.reload();
        });

        $('#refreshUsers').on('click', function() {
            userTable.ajax.reload(null, false);
        });

        $('#tableUsers').on('change', '.toggle-status', function() {
            const toggle = $(this);
            let userId = $(this).data('id');
            let status = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route("users.updateStatus") }}",
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    id: userId
                },
                success: function(response) {
                    alertAction({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message ||
                            'Status user berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(err) {
                    toggle.prop('checked', !status);
                    alertAction({
                        icon: 'error',
                        title: 'Gagal!',
                        text: err.responseJSON?.message ||
                            'Terjadi kesalahan saat mengubah status.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $('#signupForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let userId = $('#userId').val(); // Ambil ID user jika ada
            let url = userId ? "{{ route("users.update", ":id") }}".replace(':id', userId) :
                "{{ route("users.store") }}"; // Tentukan URL
            let method = userId ? 'PUT' : 'POST'; // Tentukan metode

            clearValidation($('#signupForm'));

            confirmAction({
                title: userId ? 'Apakah Anda yakin ingin memperbarui data ini?' :
                    'Apakah Anda yakin ingin menambahkan data ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: method,
                        data: formData,
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#usersModal').modal('hide');

                                alertAction({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#signupForm')[0].reset();
                                $('#userId').val(''); // Reset ID
                                userTable.ajax.reload(null, false);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                showValidationErrors(xhr.responseJSON.errors || {});
                                return;
                            }

                            alertAction({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menyimpan user.'
                            });
                        }
                    });
                }
            });
        });


        window.editUsers = function(userId) {
            const modal = $('#usersModal');
            modal.modal('show');

            // reset form & error state
            $('#signupForm')[0].reset();
            clearValidation($('#signupForm'));

            // set hidden ID user
            $('#userId').val(userId);

            $.ajax({
                url: "{{ route("users.edit", ":id") }}".replace(':id', userId),
                method: 'GET',
                success: function(response) {
                    // ubah judul dan tombol
                    $('#usersModalLabel').text('Edit User');
                    $('#submitForm').html('<i class="bi bi-check2"></i><span>Perbarui User</span>');

                    // isi field form
                    $('#name').val(response.name);
                    $('#email').val(response.email);

                    // kalau memang ada address & phone di response,
                    // pastikan form HTML juga punya fieldnya
                },
                error: function(xhr) {
                    alertAction({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message ||
                            'Gagal mengambil data user. Silakan coba lagi.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deleteUsers = function(id) {
            // Tampilkan konfirmasi hapus
            confirmAction({
                title: 'Apakah Anda yakin?',
                text: 'Users ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("users.delete", ":id") }}".replace(':id',
                            id),
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                alertAction({
                                    icon: 'success',
                                    title: 'Dihapus!',
                                    text: response.message
                                });
                                userTable.ajax.reload(null, false); // Reload DataTables
                            } else {
                                alertAction({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            alertAction({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus users.'
                            });
                        }
                    });
                }
            });
        }

        function loadRolesOptions(selectedIds = []) {
            return $.ajax({
                url: '{{ route("users.dataRoles") }}',
                type: 'GET',
                success: function(response) {
                    const rolesSelect = $('#rolesSelect');
                    const normalizedSelectedIds = selectedIds.map(String);

                    if (rolesSelect.hasClass('select2-hidden-accessible')) {
                        rolesSelect.select2('destroy');
                    }

                    rolesSelect.empty();

                    // Tambah opsi satu per satu
                    response.forEach(function(roles) {
                        const isSelected = normalizedSelectedIds.includes(roles.id
                            .toString()) ?
                            'selected' : '';
                        rolesSelect.append(
                            `<option value="${roles.id}" ${isSelected}>${roles.name}</option>`
                        );
                    });

                    // Re-init select2
                    rolesSelect.select2({
                        dropdownParent: $('#assignRolesModal'),
                        placeholder: "Pilih roles",
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap4'
                    });

                    rolesSelect.val(normalizedSelectedIds).trigger('change');
                    updateSelectedRolesCount();
                },
                error: function() {
                    alertAction({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat data roles!'
                    });
                }
            });
        }

        window.assignRoles = function(UsersId) {
            const modal = $('#assignRolesModal');
            modal.modal('show');
            $('#assignRolesModalLabel').text('Atur Role User');
            $('#userssId').val(UsersId);
            clearValidation($('#assignRolesForm'));

            // Reset select dulu
            $('#rolesSelect').val(null).trigger('change');
            updateSelectedRolesCount();

            // Ambil roles yang sudah dimiliki user, lalu load opsi roles.
            $.ajax({
                url: "{{ route("users.getUserRoles", ":id") }}".replace(':id', UsersId),
                type: "GET",
                success: function(res) {
                    if (res.status) {
                        loadRolesOptions(res.data || []);
                    }
                },
                error: function() {
                    alertAction({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Gagal mengambil data Roles!'
                    });
                }
            });
        }

        $('#rolesSelect').on('change', updateSelectedRolesCount);

        $('#assignRolesForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            clearValidation($('#assignRolesForm'));

            $.ajax({
                url: "{{ route("users.assignRoles") }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.status === 'success') { // pakai 'status' sesuai controller
                        $('#assignRolesModal').modal('hide'); // perbaikan typo
                        $('#assignRolesForm')[0].reset(); // perbaikan typo
                        $('#rolesSelect').val(null).trigger('change');
                        updateSelectedRolesCount();
                        $('#tableUsers').DataTable().ajax.reload(null, false);

                        alertAction({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message
                        });
                    } else {
                        alertAction({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        showValidationErrors(xhr.responseJSON.errors || {});
                        return;
                    }

                    alertAction({
                        icon: 'error',
                        title: 'Oops...',
                        text: xhr.responseJSON?.message ||
                            'Terjadi kesalahan server!'
                    });
                }
            });
        });


    });
</script>

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
                    permissions_id: '#permissionsSelect'
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
            $('#roleStatTotal').text(stats.total ?? 0);
            $('#roleStatSystem').text(stats.system ?? 0);
            $('#roleStatWithPermissions').text(stats.with_permissions ?? 0);
            $('#roleStatAssignments').text(stats.assignments ?? 0);
        }

        function updateSelectedPermissionsCount() {
            const selectedPermissions = $('#permissionsSelect').val() || [];
            $('#selectedPermissionsCount').text(`${selectedPermissions.length} dipilih`);
        }

        $('#rolesModal').on('show.bs.modal', function() {
            let form = $('#roleForm');
            $('#rolesModalLabel').text('Tambah Role');
            $('#submitRoleForm').html('<i class="bi bi-check2"></i><span>Simpan Role</span>');
            form.trigger('reset');
            clearValidation(form);
            $('#roleId').val('');
        });

        let roleTable = $('#tableRoles').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            dom: 'rt<"access-datatable-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3"ip>',
            language: dataTableLanguage('role'),
            ajax: {
                url: "{{ route("roles.table") }}",
                type: "GET",
                data: function(d) {
                    d.type = $('#filterRoleType').val();
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
                    data: 'guard_name',
                    name: 'guard_name'
                },
                {
                    data: 'permissions',
                    name: 'permissions',
                    orderable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'type',
                    name: 'type',
                    visible: false,
                    searchable: true
                }
            ]
        });

        roleTable.on('draw.dt', function() {
            roleTable.columns.adjust();
        });

        $(window).on('resize.accessRolesTable', function() {
            roleTable.columns.adjust();
        });

        roleTable.on('xhr.dt', function(e, settings, json) {
            updateStats(json?.stats || {});
        });

        $('#searchRole').on('keyup', function() {
            roleTable.search(this.value).draw();
        });

        $('#filterRoleType').on('change', function() {
            roleTable.ajax.reload();
        });

        $('#resetRoleFilter').on('click', function() {
            $('#searchRole').val('');
            $('#filterRoleType').val('');
            roleTable.search('').ajax.reload();
        });

        $('#refreshRoles').on('click', function() {
            roleTable.ajax.reload(null, false);
        });

        $('#roleForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let roleId = $('#roleId').val();
            let url = roleId ? "{{ route("roles.update", ":id") }}".replace(':id', roleId) :
                "{{ route("roles.store") }}";
            let method = roleId ? 'PUT' : 'POST';

            clearValidation($('#roleForm'));

            confirmAction({
                title: roleId ? 'Apakah Anda yakin ingin memperbarui role ini?' :
                    'Apakah Anda yakin ingin menambahkan role ini?',
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
                                $('#rolesModal').modal('hide');

                                alertAction({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#roleForm')[0].reset();
                                $('#roleId').val('');
                                roleTable.ajax.reload(null, false);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                showValidationErrors(xhr.responseJSON.errors || {
                                    name: [xhr.responseJSON.message]
                                });
                                return;
                            }

                            alertAction({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan role.'
                            });
                        }
                    });
                }
            });
        });

        window.editRoles = function(roleId) {
            const modal = $('#rolesModal');
            modal.modal('show');

            $('#roleForm')[0].reset();
            clearValidation($('#roleForm'));
            $('#roleId').val(roleId);

            $.ajax({
                url: "{{ route("roles.edit", ":id") }}".replace(':id', roleId),
                method: 'GET',
                success: function(response) {
                    $('#rolesModalLabel').text('Edit Role');
                    $('#submitRoleForm').html('<i class="bi bi-check2"></i><span>Perbarui Role</span>');

                    $('#name').val(response.name);
                },
                error: function(xhr) {
                    alertAction({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Gagal mengambil data role. Silakan coba lagi.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deleteRoles = function(id) {
            confirmAction({
                title: 'Apakah Anda yakin?',
                text: 'Role ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route("roles.delete", ":id") }}".replace(':id', id),
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
                                roleTable.ajax.reload(null, false);
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
                                text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus role.'
                            });
                        }
                    });
                }
            });
        }

        function loadPermissionsOptions(selectedIds = []) {
            return $.ajax({
                url: '{{ route("roles.dataPermissions") }}',
                type: 'GET',
                success: function(response) {
                    const permissionsSelect = $('#permissionsSelect');
                    const normalizedSelectedIds = selectedIds.map(String);

                    if (permissionsSelect.hasClass('select2-hidden-accessible')) {
                        permissionsSelect.select2('destroy');
                    }

                    permissionsSelect.empty();

                    response.forEach(function(permission) {
                        const isSelected = normalizedSelectedIds.includes(permission.id.toString()) ?
                            'selected' : '';
                        permissionsSelect.append(
                            `<option value="${permission.id}" ${isSelected}>${permission.name}</option>`
                        );
                    });

                    permissionsSelect.select2({
                        dropdownParent: $('#assignPermissionsModal'),
                        placeholder: "Pilih permissions",
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap4'
                    });

                    permissionsSelect.val(normalizedSelectedIds).trigger('change');
                    updateSelectedPermissionsCount();
                },
                error: function() {
                    alertAction({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat data permissions!'
                    });
                }
            });
        }

        window.assignPermissions = function(roleId) {
            const modal = $('#assignPermissionsModal');
            modal.modal('show');
            $('#assignPermissionsModalLabel').text('Atur Permission Role');
            $('#assignRoleId').val(roleId);
            clearValidation($('#assignPermissionsForm'));

            $('#permissionsSelect').val(null).trigger('change');
            updateSelectedPermissionsCount();

            $.ajax({
                url: "{{ route("roles.getRolePermissions", ":id") }}".replace(':id', roleId),
                type: "GET",
                success: function(res) {
                    if (res.status) {
                        loadPermissionsOptions(res.data || []);
                    }
                },
                error: function() {
                    alertAction({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Gagal mengambil data permissions!'
                    });
                }
            });
        }

        $('#permissionsSelect').on('change', updateSelectedPermissionsCount);

        $('#assignPermissionsForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            clearValidation($('#assignPermissionsForm'));

            $.ajax({
                url: "{{ route("roles.assignPermissions") }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#assignPermissionsModal').modal('hide');
                        $('#assignPermissionsForm')[0].reset();
                        $('#permissionsSelect').val(null).trigger('change');
                        updateSelectedPermissionsCount();
                        $('#tableRoles').DataTable().ajax.reload(null, false);

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
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan server!'
                    });
                }
            });
        });
    });
</script>

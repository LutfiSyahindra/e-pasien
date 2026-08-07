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
                const field = $(`#${normalizedKey}`);

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
            $('#permissionStatTotal').text(stats.total ?? 0);
            $('#permissionStatProtected').text(stats.protected ?? 0);
            $('#permissionStatAssigned').text(stats.assigned ?? 0);
            $('#permissionStatUnassigned').text(stats.unassigned ?? 0);
        }

        $('#permissionsModal').on('show.bs.modal', function() {
            let form = $('#permissionForm');
            $('#permissionsModalLabel').text('Tambah Permission');
            $('#submitPermissionForm').html(
                '<i class="bi bi-check2"></i><span>Simpan Permission</span>');
            form.trigger('reset');
            clearValidation(form);
            $('#permissionId').val('');
        });

        let permissionTable = $('#tablePermissions').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            dom: 'rt<"access-datatable-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3"ip>',
            language: dataTableLanguage('permission'),
            ajax: {
                url: "{{ route("permissions.table") }}",
                type: "GET",
                data: function(d) {
                    d.type = $('#filterPermissionType').val();
                }
            },
            createdRow: function(row) {
                const labels = ['No', 'Permission', 'Guard', 'Roles', 'Aksi'];

                $('td', row).each(function(index) {
                    this.dataset.label = labels[index] || '';
                });
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
                    data: 'roles',
                    name: 'roles',
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

        permissionTable.on('draw.dt', function() {
            permissionTable.columns.adjust();
        });

        $(window).on('resize.accessPermissionsTable', function() {
            permissionTable.columns.adjust();
        });

        permissionTable.on('xhr.dt', function(e, settings, json) {
            updateStats(json?.stats || {});
        });

        let permissionSearchTimer;
        $('#searchPermission').on('input', function() {
            const value = this.value;
            clearTimeout(permissionSearchTimer);
            permissionSearchTimer = setTimeout(function() {
                permissionTable.search(value).draw();
            }, 300);
        });

        $('#filterPermissionType').on('change', function() {
            permissionTable.ajax.reload();
        });

        $('#resetPermissionFilter').on('click', function() {
            clearTimeout(permissionSearchTimer);
            $('#searchPermission').val('');
            $('#filterPermissionType').val('');
            permissionTable.search('').ajax.reload();
        });

        $('#refreshPermissions').on('click', function() {
            permissionTable.ajax.reload(null, false);
        });

        $('#permissionForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let permissionId = $('#permissionId').val();
            let url = permissionId ? "{{ route("permissions.update", ":id") }}".replace(':id', permissionId) :
                "{{ route("permissions.store") }}";
            let method = permissionId ? 'PUT' : 'POST';

            clearValidation($('#permissionForm'));

            confirmAction({
                title: permissionId ? 'Apakah Anda yakin ingin memperbarui permission ini?' :
                    'Apakah Anda yakin ingin menambahkan permission ini?',
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
                                $('#permissionsModal').modal('hide');

                                alertAction({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#permissionForm')[0].reset();
                                $('#permissionId').val('');
                                permissionTable.ajax.reload(null, false);
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
                                text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan permission.'
                            });
                        }
                    });
                }
            });
        });

        window.editPermissions = function(permissionId) {
            const modal = $('#permissionsModal');
            modal.modal('show');

            $('#permissionForm')[0].reset();
            clearValidation($('#permissionForm'));
            $('#permissionId').val(permissionId);

            $.ajax({
                url: "{{ route("permissions.edit", ":id") }}".replace(':id', permissionId),
                method: 'GET',
                success: function(response) {
                    $('#permissionsModalLabel').text('Edit Permission');
                    $('#submitPermissionForm').html(
                        '<i class="bi bi-check2"></i><span>Perbarui Permission</span>');

                    $('#name').val(response.name);
                },
                error: function(xhr) {
                    alertAction({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Gagal mengambil data permission. Silakan coba lagi.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deletePermissions = function(id) {
            confirmAction({
                title: 'Apakah Anda yakin?',
                text: 'Permission ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route("permissions.delete", ":id") }}".replace(':id', id),
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
                                permissionTable.ajax.reload(null, false);
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
                                    'Terjadi kesalahan saat menghapus permission.'
                            });
                        }
                    });
                }
            });
        }
    });
</script>

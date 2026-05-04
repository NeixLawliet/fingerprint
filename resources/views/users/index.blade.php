@extends('layouts.app')
@section('title', 'Users')

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-users me-2 text-primary"></i>Data Users</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Tambah User
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dtUsers" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Device ID</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th style="width:90px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CREATE / EDIT --}}
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUserTitle">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" placeholder="Nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="userEmail" placeholder="email@domain.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="userPhone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Device ID</label>
                        <input type="text" class="form-control" id="userDeviceId" placeholder="ESP32-001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="userIsActive">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12" id="passwordField">
                        <label class="form-label">Password <span class="text-danger" id="pwRequired">*</span></label>
                        <input type="password" class="form-control" id="userPassword" placeholder="Minimal 8 karakter">
                        <small class="text-muted" id="pwHint" style="display:none">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveUser">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    // ===== DATATABLE =====
    var dt = $('#dtUsers').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/users_datatables',
            type: 'POST'
        },
        columns: [
            { data: 'id',        name: 'id',        width: '50px' },
            { data: 'name',      name: 'name' },
            { data: 'email',     name: 'email' },
            { data: 'phone',     name: 'phone', defaultContent: '-' },
            { data: 'device_id', name: 'device_id', defaultContent: '-' },
            {
                data: 'is_active', name: 'is_active', orderable: false,
                render: function (v) {
                    return v == 1
                        ? '<span class="badge-active">Active</span>'
                        : '<span class="badge-inactive">Inactive</span>';
                }
            },
            {
                data: 'created_at', name: 'created_at',
                render: function (v) { return v ? v.substring(0, 10) : '-'; }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false,
                render: function (id) {
                    return '<button class="btn btn-warning btn-sm btn-edit me-1" data-id="' + id + '" style="border-radius:6px;width:28px;height:28px;padding:0">'
                        + '<i class="fas fa-edit" style="font-size:11px"></i></button>'
                        + '<button class="btn btn-danger btn-sm btn-delete" data-id="' + id + '" style="border-radius:6px;width:28px;height:28px;padding:0">'
                        + '<i class="fas fa-trash" style="font-size:11px"></i></button>';
                }
            }
        ],
        language: { processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...' },
        order: [[0, 'desc']],
        pageLength: 10
    });

    // ===== OPEN CREATE MODAL =====
    $('#btnCreate').on('click', function () {
        $('#modalUserTitle').text('Tambah User');
        $('#userId').val('');
        $('#userName, #userEmail, #userPhone, #userDeviceId, #userPassword').val('');
        $('#userIsActive').val('1');
        $('#pwRequired').show();
        $('#pwHint').hide();
        new bootstrap.Modal('#modalUser').show();
    });

    // ===== OPEN EDIT MODAL =====
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/api/users/' + id, function (d) {
            $('#modalUserTitle').text('Edit User');
            $('#userId').val(d.id);
            $('#userName').val(d.name);
            $('#userEmail').val(d.email);
            $('#userPhone').val(d.phone);
            $('#userDeviceId').val(d.device_id);
            $('#userIsActive').val(d.is_active ? '1' : '0');
            $('#userPassword').val('');
            $('#pwRequired').hide();
            $('#pwHint').show();
            new bootstrap.Modal('#modalUser').show();
        });
    });

    // ===== SAVE =====
    $('#btnSaveUser').on('click', function () {
        var id = $('#userId').val();
        var data = {
            name:      $('#userName').val(),
            email:     $('#userEmail').val(),
            phone:     $('#userPhone').val(),
            device_id: $('#userDeviceId').val(),
            is_active: $('#userIsActive').val()
        };
        var pw = $('#userPassword').val();
        if (pw) data.password = pw;

        if (!data.name || !data.email) { toast('Nama dan email wajib diisi', 'warning'); return; }

        var url    = id ? '/api/users/' + id : '/api/users';
        var method = id ? 'PUT' : 'POST';

        $('#btnSaveUser').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: url, type: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function (r) {
                bootstrap.Modal.getInstance('#modalUser').hide();
                dt.ajax.reload(null, false);
                toast(id ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                toast(msg, 'error');
            },
            complete: function () {
                $('#btnSaveUser').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan');
            }
        });
    });

    // ===== DELETE =====
    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        confirmDelete(function () {
            $.ajax({
                url: '/api/users/' + id, type: 'DELETE',
                success: function () {
                    dt.ajax.reload(null, false);
                    toast('Data berhasil dihapus');
                },
                error: function () { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

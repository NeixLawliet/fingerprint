@extends('layouts.app')
@section('title', 'Users')

@push('styles')
<style>
/* Animasi fingerprint scan */
@keyframes pulse-ring {
    0%   { transform: scale(.8);  opacity: 1; }
    100% { transform: scale(1.6); opacity: 0; }
}
@keyframes fp-bounce {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-6px); }
}
.fp-ring {
    position: absolute; inset: -12px;
    border: 3px solid #5c6bc0;
    border-radius: 50%;
    animation: pulse-ring 1.5s ease-out infinite;
}
.fp-icon-wrap {
    position: relative; display: inline-block;
    animation: fp-bounce 2s ease-in-out infinite;
}
.step-badge {
    width: 28px; height: 28px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.step-line {
    width: 2px; height: 24px; background: #e5e7eb;
    margin: 4px auto;
}
</style>
@endpush

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
                        <th>ID</th><th>Nama</th><th>Email</th><th>Phone</th>
                        <th>Role</th><th>Status</th><th>Fingerprint</th><th>Dibuat</th>
                        <th style="width:90px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL STEP 1 — FORM DATA USER
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalForm" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Tambah User Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Progress step indicator --}}
                <div class="d-flex align-items-center gap-3 mb-4 px-2">
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-primary text-white">1</div>
                        <small class="text-primary fw-semibold mt-1" style="font-size:11px">Data</small>
                    </div>
                    <div class="flex-grow-1" style="height:2px;background:#e5e7eb"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-light text-muted border" id="step2badge">2</div>
                        <small class="text-muted mt-1" style="font-size:11px">Sidik Jari</small>
                    </div>
                    <div class="flex-grow-1" style="height:2px;background:#e5e7eb"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-light text-muted border" id="step3badge">3</div>
                        <small class="text-muted mt-1" style="font-size:11px">Selesai</small>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fName" placeholder="Nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="fEmail" placeholder="email@domain.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="fPhone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="fRole">
                            <option value="user">User Biasa</option>
                            <option value="admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Device ID</label>
                        <input type="text" class="form-control" id="fDeviceId" placeholder="ESP32-001">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnNext">
                    Lanjut — Daftarkan Sidik Jari
                    <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL STEP 2 — TUNGGU TAP JARI (REAL-TIME)
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalFinger" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-fingerprint me-2"></i>Scan Sidik Jari
                </h5>
            </div>
            <div class="modal-body text-center py-4">

                {{-- Nama user --}}
                <div class="mb-3">
                    <span class="badge bg-primary px-3 py-2" style="font-size:13px" id="fpUserName">-</span>
                </div>

                {{-- Animasi fingerprint --}}
                <div class="mb-4" style="height:90px;display:flex;align-items:center;justify-content:center" id="fpAnimArea">
                    <div class="fp-icon-wrap">
                        <div class="fp-ring"></div>
                        <i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i>
                    </div>
                </div>

                {{-- Status teks --}}
                <p class="fw-semibold mb-1" id="fpStatusText" style="font-size:15px">
                    Silakan tempel jari pada alat
                </p>
                <p class="text-muted mb-3" style="font-size:12px" id="fpSubText">
                    Alat sedang menunggu sidik jari Anda...
                </p>

                {{-- Progress bar countdown --}}
                <div class="progress mb-2" style="height:6px;border-radius:4px">
                    <div class="progress-bar bg-primary" id="fpCountdownBar"
                         style="width:100%;transition:width 1s linear"></div>
                </div>
                <small class="text-muted" id="fpCountdownText">5:00</small>

            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-outline-secondary btn-sm" id="btnCancelFinger">
                    <i class="fas fa-times me-1"></i>Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL STEP 3 — BERHASIL / GAGAL
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalDone" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div id="doneIcon" class="mb-3" style="font-size:56px"></div>
                <h5 id="doneTitle" class="fw-bold mb-1"></h5>
                <p  id="doneMsg"   class="text-muted mb-0" style="font-size:13px"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-primary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" id="editName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="editPhone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="editRole">
                            <option value="user">User Biasa</option>
                            <option value="admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editIsActive">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveEdit">
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

    // ── DataTable ─────────────────────────────────────────────────────
    var dt = $('#dtUsers').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '/api/users_datatables', type: 'POST' },
        columns: [
            { data: 'id',        name: 'id',        width: '50px' },
            { data: 'name',      name: 'name' },
            { data: 'email',     name: 'email',     defaultContent: '-' },
            { data: 'phone',     name: 'phone',     defaultContent: '-' },
            {
                data: 'role', name: 'role',
                render: v => v === 'admin'
                    ? '<span class="badge bg-danger" style="font-size:11px">Admin</span>'
                    : '<span class="badge bg-secondary" style="font-size:11px">User</span>'
            },
            {
                data: 'is_active', name: 'is_active', orderable: false,
                render: v => v == 1
                    ? '<span class="badge-active">Active</span>'
                    : '<span class="badge-inactive">Inactive</span>'
            },
            {
                data: 'id', name: 'fp_status', orderable: false, searchable: false,
                render: id => `<span class="fp-badge" id="fpb-${id}"><i class="fas fa-spinner fa-spin text-muted" style="font-size:11px"></i></span>`
            },
            {
                data: 'created_at', name: 'created_at',
                render: v => v ? v.substring(0,10) : '-'
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false,
                render: id =>
                    `<button class="btn btn-warning btn-sm btn-edit me-1" data-id="${id}" style="border-radius:6px;width:28px;height:28px;padding:0"><i class="fas fa-edit" style="font-size:11px"></i></button>` +
                    `<button class="btn btn-danger btn-sm btn-delete" data-id="${id}" style="border-radius:6px;width:28px;height:28px;padding:0"><i class="fas fa-trash" style="font-size:11px"></i></button>`
            }
        ],
        language: { processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...' },
        order: [[0, 'desc']], pageLength: 10
    });

    // Cek status fingerprint tiap row
    dt.on('draw', () => {
        $('#dtUsers tbody tr').each(function () {
            const id = $(this).find('.btn-edit').data('id');
            if (!id) return;
            $.get(`/api/fingerprints?user_id=${id}&all=1`, r => {
                const rows = r.data || r;
                const has  = rows.some(f => f.user_id == id);
                $(`#fpb-${id}`).html(has
                    ? '<span class="badge-match"><i class="fas fa-fingerprint me-1"></i>Ada</span>'
                    : '<span class="badge-inactive">Belum</span>');
            });
        });
    });

    // ── STEP 1: Buka form ─────────────────────────────────────────────
    let activeSessionId = null;
    let pollInterval    = null;
    let countdownTimer  = null;
    let secondsLeft     = 300;

    $('#btnCreate').on('click', () => {
        $('#fName, #fEmail, #fPhone, #fDeviceId').val('');
        $('#fRole').val('user');
        new bootstrap.Modal('#modalForm').show();
    });

    // ── STEP 2: Submit form → buat user + session → tampil scan modal ──
    $('#btnNext').on('click', () => {
        const name = $('#fName').val().trim();
        if (!name) { toast('Nama wajib diisi', 'warning'); return; }

        $('#btnNext').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Memproses...');

        $.ajax({
            url: '/api/registration/start',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                name:      name,
                email:     $('#fEmail').val(),
                phone:     $('#fPhone').val(),
                role:      $('#fRole').val(),
                device_id: $('#fDeviceId').val()
            }),
            success(r) {
                activeSessionId = r.session_id;
                bootstrap.Modal.getInstance('#modalForm').hide();
                openFingerModal(r.user_name);
            },
            error(xhr) {
                toast(xhr.responseJSON?.message || 'Gagal membuat user', 'error');
            },
            complete() {
                $('#btnNext').prop('disabled', false)
                    .html('Lanjut — Daftarkan Sidik Jari <i class="fas fa-arrow-right ms-1"></i>');
            }
        });
    });

    // ── Buka modal scan & mulai polling ──────────────────────────────
    function openFingerModal(userName) {
        $('#fpUserName').text(userName);
        $('#fpStatusText').text('Silakan tempel jari pada alat');
        $('#fpSubText').text('Alat sedang menunggu sidik jari Anda...');
        $('#fpCountdownBar').css('width', '100%').removeClass('bg-danger').addClass('bg-primary');
        $('#fpAnimArea').html(`
            <div class="fp-icon-wrap">
                <div class="fp-ring"></div>
                <i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i>
            </div>`);

        secondsLeft = 300;
        updateCountdown();

        new bootstrap.Modal('#modalFinger').show();
        startPolling();
        startCountdown();
    }

    function updateCountdown() {
        const m = String(Math.floor(secondsLeft / 60)).padStart(2,'0');
        const s = String(secondsLeft % 60).padStart(2,'0');
        $('#fpCountdownText').text(`${m}:${s}`);
        const pct = (secondsLeft / 300) * 100;
        $('#fpCountdownBar').css('width', pct + '%');
        if (secondsLeft <= 30) {
            $('#fpCountdownBar').removeClass('bg-primary').addClass('bg-danger');
        }
    }

    function startCountdown() {
        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            secondsLeft--;
            updateCountdown();
            if (secondsLeft <= 0) {
                clearInterval(countdownTimer);
                onTimeout();
            }
        }, 1000);
    }

    // ── Polling status sesi setiap 2 detik ───────────────────────────
    function startPolling() {
        clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            if (!activeSessionId) return;
            $.get(`/api/registration/status/${activeSessionId}`, r => {
                if (r.status === 'scanning') {
                    $('#fpStatusText').text('Alat sedang memindai jari...');
                    $('#fpSubText').text('Tahan jari di sensor hingga selesai');
                    $('#fpAnimArea').html(`
                        <div class="fp-icon-wrap">
                            <div class="fp-ring"></div>
                            <i class="fas fa-fingerprint" style="font-size:60px;color:#f59e0b;position:relative;z-index:1"></i>
                        </div>`);
                }
                if (r.status === 'complete') {
                    stopPolling();
                    onComplete();
                }
                if (r.status === 'failed' || r.status === 'expired') {
                    stopPolling();
                    onFailed();
                }
            });
        }, 2000);
    }

    function stopPolling() {
        clearInterval(pollInterval);
        clearInterval(countdownTimer);
        activeSessionId = null;
    }

    function onComplete() {
        bootstrap.Modal.getInstance('#modalFinger').hide();
        dt.ajax.reload(null, false);
        $('#doneIcon').html('<i class="fas fa-check-circle text-success"></i>');
        $('#doneTitle').text('Berhasil Terdaftar!');
        $('#doneMsg').text('Sidik jari berhasil direkam dan user sudah aktif.');
        new bootstrap.Modal('#modalDone').show();
    }

    function onFailed() {
        bootstrap.Modal.getInstance('#modalFinger').hide();
        $('#doneIcon').html('<i class="fas fa-times-circle text-danger"></i>');
        $('#doneTitle').text('Registrasi Gagal');
        $('#doneMsg').text('Sidik jari tidak berhasil direkam. User tersimpan tanpa fingerprint.');
        new bootstrap.Modal('#modalDone').show();
        dt.ajax.reload(null, false);
    }

    function onTimeout() {
        if (!activeSessionId) return;
        $.post(`/api/registration/cancel/${activeSessionId}`);
        stopPolling();
        onFailed();
    }

    // ── Batalkan sesi ─────────────────────────────────────────────────
    $('#btnCancelFinger').on('click', () => {
        if (activeSessionId) {
            $.post(`/api/registration/cancel/${activeSessionId}`);
        }
        stopPolling();
        bootstrap.Modal.getInstance('#modalFinger').hide();
        dt.ajax.reload(null, false);
        toast('Registrasi dibatalkan');
    });

    // ── Edit user ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        $.get(`/api/users/${id}`, d => {
            $('#editId').val(d.id);
            $('#editName').val(d.name);
            $('#editEmail').val(d.email);
            $('#editPhone').val(d.phone);
            $('#editRole').val(d.role || 'user');
            $('#editIsActive').val(d.is_active ? '1' : '0');
            new bootstrap.Modal('#modalEdit').show();
        });
    });

    $('#btnSaveEdit').on('click', () => {
        const id = $('#editId').val();
        const data = {
            name:      $('#editName').val(),
            email:     $('#editEmail').val(),
            phone:     $('#editPhone').val(),
            role:      $('#editRole').val(),
            is_active: $('#editIsActive').val()
        };
        $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: `/api/users/${id}`, type: 'PUT',
            data: JSON.stringify(data), contentType: 'application/json',
            success() {
                bootstrap.Modal.getInstance('#modalEdit').hide();
                dt.ajax.reload(null, false);
                toast('Data berhasil diperbarui');
            },
            error(xhr) { toast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'error'); },
            complete() { $('#btnSaveEdit').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan'); }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        confirmDelete(() => {
            $.ajax({
                url: `/api/users/${id}`, type: 'DELETE',
                success() { dt.ajax.reload(null, false); toast('Data berhasil dihapus'); },
                error()   { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

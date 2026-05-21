@extends('layouts.app')
@section('title', 'Karyawan')

@push('styles')
<style>
@keyframes pulse-ring {
    0%   { transform: scale(.8);  opacity: 1; }
    100% { transform: scale(1.6); opacity: 0; }
}
@keyframes fp-bounce {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-6px); }
}
.fp-ring { position: absolute; inset: -12px; border: 3px solid #5c6bc0; border-radius: 50%; animation: pulse-ring 1.5s ease-out infinite; }
.fp-icon-wrap { position: relative; display: inline-block; animation: fp-bounce 2s ease-in-out infinite; }
.step-badge { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
</style>
@endpush

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-users me-2 text-primary"></i>Data Karyawan</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Daftarkan Karyawan
        </button>
    </div>
    <div class="card-body">
        <table id="dtUsers" class="table table-hover w-100">
            <thead><tr>
                <th>ID</th><th>Nama</th><th>Page Sensor</th><th>Device</th><th>Terdaftar</th><th style="width:80px">Aksi</th>
            </tr></thead>
        </table>
    </div>
</div>

{{-- MODAL STEP 1: Form nama --}}
<div class="modal fade" id="modalForm" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Daftarkan Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-4 px-1">
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-primary text-white">1</div>
                        <small class="text-primary fw-semibold mt-1" style="font-size:11px">Data</small>
                    </div>
                    <div class="flex-grow-1" style="height:2px;background:#e5e7eb"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-light text-muted border">2</div>
                        <small class="text-muted mt-1" style="font-size:11px">Sidik Jari</small>
                    </div>
                    <div class="flex-grow-1" style="height:2px;background:#e5e7eb"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="step-badge bg-light text-muted border">3</div>
                        <small class="text-muted mt-1" style="font-size:11px">Selesai</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="fName" placeholder="Nama karyawan">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnNext">
                    Lanjut &mdash; Scan Sidik Jari <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL STEP 2: Tunggu scan --}}
<div class="modal fade" id="modalFinger" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fingerprint me-2"></i>Scan Sidik Jari</h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <span class="badge bg-primary px-3 py-2" style="font-size:13px" id="fpUserName">-</span>
                </div>
                <div class="mb-4" style="height:90px;display:flex;align-items:center;justify-content:center" id="fpAnimArea">
                    <div class="fp-icon-wrap">
                        <div class="fp-ring"></div>
                        <i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i>
                    </div>
                </div>
                <p class="fw-semibold mb-1" id="fpStatusText" style="font-size:15px">Silakan tempel jari pada alat</p>
                <p class="text-muted mb-3" style="font-size:12px" id="fpSubText">Alat sedang menunggu sidik jari Anda...</p>
                <div class="progress mb-2" style="height:6px;border-radius:4px">
                    <div class="progress-bar bg-primary" id="fpCountdownBar" style="width:100%;transition:width 1s linear"></div>
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

{{-- MODAL STEP 3: Hasil --}}
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
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" id="editName">
                </div>
                <div class="mb-3">
                    <label class="form-label">Device ID</label>
                    <input type="text" class="form-control" id="editDeviceId">
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

    var dt = $('#dtUsers').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '/api/users_datatables', type: 'POST' },
        columns: [
            { data: 'id',          width: '50px' },
            { data: 'name' },
            {
                data: 'finger_page',
                render: v => v !== null
                    ? `<span class="badge bg-primary" style="font-size:11px">Page ${v}</span>`
                    : '<span class="badge-inactive">-</span>'
            },
            { data: 'device_id',  defaultContent: '<span class="text-muted">-</span>' },
            { data: 'created_at', defaultContent: '-' },
            { data: 'action',     orderable: false, searchable: false }
        ],
        language: { processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...' },
        order: [[0, 'desc']], pageLength: 10
    });

    // ── Registrasi ────────────────────────────────────────────────────────────
    let activeSessionId = null;
    let pollInterval    = null;
    let countdownTimer  = null;
    let secondsLeft     = 300;

    $('#btnCreate').on('click', () => {
        $('#fName').val('');
        new bootstrap.Modal('#modalForm').show();
    });

    $('#btnNext').on('click', () => {
        const name = $('#fName').val().trim();
        if (!name) { alert('Nama wajib diisi'); return; }

        $('#btnNext').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: '/api/registration/start',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ name }),
            success(r) {
                activeSessionId = r.session_id;
                bootstrap.Modal.getInstance('#modalForm').hide();
                openFingerModal(r.user_name);
            },
            error(xhr) { alert(xhr.responseJSON?.message || 'Gagal memulai registrasi'); },
            complete() {
                $('#btnNext').prop('disabled', false)
                    .html('Lanjut &mdash; Scan Sidik Jari <i class="fas fa-arrow-right ms-1"></i>');
            }
        });
    });

    function openFingerModal(userName) {
        $('#fpUserName').text(userName);
        $('#fpStatusText').text('Silakan tempel jari pada alat');
        $('#fpSubText').text('Alat sedang menunggu sidik jari Anda...');
        $('#fpCountdownBar').css('width','100%').removeClass('bg-danger').addClass('bg-primary');
        $('#fpAnimArea').html(`<div class="fp-icon-wrap"><div class="fp-ring"></div>
            <i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i></div>`);
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
        $('#fpCountdownBar').css('width', (secondsLeft / 300 * 100) + '%');
        if (secondsLeft <= 30) $('#fpCountdownBar').removeClass('bg-primary').addClass('bg-danger');
    }

    function startCountdown() {
        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            secondsLeft--;
            updateCountdown();
            if (secondsLeft <= 0) { clearInterval(countdownTimer); onTimeout(); }
        }, 1000);
    }

    function startPolling() {
        clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            if (!activeSessionId) return;
            $.get(`/api/registration/status/${activeSessionId}`, r => {
                if (r.status === 'scanning') {
                    $('#fpStatusText').text('Alat sedang memindai...');
                    $('#fpSubText').text('Tahan jari di sensor hingga selesai');
                }
                if (r.status === 'complete') { stopPolling(); onComplete(); }
                if (r.status === 'failed' || r.status === 'expired') { stopPolling(); onFailed(); }
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
        $('#doneMsg').text('Sidik jari berhasil direkam.');
        new bootstrap.Modal('#modalDone').show();
    }

    function onFailed() {
        try { bootstrap.Modal.getInstance('#modalFinger').hide(); } catch(e) {}
        $('#doneIcon').html('<i class="fas fa-times-circle text-danger"></i>');
        $('#doneTitle').text('Registrasi Gagal');
        $('#doneMsg').text('Sidik jari tidak berhasil direkam. Coba lagi.');
        new bootstrap.Modal('#modalDone').show();
        dt.ajax.reload(null, false);
    }

    function onTimeout() {
        if (activeSessionId) $.post(`/api/registration/cancel/${activeSessionId}`);
        stopPolling();
        onFailed();
    }

    $('#btnCancelFinger').on('click', () => {
        if (activeSessionId) $.post(`/api/registration/cancel/${activeSessionId}`);
        stopPolling();
        bootstrap.Modal.getInstance('#modalFinger').hide();
        dt.ajax.reload(null, false);
    });

    // ── Edit ──────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        $.get(`/api/users/${id}`, d => {
            const u = d.data || d;
            $('#editId').val(u.id);
            $('#editName').val(u.name);
            $('#editDeviceId').val(u.device_id ?? '');
            new bootstrap.Modal('#modalEdit').show();
        });
    });

    $('#btnSaveEdit').on('click', () => {
        const id = $('#editId').val();
        $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: `/api/users/${id}`, type: 'PATCH',
            data: JSON.stringify({ name: $('#editName').val(), device_id: $('#editDeviceId').val() }),
            contentType: 'application/json',
            success() {
                bootstrap.Modal.getInstance('#modalEdit').hide();
                dt.ajax.reload(null, false);
            },
            error(xhr) { alert(xhr.responseJSON?.message || 'Gagal menyimpan'); },
            complete() { $('#btnSaveEdit').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan'); }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        if (!confirm('Hapus karyawan ini?')) return;
        const id = $(this).data('id');
        $.ajax({
            url: `/api/users/${id}`, type: 'DELETE',
            success() { dt.ajax.reload(null, false); },
            error()   { alert('Gagal menghapus'); }
        });
    });

});
</script>
@endpush

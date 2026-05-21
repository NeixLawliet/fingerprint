@extends('layouts.main')

@section('title', 'Users')

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
    .fp-ring      { position: absolute; inset: -12px; border: 3px solid #5c6bc0; border-radius: 50%; animation: pulse-ring 1.5s ease-out infinite; }
    .fp-icon-wrap { position: relative; display: inline-block; animation: fp-bounce 2s ease-in-out infinite; }
    .step-badge   { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Data Users</h6>
                <button class="btn btn-success btn-sm" id="add-button">
                    <i class="fas fa-plus me-1"></i> Daftarkan Karyawan
                </button>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Page Sensor</th>
                            <th>Device</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modal')

{{-- MODAL STEP 1: Form nama --}}
<div class="modal fade" id="form-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
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
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="input-name" placeholder="Nama karyawan">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success" id="btn-next">
                    Lanjut &mdash; Scan Sidik Jari <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL STEP 2: Tunggu scan --}}
<div class="modal fade" id="finger-modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fingerprint me-2"></i>Scan Sidik Jari</h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <span class="badge bg-primary px-3 py-2" style="font-size:13px" id="fp-user-name">-</span>
                </div>
                <div class="mb-4" style="height:90px;display:flex;align-items:center;justify-content:center" id="fp-anim-area">
                    <div class="fp-icon-wrap">
                        <div class="fp-ring"></div>
                        <i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i>
                    </div>
                </div>
                <p class="fw-semibold mb-1" id="fp-status-text" style="font-size:15px">Silakan tempel jari pada alat</p>
                <p class="text-muted mb-3" style="font-size:12px" id="fp-sub-text">Alat sedang menunggu sidik jari Anda...</p>
                <div class="progress mb-2" style="height:6px;border-radius:4px">
                    <div class="progress-bar bg-primary" id="fp-countdown-bar" style="width:100%;transition:width 1s linear"></div>
                </div>
                <small class="text-muted" id="fp-countdown-text">5:00</small>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-cancel-finger">
                    <i class="fas fa-times me-1"></i> Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL STEP 3: Hasil --}}
<div class="modal fade" id="done-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div id="done-icon" class="mb-3" style="font-size:56px"></div>
                <h5 id="done-title" class="fw-bold mb-1"></h5>
                <p id="done-msg" class="text-muted mb-0" style="font-size:13px"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="edit-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="edit-form">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" id="edit-name">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Device ID</label>
                            <input type="text" class="form-control" id="edit-device_id">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let dt;
    let endpoint        = 'users';
    let activeSessionId = null;
    let pollInterval    = null;
    let countdownTimer  = null;
    let secondsLeft     = 300;

    drawDatatable();

    function drawDatatable() {
        dt = $('#main-table').DataTable({
            destroy: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: BASE_URL + '/api/' + endpoint + '_datatables',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
            },
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    visible: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'finger_page',
                    name: 'finger_page',
                    render: function (v) {
                        return v !== null && v !== undefined
                            ? '<span class="badge bg-primary" style="font-size:11px">Page ' + v + '</span>'
                            : '<span class="badge-inactive">-</span>';
                    }
                },
                {
                    data: 'device_id',
                    name: 'device_id',
                    defaultContent: '<span class="text-muted">-</span>'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    defaultContent: '-'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                },
            ],
            order: [[0, 'desc']],
        });
    }

    // ── Registrasi ────────────────────────────────────────────────────────────

    $(document).on('click', '#add-button', function () {
        $('#input-name').val('');
        $('#form-modal').modal('show');
    });

    $('#btn-next').on('click', function () {
        const name = $('#input-name').val().trim();
        if (!name) { alert('Nama wajib diisi'); return; }

        $('#btn-next').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: BASE_URL + '/api/registration/start',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ name: name }),
            contentType: 'application/json',
            dataType: 'json',
            success: function (r) {
                activeSessionId = r.session_id;
                $('#form-modal').modal('hide');
                openFingerModal(r.employee_name);
            },
            error: function (xhr) {
                alert((xhr.responseJSON || {}).message || 'Gagal memulai registrasi');
            },
            complete: function () {
                $('#btn-next').prop('disabled', false)
                    .html('Lanjut &mdash; Scan Sidik Jari <i class="fas fa-arrow-right ms-1"></i>');
            },
        });
    });

    function openFingerModal(name) {
        $('#fp-user-name').text(name);
        $('#fp-status-text').text('Silakan tempel jari pada alat');
        $('#fp-sub-text').text('Alat sedang menunggu sidik jari Anda...');
        $('#fp-countdown-bar').css('width', '100%').removeClass('bg-danger').addClass('bg-primary');
        $('#fp-anim-area').html(
            '<div class="fp-icon-wrap"><div class="fp-ring"></div>' +
            '<i class="fas fa-fingerprint" style="font-size:60px;color:#5c6bc0;position:relative;z-index:1"></i></div>'
        );
        secondsLeft = 300;
        updateCountdown();
        $('#finger-modal').modal('show');
        startPolling();
        startCountdown();
    }

    function updateCountdown() {
        const m = String(Math.floor(secondsLeft / 60)).padStart(2, '0');
        const s = String(secondsLeft % 60).padStart(2, '0');
        $('#fp-countdown-text').text(m + ':' + s);
        $('#fp-countdown-bar').css('width', (secondsLeft / 300 * 100) + '%');
        if (secondsLeft <= 30) {
            $('#fp-countdown-bar').removeClass('bg-primary').addClass('bg-danger');
        }
    }

    function startCountdown() {
        clearInterval(countdownTimer);
        countdownTimer = setInterval(function () {
            secondsLeft--;
            updateCountdown();
            if (secondsLeft <= 0) { clearInterval(countdownTimer); onTimeout(); }
        }, 1000);
    }

    function startPolling() {
        clearInterval(pollInterval);
        pollInterval = setInterval(function () {
            if (!activeSessionId) return;
            $.get(BASE_URL + '/api/registration/status/' + activeSessionId, function (r) {
                if (r.status === 'scanning') {
                    $('#fp-status-text').text('Alat sedang memindai...');
                    $('#fp-sub-text').text('Tahan jari di sensor hingga selesai');
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
        $('#finger-modal').modal('hide');
        dt.ajax.reload(null, false);
        $('#done-icon').html('<i class="fas fa-check-circle text-success"></i>');
        $('#done-title').text('Berhasil Terdaftar!');
        $('#done-msg').text('Sidik jari berhasil direkam.');
        $('#done-modal').modal('show');
    }

    function onFailed() {
        try { $('#finger-modal').modal('hide'); } catch (e) {}
        $('#done-icon').html('<i class="fas fa-times-circle text-danger"></i>');
        $('#done-title').text('Registrasi Gagal');
        $('#done-msg').text('Sidik jari tidak berhasil direkam. Coba lagi.');
        $('#done-modal').modal('show');
        dt.ajax.reload(null, false);
    }

    function onTimeout() {
        if (activeSessionId) {
            $.post(BASE_URL + '/api/registration/cancel/' + activeSessionId, {
                _token: $('meta[name="csrf-token"]').attr('content'),
            });
        }
        stopPolling();
        onFailed();
    }

    $('#btn-cancel-finger').on('click', function () {
        if (activeSessionId) {
            $.post(BASE_URL + '/api/registration/cancel/' + activeSessionId, {
                _token: $('meta[name="csrf-token"]').attr('content'),
            });
        }
        stopPolling();
        $('#finger-modal').modal('hide');
        dt.ajax.reload(null, false);
    });

    // ── Edit ──────────────────────────────────────────────────────────────────

    $(document).on('click', '#edit-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        $.ajax({
            url: BASE_URL + '/api/' + endpoint + '/' + id,
            type: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            dataType: 'JSON',
            beforeSend: function () {
                showLoading('Harap Menunggu!', 'Sedang mengambil data');
            },
            success: function (d) {
                $('#edit-id').val(d.id);
                $('#edit-name').val(d.name);
                $('#edit-device_id').val(d.device_id ?? '');
                $('#edit-modal').modal('show');
                Swal.close();
            },
        });
    });

    $('#edit-form').on('submit', function (e) {
        e.preventDefault();

        const id = $('#edit-id').val();

        $.ajax({
            url: BASE_URL + '/api/' + endpoint + '/' + id,
            type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                name:      $('#edit-name').val(),
                device_id: $('#edit-device_id').val(),
            }),
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: function () {
                showLoading('Harap Menunggu!', 'Sedang menyimpan data');
            },
            success: function (res) {
                Swal.close();
                showAlertOnSubmit(res, '#edit-modal', '#main-table');
            },
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus data user ini ?',
            'warning',
            'DELETE',
            null,
            BASE_URL + '/api/' + endpoint + '/' + id,
            '',
            ['#main-table']
        );
    });
</script>
@endpush

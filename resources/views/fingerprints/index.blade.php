@extends('layouts.app')
@section('title', 'Fingerprints')

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-fingerprint me-2 text-danger"></i>Data Fingerprints</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Tambah Fingerprint
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dtFingerprints" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Finger Type</th>
                        <th>Device ID</th>
                        <th>Quality</th>
                        <th>Template</th>
                        <th>Dibuat</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CREATE / EDIT --}}
<div class="modal fade" id="modalFP" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFPTitle">Tambah Fingerprint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fpId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">User ID <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="fpUserId" placeholder="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Finger Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="fpFingerType">
                            <option value="">-- Pilih --</option>
                            <option value="right_thumb">Right Thumb</option>
                            <option value="right_index">Right Index</option>
                            <option value="right_middle">Right Middle</option>
                            <option value="right_ring">Right Ring</option>
                            <option value="right_pinky">Right Pinky</option>
                            <option value="left_thumb">Left Thumb</option>
                            <option value="left_index">Left Index</option>
                            <option value="left_middle">Left Middle</option>
                            <option value="left_ring">Left Ring</option>
                            <option value="left_pinky">Left Pinky</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Device ID</label>
                        <input type="text" class="form-control" id="fpDeviceId" placeholder="ESP32-001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Quality Score</label>
                        <input type="number" class="form-control" id="fpQualityScore"
                            placeholder="0.00" step="0.01" min="0" max="100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveFP">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL HASIL PROCESS --}}
<div class="modal fade" id="modalProcessResult" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cogs me-2"></i>Hasil Processing — Fingerprint #<span id="processedFpId"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Template info --}}
                <div class="card mb-3" style="border-left:4px solid #5c6bc0">
                    <div class="card-body py-3">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="resVectorSize">-</div>
                                <small class="text-muted">Features</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="resSampleCount">-</div>
                                <small class="text-muted">Samples</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="resAlgoVersion">-</div>
                                <small class="text-muted">Algorithm</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Match result --}}
                <div class="text-center mb-3">
                    <div id="resMatchBadge" class="mb-2"></div>
                    <div style="font-size:36px;font-weight:700" id="resScore">-</div>
                    <small class="text-muted">Similarity Score</small>
                </div>

                {{-- Progress bar --}}
                <div class="progress mb-3" style="height:10px;border-radius:8px">
                    <div class="progress-bar" id="resProgressBar"
                        role="progressbar" style="width:0%;border-radius:8px"
                        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="alert mb-0" id="resNote" style="font-size:13px"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="{{ route('fingerprint_logs.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-clipboard-list me-1"></i> Lihat Logs
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    // ===== DATATABLE =====
    var dt = $('#dtFingerprints').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '/api/fingerprints_datatables', type: 'POST' },
        columns: [
            { data: 'id', name: 'id', width: '50px' },
            { data: 'user_id', name: 'user_id' },
            {
                data: 'finger_type', name: 'finger_type',
                render: function (v) {
                    return '<code style="font-size:12px">' + (v || '-') + '</code>';
                }
            },
            { data: 'device_id', name: 'device_id', defaultContent: '-' },
            {
                data: 'quality_score', name: 'quality_score',
                render: function (v) {
                    var val = parseFloat(v || 0);
                    var color = val >= 70 ? '#065f46' : val >= 40 ? '#92400e' : '#991b1b';
                    var bg    = val >= 70 ? '#d1fae5' : val >= 40 ? '#fef3c7' : '#fee2e2';
                    return '<span style="background:' + bg + ';color:' + color
                        + ';padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">'
                        + val.toFixed(1) + '</span>';
                }
            },
            {
                // Cek apakah template sudah ada via API
                data: 'id', name: 'template_status', orderable: false, searchable: false,
                render: function (id) {
                    return '<span class="template-badge" id="tpl-' + id + '">'
                        + '<i class="fas fa-spinner fa-spin text-muted" style="font-size:11px"></i>'
                        + '</span>';
                }
            },
            {
                data: 'created_at', name: 'created_at',
                render: function (v) { return v ? v.substring(0, 10) : '-'; }
            },
            {
                data: 'id', name: 'action', orderable: false, searchable: false,
                render: function (id) {
                    return '<button class="btn btn-success btn-sm btn-process me-1" data-id="' + id
                        + '" title="Process template" style="border-radius:6px;width:28px;height:28px;padding:0">'
                        + '<i class="fas fa-cogs" style="font-size:11px"></i></button>'
                        + '<button class="btn btn-warning btn-sm btn-edit me-1" data-id="' + id
                        + '" style="border-radius:6px;width:28px;height:28px;padding:0">'
                        + '<i class="fas fa-edit" style="font-size:11px"></i></button>'
                        + '<button class="btn btn-danger btn-sm btn-delete" data-id="' + id
                        + '" style="border-radius:6px;width:28px;height:28px;padding:0">'
                        + '<i class="fas fa-trash" style="font-size:11px"></i></button>';
                }
            }
        ],
        language: { processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...' },
        order: [[0, 'desc']],
        pageLength: 10
    });

    // Cek status template tiap row setelah draw
    dt.on('draw', function () {
        $('#dtFingerprints tbody tr').each(function () {
            var id = $(this).find('.btn-process').data('id');
            if (!id) return;
            checkTemplateStatus(id);
        });
    });

    function checkTemplateStatus(fpId) {
        $.get('/api/fingerprint_templates?fingerprint_id=' + fpId + '&all=1', function (r) {
            var rows = r.data || r;
            var exists = rows.some(function (t) { return t.fingerprint_id == fpId; });
            $('#tpl-' + fpId).html(
                exists
                    ? '<span class="badge-match"><i class="fas fa-check me-1"></i>Ada</span>'
                    : '<span class="badge-inactive">Belum</span>'
            );
        });
    }

    // ===== PROCESS FINGERPRINT =====
    $(document).on('click', '.btn-process', function () {
        var id = $(this).data('id');
        var $btn = $(this);

        Swal.fire({
            title: 'Proses Fingerprint #' + id + '?',
            html: 'Server akan mengekstrak template dari sample yang tersimpan<br>dan melakukan matching.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-cogs me-1"></i> Proses',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" style="font-size:11px"></i>');

            $.ajax({
                url: '/api/fingerprints/' + id + '/process',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({}),
                success: function (r) {
                    showProcessResult(id, r);
                    dt.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'Gagal memproses fingerprint';
                    toast(msg, 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false)
                        .html('<i class="fas fa-cogs" style="font-size:11px"></i>');
                }
            });
        });
    });

    function showProcessResult(id, r) {
        var match   = r.match || {};
        var tpl     = r.template || {};
        var isMatch = match.status === 'match';
        var score   = parseFloat(match.similarity_score || 0);
        var pct     = Math.round(score * 100);

        $('#processedFpId').text(id);
        $('#resVectorSize').text(tpl.vector_size || '-');
        $('#resSampleCount').text(tpl.sample_count || '-');
        $('#resAlgoVersion').text(tpl.algorithm_version || '-');
        $('#resScore').text(score.toFixed(4));

        if (isMatch) {
            $('#resMatchBadge').html('<span class="badge-match" style="font-size:14px;padding:6px 18px">'
                + '<i class="fas fa-check-circle me-1"></i>MATCH</span>');
            $('#resProgressBar')
                .css('width', pct + '%')
                .removeClass('bg-danger').addClass('bg-success');
            $('#resNote').removeClass('alert-danger').addClass('alert-success').text(match.note || '');
        } else {
            $('#resMatchBadge').html('<span class="badge-no-match" style="font-size:14px;padding:6px 18px">'
                + '<i class="fas fa-times-circle me-1"></i>NOT MATCH</span>');
            $('#resProgressBar')
                .css('width', pct + '%')
                .removeClass('bg-success').addClass('bg-danger');
            $('#resNote').removeClass('alert-success').addClass('alert-danger').text(match.note || '');
        }

        new bootstrap.Modal('#modalProcessResult').show();
    }

    // ===== CREATE / EDIT =====
    function resetModal() {
        $('#fpId').val('');
        $('#fpUserId, #fpDeviceId, #fpQualityScore').val('');
        $('#fpFingerType').val('');
    }

    $('#btnCreate').on('click', function () {
        resetModal();
        $('#modalFPTitle').text('Tambah Fingerprint');
        new bootstrap.Modal('#modalFP').show();
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprints/' + id, function (d) {
            $('#modalFPTitle').text('Edit Fingerprint');
            $('#fpId').val(d.id);
            $('#fpUserId').val(d.user_id);
            $('#fpFingerType').val(d.finger_type);
            $('#fpDeviceId').val(d.device_id);
            $('#fpQualityScore').val(d.quality_score);
            new bootstrap.Modal('#modalFP').show();
        });
    });

    $('#btnSaveFP').on('click', function () {
        var id = $('#fpId').val();
        var data = {
            user_id:       $('#fpUserId').val(),
            finger_type:   $('#fpFingerType').val(),
            device_id:     $('#fpDeviceId').val(),
            quality_score: $('#fpQualityScore').val()
        };

        if (!data.user_id || !data.finger_type) {
            toast('User ID dan finger type wajib diisi', 'warning'); return;
        }

        var url    = id ? '/api/fingerprints/' + id : '/api/fingerprints';
        var method = id ? 'PUT' : 'POST';

        $('#btnSaveFP').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: url, type: method,
            data: JSON.stringify(data), contentType: 'application/json',
            success: function () {
                bootstrap.Modal.getInstance('#modalFP').hide();
                dt.ajax.reload(null, false);
                toast(id ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan');
            },
            error: function (xhr) { toast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'error'); },
            complete: function () {
                $('#btnSaveFP').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan');
            }
        });
    });

    // ===== DELETE =====
    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        confirmDelete(function () {
            $.ajax({
                url: '/api/fingerprints/' + id, type: 'DELETE',
                success: function () { dt.ajax.reload(null, false); toast('Data berhasil dihapus'); },
                error: function () { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

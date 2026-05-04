@extends('layouts.app')
@section('title', 'Fingerprint Logs')

@section('content')

{{-- FILTER STATS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#065f46" id="cntMatch">-</div>
            <small class="text-muted">Match</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#991b1b" id="cntNotMatch">-</div>
            <small class="text-muted">Not Match</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#1e40af" id="cntAvgScore">-</div>
            <small class="text-muted">Avg Score</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#374151" id="cntTotal">-</div>
            <small class="text-muted">Total Logs</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-clipboard-list me-2 text-warning"></i>Data Fingerprint Logs</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Tambah Log
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dtLogs" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Similarity Score</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th>Dibuat</th>
                        <th style="width:90px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CREATE / EDIT --}}
<div class="modal fade" id="modalLog" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLogTitle">Tambah Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="logId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">User ID <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="logUserId" placeholder="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Similarity Score <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="logSimilarityScore"
                            placeholder="0.000" step="0.001" min="0" max="1">
                        <small class="text-muted">Range: 0.000 – 1.000</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="logStatus">
                            <option value="match">Match</option>
                            <option value="not_match">Not Match</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="logNote" rows="3"
                            placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveLog">
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

    // ===== MINI STATS =====
    $.get('/api/fingerprint_logs?all=1', function (r) {
        var rows = r.data || r;
        var match    = rows.filter(function (d) { return d.status === 'match'; }).length;
        var notMatch = rows.filter(function (d) { return d.status === 'not_match'; }).length;
        var total    = rows.length;
        var avg = total > 0
            ? (rows.reduce(function (s, d) { return s + parseFloat(d.similarity_score || 0); }, 0) / total).toFixed(3)
            : '0.000';

        $('#cntMatch').text(match);
        $('#cntNotMatch').text(notMatch);
        $('#cntAvgScore').text(avg);
        $('#cntTotal').text(total);
    });

    // ===== DATATABLE =====
    var dt = $('#dtLogs').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '/api/fingerprint_logs_datatables', type: 'POST' },
        columns: [
            { data: 'id',       name: 'id',       width: '55px' },
            { data: 'user_id',  name: 'user_id' },
            {
                data: 'similarity_score', name: 'similarity_score',
                render: function (v) {
                    var val = parseFloat(v || 0);
                    var pct = (val * 100).toFixed(1);
                    var color = val >= 0.7 ? '#065f46' : val >= 0.4 ? '#92400e' : '#991b1b';
                    var bg    = val >= 0.7 ? '#d1fae5' : val >= 0.4 ? '#fef3c7' : '#fee2e2';
                    return '<span style="background:' + bg + ';color:' + color + ';padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">'
                        + val.toFixed(4) + '</span>';
                }
            },
            {
                data: 'status', name: 'status',
                render: function (v) {
                    return v === 'match'
                        ? '<span class="badge-match">match</span>'
                        : '<span class="badge-no-match">not match</span>';
                }
            },
            {
                data: 'note', name: 'note', orderable: false,
                render: function (v) {
                    if (!v) return '<span class="text-muted">-</span>';
                    return v.length > 40 ? v.substring(0, 40) + '...' : v;
                }
            },
            {
                data: 'created_at', name: 'created_at',
                render: function (v) { return v ? v.substring(0, 16).replace('T', ' ') : '-'; }
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

    function resetModal() {
        $('#logId, #logUserId, #logSimilarityScore, #logNote').val('');
        $('#logStatus').val('match');
    }

    $('#btnCreate').on('click', function () {
        resetModal();
        $('#modalLogTitle').text('Tambah Log');
        new bootstrap.Modal('#modalLog').show();
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprint_logs/' + id, function (d) {
            $('#modalLogTitle').text('Edit Log');
            $('#logId').val(d.id);
            $('#logUserId').val(d.user_id);
            $('#logSimilarityScore').val(d.similarity_score);
            $('#logStatus').val(d.status);
            $('#logNote').val(d.note);
            new bootstrap.Modal('#modalLog').show();
        });
    });

    $('#btnSaveLog').on('click', function () {
        var id = $('#logId').val();
        var data = {
            user_id:          $('#logUserId').val(),
            similarity_score: $('#logSimilarityScore').val(),
            status:           $('#logStatus').val(),
            note:             $('#logNote').val()
        };

        if (!data.user_id || data.similarity_score === '') {
            toast('User ID dan similarity score wajib diisi', 'warning');
            return;
        }

        var url    = id ? '/api/fingerprint_logs/' + id : '/api/fingerprint_logs';
        var method = id ? 'PUT' : 'POST';

        $('#btnSaveLog').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: url, type: method,
            data: JSON.stringify(data), contentType: 'application/json',
            success: function () {
                bootstrap.Modal.getInstance('#modalLog').hide();
                dt.ajax.reload(null, false);
                toast(id ? 'Data berhasil diperbarui' : 'Log berhasil ditambahkan');
            },
            error: function (xhr) { toast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'error'); },
            complete: function () {
                $('#btnSaveLog').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan');
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        confirmDelete(function () {
            $.ajax({
                url: '/api/fingerprint_logs/' + id, type: 'DELETE',
                success: function () { dt.ajax.reload(null, false); toast('Data berhasil dihapus'); },
                error: function () { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

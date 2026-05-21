@extends('layouts.main')

@section('title', 'Fingerprint Logs')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#065f46" id="cnt-match">-</div>
            <small class="text-muted">Match</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#991b1b" id="cnt-not-match">-</div>
            <small class="text-muted">Not Match</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#1e40af" id="cnt-avg-score">-</div>
            <small class="text-muted">Avg Score</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="fw-bold" style="font-size:22px;color:#374151" id="cnt-total">-</div>
            <small class="text-muted">Total Logs</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2 text-warning"></i>Data Fingerprint Logs</h6>
                <button class="btn btn-success btn-sm" id="add-button">
                    <i class="fas fa-plus me-1"></i> Tambah Log
                </button>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee ID</th>
                            <th>Similarity Score</th>
                            <th>Status</th>
                            <th>Note</th>
                            <th>Dibuat</th>
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

<div class="modal fade" id="form-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="main-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalTitle">Tambah Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="input-id">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Karyawan <span class="text-danger">*</span></label>
                            <select class="form-select" name="employee_id" id="input-employee_id">
                                <option value="">Pilih Karyawan</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Similarity Score <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="similarity_score" id="input-similarity_score" placeholder="0.000" step="0.001" min="0" max="1">
                            <small class="text-muted">Range: 0.000 – 1.000</small>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="input-status">
                                <option value="match">Match</option>
                                <option value="not_match">Not Match</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" name="note" id="input-note" rows="3" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let dt;
    let endpoint = 'fingerprint_logs';

    loadStats();
    drawDatatable();

    function loadStats() {
        $.ajax({
            url: BASE_URL + '/api/' + endpoint + '?all=1',
            type: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            dataType: 'JSON',
            success: function (rows) {
                rows = rows.data || rows;
                const match    = rows.filter(function (d) { return d.status === 'match'; }).length;
                const notMatch = rows.filter(function (d) { return d.status === 'not_match'; }).length;
                const total    = rows.length;
                const avg = total > 0
                    ? (rows.reduce(function (s, d) { return s + parseFloat(d.similarity_score || 0); }, 0) / total).toFixed(3)
                    : '0.000';

                $('#cnt-match').text(match);
                $('#cnt-not-match').text(notMatch);
                $('#cnt-avg-score').text(avg);
                $('#cnt-total').text(total);
            },
        });
    }

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
                    data: 'employee_id',
                    name: 'employee_id'
                },
                {
                    data: 'similarity_score',
                    name: 'similarity_score',
                    render: function (v) {
                        const val   = parseFloat(v || 0);
                        const color = val >= 0.7 ? '#065f46' : val >= 0.4 ? '#92400e' : '#991b1b';
                        const bg    = val >= 0.7 ? '#d1fae5' : val >= 0.4 ? '#fef3c7' : '#fee2e2';
                        return '<span style="background:' + bg + ';color:' + color + ';padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">'
                            + val.toFixed(4) + '</span>';
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (v) {
                        return v === 'match'
                            ? '<span class="badge-match">match</span>'
                            : '<span class="badge-no-match">not match</span>';
                    }
                },
                {
                    data: 'note',
                    name: 'note',
                    orderable: false,
                    render: function (v) {
                        if (!v) return '<span class="text-muted">-</span>';
                        return v.length > 40 ? v.substring(0, 40) + '...' : v;
                    }
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (v) { return v ? v.substring(0, 16).replace('T', ' ') : '-'; }
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

    $(document).on('click', '#add-button', function () {
        resetAllInputOnForm('#main-form');
        $('#input-id').val('');
        $('#input-status').val('match');
        getEmployees({ element: '#input-employee_id' });
        $('#formModalTitle').text('Tambah Log');
        $('#form-modal').modal('show');
    });

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
                $('#input-id').val(d.id);
                getEmployees({ element: '#input-employee_id', selected_val: d.employee_id });
                $('#input-similarity_score').val(d.similarity_score);
                $('#input-status').val(d.status);
                $('#input-note').val(d.note);
                $('#formModalTitle').text('Edit Log');
                $('#form-modal').modal('show');
                Swal.close();
            },
        });
    });

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus data log ini ?',
            'warning',
            'DELETE',
            null,
            BASE_URL + '/api/' + endpoint + '/' + id,
            '',
            ['#main-table']
        );
    });

    $('#main-form').on('submit', function (e) {
        e.preventDefault();

        const id = $('#input-id').val();

        $.ajax({
            url: id ? BASE_URL + '/api/' + endpoint + '/' + id : BASE_URL + '/api/' + endpoint,
            type: id ? 'PATCH' : 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                employee_id:      $('#input-employee_id').val(),
                similarity_score: $('#input-similarity_score').val(),
                status:           $('#input-status').val(),
                note:             $('#input-note').val(),
            }),
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: function () {
                showLoading('Harap Menunggu!', 'Sedang menyimpan data');
            },
            success: function (res) {
                Swal.close();
                showAlertOnSubmit(res, '#form-modal', '#main-table');
            },
        });
    });
</script>
@endpush

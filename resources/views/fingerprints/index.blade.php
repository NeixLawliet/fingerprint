@extends('layouts.main')

@section('title', 'Fingerprints')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-fingerprint me-2 text-danger"></i>Data Fingerprints</h6>
                <button class="btn btn-success btn-sm" id="add-button">
                    <i class="fas fa-plus me-1"></i> Tambah Fingerprint
                </button>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee ID</th>
                            <th>Finger Type</th>
                            <th>Device ID</th>
                            <th>Quality</th>
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
                    <h5 class="modal-title" id="formModalTitle">Tambah Fingerprint</h5>
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
                            <label class="form-label">Finger Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="finger_type" id="input-finger_type">
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

                        <div class="col-6">
                            <label class="form-label">Device ID</label>
                            <input type="text" class="form-control" name="device_id" id="input-device_id" placeholder="ESP32-001">
                        </div>

                        <div class="col-6">
                            <label class="form-label">Quality Score</label>
                            <input type="number" class="form-control" name="quality_score" id="input-quality_score" placeholder="0.00" step="0.01" min="0" max="100">
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

<div class="modal fade" id="process-result-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasil Processing — Fingerprint #<span id="processed-fp-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3" style="border-left:4px solid #5c6bc0">
                    <div class="card-body py-3">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="res-vector-size">-</div>
                                <small class="text-muted">Features</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="res-sample-count">-</div>
                                <small class="text-muted">Samples</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary" id="res-algo-version">-</div>
                                <small class="text-muted">Algorithm</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mb-3">
                    <div id="res-match-badge" class="mb-2"></div>
                    <div style="font-size:36px;font-weight:700" id="res-score">-</div>
                    <small class="text-muted">Similarity Score</small>
                </div>
                <div class="progress mb-3" style="height:10px;border-radius:8px">
                    <div class="progress-bar" id="res-progress-bar" role="progressbar" style="width:0%;border-radius:8px"></div>
                </div>
                <div class="alert mb-0" id="res-note" style="font-size:13px"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let dt;
    let endpoint = 'fingerprints';

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
                    data: 'employee_id',
                    name: 'employee_id'
                },
                {
                    data: 'finger_type',
                    name: 'finger_type',
                    render: function (v) {
                        return '<code>' + (v || '-') + '</code>';
                    }
                },
                {
                    data: 'device_id',
                    name: 'device_id',
                    defaultContent: '-'
                },
                {
                    data: 'quality_score',
                    name: 'quality_score',
                    render: function (v) {
                        const val   = parseFloat(v || 0);
                        const color = val >= 70 ? '#065f46' : val >= 40 ? '#92400e' : '#991b1b';
                        const bg    = val >= 70 ? '#d1fae5' : val >= 40 ? '#fef3c7' : '#fee2e2';
                        return '<span style="background:' + bg + ';color:' + color + ';padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600">'
                            + val.toFixed(1) + '</span>';
                    }
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (v) { return v ? v.substring(0, 10) : '-'; }
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
        getEmployees({ element: '#input-employee_id' });
        $('#formModalTitle').text('Tambah Fingerprint');
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
                $('#input-finger_type').val(d.finger_type);
                $('#input-device_id').val(d.device_id);
                $('#input-quality_score').val(d.quality_score);
                $('#formModalTitle').text('Edit Fingerprint');
                $('#form-modal').modal('show');
                Swal.close();
            },
        });
    });

    $(document).on('click', '#process-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const $btn = $(this);

        showPopupWithAction(
            'Proses Fingerprint #' + id + '?',
            'Server akan mengekstrak template dari sample yang tersimpan.',
            'question',
            'POST',
            JSON.stringify({}),
            BASE_URL + '/api/' + endpoint + '/' + id + '/process',
            '',
            ['#main-table'],
            null,
            function (res) { showProcessResult(id, res); }
        );
    });

    function showProcessResult(id, r) {
        const match   = r.match || {};
        const tpl     = r.template || {};
        const isMatch = match.status === 'match';
        const score   = parseFloat(match.similarity_score || 0);
        const pct     = Math.round(score * 100);

        $('#processed-fp-id').text(id);
        $('#res-vector-size').text(tpl.vector_size || '-');
        $('#res-sample-count').text(tpl.sample_count || '-');
        $('#res-algo-version').text(tpl.algorithm_version || '-');
        $('#res-score').text(score.toFixed(4));

        if (isMatch) {
            $('#res-match-badge').html('<span class="badge-match" style="font-size:14px;padding:6px 18px"><i class="fas fa-check-circle me-1"></i>MATCH</span>');
            $('#res-progress-bar').css('width', pct + '%').removeClass('bg-danger').addClass('bg-success');
            $('#res-note').removeClass('alert-danger').addClass('alert-success').text(match.note || '');
        } else {
            $('#res-match-badge').html('<span class="badge-no-match" style="font-size:14px;padding:6px 18px"><i class="fas fa-times-circle me-1"></i>NOT MATCH</span>');
            $('#res-progress-bar').css('width', pct + '%').removeClass('bg-success').addClass('bg-danger');
            $('#res-note').removeClass('alert-success').addClass('alert-danger').text(match.note || '');
        }

        $('#process-result-modal').modal('show');
    }

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus data fingerprint ini ?',
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
                employee_id:   $('#input-employee_id').val(),
                finger_type:   $('#input-finger_type').val(),
                device_id:     $('#input-device_id').val(),
                quality_score: $('#input-quality_score').val(),
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

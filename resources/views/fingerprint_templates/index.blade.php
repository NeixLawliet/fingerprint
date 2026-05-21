@extends('layouts.main')

@section('title', 'Fingerprint Templates')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-layer-group me-2 text-info"></i>Data Fingerprint Templates</h6>
                <button class="btn btn-success btn-sm" id="add-button">
                    <i class="fas fa-plus me-1"></i> Tambah Template
                </button>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fingerprint ID</th>
                            <th>Algorithm Version</th>
                            <th>Template Vector</th>
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="main-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalTitle">Tambah Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="input-id">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Fingerprint <span class="text-danger">*</span></label>
                            <select class="form-select" name="fingerprint_id" id="input-fingerprint_id">
                                <option value="">Pilih Fingerprint</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Algorithm Version</label>
                            <input type="text" class="form-control" name="algorithm_version" id="input-algorithm_version" placeholder="v1">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Template Vector (JSON)</label>
                            <textarea class="form-control font-monospace" name="template_vector" id="input-template_vector" rows="8" placeholder="[0.123, 0.456, ...]"></textarea>
                            <small class="text-muted">Array JSON berisi vektor hasil ekstraksi fitur sidik jari.</small>
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

<div class="modal fade" id="view-vector-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Vector — #<span id="view-vector-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-3 mb-3">
                    <span class="badge bg-secondary" id="view-algo-version">-</span>
                    <small class="text-muted">Fingerprint ID: <strong id="view-vector-fp-id">-</strong></small>
                </div>
                <textarea class="form-control font-monospace" id="view-vector-data" rows="12" readonly></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success" id="btn-copy-vector">
                    <i class="fas fa-copy me-1"></i> Copy
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let dt;
    let endpoint = 'fingerprint_templates';

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
                    data: 'fingerprint_id',
                    name: 'fingerprint_id'
                },
                {
                    data: 'algorithm_version',
                    name: 'algorithm_version',
                    render: function (v) {
                        return '<span class="badge bg-secondary">' + (v || 'v1') + '</span>';
                    }
                },
                {
                    data: 'template_vector',
                    name: 'template_vector',
                    orderable: false,
                    searchable: false,
                    render: function (v, t, row) {
                        if (!v) return '<span class="text-muted">-</span>';
                        return '<a href="javascript:void(0);" class="btn btn-sm btn-outline-info" id="view-vector" data-id="' + row.id + '">'
                            + '<i class="fas fa-eye me-1"></i> Lihat Vector</a>';
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
        $('#input-algorithm_version').val('v1');
        getFingerprints({ element: '#input-fingerprint_id' });
        $('#formModalTitle').text('Tambah Template');
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
                getFingerprints({ element: '#input-fingerprint_id', selected_val: d.fingerprint_id });
                $('#input-algorithm_version').val(d.algorithm_version || 'v1');
                $('#input-template_vector').val(typeof d.template_vector === 'string' ? d.template_vector : JSON.stringify(d.template_vector, null, 2));
                $('#formModalTitle').text('Edit Template');
                $('#form-modal').modal('show');
                Swal.close();
            },
        });
    });

    $(document).on('click', '#view-vector', function (e) {
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
                $('#view-vector-id').text(d.id);
                $('#view-vector-fp-id').text(d.fingerprint_id);
                $('#view-algo-version').text(d.algorithm_version || 'v1');
                $('#view-vector-data').val(typeof d.template_vector === 'string' ? d.template_vector : JSON.stringify(d.template_vector, null, 2));
                $('#view-vector-modal').modal('show');
                Swal.close();
            },
        });
    });

    $('#btn-copy-vector').on('click', function () {
        navigator.clipboard.writeText($('#view-vector-data').val()).then(function () {
            toast('Berhasil dicopy');
        });
    });

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus data template ini ?',
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
                fingerprint_id:    $('#input-fingerprint_id').val(),
                algorithm_version: $('#input-algorithm_version').val() || 'v1',
                template_vector:   $('#input-template_vector').val(),
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

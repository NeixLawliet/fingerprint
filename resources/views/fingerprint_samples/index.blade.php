@extends('layouts.main')

@section('title', 'Fingerprint Samples')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-database me-2 text-success"></i>Data Fingerprint Samples</h6>
                <button class="btn btn-success btn-sm" id="add-button">
                    <i class="fas fa-plus me-1"></i> Tambah Sample
                </button>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fingerprint ID</th>
                            <th>Sample Index</th>
                            <th>Raw Data</th>
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
                    <h5 class="modal-title" id="formModalTitle">Tambah Sample</h5>
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
                            <label class="form-label">Sample Index <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="sample_index" id="input-sample_index" placeholder="0" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Raw Data (Base64 / JSON)</label>
                            <textarea class="form-control" name="raw_data" id="input-raw_data" rows="6" placeholder="Data mentah fingerprint..."></textarea>
                            <small class="text-muted">Data dikirim otomatis oleh ESP32. Isi manual jika diperlukan.</small>
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

<div class="modal fade" id="view-raw-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Raw Data — Sample #<span id="view-sample-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control font-monospace" id="view-raw-data" rows="12" readonly></textarea>
                <small class="text-muted mt-2 d-block">Ukuran: <strong id="view-raw-size">-</strong></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success" id="btn-copy-raw">
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
    let endpoint = 'fingerprint_samples';

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
                    data: 'sample_index',
                    name: 'sample_index'
                },
                {
                    data: 'raw_data',
                    name: 'raw_data',
                    orderable: false,
                    searchable: false,
                    render: function (v, t, row) {
                        if (!v) return '<span class="text-muted">-</span>';
                        return '<a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary" id="view-raw" data-id="' + row.id + '">'
                            + '<i class="fas fa-eye me-1"></i>' + v.length.toLocaleString() + ' chars</a>';
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
        $('#input-sample_index').val(0);
        getFingerprints({ element: '#input-fingerprint_id' });
        $('#formModalTitle').text('Tambah Sample');
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
                $('#input-sample_index').val(d.sample_index);
                $('#input-raw_data').val(d.raw_data);
                $('#formModalTitle').text('Edit Sample');
                $('#form-modal').modal('show');
                Swal.close();
            },
        });
    });

    $(document).on('click', '#view-raw', function (e) {
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
                $('#view-sample-id').text(d.id);
                $('#view-raw-data').val(d.raw_data || '');
                $('#view-raw-size').text((d.raw_data ? d.raw_data.length : 0).toLocaleString() + ' chars');
                $('#view-raw-modal').modal('show');
                Swal.close();
            },
        });
    });

    $('#btn-copy-raw').on('click', function () {
        navigator.clipboard.writeText($('#view-raw-data').val()).then(function () {
            toast('Berhasil dicopy');
        });
    });

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus data sample ini ?',
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
                fingerprint_id: $('#input-fingerprint_id').val(),
                sample_index:   $('#input-sample_index').val(),
                raw_data:       $('#input-raw_data').val(),
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

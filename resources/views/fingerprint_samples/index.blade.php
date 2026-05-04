@extends('layouts.app')
@section('title', 'Fingerprint Samples')

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-database me-2 text-success"></i>Data Fingerprint Samples</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Tambah Sample
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dtSamples" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fingerprint ID</th>
                        <th>Sample Index</th>
                        <th>Raw Data</th>
                        <th>Dibuat</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CREATE / EDIT --}}
<div class="modal fade" id="modalSample" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSampleTitle">Tambah Sample</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sampleId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fingerprint ID <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="sampleFpId" placeholder="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sample Index <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="sampleIndex" placeholder="0" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Raw Data (Base64 / JSON)</label>
                        <textarea class="form-control" id="sampleRawData" rows="6"
                            placeholder="Data mentah fingerprint dalam format base64 atau JSON array..."></textarea>
                        <small class="text-muted">Data dikirim otomatis oleh ESP32. Isi manual jika diperlukan.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveSample">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL VIEW RAW DATA --}}
<div class="modal fade" id="modalViewRaw" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Raw Data — Sample #<span id="viewSampleId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control font-monospace" id="viewRawData" rows="12" readonly></textarea>
                <div class="mt-2 d-flex gap-2">
                    <small class="text-muted">Ukuran: <strong id="viewRawSize">-</strong></small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" id="btnCopyRaw">
                    <i class="fas fa-copy me-1"></i> Copy
                </button>
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    var dt = $('#dtSamples').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '/api/fingerprint_samples_datatables', type: 'POST' },
        columns: [
            { data: 'id',             name: 'id',             width: '55px' },
            { data: 'fingerprint_id', name: 'fingerprint_id' },
            { data: 'sample_index',   name: 'sample_index' },
            {
                data: 'raw_data', name: 'raw_data', orderable: false, searchable: false,
                render: function (v, t, row) {
                    if (!v) return '<span class="text-muted">-</span>';
                    var len = v.length;
                    return '<button class="btn btn-sm btn-outline-secondary btn-view-raw" data-id="' + row.id + '" style="border-radius:6px;font-size:11px;padding:2px 10px">'
                        + '<i class="fas fa-eye me-1"></i>' + len.toLocaleString() + ' chars</button>';
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

    function resetModal() {
        $('#sampleId, #sampleFpId, #sampleIndex, #sampleRawData').val('');
    }

    $('#btnCreate').on('click', function () {
        resetModal();
        $('#sampleIndex').val(0);
        $('#modalSampleTitle').text('Tambah Sample');
        new bootstrap.Modal('#modalSample').show();
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprint_samples/' + id, function (d) {
            $('#modalSampleTitle').text('Edit Sample');
            $('#sampleId').val(d.id);
            $('#sampleFpId').val(d.fingerprint_id);
            $('#sampleIndex').val(d.sample_index);
            $('#sampleRawData').val(d.raw_data);
            new bootstrap.Modal('#modalSample').show();
        });
    });

    $(document).on('click', '.btn-view-raw', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprint_samples/' + id, function (d) {
            $('#viewSampleId').text(d.id);
            $('#viewRawData').val(d.raw_data || '');
            $('#viewRawSize').text((d.raw_data ? d.raw_data.length : 0).toLocaleString() + ' chars');
            new bootstrap.Modal('#modalViewRaw').show();
        });
    });

    $('#btnCopyRaw').on('click', function () {
        var text = $('#viewRawData').val();
        navigator.clipboard.writeText(text).then(function () { toast('Berhasil dicopy'); });
    });

    $('#btnSaveSample').on('click', function () {
        var id = $('#sampleId').val();
        var data = {
            fingerprint_id: $('#sampleFpId').val(),
            sample_index:   $('#sampleIndex').val(),
            raw_data:       $('#sampleRawData').val()
        };

        if (!data.fingerprint_id) { toast('Fingerprint ID wajib diisi', 'warning'); return; }

        var url    = id ? '/api/fingerprint_samples/' + id : '/api/fingerprint_samples';
        var method = id ? 'PUT' : 'POST';

        $('#btnSaveSample').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: url, type: method,
            data: JSON.stringify(data), contentType: 'application/json',
            success: function () {
                bootstrap.Modal.getInstance('#modalSample').hide();
                dt.ajax.reload(null, false);
                toast(id ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan');
            },
            error: function (xhr) { toast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'error'); },
            complete: function () {
                $('#btnSaveSample').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan');
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        confirmDelete(function () {
            $.ajax({
                url: '/api/fingerprint_samples/' + id, type: 'DELETE',
                success: function () { dt.ajax.reload(null, false); toast('Data berhasil dihapus'); },
                error: function () { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

@extends('layouts.app')
@section('title', 'Fingerprint Templates')

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-layer-group me-2 text-info"></i>Data Fingerprint Templates</h6>
        <button class="btn btn-primary btn-sm" id="btnCreate" style="border-radius:8px;font-size:13px">
            <i class="fas fa-plus me-1"></i> Tambah Template
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dtTemplates" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fingerprint ID</th>
                        <th>Algorithm Version</th>
                        <th>Template Vector</th>
                        <th>Dibuat</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CREATE / EDIT --}}
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTemplateTitle">Tambah Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tplId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fingerprint ID <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="tplFpId" placeholder="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Algorithm Version</label>
                        <input type="text" class="form-control" id="tplAlgoVersion" placeholder="v1">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Template Vector (JSON)</label>
                        <textarea class="form-control font-monospace" id="tplVector" rows="8"
                            placeholder='[0.123, 0.456, 0.789, ...]'></textarea>
                        <small class="text-muted">Array JSON berisi vektor template hasil ekstraksi fitur sidik jari.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary btn-sm" id="btnSaveTemplate">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL VIEW VECTOR --}}
<div class="modal fade" id="modalViewVector" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Vector — #<span id="viewVectorId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <span class="badge bg-secondary" id="viewAlgoVersion">-</span>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Fingerprint ID: <strong id="viewVectorFpId">-</strong></small>
                    </div>
                </div>
                <textarea class="form-control font-monospace" id="viewVectorData" rows="12" readonly></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" id="btnCopyVector">
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

    var dt = $('#dtTemplates').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '/api/fingerprint_templates_datatables', type: 'POST' },
        columns: [
            { data: 'id',             name: 'id',             width: '55px' },
            { data: 'fingerprint_id', name: 'fingerprint_id' },
            {
                data: 'algorithm_version', name: 'algorithm_version',
                render: function (v) {
                    return '<span class="badge bg-secondary" style="font-size:11px">' + (v || 'v1') + '</span>';
                }
            },
            {
                data: 'template_vector', name: 'template_vector', orderable: false, searchable: false,
                render: function (v, t, row) {
                    if (!v) return '<span class="text-muted">-</span>';
                    var preview = typeof v === 'string' ? v.substring(0, 40) + '...' : JSON.stringify(v).substring(0, 40) + '...';
                    return '<button class="btn btn-sm btn-outline-info btn-view-vector" data-id="' + row.id + '" style="border-radius:6px;font-size:11px;padding:2px 10px">'
                        + '<i class="fas fa-eye me-1"></i> Lihat Vector</button>';
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
        $('#tplId, #tplFpId, #tplVector').val('');
        $('#tplAlgoVersion').val('v1');
    }

    $('#btnCreate').on('click', function () {
        resetModal();
        $('#modalTemplateTitle').text('Tambah Template');
        new bootstrap.Modal('#modalTemplate').show();
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprint_templates/' + id, function (d) {
            $('#modalTemplateTitle').text('Edit Template');
            $('#tplId').val(d.id);
            $('#tplFpId').val(d.fingerprint_id);
            $('#tplAlgoVersion').val(d.algorithm_version || 'v1');
            var vec = d.template_vector;
            $('#tplVector').val(typeof vec === 'string' ? vec : JSON.stringify(vec, null, 2));
            new bootstrap.Modal('#modalTemplate').show();
        });
    });

    $(document).on('click', '.btn-view-vector', function () {
        var id = $(this).data('id');
        $.get('/api/fingerprint_templates/' + id, function (d) {
            $('#viewVectorId').text(d.id);
            $('#viewVectorFpId').text(d.fingerprint_id);
            $('#viewAlgoVersion').text(d.algorithm_version || 'v1');
            var vec = d.template_vector;
            $('#viewVectorData').val(typeof vec === 'string' ? vec : JSON.stringify(vec, null, 2));
            new bootstrap.Modal('#modalViewVector').show();
        });
    });

    $('#btnCopyVector').on('click', function () {
        navigator.clipboard.writeText($('#viewVectorData').val()).then(function () { toast('Berhasil dicopy'); });
    });

    $('#btnSaveTemplate').on('click', function () {
        var id = $('#tplId').val();
        var data = {
            fingerprint_id:    $('#tplFpId').val(),
            algorithm_version: $('#tplAlgoVersion').val() || 'v1',
            template_vector:   $('#tplVector').val()
        };

        if (!data.fingerprint_id) { toast('Fingerprint ID wajib diisi', 'warning'); return; }

        var url    = id ? '/api/fingerprint_templates/' + id : '/api/fingerprint_templates';
        var method = id ? 'PUT' : 'POST';

        $('#btnSaveTemplate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: url, type: method,
            data: JSON.stringify(data), contentType: 'application/json',
            success: function () {
                bootstrap.Modal.getInstance('#modalTemplate').hide();
                dt.ajax.reload(null, false);
                toast(id ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan');
            },
            error: function (xhr) { toast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'error'); },
            complete: function () {
                $('#btnSaveTemplate').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan');
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        confirmDelete(function () {
            $.ajax({
                url: '/api/fingerprint_templates/' + id, type: 'DELETE',
                success: function () { dt.ajax.reload(null, false); toast('Data berhasil dihapus'); },
                error: function () { toast('Gagal menghapus data', 'error'); }
            });
        });
    });

});
</script>
@endpush

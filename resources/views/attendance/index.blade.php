@extends('layouts.main')

@section('title', 'Log Absensi')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Log Absensi</h6>
            </div>
            <div class="card-body">
                <table id="main-table" class="table table-hover nowrap w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Karyawan</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Device</th>
                            <th>Waktu</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let dt;
    let endpoint = 'attendance';

    drawDatatable();

    function drawDatatable() {
        dt = $('#main-table').DataTable({
            destroy: true,
            pageLength: 25,
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
                    data: 'employee_name', 
                    name: 'employee_name', 
                },
                { 
                    data: 'score',
                    name: 'score' 
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data) {
                        return data === 'match'
                            ? '<span class="badge-match"><i class="fas fa-check me-1"></i>Match</span>'
                            : '<span class="badge-no-match"><i class="fas fa-times me-1"></i>No Match</span>';
                    }
                },
                { 
                    data: 'device_id',  
                    name: 'device_id',  
                },
                { 
                    data: 'created_at', 
                    name: 'created_at', 
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

    $(document).on('click', '#delete-data', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        showPopupWithAction(
            'Apakah Anda Yakin ?',
            'Menghapus log absensi ini ?',
            'warning',
            'DELETE',
            null,
            BASE_URL + '/api/' + endpoint + '/' + id,
            '',
            ['#main-table']
        );
    });

    // Auto-refresh setiap 5 detik untuk real-time attendance
    setInterval(function () {
        dt.ajax.reload(null, false);
    }, 5000);
</script>
@endpush

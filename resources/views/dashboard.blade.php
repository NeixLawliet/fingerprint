@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#5c6bc0,#7986cb)">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="statUsers">-</div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#e91e63,#f06292)">
            <div class="stat-icon"><i class="fas fa-fingerprint"></i></div>
            <div class="stat-value" id="statFingerprints">-</div>
            <div class="stat-label">Fingerprints</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#00897b,#4db6ac)">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-value" id="statTemplates">-</div>
            <div class="stat-label">Templates</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#f57c00,#ffb74d)">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-value" id="statLogs">-</div>
            <div class="stat-label">Match Logs</div>
        </div>
    </div>
</div>

{{-- RECENT TABLES --}}
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6>Recent Fingerprints</h6>
                <a href="{{ route('fingerprints.index') }}" class="btn btn-sm btn-light" style="font-size:12px">
                    Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th><th>User ID</th><th>Finger Type</th>
                                <th>Quality</th><th>Device</th><th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tbFingerprints">
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-1"></i> Loading...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6>Recent Logs</h6>
                <a href="{{ route('fingerprint_logs.index') }}" class="btn btn-sm btn-light" style="font-size:12px">
                    Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th><th>User</th><th>Score</th><th>Status</th><th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody id="tbLogs">
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-1"></i> Loading...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    function fmt(val) { return val !== null && val !== undefined ? val : '-'; }
    function fmtDate(d) { return d ? d.substring(0, 10) : '-'; }

    // Stats
    $.get('/api/users?page=1', function (r) { $('#statUsers').text(r.total ?? '-'); });
    $.get('/api/fingerprints?page=1', function (r) { $('#statFingerprints').text(r.total ?? '-'); });
    $.get('/api/fingerprint_templates?page=1', function (r) { $('#statTemplates').text(r.total ?? '-'); });
    $.get('/api/fingerprint_logs?page=1', function (r) { $('#statLogs').text(r.total ?? '-'); });

    // Recent Fingerprints
    $.get('/api/fingerprints?all=1', function (r) {
        var rows = (r.data || r).slice(0, 8);
        if (!rows.length) {
            $('#tbFingerprints').html('<tr><td colspan="6" class="text-center text-muted py-3">Belum ada data</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function (d) {
            html += '<tr>'
                + '<td><span class="text-muted" style="font-size:12px">#' + d.id + '</span></td>'
                + '<td>' + fmt(d.user_id) + '</td>'
                + '<td><code style="font-size:11px">' + fmt(d.finger_type) + '</code></td>'
                + '<td>' + parseFloat(d.quality_score || 0).toFixed(1) + '</td>'
                + '<td style="font-size:12px">' + fmt(d.device_id) + '</td>'
                + '<td style="font-size:12px">' + fmtDate(d.created_at) + '</td>'
                + '</tr>';
        });
        $('#tbFingerprints').html(html);
    });

    // Recent Logs
    $.get('/api/fingerprint_logs?all=1', function (r) {
        var rows = (r.data || r).slice(0, 8);
        if (!rows.length) {
            $('#tbLogs').html('<tr><td colspan="5" class="text-center text-muted py-3">Belum ada data</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function (d) {
            var badge = d.status === 'match'
                ? '<span class="badge-match">match</span>'
                : '<span class="badge-no-match">not match</span>';
            html += '<tr>'
                + '<td><span class="text-muted" style="font-size:12px">#' + d.id + '</span></td>'
                + '<td>' + fmt(d.user_id) + '</td>'
                + '<td>' + parseFloat(d.similarity_score || 0).toFixed(3) + '</td>'
                + '<td>' + badge + '</td>'
                + '<td style="font-size:12px">' + fmtDate(d.created_at) + '</td>'
                + '</tr>';
        });
        $('#tbLogs').html(html);
    });

});
</script>
@endpush

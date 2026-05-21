@extends('layouts.main')
@section('title', 'Dashboard')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#5c6bc0,#7986cb)">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="statUsers">-</div>
            <div class="stat-label">Total Karyawan</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#00897b,#4db6ac)">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="statMatch">-</div>
            <div class="stat-label">Absen Berhasil (Hari Ini)</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#f57c00,#ffb74d)">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-value" id="statTotal">-</div>
            <div class="stat-label">Total Log Absensi</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6><i class="fas fa-history me-2 text-primary"></i>Log Absensi Terbaru</h6>
        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr>
                <th>#</th><th>Nama</th><th>Score</th><th>Status</th><th>Device</th><th>Waktu</th>
            </tr></thead>
            <tbody id="recentLogs">
                <tr><td colspan="6" class="text-center text-muted py-4">Memuat...</td></tr>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function loadStats() {
    $.get('/api/users', function(r) {
        $('#statUsers').text(r.data ? r.data.length : 0);
    });
    $.get('/api/attendance-stats', function(r) {
        $('#statMatch').text(r.today_match ?? 0);
        $('#statTotal').text(r.total ?? 0);
    });
}

function loadRecentLogs() {
    $.get('/api/attendance-logs?limit=8', function(r) {
        let rows = '';
        (r.data || []).forEach((l, i) => {
            const badge = l.status === 'match'
                ? '<span class="badge-match">Match</span>'
                : '<span class="badge-no-match">No Match</span>';
            rows += `<tr>
                <td>${i+1}</td>
                <td>${l.employee_name ?? '<em class="text-muted">Unknown</em>'}</td>
                <td>${l.score}</td>
                <td>${badge}</td>
                <td><small class="text-muted">${l.device_id ?? '-'}</small></td>
                <td><small>${l.created_at ?? '-'}</small></td>
            </tr>`;
        });
        $('#recentLogs').html(rows || '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada data</td></tr>');
    });
}

loadStats();
loadRecentLogs();
</script>
@endpush

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pringer Print | @yield('title', 'Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 255px;
            --sidebar-bg: #1a1d2e;
            --sidebar-hover: #252840;
            --sidebar-active: #5c6bc0;
            --topbar-h: 58px;
        }

        body { background: #f0f2f8; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-width); height: 100vh;
            background: var(--sidebar-bg); z-index: 1040;
            overflow-y: auto; transition: transform .28s ease;
            display: flex; flex-direction: column;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }

        .sidebar-brand {
            padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        .brand-icon {
            width: 36px; height: 36px; background: var(--sidebar-active);
            border-radius: 9px; display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 17px; flex-shrink: 0;
        }
        .brand-name { color: #fff; font-size: 15px; font-weight: 700; line-height: 1.2; }
        .brand-sub  { color: rgba(255,255,255,.35); font-size: 10.5px; }

        .sidebar-section {
            padding: 18px 20px 6px;
            color: rgba(255,255,255,.28); font-size: 9.5px;
            font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase;
        }
        .sidebar-menu { list-style: none; padding: 0 10px; margin: 0 0 4px; }
        .sidebar-menu li a {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 14px; color: rgba(255,255,255,.58);
            text-decoration: none; border-radius: 8px;
            font-size: 13px; font-weight: 500; transition: all .18s;
            margin-bottom: 2px;
        }
        .sidebar-menu li a:hover { background: var(--sidebar-hover); color: #fff; }
        .sidebar-menu li a.active { background: var(--sidebar-active); color: #fff; }
        .sidebar-menu li a .mi { width: 18px; text-align: center; font-size: 13px; flex-shrink: 0; }

        /* ===== MAIN ===== */
        .main-wrap { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }

        .topbar {
            height: var(--topbar-h); background: #fff;
            border-bottom: 1px solid #e8ebf0;
            display: flex; align-items: center; padding: 0 24px;
            position: sticky; top: 0; z-index: 1030; gap: 12px;
        }
        .topbar-title { font-size: 15px; font-weight: 600; color: #1a1d2e; flex: 1; }

        .content-area { padding: 26px 24px; flex: 1; }

        /* ===== CARD ===== */
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        .card-header {
            background: #fff; border-bottom: 1px solid #f0f2f8;
            border-radius: 12px 12px 0 0 !important; padding: 15px 20px;
        }
        .card-header h6 { font-size: 14px; font-weight: 600; color: #1a1d2e; margin: 0; }

        /* ===== STAT CARDS ===== */
        .stat-card { border-radius: 14px; padding: 22px; color: #fff; border: none; }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 12px;
            background: rgba(255,255,255,.22); display: flex;
            align-items: center; justify-content: center; font-size: 20px;
        }
        .stat-value { font-size: 30px; font-weight: 700; margin: 10px 0 3px; line-height: 1; }
        .stat-label { font-size: 12.5px; opacity: .82; }

        /* ===== TABLE ===== */
        .table > :not(caption) > * > * { padding: 11px 14px; vertical-align: middle; }
        .table thead th { font-size: 11.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .6px; color: #6b7280; border-bottom: 2px solid #f0f2f8; }

        /* ===== BADGES ===== */
        .badge-match    { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-no-match { background:#fee2e2; color:#991b1b; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-active   { background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-inactive { background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }

        /* ===== MODAL ===== */
        .modal-header { background: var(--sidebar-bg); color: #fff; border-radius: 12px 12px 0 0; padding: 16px 20px; }
        .modal-header .btn-close { filter: invert(1) opacity(.7); }
        .modal-header .modal-title { font-size: 14.5px; font-weight: 600; }
        .modal-content { border-radius: 12px; border: none; }
        .modal-footer { border-top: 1px solid #f0f2f8; padding: 14px 20px; }
        .form-label { font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-control, .form-select { font-size: 13px; border-radius: 8px; border-color: #e5e7eb; }
        .form-control:focus, .form-select:focus { border-color: var(--sidebar-active); box-shadow: 0 0 0 3px rgba(92,107,192,.15); }

        /* ===== DATATABLES ===== */
        .dataTables_wrapper .dataTables_filter input { border-radius: 8px; border: 1px solid #e5e7eb; padding: 5px 12px; font-size: 13px; }
        .dataTables_wrapper .dataTables_length select { border-radius: 8px; border: 1px solid #e5e7eb; padding: 4px 8px; font-size: 13px; }
        .paginate_button.current, .paginate_button.current:hover { background: var(--sidebar-active) !important; border-color: var(--sidebar-active) !important; color: #fff !important; border-radius: 6px !important; }
        .paginate_button:hover { background: #f0f2f8 !important; border-color: #f0f2f8 !important; color: #1a1d2e !important; border-radius: 6px !important; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
            .sidebar-overlay { display: block !important; }
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay d-none position-fixed top-0 start-0 w-100 h-100"
     style="background:rgba(0,0,0,.45);z-index:1035" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-fingerprint"></i></div>
        <div>
            <div class="brand-name">Pringer Print</div>
            <div class="brand-sub">Fingerprint System</div>
        </div>
    </div>

    <div class="sidebar-section">Main</div>
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-th-large"></i></span> Dashboard
            </a>
        </li>
    </ul>

    <div class="sidebar-section">Fingerprint</div>
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('fingerprints.index') }}" class="{{ request()->routeIs('fingerprints.*') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-fingerprint"></i></span> Fingerprints
            </a>
        </li>
        <li>
            <a href="{{ route('fingerprint_samples.index') }}" class="{{ request()->routeIs('fingerprint_samples.*') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-database"></i></span> Samples
            </a>
        </li>
        <li>
            <a href="{{ route('fingerprint_templates.index') }}" class="{{ request()->routeIs('fingerprint_templates.*') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-layer-group"></i></span> Templates
            </a>
        </li>
        <li>
            <a href="{{ route('fingerprint_logs.index') }}" class="{{ request()->routeIs('fingerprint_logs.*') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-clipboard-list"></i></span> Logs
            </a>
        </li>
    </ul>

    <div class="sidebar-section">Management</div>
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <span class="mi"><i class="fas fa-users"></i></span> Users
            </a>
        </li>
    </ul>
</nav>

<!-- MAIN WRAPPER -->
<div class="main-wrap">

    <!-- TOPBAR -->
    <div class="topbar">
        <button class="btn btn-sm btn-light d-md-none me-1" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title">@yield('title', 'Dashboard')</div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <small class="text-muted">
                <i class="fas fa-circle text-success" style="font-size:7px;vertical-align:2px"></i>
                &nbsp;Online
            </small>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content-area">
        @yield('content')
    </div>

</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ===== SIDEBAR TOGGLE =====
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('open');
        $('#sidebarOverlay').toggleClass('d-none');
    });
    $('#sidebarOverlay').on('click', function () {
        $('#sidebar').removeClass('open');
        $(this).addClass('d-none');
    });

    // ===== GLOBAL HELPERS =====
    function toast(msg, type = 'success') {
        Swal.fire({
            toast: true, position: 'top-end',
            icon: type, title: msg,
            showConfirmButton: false, timer: 3000, timerProgressBar: true
        });
    }

    function confirmDelete(callback) {
        Swal.fire({
            title: 'Hapus data?',
            text: 'Data yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) callback();
        });
    }

    $.ajaxSetup({ headers: { 'X-Requested-With': 'XMLHttpRequest' } });
</script>

@stack('scripts')
</body>
</html>

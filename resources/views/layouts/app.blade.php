<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>
    <script>
    (function(){try{var t=localStorage.getItem('cohas-theme');if(t!=='light'&&t!=='dark'){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();
    </script>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-brand.css') }}" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --sidebar-collapsed: 72px; }
        body { min-height: 100vh; background: #e2e8f0; margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .sidebar-wrap {
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1030;
            width: var(--sidebar-width); background: #fff; color: #334155;
            transition: width .25s ease; overflow-x: hidden; overflow-y: auto;
            border-right: 1px solid #e2e8f0; display: flex; flex-direction: column;
        }
        .sidebar-wrap.collapsed { width: var(--sidebar-collapsed); }
        .sidebar-header {
            padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
        }
        .sidebar-wrap .brand { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .sidebar-wrap .brand .logo-circle { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #fff; display: inline-flex; align-items: center; justify-content: center; padding: 3px; box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.06); }
        .sidebar-wrap .brand .logo-circle img { width: 100%; height: 100%; object-fit: cover; object-position: center 32%; display: block; }
        .sidebar-wrap .brand .app-name { font-weight: 700; color: #1e293b; font-size: 1.1rem; }
        .sidebar-wrap.collapsed .brand .app-name { opacity: 0; width: 0; overflow: hidden; }
        .sidebar-nav { flex: 1; padding: .75rem 0; overflow-y: auto; }
        .sidebar-wrap .nav-link {
            color: #475569; padding: .7rem 1.25rem; margin: 0 .5rem; border-radius: .5rem;
            display: flex; align-items: center; gap: .75rem; transition: all .2s; text-decoration: none; font-size: .9375rem;
        }
        .sidebar-wrap .nav-link i:first-of-type { font-size: 1.15rem; flex-shrink: 0; width: 1.5rem; text-align: center; }
        .sidebar-wrap .nav-link .bi-chevron-right { margin-left: auto; font-size: .7rem; opacity: .6; }
        .sidebar-wrap .nav-link:hover { background: #f1f5f9; color: #1e293b; }
        .sidebar-wrap .nav-link.active {
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%); color: #fff;
        }
        .sidebar-wrap .nav-link.active:hover { background: linear-gradient(135deg, #0d3651 0%, #0a1628 100%); color: #fff; }
        .sidebar-wrap .nav-link.active .bi-chevron-right { opacity: 1; }
        .sidebar-wrap .nav-group { margin: 0 .5rem .25rem; border-radius: .5rem; }
        .sidebar-wrap .nav-group-toggle {
            width: 100%; padding: .7rem 1.25rem; border: none; border-radius: .5rem; background: transparent;
            color: #475569; display: flex; align-items: center; gap: .75rem; font-size: .9375rem; text-align: left; cursor: pointer;
        }
        .sidebar-wrap .nav-group-toggle i:first-of-type { font-size: 1.15rem; flex-shrink: 0; width: 1.5rem; text-align: center; color: #0d3651; }
        .sidebar-wrap .nav-group-toggle:hover { background: #f1f5f9; color: #1e293b; }
        .sidebar-wrap .nav-group-toggle:hover i:first-of-type { color: #0a1628; }
        .sidebar-wrap .nav-group.expanded .nav-group-toggle { background: #f1f5f9; color: #1e293b; }
        .sidebar-wrap .nav-group.expanded .nav-group-toggle i:first-of-type { color: #0a1628; }
        .sidebar-wrap .nav-group-toggle .bi-chevron-down { margin-left: auto; font-size: .75rem; transition: transform .2s; }
        .sidebar-wrap .nav-group.expanded .nav-group-toggle .bi-chevron-down { transform: rotate(0deg); }
        .sidebar-wrap .nav-group:not(.expanded) .nav-group-toggle .bi-chevron-down { transform: rotate(-90deg); }
        .sidebar-wrap .nav-group .nav-group-sub { display: none; padding: .25rem 0 .25rem .5rem; list-style: none; margin: 0 .5rem; overflow: hidden; border-radius: .5rem; }
        .sidebar-wrap .nav-group.expanded .nav-group-sub { display: block; }
        .sidebar-wrap .nav-group-sub a { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; color: #64748b; text-decoration: none; font-size: .875rem; border-radius: .375rem; margin: .15rem 0; }
        .sidebar-wrap .nav-group-sub a:hover { background: #f1f5f9; color: #1e293b; }
        .sidebar-wrap .nav-group-sub a .bi { font-size: 1rem; width: 1.25rem; text-align: center; flex-shrink: 0; opacity: .85; }
        .sidebar-wrap .nav-group-sub a.active { background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%); color: #fff; }
        .sidebar-wrap .nav-group-sub a.active:hover { background: linear-gradient(135deg, #0d3651 0%, #0a1628 100%); color: #fff; }
        .sidebar-wrap .nav-group-sub a.active .bi { opacity: 1; }
        .sidebar-wrap li.nav-group-sub-label {
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #94a3b8;
            padding: .55rem .75rem .3rem;
            margin-top: .35rem;
            list-style: none;
            pointer-events: none;
        }
        .sidebar-wrap .nav-group-sub > li.nav-group-sub-label:first-child { margin-top: 0; padding-top: .15rem; }
        .sidebar-wrap .sidebar-section-heading {
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #94a3b8;
            padding: .65rem 1.25rem .25rem;
            margin: 0 .5rem;
        }
        .sidebar-wrap .sidebar-section-heading:first-of-type { padding-top: .35rem; }
        .sidebar-wrap.collapsed .sidebar-section-heading { display: none; }
        .sidebar-wrap.collapsed .nav-link span, .sidebar-wrap.collapsed .nav-link .bi-chevron-right,
        .sidebar-wrap.collapsed .nav-group-toggle span, .sidebar-wrap.collapsed .nav-group-sub { opacity: 0; width: 0; overflow: hidden; height: 0; padding: 0; margin: 0; }
        .sidebar-wrap.collapsed .nav-group.expanded .nav-group-sub { display: none; }
        .main-wrap { transition: margin-left .25s ease; min-height: 100vh; display: flex; flex-direction: column; background: #f1f5f9; }
        .main-wrap.expanded { margin-left: var(--sidebar-width); }
        .main-wrap.collapsed { margin-left: var(--sidebar-collapsed); }
        .topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0; padding: .6rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .topbar .topbar-left { display: flex; align-items: center; gap: .5rem; min-width: 0; }
        .topbar .login-as { font-size: .8125rem; color: #64748b; }
        .topbar .topbar-reg-no { font-size: .8125rem; color: #1e293b; font-weight: 600; }
        .topbar .topbar-datetime { font-size: .8125rem; color: #64748b; text-align: center; flex: 1; }
        .topbar .topbar-right { display: flex; align-items: center; gap: .75rem; flex-shrink: 0; }
        .lang-toggle {
            display: inline-flex;
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            overflow: hidden;
            background: #f8fafc;
        }
        .lang-toggle__btn {
            padding: .25rem .55rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-decoration: none;
            color: #64748b;
            line-height: 1.4;
        }
        .lang-toggle__btn:hover { background: #e2e8f0; color: #1e293b; }
        .lang-toggle__btn.is-active {
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
            color: #fff;
        }
        .navbar-dark .lang-toggle { border-color: rgba(255,255,255,.25); background: rgba(255,255,255,.08); }
        .navbar-dark .lang-toggle__btn { color: rgba(255,255,255,.9); }
        .navbar-dark .lang-toggle__btn:hover { background: rgba(255,255,255,.15); color: #fff; }
        .navbar-dark .lang-toggle__btn.is-active { background: rgba(255,255,255,.95); color: #0d3651; }
        .topbar .profile-dropdown { position: relative; }
        .topbar .notif-dropdown { position: relative; }
        .topbar .topbar-bell {
            width: 40px; height: 40px; border-radius: 50%;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
            display: inline-flex; align-items: center; justify-content: center;
            position: relative;
        }
        .topbar .topbar-bell .notif-badge {
            position: absolute; top: -3px; right: -3px;
            min-width: 18px; height: 18px; border-radius: 9px;
            font-size: .65rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0 .25rem;
        }
        .topbar .notif-menu {
            display: none; position: absolute; top: 100%; right: 0; margin-top: .25rem; width: 360px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,.15);
            z-index: 1050;
        }
        .topbar .notif-menu.show { display: block; }
        .topbar .notif-menu-header { padding: .65rem .9rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .topbar .notif-list { max-height: 320px; overflow-y: auto; }
        .topbar .notif-item { display: block; padding: .65rem .9rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: #334155; }
        .topbar .notif-item:hover { background: #f8fafc; color: #0f172a; }
        .topbar .notif-item-title { font-size: .82rem; font-weight: 600; }
        .topbar .notif-item-msg { font-size: .75rem; color: #64748b; }
        .topbar .notif-menu-footer { padding: .55rem .9rem; text-align: center; }
        .topbar .topbar-avatar {
            width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; border: none; cursor: pointer; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%); color: #fff; font-size: .875rem; font-weight: 600;
            overflow: hidden; object-fit: cover; position: relative;
        }
        .topbar .topbar-avatar::after {
            content: '';
            position: absolute;
            right: 2px; bottom: 2px;
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #22c55e;
            border: 2px solid #fff;
        }
        .topbar .topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .topbar .profile-menu {
            display: none; position: absolute; top: 100%; right: 0; margin-top: .25rem; min-width: 200px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,.15);
            padding: .5rem 0; z-index: 1050;
        }
        .topbar .profile-menu.show { display: block; }
        .topbar .profile-menu .profile-menu-header { padding: .5rem 1rem; border-bottom: 1px solid #e2e8f0; }
        .topbar .profile-menu .profile-menu-header strong { font-size: .875rem; }
        .topbar .profile-menu .profile-menu-header small { display: block; color: #64748b; font-size: .75rem; }
        .topbar .profile-menu .profile-menu-item { display: block; width: 100%; padding: .5rem 1rem; text-align: left; border: none; background: none; color: #475569; font-size: .875rem; cursor: pointer; text-decoration: none; }
        .topbar .profile-menu .profile-menu-item:hover { background: #f1f5f9; color: #1e293b; }
        .navbar .navbar-brand .logo-circle.navbar-logo { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: rgba(255,255,255,.2); display: inline-flex; align-items: center; justify-content: center; padding: 3px; flex-shrink: 0; }
        .navbar .navbar-brand .logo-circle.navbar-logo img { width: 100%; height: 100%; object-fit: cover; object-position: center 32%; display: block; }
        .main-content {
            flex: 1; padding: 1.5rem 1.75rem; background: #f1f5f9;
        }
        .app-footer {
            flex-shrink: 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 0.875rem 1.75rem;
            font-size: 0.8125rem;
            color: #64748b;
            text-align: center;
        }
        .app-footer p { margin: 0; }
        .card-modern {
            border: 0; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .card-modern .card-header {
            background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; font-weight: 600; border-radius: 1rem 1rem 0 0;
        }
        .page-header {
            margin-bottom: 1.5rem;
        }
        .page-header .page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; }
        .btn-modern { border-radius: .5rem; font-weight: 500; padding: .5rem 1rem; }
        .table-modern thead th { font-weight: 600; color: #475569; font-size: .8125rem; text-transform: uppercase; letter-spacing: .02em; padding: .875rem 1rem; }
        .table-modern tbody td { padding: .875rem 1rem; vertical-align: middle; }
        .pagination { margin-bottom: 0; flex-wrap: wrap; justify-content: center; gap: .15rem; }
        .pagination .page-item { margin: 0; }
        .pagination .page-item .page-link {
            border-radius: .35rem;
            padding: .28rem .5rem !important;
            font-size: .8125rem;
            line-height: 1.2;
            min-height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            padding: .28rem .4rem !important;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .pagination .page-item.disabled .page-link { opacity: .55; }
        .pagination.pagination-sm .page-item .page-link {
            padding: .22rem .45rem !important;
            font-size: .78rem;
            min-width: 1.7rem;
            min-height: 1.7rem;
        }
        .pagination.pagination-sm .page-item:first-child .page-link,
        .pagination.pagination-sm .page-item:last-child .page-link {
            padding: .22rem .4rem !important;
            font-size: .8rem;
        }
        .pagination.pagination-sm .page-link.pagination-chevron {
            font-size: .88rem;
            line-height: 1;
            padding: .2rem .35rem !important;
            min-width: 1.55rem;
            font-weight: 700;
        }
        .pagination-nav-compact .pagination { margin-bottom: 0; }
        /* Landing-style student (and similar) pages */
        .page-header-landing { background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%); border-radius: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; color: #fff; }
        .page-header-landing .page-title-landing { font-size: 1.35rem; font-weight: 700; margin: 0 0 .25rem; color: #fff; }
        .page-header-landing .page-subtitle-landing { font-size: .875rem; opacity: .92; margin: 0; color: rgba(255,255,255,.9); }
        .page-header-landing .btn-outline-light { border-color: rgba(255,255,255,.4); color: #fff; }
        .page-header-landing .btn-outline-light:hover { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.6); color: #fff; }
        .page-header-landing .btn-cohas-delete,
        .page-header-landing .btn-cohas-edit,
        .page-header-landing .btn-outline-danger,
        .page-header-landing .btn-outline-primary { background: #fff !important; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
        .page-header-landing .btn-cohas-delete,
        .page-header-landing .btn-outline-danger { color: #991b1b !important; border: 2px solid #dc2626 !important; }
        .page-header-landing .btn-cohas-edit,
        .page-header-landing .btn-outline-primary { color: #0e3583 !important; border: 2px solid #1a4fb5 !important; }
        .card-header-landing .btn-cohas-delete,
        .card-header-landing .btn-cohas-edit,
        .card-header-landing .btn-outline-danger,
        .card-header-landing .btn-outline-primary { background: #fff !important; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
        .card-header-landing .btn-cohas-delete,
        .card-header-landing .btn-outline-danger { color: #991b1b !important; border: 2px solid #dc2626 !important; }
        .card-header-landing .btn-cohas-edit,
        .card-header-landing .btn-outline-primary { color: #0e3583 !important; border: 2px solid #1a4fb5 !important; }
        .page-header-landing .text-danger,
        .card-header-landing .text-danger { color: #fff !important; background: #dc2626; padding: .12rem .45rem; border-radius: .35rem; font-weight: 600; }
        .card-landing { border: none; border-radius: 1rem; box-shadow: 0 4px 20px rgba(10, 22, 40, .08); overflow: hidden; }
        .card-landing .card-header-landing { background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%); color: #fff; padding: .875rem 1.25rem; font-weight: 600; font-size: 1rem; }
        .card-landing .card-body { background: #fff; padding: 1.25rem 1.5rem; }
        .card-landing .table thead th { background: #f1f5f9; color: #334155; font-weight: 600; font-size: .8125rem; padding: .75rem 1rem; border-bottom: 1px solid #e2e8f0; }
        .card-landing .table tbody td { padding: .75rem 1rem; vertical-align: middle; }
        .card-landing .table tbody tr:hover { background: #f8fafc; }
        /* Students list & profile — readable wide tables */
        .card-landing .table-students-landing { margin-bottom: 0; font-size: .875rem; }
        .card-landing .table-students-landing thead th {
            white-space: nowrap;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #475569;
            font-weight: 700;
            padding: .65rem .85rem;
            border-bottom: 2px solid #cbd5e1;
            background: #e8eef5 !important;
        }
        .card-landing .table-students-landing tbody td { padding: .65rem .85rem; vertical-align: middle; border-color: #e2e8f0; }
        .card-landing .table-students-landing tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.92); }
        .card-landing .table-students-landing tbody tr:hover { background: #e8f0fa !important; }
        .card-landing .table-students-landing .reg-cell { font-weight: 600; font-variant-numeric: tabular-nums; color: #0f172a; }
        .card-landing .table-students-landing .nacte-cell code {
            font-size: .74rem;
            color: #334155;
            background: #f1f5f9;
            padding: .15rem .35rem;
            border-radius: .25rem;
            word-break: break-word;
            white-space: normal;
            line-height: 1.35;
        }
        .card-landing .table-students-landing .name-cell { min-width: 9rem; max-width: 14rem; }
        .card-landing .table-students-landing .name-cell .name-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
        }
        .card-landing .table-students-landing .actions-cell { width: 1%; white-space: nowrap; }
        .card-landing .table-students-landing .actions-cell .btn { padding: .35rem .5rem; font-size: .8125rem; }
        .card-landing .table-students-landing .actions-cell .btn-group .btn { border-radius: .375rem; }
        .table-responsive-students-landing {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .students-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem 1.1rem;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            font-size: .8125rem;
            color: #64748b;
        }
        .students-toolbar strong { color: #334155; }
        .student-detail-dl dt { font-weight: 500; padding-top: .35rem; padding-bottom: .35rem; }
        .student-detail-dl dd { padding-top: .35rem; padding-bottom: .35rem; border-bottom: 1px solid #f1f5f9; margin-bottom: 0; }
        .student-detail-dl dd:last-of-type { border-bottom: none; }
        .student-glance-card { background: linear-gradient(180deg, #f8fafc 0%, #fff 40%); }
        .student-glance-card .glance-item { padding: .65rem 0; border-bottom: 1px solid #e2e8f0; }
        .student-glance-card .glance-item:last-child { border-bottom: none; }
        .student-glance-card .glance-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; margin-bottom: .15rem; }
        .student-glance-card .glance-value { font-size: .9375rem; color: #0f172a; font-weight: 600; word-break: break-word; }
        .student-breadcrumb { font-size: .875rem; color: #64748b; margin-bottom: 1rem; }
        .student-breadcrumb a { color: #0d3651; text-decoration: none; }
        .student-breadcrumb a:hover { text-decoration: underline; }
        .form-section-title { font-size: .875rem; font-weight: 600; color: #334155; margin: 1rem 0 .5rem; padding-bottom: .35rem; border-bottom: 1px solid #e2e8f0; }
        .form-section-title:first-child { margin-top: 0; }
        /* Buttons: landing-page blue */
        .btn-primary, a.btn-primary {
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
            border-color: #0d3651;
            color: #fff;
        }
        .btn-primary:hover, a.btn-primary:hover {
            background: linear-gradient(135deg, #0d3651 0%, #0a1628 100%);
            border-color: #0a1628;
            color: #fff;
        }
        .btn-outline-primary, a.btn-outline-primary {
            color: #0d3651;
            border-color: #0d3651;
        }
        .btn-outline-primary:hover, a.btn-outline-primary:hover {
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
            border-color: #0d3651;
            color: #fff;
        }
        .btn-success, a.btn-success {
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
            border-color: #166534;
            color: #fff;
        }
        .btn-success:hover, a.btn-success:hover {
            background: linear-gradient(135deg, #15803d 0%, #14532d 100%);
            border-color: #14532d;
            color: #fff;
        }
        /* Segment navigation (NTA level, user type, programme filters) */
        .cohas-segment-nav {
            background: #fff;
            border-radius: 1rem;
            padding: 1rem 1.25rem 1.25rem;
            box-shadow: 0 4px 24px rgba(10, 22, 40, .07), 0 1px 0 rgba(10, 22, 40, .04);
            border: 1px solid rgba(226, 232, 240, .9);
        }
        .cohas-segment-nav__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem .75rem;
            margin-bottom: .875rem;
        }
        .cohas-segment-nav__eyebrow {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .cohas-segment-nav__clear {
            font-size: .8125rem;
            font-weight: 600;
            color: #0d3651;
            text-decoration: none;
            padding: .35rem .65rem;
            border-radius: 2rem;
            background: rgba(13, 54, 81, .08);
            transition: background .2s ease, color .2s ease;
        }
        .cohas-segment-nav__clear:hover { background: rgba(13, 54, 81, .14); color: #0a1628; }
        .cohas-segment-nav__hint { font-size: .8125rem; }
        .cohas-segment-nav__grid {
            display: grid;
            gap: .75rem;
        }
        .cohas-segment-nav__grid--cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cohas-segment-nav__grid--cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .cohas-segment-nav__grid--cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 767.98px) {
            .cohas-segment-nav__grid--cols-2,
            .cohas-segment-nav__grid--cols-3,
            .cohas-segment-nav__grid--cols-4 { grid-template-columns: 1fr; }
        }
        .cohas-segment-nav__item {
            display: flex;
            align-items: center;
            gap: .875rem;
            padding: 1rem 1.1rem;
            border-radius: .875rem;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
            text-decoration: none;
            color: inherit;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
            position: relative;
            overflow: hidden;
        }
        .cohas-segment-nav__item::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--segment-accent, #0d3651);
            opacity: .85;
            border-radius: .875rem 0 0 .875rem;
        }
        .cohas-segment-nav__item--l4 { --segment-accent: #059669; }
        .cohas-segment-nav__item--l5 { --segment-accent: #2563eb; }
        .cohas-segment-nav__item--l6 { --segment-accent: #7c3aed; }
        .cohas-segment-nav__item--cmt { --segment-accent: #059669; }
        .cohas-segment-nav__item--mlt { --segment-accent: #7c3aed; }
        .cohas-segment-nav__item--students { --segment-accent: #2563eb; }
        .cohas-segment-nav__item--staff { --segment-accent: #d97706; }
        .cohas-segment-nav__item--all { --segment-accent: #64748b; }
        .cohas-segment-nav__item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(10, 22, 40, .1);
            border-color: #cbd5e1;
        }
        .cohas-segment-nav__item.is-active {
            border-color: transparent;
            background: linear-gradient(135deg, #0a1628 0%, #0d3651 55%, #134e6f 100%);
            box-shadow: 0 12px 32px rgba(10, 22, 40, .22);
            color: #fff;
        }
        .cohas-segment-nav__item.is-active::before { opacity: 0; }
        .cohas-segment-nav__item.is-active .cohas-segment-nav__icon {
            background: rgba(255, 255, 255, .18);
            color: #fff;
        }
        .cohas-segment-nav__item.is-active .cohas-segment-nav__title { color: #fff; }
        .cohas-segment-nav__item.is-active .cohas-segment-nav__count { color: rgba(255, 255, 255, .82); }
        .cohas-segment-nav__item.is-active .cohas-segment-nav__chevron {
            opacity: 1;
            color: #fff;
            transform: translateX(2px);
        }
        .cohas-segment-nav__icon {
            flex-shrink: 0;
            width: 2.75rem;
            height: 2.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            background: rgba(13, 54, 81, .08);
            color: var(--segment-accent, #0d3651);
            font-size: 1.35rem;
        }
        .cohas-segment-nav__body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }
        .cohas-segment-nav__title {
            font-weight: 700;
            font-size: 1rem;
            line-height: 1.2;
            color: #0f172a;
        }
        .cohas-segment-nav__count {
            font-size: .8125rem;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }
        .cohas-segment-nav__chevron {
            flex-shrink: 0;
            font-size: 1.5rem;
            line-height: 1;
            color: #94a3b8;
            opacity: .6;
            transition: transform .2s ease, opacity .2s ease, color .2s ease;
        }
        .cohas-segment-nav__item:hover .cohas-segment-nav__chevron {
            opacity: 1;
            transform: translateX(2px);
        }
        .cohas-filter-panel {
            background: #fff;
            border-radius: 1rem;
            padding: 1rem 1.25rem 1.25rem;
            box-shadow: 0 4px 24px rgba(10, 22, 40, .07);
            border: 1px solid rgba(226, 232, 240, .9);
            margin-bottom: 1rem;
        }
        .cohas-filter-panel__title {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: .875rem;
        }
        .cohas-filter-panel .form-control,
        .cohas-filter-panel .form-select {
            border-radius: .5rem;
            border-color: #e2e8f0;
        }
        .cohas-filter-panel .form-control:focus,
        .cohas-filter-panel .form-select:focus {
            border-color: #0d3651;
            box-shadow: 0 0 0 .2rem rgba(13, 54, 81, .12);
        }
        .users-actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            padding: .75rem 1rem;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        .card-landing .table-users-landing thead th {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: #e8eef5 !important;
            border-bottom: 2px solid #cbd5e1;
        }
        @media (max-width: 991.98px) {
            .sidebar-wrap { transform: translateX(-100%); }
            .sidebar-wrap.show { transform: translateX(0); width: var(--sidebar-width) !important; }
            .main-wrap { margin-left: 0 !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @include('layouts.partials.cohas-page-loader')
    @auth
    <aside class="sidebar-wrap" id="sidebar" aria-label="Sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <a href="{{ route('dashboard') }}" class="logo-circle d-inline-flex"><img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}"></a>
                <span class="app-name">{{ config('app.name') }}</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-house-door"></i><span>{{ __('ui.nav.dashboard') }}</span><i class="bi bi-chevron-right"></i>
            </a>
            @if(auth()->user()->isGuardian())
            <a class="nav-link {{ request()->routeIs('parent.portal') ? 'active' : '' }}" href="{{ route('parent.portal') }}">
                <i class="bi bi-people"></i><span>Parent portal</span><i class="bi bi-chevron-right"></i>
            </a>
            <a class="nav-link" href="{{ route('college-documents.index') }}"><i class="bi bi-folder2-open"></i><span>College documents</span><i class="bi bi-chevron-right"></i></a>
            @if(config('college.integrations.moodle_url'))
            <a class="nav-link" href="{{ config('college.integrations.moodle_url') }}" target="_blank" rel="noopener"><i class="bi bi-mortarboard"></i><span>Moodle</span><i class="bi bi-box-arrow-up-right small"></i></a>
            @endif
            @elseif(auth()->user()->isStudent())
            @php $me = auth()->user()->student; @endphp
            @if($me)
            @php
                $studentAcademicsActive = request()->routeIs(
                    'my.modules',
                    'my.module-registration',
                    'my.timetable',
                    'my.assessments',
                    'my.module-results',
                    'results.portal',
                    'results.transcript*'
                );
                $assessmentsActive = request()->routeIs('my.assessments');
                $moduleResultsActive = request()->routeIs('my.module-results');
                $timetableActive = request()->routeIs('my.timetable');
            @endphp
            <a class="nav-link {{ request()->routeIs('students.show') && request()->route('student')?->id === $me->id ? 'active' : '' }}" href="{{ route('students.show', $me) }}">
                <i class="bi bi-mortarboard"></i><span>Graduation</span><i class="bi bi-chevron-right"></i>
            </a>
            <div class="nav-group {{ $studentAcademicsActive ? 'expanded' : '' }}" id="navGroupStudentAcademics">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ $studentAcademicsActive ? 'true' : 'false' }}" aria-controls="navGroupStudentAcademicsSub">
                    <i class="bi bi-journal-bookmark"></i><span>Academics</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupStudentAcademicsSub">
                    <li>
                        <a href="{{ route('my.module-registration') }}" class="{{ request()->routeIs('my.module-registration*') ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>Register modules
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.modules') }}" class="{{ request()->routeIs('my.modules') ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>My Modules Detail
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.assessments') }}" class="{{ $assessmentsActive ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>My Assessments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.module-results') }}" class="{{ $moduleResultsActive ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>My Modules Result
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.timetable') }}" class="{{ $timetableActive ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>My Class Timetable
                        </a>
                    </li>
                </ul>
            </div>
            <a class="nav-link {{ request()->routeIs('my.registrations') ? 'active' : '' }}" href="{{ route('my.registrations') }}">
                <i class="bi bi-ui-checks-grid"></i><span>Online registration</span><i class="bi bi-chevron-right"></i>
            </a>
            <a class="nav-link {{ request()->routeIs('college-documents.*') ? 'active' : '' }}" href="{{ route('college-documents.index') }}">
                <i class="bi bi-folder2-open"></i><span>College documents</span><i class="bi bi-chevron-right"></i>
            </a>
            @php $studentFinanceActive = request()->routeIs('students.ledger'); @endphp
            <div class="nav-group {{ $studentFinanceActive ? 'expanded' : '' }}" id="navGroupStudentFinance">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ $studentFinanceActive ? 'true' : 'false' }}" aria-controls="navGroupStudentFinanceSub">
                    <i class="bi bi-wallet2"></i><span>{{ __('ui.nav.finance') }}</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupStudentFinanceSub">
                    <li>
                        <a href="{{ route('students.ledger', $me) }}" class="{{ $studentFinanceActive ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>Financial Statement
                        </a>
                    </li>
                </ul>
            </div>
            <a class="nav-link {{ request()->routeIs('my.accommodation') ? 'active' : '' }}" href="{{ route('my.accommodation') }}">
                <i class="bi bi-building"></i><span>Accommodation</span><i class="bi bi-chevron-right"></i>
            </a>
            @php $studentClinicalActive = request()->routeIs('my.clinical.*'); @endphp
            <div class="nav-group {{ $studentClinicalActive ? 'expanded' : '' }}" id="navGroupStudentClinical">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ $studentClinicalActive ? 'true' : 'false' }}" aria-controls="navGroupStudentClinicalSub">
                    <i class="bi bi-hospital"></i><span>Clinical training</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupStudentClinicalSub">
                    <li>
                        <a href="{{ route('my.clinical.placement') }}" class="{{ request()->routeIs('my.clinical.placement') ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>My placement
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.clinical.logbook.index') }}" class="{{ request()->routeIs('my.clinical.logbook*') ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>Clinical logbook
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('my.clinical.remediation') }}" class="{{ request()->routeIs('my.clinical.remediation') ? 'active' : '' }}">
                            <i class="bi bi-circle"></i>Remediation
                        </a>
                    </li>
                </ul>
            </div>
            <a class="nav-link {{ request()->routeIs('my.exams') ? 'active' : '' }}" href="{{ route('my.exams') }}">
                <i class="bi bi-ticket-perforated"></i><span>Examination ticket</span><i class="bi bi-chevron-right"></i>
            </a>
            <a class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}">
                <i class="bi bi-calendar3"></i><span>College calendar</span><i class="bi bi-chevron-right"></i>
            </a>
            @else
            <a class="nav-link {{ request()->routeIs('profile.complete*') ? 'active' : '' }}" href="{{ route('profile.complete') }}">
                <i class="bi bi-person-lines-fill"></i><span>Complete Profile</span><i class="bi bi-chevron-right"></i>
            </a>
            @endif
            @else
            @php
                $showAcademicMenu = auth()->user()->canModule('programmes', 'view')
                    || auth()->user()->canModule('semesters', 'view')
                    || auth()->user()->canModule('courses', 'view')
                    || auth()->user()->canModule('registrations', 'view')
                    || auth()->user()->canModule('results', 'view')
                    || auth()->user()->canModule('question_bank', 'view')
                    || auth()->user()->canModule('exams', 'view')
                    || auth()->user()->canModule('timetable', 'view')
                    || auth()->user()->canModule('clinical', 'view');
            @endphp
            @if($showAcademicMenu)
            <div class="nav-group {{ request()->routeIs('programmes.*', 'semesters.*', 'courses.*', 'semester-registrations.*', 'registration-wizard.*', 'results.*', 'exam-slots.*', 'timetable-slots.*', 'question-bank.*', 'clinical-rotations.*', 'clinical-procedures.*', 'clinical-logbook.*', 'clinical.framework') ? 'expanded' : '' }}" id="navGroupAcademics">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('programmes.*', 'semesters.*', 'courses.*', 'semester-registrations.*', 'registration-wizard.*', 'results.*', 'exam-slots.*', 'timetable-slots.*', 'question-bank.*', 'clinical-rotations.*', 'clinical-procedures.*', 'clinical-logbook.*', 'clinical.framework') ? 'true' : 'false' }}" aria-controls="navGroupAcademicsSub">
                    <i class="bi bi-mortarboard-fill"></i><span>{{ __('ui.nav.academics') }}</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupAcademicsSub">
                    @if(auth()->user()->canModule('programmes', 'view') || auth()->user()->canModule('semesters', 'view'))
                    <li class="nav-group-sub-label">Programme &amp; terms</li>
                    @endif
                    @canModule('programmes', 'view')
                    <li><a href="{{ route('programmes.index') }}" class="{{ request()->routeIs('programmes.*') ? 'active' : '' }}" title="View programmes (ICT administrator adds new programmes)"><i class="bi bi-collection"></i>Programmes</a></li>
                    @endcanModule
                    @canModule('semesters', 'view')
                    <li><a href="{{ route('semesters.index') }}" class="{{ request()->routeIs('semesters.*') ? 'active' : '' }}"><i class="bi bi-calendar-range"></i>Semesters</a></li>
                    @endcanModule
                    @canModule('courses', 'view')
                    <li class="nav-group-sub-label">Module catalogue</li>
                    <li><a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}" title="Full tree or filter by teaching semester"><i class="bi bi-diagram-3"></i>Module catalogue</a></li>
                    @endcanModule
                    @canModule('registrations', 'view')
                    <li class="nav-group-sub-label">Registration</li>
                    <li><a href="{{ route('semester-registrations.index') }}" class="{{ request()->routeIs('semester-registrations.*', 'registration-wizard.*') ? 'active' : '' }}"><i class="bi bi-ui-checks-grid"></i>Student registration</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('results', 'view') || auth()->user()->canModule('question_bank', 'view'))
                    <li class="nav-group-sub-label">Marks &amp; exams</li>
                    @endif
                    @canModule('results', 'view')
                    <li><a href="{{ route('results.index') }}" class="{{ request()->routeIs('results.*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i>Results</a></li>
                    @endcanModule
                    @canModule('question_bank', 'view')
                    <li><a href="{{ route('question-bank.index') }}" class="{{ request()->routeIs('question-bank.*') ? 'active' : '' }}"><i class="bi bi-question-circle"></i>Question bank</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('exams', 'view') || auth()->user()->canModule('timetable', 'view'))
                    <li class="nav-group-sub-label">Timetables</li>
                    @endif
                    @canModule('exams', 'view')
                    <li><a href="{{ route('exam-slots.index') }}" class="{{ request()->routeIs('exam-slots.*') ? 'active' : '' }}"><i class="bi bi-calendar-event"></i>Exam schedule</a></li>
                    @endcanModule
                    @canModule('timetable', 'view')
                    <li><a href="{{ route('timetable-slots.index') }}" class="{{ request()->routeIs('timetable-slots.*') ? 'active' : '' }}"><i class="bi bi-clock-history"></i>Class schedule</a></li>
                    @endcanModule
                    @canModule('clinical', 'view')
                    <li class="nav-group-sub-label">Clinical training</li>
                    <li><a href="{{ route('clinical-rotations.index') }}" class="{{ request()->routeIs('clinical-rotations.*') ? 'active' : '' }}" title="Rotation groups, hospitals, attendance"><i class="bi bi-hospital"></i>Rotation rounds</a></li>
                    <li><a href="{{ route('clinical-logbook.index') }}" class="{{ request()->routeIs('clinical-logbook.*', 'clinical.framework') ? 'active' : '' }}"><i class="bi bi-journal-medical"></i>Logbook review</a></li>
                    <li><a href="{{ route('clinical-procedures.index') }}" class="{{ request()->routeIs('clinical-procedures.*') ? 'active' : '' }}"><i class="bi bi-list-check"></i>Procedures catalogue</a></li>
                    <li><a href="{{ route('clinical.coordinator') }}" class="{{ request()->routeIs('clinical.coordinator', 'clinical.reports', 'clinical.progression.*') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i>Coordinator dashboard</a></li>
                    @endcanModule
                </ul>
            </div>
            @endif
            @php
                $showFinanceMenu = auth()->user()->canModule('finance_fees', 'view')
                    || auth()->user()->canModule('finance_payments', 'view')
                    || auth()->user()->canModule('finance_reports', 'view');
            @endphp
            @if($showFinanceMenu)
            <div class="nav-group {{ request()->routeIs('fee-structures.*', 'payments.*', 'payment-instalments.*', 'reports.*') ? 'expanded' : '' }}" id="navGroupFinance">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('fee-structures.*', 'payments.*', 'reports.*') ? 'true' : 'false' }}" aria-controls="navGroupFinanceSub">
                    <i class="bi bi-currency-exchange"></i><span>{{ __('ui.nav.finance') }}</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupFinanceSub">
                    @canModule('finance_fees', 'view')
                    <li class="nav-group-sub-label">Fees &amp; setup</li>
                    <li><a href="{{ route('fee-structures.index') }}" class="{{ request()->routeIs('fee-structures.*') ? 'active' : '' }}"><i class="bi bi-table"></i>Fees</a></li>
                    @endcanModule
                    @canModule('finance_payments', 'view')
                    <li class="nav-group-sub-label">Payments</li>
                    <li><a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}"><i class="bi bi-clock-history"></i>Payment history</a></li>
                    <li><a href="{{ route('payment-instalments.index') }}" class="{{ request()->routeIs('payment-instalments.*') ? 'active' : '' }}"><i class="bi bi-calendar2-check"></i>Payment instalments</a></li>
                    @endcanModule
                    @canModule('finance_reports', 'view')
                    <li class="nav-group-sub-label">Reports</li>
                    <li><a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.index') ? 'active' : '' }}"><i class="bi bi-graph-up"></i>Financial reports</a></li>
                    <li><a href="{{ route('reports.payment-by-programme') }}" class="{{ request()->routeIs('reports.payment-by-programme') ? 'active' : '' }}"><i class="bi bi-pie-chart"></i>Payments by programme</a></li>
                    <li><a href="{{ route('reports.nactvet-hub') }}" class="{{ request()->routeIs('reports.nactvet*') ? 'active' : '' }}"><i class="bi bi-building"></i>NACTVET pack</a></li>
                    @endcanModule
                </ul>
            </div>
            @endif
            <div class="nav-group {{ request()->routeIs('students.*', 'message-logs.*', 'announcements.*', 'leave-applications.*', 'graduation-clearances.*', 'conduct-records.*', 'transcript-requests.*', 'calendar.*') ? 'expanded' : '' }}" id="navGroupCollegeOffice">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('students.*', 'message-logs.*', 'announcements.*', 'leave-applications.*', 'graduation-clearances.*', 'conduct-records.*', 'calendar.*') ? 'true' : 'false' }}" aria-controls="navGroupCollegeOfficeSub">
                    <i class="bi bi-briefcase"></i><span>College office</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupCollegeOfficeSub">
                    <li class="nav-group-sub-label">People</li>
                    <li><a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}"><i class="bi bi-people"></i>Students</a></li>
                    <li class="nav-group-sub-label">Communication</li>
                    <li><a href="{{ route('message-logs.index') }}" class="{{ request()->routeIs('message-logs.*') ? 'active' : '' }}"><i class="bi bi-chat-dots"></i>Message log</a></li>
                    <li><a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}"><i class="bi bi-megaphone"></i>Announcements</a></li>
                    <li class="nav-group-sub-label">Student affairs</li>
                    <li><a href="{{ route('leave-applications.index') }}" class="{{ request()->routeIs('leave-applications.*') ? 'active' : '' }}"><i class="bi bi-calendar-x"></i>Leave applications</a></li>
                    <li><a href="{{ route('graduation-clearances.index') }}" class="{{ request()->routeIs('graduation-clearances.*') ? 'active' : '' }}"><i class="bi bi-clipboard-check"></i>Graduation clearance</a></li>
                    <li><a href="{{ route('conduct-records.index') }}" class="{{ request()->routeIs('conduct-records.*') ? 'active' : '' }}"><i class="bi bi-shield-exclamation"></i>Conduct records</a></li>
                    <li><a href="{{ route('transcript-requests.index') }}" class="{{ request()->routeIs('transcript-requests.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i>Transcript requests</a></li>
                    @canModule('institution_docs', 'view')
                    <li class="nav-group-sub-label">Records</li>
                    <li><a href="{{ route('institution-documents.index') }}" class="{{ request()->routeIs('institution-documents.*') ? 'active' : '' }}"><i class="bi bi-folder2-open"></i>Institution documents</a></li>
                    @endcanModule
                    <li class="nav-group-sub-label">Planning</li>
                    <li><a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}"><i class="bi bi-calendar3"></i>College calendar</a></li>
                </ul>
            </div>
            @canModule('accommodation', 'view')
            <div class="nav-group {{ request()->routeIs('hostels.*', 'rooms.*', 'rooms.occupancy*', 'accommodation-allocations.*') ? 'expanded' : '' }}" id="navGroupAccommodation">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('hostels.*', 'rooms.*', 'rooms.occupancy*', 'accommodation-allocations.*') ? 'true' : 'false' }}" aria-controls="navGroupAccommodationSub">
                    <i class="bi bi-building"></i><span>Accommodation</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupAccommodationSub">
                    <li class="nav-group-sub-label">Buildings</li>
                    <li><a href="{{ route('hostels.index') }}" class="{{ request()->routeIs('hostels.*') ? 'active' : '' }}"><i class="bi bi-building"></i>Hostels</a></li>
                    <li><a href="{{ route('rooms.index') }}" class="{{ request()->routeIs('rooms.index', 'rooms.create', 'rooms.edit') ? 'active' : '' }}"><i class="bi bi-door-open"></i>Rooms</a></li>
                    <li class="nav-group-sub-label">Occupancy</li>
                    <li><a href="{{ route('rooms.occupancy') }}" class="{{ request()->routeIs('rooms.occupancy*') ? 'active' : '' }}"><i class="bi bi-people"></i>Live by room</a></li>
                    <li><a href="{{ route('accommodation-allocations.index') }}" class="{{ request()->routeIs('accommodation-allocations.*') ? 'active' : '' }}"><i class="bi bi-person-badge"></i>Allocations</a></li>
                </ul>
            </div>
            @endcanModule
            @canModule('inventory', 'view')
            <div class="nav-group {{ request()->routeIs('inventory-items.*') ? 'expanded' : '' }}" id="navGroupInventory">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('inventory-items.*') ? 'true' : 'false' }}" aria-controls="navGroupInventorySub">
                    <i class="bi bi-box-seam"></i><span>Inventory</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupInventorySub">
                    <li class="nav-group-sub-label">Institution</li>
                    <li><a href="{{ route('inventory-items.index') }}" class="{{ request()->routeIs('inventory-items.*') ? 'active' : '' }}"><i class="bi bi-clipboard-data"></i>Items &amp; assets</a></li>
                </ul>
            </div>
            @endcanModule
            @if(auth()->user()->isAdmin())
            <div class="nav-group {{ request()->routeIs('users.*', 'activity-log*', 'export*', 'users.role-permissions') ? 'expanded' : '' }}" id="navGroupSystem">
                <button type="button" class="nav-group-toggle" aria-expanded="{{ request()->routeIs('users.*', 'activity-log*', 'export*') ? 'true' : 'false' }}" aria-controls="navGroupSystemSub">
                    <i class="bi bi-gear"></i><span>System</span><i class="bi bi-chevron-down"></i>
                </button>
                <ul class="nav-group-sub" id="navGroupSystemSub">
                    <li class="nav-group-sub-label">Access</li>
                    <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index', 'users.create', 'users.edit') ? 'active' : '' }}"><i class="bi bi-person-gear"></i>Users</a></li>
                    <li><a href="{{ route('users.role-permissions') }}" class="{{ request()->routeIs('users.role-permissions') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i>Role permissions</a></li>
                    <li class="nav-group-sub-label">Audit &amp; data</li>
                    <li><a href="{{ route('activity-log.index') }}" class="{{ request()->routeIs('activity-log*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i>Activity log</a></li>
                    <li><a href="{{ route('export.index') }}" class="{{ request()->routeIs('export*') ? 'active' : '' }}"><i class="bi bi-download"></i>Export data</a></li>
                    <li><a href="{{ route('integrations.index') }}" class="{{ request()->routeIs('integrations.*') ? 'active' : '' }}"><i class="bi bi-plug"></i>Integrations</a></li>
                </ul>
            </div>
            @endif
            @endif
            <hr class="border-secondary my-2 mx-3">
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="button" class="nav-link border-0 bg-transparent w-100 text-start">
                    <i class="bi bi-box-arrow-left"></i><span>Logout</span><i class="bi bi-chevron-right"></i>
                </button>
            </form>
        </nav>
    </aside>
    <div class="main-wrap expanded" id="mainWrap">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn btn-link text-dark d-lg-none p-0" id="sidebarToggleMobile" aria-label="Menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <button type="button" class="btn btn-link text-dark d-none d-lg-inline-flex p-0 me-2" id="sidebarToggle" aria-label="Collapse sidebar">
                    <i class="bi bi-layout-sidebar-inset-reverse" id="sidebarToggleIcon"></i>
                </button>
                @php
                    $currentUser = auth()->user();
                    $studentRecord = $currentUser->student;
                    $unreadNotificationsCount = $currentUser->unreadNotifications()->count();
                    $topbarNotifications = $currentUser->notifications()->latest()->limit(6)->get();
                @endphp
                @if($studentRecord)
                    <span class="login-as">{{ __('ui.nav.login_as') }} <strong class="topbar-reg-no">{{ $studentRecord->registrationNumberDisplay() ?: $studentRecord->reg_no }}</strong></span>
                @elseif($currentUser->staff_id)
                    <span class="topbar-reg-no">ID: {{ $currentUser->staff_id }}</span>
                @else
                    <span class="login-as">Login as: {{ $currentUser->email }}</span>
                @endif
            </div>
            <div class="topbar-datetime" id="topbarDateTime" aria-live="polite">
                <span id="topbarDate"></span> &middot; <span id="topbarTime"></span>
            </div>
            <div class="topbar-right">
            <div class="notif-dropdown">
                <button type="button" class="topbar-bell" id="notifToggle" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotificationsCount > 0)
                        <span class="notif-badge bg-danger text-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
                    @endif
                </button>
                <div class="notif-menu" id="notifMenu">
                    <div class="notif-menu-header">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0">Mark all read</button>
                        </form>
                    </div>
                    <div class="notif-list">
                        @forelse($topbarNotifications as $n)
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                @csrf
                                <button type="submit" class="notif-item w-100 text-start border-0 bg-transparent">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="notif-item-title">{{ $n->data['title'] ?? 'Notification' }}</span>
                                        @if(!$n->read_at)<span class="badge bg-primary">New</span>@endif
                                    </div>
                                    <div class="notif-item-msg">{{ $n->data['message'] ?? '' }}</div>
                                    <div class="small text-muted mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                                </button>
                            </form>
                        @empty
                            <div class="p-3 text-muted small">No notifications yet.</div>
                        @endforelse
                    </div>
                    <div class="notif-menu-footer">
                        <a href="{{ route('notifications.index') }}" class="small text-decoration-none">View all notifications</a>
                    </div>
                </div>
            </div>
            @include('layouts.partials.theme-toggle')
            @include('layouts.partials.language-toggle')
            <div class="profile-dropdown">
                <button type="button" class="topbar-avatar" id="profileToggle" aria-label="Profile menu" aria-expanded="false" aria-haspopup="true">
                    @if($currentUser->profile_photo_url)
                        <img src="{{ $currentUser->profile_photo_url }}" alt="">
                    @else
                        <span>{{ $currentUser->initials }}</span>
                    @endif
                </button>
                <div class="profile-menu" id="profileMenu" role="menu">
                    <div class="profile-menu-header">
                        <strong>{{ $currentUser->name }}</strong>
                        <small>{{ $studentRecord ? $studentRecord->reg_no : $currentUser->email }}</small>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form-topbar">
                        @csrf
                        <button type="button" class="profile-menu-item w-100 border-0" role="menuitem">
                            <i class="bi bi-box-arrow-left me-2"></i>{{ __('ui.nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>
            </div>
        </header>
        <main class="main-content">
            @yield('content')
        </main>
        <footer class="app-footer" role="contentinfo">
            <p>© {{ now()->year }} Musoma COHAS. {{ __('ui.footer.rights') }} {{ __('ui.footer.powered') }}</p>
        </footer>
    </div>
    <div class="sidebar-overlay d-lg-none" id="sidebarOverlay" style="display:none!important; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:1029;"></div>
    @else
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('home') }}"><span class="logo-circle navbar-logo me-2"><img src="{{ asset('images/logo.png') }}" alt=""></span>{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
                @include('layouts.partials.theme-toggle')
                @include('layouts.partials.language-toggle')
                <a class="nav-link" href="{{ route('login.create') }}">{{ __('ui.nav.login') }}</a>
            </div>
        </div>
    </nav>
    <main class="container flex-grow-1 py-4">
        @yield('content')
    </main>
    <footer class="border-top bg-light py-3 mt-auto" role="contentinfo">
        <div class="container small text-center text-muted">
            <p class="mb-0">© {{ now()->year }} Musoma COHAS. {{ __('ui.footer.rights') }} {{ __('ui.footer.powered') }}</p>
        </div>
    </footer>
    @endauth

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/cohas-theme.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isAuthenticated = @json(auth()->check());
            var idleLogoutMinutes = Number(@json((int) env('IDLE_LOGOUT_MINUTES', 20)));
            var idlePromptSeconds = Number(@json((int) env('IDLE_PROMPT_SECONDS', 60)));
            var logoutUrl = @json(route('logout'));
            var loginUrl = @json(route('login.create'));

            function autoLogoutNow() {
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch(logoutUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: '{}',
                    credentials: 'same-origin',
                }).finally(function() {
                    window.location.href = loginUrl + '?expired=1';
                });
            }

            if (isAuthenticated && idleLogoutMinutes > 0) {
                var idleTimer = null;
                var countdownTimer = null;
                var warningOpen = false;
                var activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'];

                function scheduleIdleWarning() {
                    if (idleTimer) clearTimeout(idleTimer);
                    idleTimer = setTimeout(showIdleWarning, idleLogoutMinutes * 60 * 1000);
                }

                function showIdleWarning() {
                    warningOpen = true;
                    var remaining = Math.max(1, idlePromptSeconds);
                    Swal.fire({
                        title: 'Session expiring',
                        html: 'You have been inactive. Continue session? <br><small>Auto logout in <strong id="idleCountdown">'+remaining+'</strong>s</small>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Logout',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: remaining * 1000,
                        timerProgressBar: true,
                        didOpen: function() {
                            countdownTimer = setInterval(function() {
                                remaining -= 1;
                                var el = document.getElementById('idleCountdown');
                                if (el) el.textContent = String(Math.max(remaining, 0));
                            }, 1000);
                        },
                        willClose: function() {
                            if (countdownTimer) clearInterval(countdownTimer);
                        },
                    }).then(function(result) {
                        warningOpen = false;
                        if (result.isConfirmed) {
                            scheduleIdleWarning();
                        } else {
                            autoLogoutNow();
                        }
                    });
                }

                activityEvents.forEach(function(evt) {
                    document.addEventListener(evt, function() {
                        if (!warningOpen) scheduleIdleWarning();
                    }, { passive: true });
                });

                scheduleIdleWarning();
            }

            function updateDateTime() {
                var d = new Date();
                var dateEl = document.getElementById('topbarDate');
                var timeEl = document.getElementById('topbarTime');
                var dateLocale = (document.documentElement.lang || 'en').startsWith('sw') ? 'sw-TZ' : 'en-GB';
                if (dateEl) dateEl.textContent = d.toLocaleDateString(dateLocale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                if (timeEl) timeEl.textContent = d.toLocaleTimeString(dateLocale, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            updateDateTime();
            if (setInterval) setInterval(updateDateTime, 1000);
            var profileToggle = document.getElementById('profileToggle');
            var profileMenu = document.getElementById('profileMenu');
            var notifToggle = document.getElementById('notifToggle');
            var notifMenu = document.getElementById('notifMenu');
            if (profileToggle && profileMenu) {
                profileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('show');
                    if (notifMenu) notifMenu.classList.remove('show');
                    profileToggle.setAttribute('aria-expanded', profileMenu.classList.contains('show'));
                });
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('show');
                    if (notifMenu) notifMenu.classList.remove('show');
                    profileToggle.setAttribute('aria-expanded', 'false');
                    if (notifToggle) notifToggle.setAttribute('aria-expanded', 'false');
                });
                profileMenu.addEventListener('click', function(e) { e.stopPropagation(); });
            }
            if (notifToggle && notifMenu) {
                notifToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifMenu.classList.toggle('show');
                    if (profileMenu) profileMenu.classList.remove('show');
                    notifToggle.setAttribute('aria-expanded', notifMenu.classList.contains('show'));
                });
                notifMenu.addEventListener('click', function(e) { e.stopPropagation(); });
            }
            document.querySelectorAll('.logout-form-topbar').forEach(function(f) {
                f.querySelector('button')?.addEventListener('click', function() {
                    Swal.fire({ title: 'Logout?', text: 'You will be signed out.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) f.submit(); });
                });
            });
            var sidebar = document.getElementById('sidebar');
            var mainWrap = document.getElementById('mainWrap');
            var toggle = document.getElementById('sidebarToggle');
            var toggleMobile = document.getElementById('sidebarToggleMobile');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && mainWrap) {
                var collapsed = localStorage.getItem('sidebarCollapsed') === '1';
                if (collapsed) { sidebar.classList.add('collapsed'); mainWrap.classList.remove('expanded'); mainWrap.classList.add('collapsed'); }
                if (toggle) {
                    var icon = document.getElementById('sidebarToggleIcon');
                    toggle.addEventListener('click', function() {
                        sidebar.classList.toggle('collapsed');
                        mainWrap.classList.toggle('expanded'); mainWrap.classList.toggle('collapsed');
                        if (icon) icon.className = mainWrap.classList.contains('collapsed') ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar-inset-reverse';
                        localStorage.setItem('sidebarCollapsed', mainWrap.classList.contains('collapsed') ? '1' : '0');
                    });
                    if (collapsed && icon) icon.className = 'bi bi-layout-sidebar-inset';
                }
                if (toggleMobile && overlay) {
                    toggleMobile.addEventListener('click', function() { sidebar.classList.add('show'); overlay.style.display = 'block'; });
                    overlay.addEventListener('click', function() { sidebar.classList.remove('show'); overlay.style.display = 'none'; });
                }
            }
            document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var group = btn.closest('.nav-group');
                    if (group) group.classList.toggle('expanded');
                });
            });
            document.querySelectorAll('.logout-form').forEach(function(f) {
                f.querySelector('button')?.addEventListener('click', function() {
                    Swal.fire({ title: 'Logout?', text: 'You will be signed out.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) f.submit(); });
                });
            });
            document.querySelectorAll('[data-swal-confirm]').forEach(function(el) {
                var form = el.closest('form');
                if (!form) return;
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    var title = el.getAttribute('data-swal-title') || 'Are you sure?';
                    var text = el.getAttribute('data-swal-text') || '';
                    var icon = el.getAttribute('data-swal-icon') || 'warning';
                    Swal.fire({ title: title, text: text, icon: icon, showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) form.submit(); });
                });
            });
            var success = @json(session('success'));
            var error = @json(session('error'));
            var warning = @json(session('warning'));
            var info = @json(session('info'));
            if (success) Swal.fire({ icon: 'success', title: 'Success', text: success, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (info) Swal.fire({ icon: 'info', title: 'Notice', text: info, timer: 4000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (warning) Swal.fire({ icon: 'warning', title: 'Notice', text: warning, timer: 4000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (error) Swal.fire({ icon: 'error', title: 'Error', text: error });
            @if($errors->any())
            Swal.fire({ icon: 'error', title: 'Validation', html: {!! json_encode(implode('<br>', $errors->all())) !!} });
            @endif
        });
    </script>
    @include('layouts.partials.cohas-loader-script')
    @stack('scripts')
</body>
</html>

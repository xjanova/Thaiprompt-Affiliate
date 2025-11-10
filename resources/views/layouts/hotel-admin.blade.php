<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Hotel Admin') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* Sidebar */
        .hotel-admin-sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
        }

        .sidebar-logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .sidebar-logo-text h2 {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #fff;
        }

        .sidebar-logo-text p {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu-section {
            margin-bottom: 30px;
        }

        .sidebar-menu-title {
            padding: 0 25px 10px;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-menu-item {
            padding: 0 15px;
        }

        .sidebar-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar-menu-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .sidebar-menu-icon {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .sidebar-user {
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
        }

        .user-details h4 {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 3px 0;
        }

        .user-details p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        /* Main Content */
        .hotel-admin-content {
            margin-left: 280px;
            min-height: 100vh;
        }

        .topbar {
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left h1 {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: #fff;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hotel-admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .hotel-admin-sidebar.active {
                transform: translateX(0);
            }

            .hotel-admin-content {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block !important;
            }
        }

        .mobile-menu-toggle {
            display: none;
            width: 40px;
            height: 40px;
            background: #667eea;
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="hotel-admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('hotel-admin.dashboard') }}" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h2>Hotel Admin</h2>
                    <p>Management Portal</p>
                </div>
            </a>
        </div>

        <div class="sidebar-menu">
            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">Main Menu</div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home sidebar-menu-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.bookings.index') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.bookings.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check sidebar-menu-icon"></i>
                        <span>Bookings</span>
                    </a>
                </div>
                @if(auth()->user()->is_admin)
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.hotels.index') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.hotels.*') ? 'active' : '' }}">
                        <i class="fas fa-building sidebar-menu-icon"></i>
                        <span>Hotels</span>
                    </a>
                </div>
                @else
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.hotel.settings') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.hotel.settings') ? 'active' : '' }}">
                        <i class="fas fa-building sidebar-menu-icon"></i>
                        <span>My Hotel</span>
                    </a>
                </div>
                @endif
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.rooms.index') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.rooms.*') ? 'active' : '' }}">
                        <i class="fas fa-bed sidebar-menu-icon"></i>
                        <span>Rooms</span>
                    </a>
                </div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.reviews.index') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.reviews.*') ? 'active' : '' }}">
                        <i class="fas fa-star sidebar-menu-icon"></i>
                        <span>Reviews</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">Reports & Analytics</div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.reports.revenue') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.reports.revenue') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar sidebar-menu-icon"></i>
                        <span>Revenue Report</span>
                    </a>
                </div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('hotel-admin.reports.occupancy') }}" class="sidebar-menu-link {{ request()->routeIs('hotel-admin.reports.occupancy') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie sidebar-menu-icon"></i>
                        <span>Occupancy Report</span>
                    </a>
                </div>
            </div>

            @if(auth()->user()->is_admin)
            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">Super Admin</div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-menu-link">
                        <i class="fas fa-crown sidebar-menu-icon"></i>
                        <span>All Dashboards</span>
                    </a>
                </div>
                <div class="sidebar-menu-item">
                    <a href="{{ route('admin.users.index') }}" class="sidebar-menu-link">
                        <i class="fas fa-users sidebar-menu-icon"></i>
                        <span>Manage Users</span>
                    </a>
                </div>
            </div>
            @endif
        </div>

        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="user-details">
                    <h4>{{ auth()->user()->name }}</h4>
                    <p>
                        @if(auth()->user()->is_admin)
                            Super Administrator
                        @else
                            Hotel Administrator
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="hotel-admin-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="topbar-right">
                <a href="{{ route('hotel-admin.dashboard') }}" class="topbar-btn">
                    <i class="fas fa-chart-line"></i>
                    View Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="topbar-btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <main class="content-area">
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>

    @stack('scripts')
</body>
</html>

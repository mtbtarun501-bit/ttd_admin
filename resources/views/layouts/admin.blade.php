<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sri Garuda Divine Bookings') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
    
    <!-- Garuda Theme Override -->
    <link rel="stylesheet" href="{{ asset('assets/theme/css/garuda-theme.css') }}">
</head>
<body>

    <!-- Top Navigation Bar (Full Width) -->
    <header class="admin-header">
        <div class="header-left">
            <button class="btn btn-light d-lg-none me-2" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('dashboard') }}" class="brand-area">
                <div style="font-size: 28px; color: var(--garuda-gold); margin-right: 15px;"><i class="fa-solid fa-om"></i></div>
                <div class="brand-text">
                    <div class="brand-text-top">GARUDA DIVINE BOOKINGS</div>
                    <div class="brand-text-bottom">TIRUMALA ALIPIRI ENTRANCE</div>
                </div>
            </a>
        </div>

        <div class="nav-center-text d-none d-md-flex">
            <i class="fa-solid fa-om" style="font-size:24px;"></i>
            <span>ఓం నమో వేంకటేశాయ</span>
        </div>

        <div class="header-right">
            <div class="header-date d-none d-md-block">
                <i class="far fa-calendar-alt"></i> {{ now()->format('d M Y | h:i A') }}
            </div>
            
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> {{ Auth::user()->name ?? 'garuda bookings' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-cog me-2 text-muted"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Sidebar Backdrop for Mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('devotees.index') }}" class="{{ request()->routeIs('devotees.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Devotees
                </a>
            </li>
            @hasanyrole('Super Admin|Operator')
            <li>
                <a href="{{ route('bookings.index') }}" class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
            </li>
            <li>
                <a href="{{ route('phone-usages.index') }}" class="{{ request()->routeIs('phone-usages.*') ? 'active' : '' }}">
                    <i class="fas fa-mobile-alt"></i> Phone Usage
                </a>
            </li>
            <li>
                <a href="{{ route('investments.index') }}" class="{{ request()->routeIs('investments.*') ? 'active' : '' }}">
                    <i class="fas fa-coins"></i> Investments
                </a>
            </li>
            <li>
                <a href="{{ route('revenues.index') }}" class="{{ request()->routeIs('revenues.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Revenue
                </a>
            </li>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 20px;">
            
            <li>
                <a href="#">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </li>
            <li>
                <a href="{{ route('profile.edit') }}">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            @endhasanyrole
            
            @hasrole('Super Admin')
            <li>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i> Users
                </a>
            </li>
            @endhasrole
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </form>
            </li>
        </ul>
        <div class="sidebar-bottom-image"></div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        @if(request()->routeIs('dashboard'))
        <div class="hero-banner-container">
            <div class="hero-banner-blur-bg" style="background-image: url('{{ asset('assets/theme/images/dashboard_hero.jpg') }}');"></div>
            <img class="hero-banner-img" src="{{ asset('assets/theme/images/dashboard_hero.jpg') }}" alt="Garuda Divine Bookings" loading="lazy">
        </div>
        @endif

        <!-- Dynamic Page Content -->
        @yield('content')

    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @stack('scripts')
    
    <!-- Mobile Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if(mobileToggle && sidebar && backdrop) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    backdrop.classList.toggle('show');
                    document.body.classList.toggle('overflow-hidden');
                });

                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                    document.body.classList.remove('overflow-hidden');
                });
            }
        });
    </script>
</body>
</html>

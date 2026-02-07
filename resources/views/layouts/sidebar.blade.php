<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Light Logo -->
        {{-- <a href="index" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/logo.svg') }}" alt="" height="20">
            </span>
        </a> --}}
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <!-- dashboard -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}" role="button">
                        <i class="ri-dashboard-line"></i> <span>{{ __('menu.dashboard') }}</span>
                    </a>
                </li>
                <!-- store  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('store') }}" role="button">
                        <i class="ri-store-line"></i> <span>{{ __('menu.store') }}</span>
                    </a>
                </li>
                <!-- customers  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('customers') }}" role="button">
                        <i class="ri-user-line"></i> <span>{{ __('menu.customers') }}</span>
                    </a>
                </li>
                <!-- payments  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('payments') }}" role="button">
                        <i class="ri-money-dollar-box-line"></i>
                        <span>{{ __('menu.payments') }}</span>
                    </a>
                </li>
                <!-- customer info  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('customers.info') }}" role="button">
                        <i class="ri-contacts-line"></i>
                        <span>{{ __('menu.customer_info') }}</span>
                    </a>
                </li>
                <!-- report manthly payments  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('report') }}" role="button">
                        <i class="ri-folder-chart-line"></i>
                        <span>{{ __('pages/monthly_report.th.monthly_payment') }}</span>
                    </a>
                </li>
                <!-- settings -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarDashboards">
                        <i class="ri-settings-3-line"></i> <span>{{ __('menu.settings') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="{{ route('users.index') }}" role="button">
                                    <span>{{ __('menu.create_user') }}</span>
                                </a>
                            </li>
                        </ul>
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="{{ route('commands.index') }}" role="button">
                                    <span>{{ __('menu.commands') }}</span>
                                </a>
                            </li>
                        </ul>

                    </div>
                </li> <!-- end settings -->
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<div class="vertical-overlay"></div>

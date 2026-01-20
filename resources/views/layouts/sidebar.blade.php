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
                <li class="menu-title"><span>Dashboard</span></li>
                <!-- dashboard -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}" role="button">
                        <i class="ri-dashboard-line"></i> <span>Dashboard</span>
                    </a>
                </li>
                <!-- store  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('store') }}" role="button">
                        <i class="ri-store-line"></i> <span>Store</span>
                    </a>
                </li>
                <!-- report  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('report') }}" role="button">
                        <i class="ri-folder-chart-line"></i> <span>Report</span>
                    </a>
                </li>
                <!-- customers  -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('customers') }}" role="button">
                        <i class="ri-user-line"></i> <span>Customers</span>
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('apps.ecommerce.checkout') }}" role="button">
                        <i class="ri-shopping-cart-line"></i>
                        <span>Checkout</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('cusinfo') }}" role="button">
                        <i class="ri-contacts-line"></i>
                        <span>Customer info</span>
                    </a>
                </li>


                <!-- settings -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarDashboards">
                        <i class="ri-settings-3-line"></i> <span>Settings</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="{{ route('users.index') }}" role="button">
                                    <span>Create user</span>
                                </a>
                            </li>
                        </ul>
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link menu-link" href="{{ route('commands.index') }}" role="button">
                                    <span>Commands</span>
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

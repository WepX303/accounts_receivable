{{-- <div class="app-menu navbar-menu"> --}}

<div class="app-menu navbar-menu d-block">

    <div class="navbar-brand-box"></div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu"></div>

            @php
                $user = auth()->user();
                $role = is_object($user->role) ? $user->role->value : $user->role;
            @endphp

            <div class="collapse navbar-collapse d-lg-flex" id="topnav-menu-content">
                <ul class="navbar-nav" id="navbar-nav">

                    <!-- dashboard (herkes) -->
                    <li class="nav-item">
                        <a class="nav-link menu-link" href="{{ route('dashboard') }}" role="button">
                            <i class="ri-dashboard-line"></i> <span>{{ __('menu.dashboard') }}</span>
                        </a>
                    </li>

                    {{-- Customers (Admin + Manager + Analyst + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Manager', 'Analyst', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('customers') }}">
                                <i class="ri-user-line"></i> <span>{{ __('menu.customers') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Payments (Admin + Cashier + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('payments') }}">
                                <i class="ri-money-dollar-box-line"></i>
                                <span>{{ __('menu.payments') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Customer Info (Admin + Cashier + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('customers.info') }}">
                                <i class="ri-contacts-line"></i>
                                <span>{{ __('menu.customer_info') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Report (Admin + Cashier + Analyst + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Analyst', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('report') }}">
                                <i class="ri-folder-chart-line"></i>
                                <span>{{ __('pages/monthly_report.th.monthly_payment') }}</span>
                            </a>
                        </li>
                    @endif

                    <!-- SETTINGS (ADMIN ONLY) -->
                    {{-- @if ($role === 'Admin')
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarSettings" data-bs-toggle="collapse"
                                role="button" aria-expanded="false" aria-controls="sidebarSettings">
                                <i class="ri-settings-3-line"></i> <span>{{ __('menu.settings') }}</span>
                            </a>

                            <div class="collapse menu-dropdown" id="sidebarSettings">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link menu-link" href="{{ route('users.index') }}" role="button">
                                            <span>{{ __('menu.create_user') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif --}}

                    {{-- Logs (Admin ) --}}
                    @if (in_array($role, ['SuperAdmin'], true))
                        <li class="nav-item">
                            <a href="{{ route('logs') }}" class="nav-link">
                                <i class="ri-file-list-3-line"></i>
                                <span>Logs</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array($role, ['SuperAdmin', 'Admin'], true))
                        <li class="nav-item dropdown">
                            <a class="nav-link menu-link dropdown-toggle" href="#" id="settingsDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-settings-3-line"></i>
                                <span>{{ __('menu.settings') }}</span>
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('users.index') }}">
                                        {{ __('menu.create_user') }}
                                    </a>
                                </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('commands.index') }}">
                                            {{ __('menu.commands') }}
                                        </a>
                                    </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
<div class="vertical-overlay"></div>

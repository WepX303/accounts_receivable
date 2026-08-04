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

                    <!-- dashboard -->
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

                    {{-- Customer Info (Superadmin + Admin + Cashier + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('customers.info') }}">
                                <i class="ri-contacts-line"></i>
                                <span>{{ __('menu.customer_info') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Report (Superadmin + Admin + Cashier + Analyst + Operator) --}}
                    {{-- @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Analyst', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('report') }}">
                                <i class="ri-folder-chart-line"></i>
                                <span>{{ __('pages/monthly_report.th.monthly_payment') }}</span>
                            </a>
                        </li>
                    @endif --}}
                    {{-- Reports --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Cashier', 'Analyst', 'Operator'], true))
                        <li class="nav-item dropdown">
                            <a class="nav-link menu-link dropdown-toggle" href="#" id="reportsDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-folder-chart-line"></i>
                                <span>{{ __('menu.report') }}</span>
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="reportsDropdown">

                                <li>
                                    <a class="dropdown-item" href="{{ route('report') }}">
                                        {{ __('pages/reports.avshocrecat.title') }}
                                    </a>
                                </li>

                                @if (in_array($role, ['SuperAdmin', 'Analyst', 'Admin'], true))
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.payment-calendar') }}">
                                            {{ __('pages/reports.payment_calendar.title') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.overdue-payments') }}">
                                            {{ __('pages/reports.overdue_payments.title') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.collection-performance') }}">
                                            {{ __('pages/reports.collection_performance.title') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.daily-cash-closing') }}">
                                            {{ __('pages/reports.daily_cash_closing.title') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.collection-trend') }}">
                                            {{ __('pages/reports.collection_trend.title') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.promise-to-pay') }}">
                                            {{ __('pages/reports.promise_to_pay.title') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.recovery-effectiveness') }}">
                                            {{ __('pages/reports.recovery_effectiveness.title') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.customer-statement') }}">
                                            {{ __('pages/reports.customer_statement.table_title') }}
                                        </a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif

                    {{-- SMS (Superadmin + Admin + Operator) --}}
                    @if (in_array($role, ['SuperAdmin', 'Admin', 'Operator'], true))
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="{{ route('sms.index') }}">
                                <i class="bx bx-message-detail me-1"></i>
                                <span>{{ __('messages.sms_distribution') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Logs (SuperAdmin) --}}
                    {{-- @if (in_array($role, ['SuperAdmin'], true))
                        <li class="nav-item">
                            <a href="{{ route('logs') }}" class="nav-link">
                                <i class="ri-file-list-3-line"></i>
                                <span>Logs</span>
                            </a>
                        </li>
                    @endif --}}

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
                                @if (in_array($role, ['SuperAdmin'], true))
                                    <li>
                                        <a class="dropdown-item" href="{{ route('commands.index') }}">
                                            {{ __('menu.commands') }}
                                        </a>
                                    </li>
                                @endif
                                @if (in_array($role, ['SuperAdmin'], true))
                                    <li class="nav-item">
                                        <a class="dropdown-item" href="{{ route('logs') }}">
                                            <span>Logs</span>
                                        </a>
                                    </li>
                                @endif
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

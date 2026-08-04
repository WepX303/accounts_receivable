@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ __('pages/users.title') }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal" id="create-btn"
                                    data-bs-target="#showModal">
                                    <i class="ri-add-line align-bottom me-1"></i>
                                    {{ __('pages/users.add_user') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div>
                        <div class="table-responsive table-card mb-1">
                            <table class="table align-middle" id="customerTable">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th data-sort="id">{{ __('pages/users.th_id') }}</th>
                                        <th data-sort="firstname_lastname">{{ __('pages/users.th_user_name') }}</th>
                                        <th data-sort="email">{{ __('pages/users.th_email') }}</th>
                                        <th data-sort="phonenumber">{{ __('pages/users.th_phone') }}</th>
                                        <th data-sort="position">{{ __('pages/users.th_position') }}</th>
                                        <th data-sort="role">{{ __('pages/users.th_role') }}</th>
                                        <th data-sort="branches">{{ __('pages/users.th_branches') }}</th>
                                        <th data-sort="daily_report">{{ __('pages/users.th_daily_report') }}</th>
                                        <th data-sort="status">{{ __('pages/users.th_status') }}</th>
                                        <th data-sort="action">{{ __('pages/users.th_action') }}</th>
                                    </tr>
                                </thead>

                                <tbody class="list form-check-all">
                                    @forelse ($users as $user)
                                        <tr>
                                            <td class="id">
                                                <a class="fw-medium link-primary"># {{ $user->id }}</a>
                                            </td>
                                            <td class="firstname_lastname">{{ $user->firstname }} {{ $user->lastname }}
                                            </td>
                                            <td class="email">{{ $user->email }}</td>
                                            <td class="phonenumber">{{ $user->phonenumber }}</td>
                                            <td class="position">{{ $user->position }}</td>
                                            {{-- <td class="role">{{ $user->role }}</td> --}}
                                            <td class="role">{{ $user->role->label() }}</td>
                                            <td class="daily_report">
                                                @if ($user->daily_report)
                                                    <span class="badge bg-success-subtle text-success">
                                                        {{ __('pages/users.daily_report_on') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted">
                                                        {{ __('pages/users.daily_report_off') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="branches">
                                                @if ($user->isSuperAdmin())
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ __('pages/users.all_branches') }}
                                                    </span>
                                                @elseif (empty($user->branches))
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        {{ __('pages/users.all_branches') }}
                                                    </span>
                                                @else
                                                    @foreach ($user->branches as $userBranch)
                                                        <span class="badge bg-primary-subtle text-primary">{{ $userBranch }}</span>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td class="status">
                                                @if ($user->status)
                                                    <span class="badge bg-success-subtle text-success text-uppercase">
                                                        {{ __('pages/users.status_active') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger text-white text-uppercase">
                                                        {{ __('pages/users.status_inactive') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <ul class="list-inline hstack gap-2 mb-0">
                                                    {{-- <li class="list-inline-item" data-bs-toggle="tooltip"
                                                        data-bs-trigger="hover" data-bs-placement="top"
                                                        title="{{ __('pages/users.edit') }}">
                                                        <a href="#showModal" data-bs-toggle="modal"
                                                            class="text-primary d-inline-block edit-item-btn"
                                                            data-id="{{ $user->id }}"
                                                            data-firstname="{{ $user->firstname }}"
                                                            data-lastname="{{ $user->lastname }}"
                                                            data-email="{{ $user->email }}"
                                                            data-phonenumber="{{ $user->phonenumber }}"
                                                            data-position="{{ $user->position }}" 
                                                            data-role="{{ $user->role?->value }}"
                                                            data-status="{{ $user->status }}">
                                                            <i class="ri-pencil-fill fs-16"></i>
                                                        </a>
                                                    </li> --}}
                                                    @if (auth()->user()->isSuperAdmin() || $user->role !== \App\Enums\UserRoleEnum::SUPER_ADMIN)
                                                        <li class="list-inline-item" data-bs-toggle="tooltip"
                                                            data-bs-trigger="hover" data-bs-placement="top"
                                                            title="{{ __('pages/users.edit') }}">
                                                            <a href="#showModal" data-bs-toggle="modal"
                                                                class="text-primary d-inline-block edit-item-btn"
                                                                data-id="{{ $user->id }}"
                                                                data-firstname="{{ $user->firstname }}"
                                                                data-lastname="{{ $user->lastname }}"
                                                                data-email="{{ $user->email }}"
                                                                data-phonenumber="{{ $user->phonenumber }}"
                                                                data-position="{{ $user->position }}"
                                                                data-role="{{ $user->role?->value }}"
                                                                data-branches="{{ json_encode($user->branches ?? []) }}"
                                                                data-daily-report="{{ $user->daily_report ? 1 : 0 }}"
                                                                data-status="{{ $user->status }}">
                                                                <i class="ri-pencil-fill fs-16"></i>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    {{-- <li class="list-inline-item" data-bs-toggle="tooltip"
                                                        data-bs-trigger="hover" data-bs-placement="top"
                                                        title="{{ __('pages/users.remove') }}">
                                                        <button type="button"
                                                            class="text-danger d-inline-block border-0 bg-transparent p-0 delete-btn"
                                                            data-id="{{ $user->id }}">
                                                            <i class="ri-delete-bin-5-fill fs-16"></i>
                                                        </button>
                                                    </li> --}}
                                                    @if (auth()->user()->isSuperAdmin() || $user->role !== \App\Enums\UserRoleEnum::SUPER_ADMIN)
                                                        <li class="list-inline-item" data-bs-toggle="tooltip"
                                                            data-bs-trigger="hover" data-bs-placement="top"
                                                            title="{{ __('pages/users.remove') }}">
                                                            <button type="button"
                                                                class="text-danger d-inline-block border-0 bg-transparent p-0 delete-btn"
                                                                data-id="{{ $user->id }}">
                                                                <i class="ri-delete-bin-5-fill fs-16"></i>
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                {{ __('pages/users.no_users_found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $users->links('vendor.pagination.custom') }}
                    </div>

                    {{-- CREATE/EDIT MODAL --}}
                    <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-light p-3">
                                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                        id="close-modal"></button>
                                </div>

                                <form action="{{ route('users.store') }}" method="POST" autocomplete="off">
                                    @csrf

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.firstname') }}</label>
                                            <input type="text" name="firstname"
                                                class="form-control @error('firstname') is-invalid @enderror"
                                                placeholder="{{ __('pages/users.enter_firstname') }}"
                                                value="{{ old('firstname') }}" required>
                                            @error('firstname')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.lastname') }}</label>
                                            <input type="text" name="lastname"
                                                class="form-control @error('lastname') is-invalid @enderror"
                                                placeholder="{{ __('pages/users.enter_lastname') }}"
                                                value="{{ old('lastname') }}" required>
                                            @error('lastname')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.email') }}</label>
                                            <input type="email" name="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="{{ __('pages/users.enter_email') }}"
                                                value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.phone') }}</label>
                                            <input type="text" name="phonenumber"
                                                class="form-control @error('phonenumber') is-invalid @enderror"
                                                placeholder="{{ __('pages/users.enter_phone') }}"
                                                value="{{ old('phonenumber') }}" required>
                                            @error('phonenumber')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.position') }}</label>
                                            <input type="text" name="position" class="form-control"
                                                placeholder="{{ __('pages/users.enter_position') }}"
                                                value="{{ old('position') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.role') }}</label>
                                            <select name="role"
                                                class="form-control @error('role') is-invalid @enderror" required>
                                                <option value="">{{ __('pages/users.select_role') }}</option>
                                                {{-- @foreach (\App\Enums\UserRoleEnum::cases() as $role)
                                                    <option value="{{ $role->value }}"
                                                        {{ old('role', $user->role?->value ?? '') == $role->value ? 'selected' : '' }}>
                                                        {{ $role->label() }}
                                                    </option>
                                                @endforeach --}}
                                                @php
                                                    $authUser = auth()->user();
                                                    $availableRoles = collect(\App\Enums\UserRoleEnum::cases())->filter(
                                                        function ($role) use ($authUser) {
                                                            if ($authUser?->isSuperAdmin()) {
                                                                return true;
                                                            }

                                                            return $role !== \App\Enums\UserRoleEnum::SUPER_ADMIN;
                                                        },
                                                    );
                                                @endphp

                                                @foreach ($availableRoles as $role)
                                                    <option value="{{ $role->value }}"
                                                        {{ old('role') == $role->value ? 'selected' : '' }}>
                                                        {{ $role->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.branches') }}</label>

                                            <div class="dropdown w-100">
                                                <button
                                                    class="btn btn-light border dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center"
                                                    type="button" data-bs-toggle="dropdown"
                                                    data-bs-auto-close="outside" aria-expanded="false">
                                                    <span id="userBranchLabel" class="text-truncate">
                                                        {{ __('pages/users.all_branches') }}
                                                    </span>
                                                </button>

                                                <div class="dropdown-menu w-100 p-2"
                                                    style="max-height: 240px; overflow-y: auto;">
                                                    <div class="d-flex gap-2 px-2 pb-2 border-bottom mb-2">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary flex-grow-1"
                                                            id="userBranchClear">
                                                            {{ __('pages/users.all_branches') }}
                                                        </button>
                                                    </div>

                                                    @forelse ($branches as $branch)
                                                        <label class="dropdown-item d-flex align-items-center gap-2">
                                                            <input type="checkbox" name="branches[]"
                                                                value="{{ $branch }}"
                                                                class="form-check-input user-branch-checkbox">
                                                            <span>{{ $branch }}</span>
                                                        </label>
                                                    @empty
                                                        <div class="text-muted px-2">
                                                            {{ __('pages/users.no_branches') }}
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="form-text">{{ __('pages/users.branches_hint') }}</div>
                                        </div>

                                        @if (auth()->user()->isSuperAdmin())
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="hidden" name="daily_report" value="0">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="daily_report" value="1" id="dailyReportCheck">
                                                    <label class="form-check-label" for="dailyReportCheck">
                                                        {{ __('pages/users.daily_report') }}
                                                    </label>
                                                </div>
                                                <div class="form-text">{{ __('pages/users.daily_report_hint') }}</div>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.status') }}</label>
                                            <select name="status"
                                                class="form-control @error('status') is-invalid @enderror" required>
                                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>
                                                    {{ __('pages/users.status_active') }}
                                                </option>
                                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>
                                                    {{ __('pages/users.status_inactive') }}
                                                </option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('pages/users.password') }}</label>
                                            <input type="password" name="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="{{ __('pages/users.enter_password') }}">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('users.index') }}" class="btn btn-light">
                                                {{ __('pages/users.cancel') }}
                                            </a>
                                            <button type="submit" class="btn btn-success" id="modal-submit-btn">
                                                {{ __('pages/users.create_user') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    {{-- DELETE MODAL --}}
                    <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                        id="btn-close deleteRecord-close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mt-2 text-center">
                                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                            colors="primary:#f7b84b,secondary:#f06548"
                                            style="width:100px;height:100px"></lord-icon>
                                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                            <h4>{{ __('pages/users.are_you_sure') }}</h4>
                                            <p class="text-muted mx-4 mb-0">
                                                {{ __('pages/users.remove_record_confirm') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">
                                            {{ __('pages/users.close') }}
                                        </button>
                                        <button type="button" class="btn w-sm btn-danger" id="delete-record">
                                            {{ __('pages/users.yes_delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end modal -->
                </div>
            </div>

        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/list.pagination.js/list.pagination.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/ecommerce-customer-list.init.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const t = {
                create_user: @json(__('pages/users.create_user')),
                add_user: @json(__('pages/users.add_user')),
                update_user: @json(__('pages/users.update_user')),
                edit_user: @json(__('pages/users.edit_user')),
            };

            const allBranchesText = @json(__('pages/users.all_branches'));

            const modal = document.getElementById('showModal');
            const form = modal.querySelector('form');
            const modalTitle = modal.querySelector('.modal-title');
            const submitButton = modal.querySelector('button[type="submit"]');
            const branchBoxes = modal.querySelectorAll('.user-branch-checkbox');
            const branchLabel = document.getElementById('userBranchLabel');
            const branchClearBtn = document.getElementById('userBranchClear');

            function updateBranchLabel() {
                if (!branchLabel) return;

                const selected = Array.from(branchBoxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // Nothing ticked means the account is not restricted at all.
                branchLabel.textContent = selected.length ? selected.join(', ') : allBranchesText;
            }

            function setBranches(values) {
                const wanted = Array.isArray(values) ? values : [];
                branchBoxes.forEach(cb => cb.checked = wanted.includes(cb.value));
                updateBranchLabel();
            }

            branchBoxes.forEach(cb => cb.addEventListener('change', updateBranchLabel));

            if (branchClearBtn) {
                branchClearBtn.addEventListener('click', function() {
                    setBranches([]);
                });
            }

            document.getElementById('create-btn').addEventListener('click', function() {
                form.reset();
                setBranches([]);

                const newDailyBox = form.querySelector('input[type="checkbox"][name="daily_report"]');
                if (newDailyBox) {
                    newDailyBox.checked = false;
                }

                form.action = "{{ route('users.store') }}";
                form.method = "POST";
                submitButton.textContent = t.create_user;
                modalTitle.textContent = t.add_user;
            });

            const editButtons = document.querySelectorAll('.edit-item-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const firstname = this.dataset.firstname;
                    const lastname = this.dataset.lastname;
                    const email = this.dataset.email;
                    const phonenumber = this.dataset.phonenumber;
                    const position = this.dataset.position;
                    const role = this.dataset.role;
                    const status = this.dataset.status;

                    let branches = [];
                    try {
                        branches = JSON.parse(this.dataset.branches || '[]') || [];
                    } catch (e) {
                        branches = [];
                    }

                    form.firstname.value = firstname;
                    form.lastname.value = lastname;
                    form.email.value = email;
                    form.phonenumber.value = phonenumber;
                    form.position.value = position;
                    form.role.value = role;
                    form.status.value = status ? 1 : 0;
                    setBranches(branches);

                    const dailyReportBox = form.querySelector('input[type="checkbox"][name="daily_report"]');
                    if (dailyReportBox) {
                        dailyReportBox.checked = this.dataset.dailyReport === '1';
                    }

                    form.action = "/users/" + id;
                    let methodInput = form.querySelector('input[name="_method"]');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = "hidden";
                        methodInput.name = "_method";
                        form.appendChild(methodInput);
                    }
                    methodInput.value = "PUT";

                    submitButton.textContent = t.update_user;
                    modalTitle.textContent = t.edit_user;
                });
            });

            updateBranchLabel();

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const deleteRecordBtn = document.getElementById('delete-record');

            let currentUserId = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentUserId = this.dataset.id;
                    deleteModal.show();
                });
            });

            deleteRecordBtn.addEventListener('click', function() {
                if (currentUserId) {
                    const form = document.createElement('form');
                    form.method = "POST";
                    form.action = "/users/" + currentUserId;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = "hidden";
                    csrfInput.name = "_token";
                    csrfInput.value = "{{ csrf_token() }}";
                    form.appendChild(csrfInput);

                    const methodInput = document.createElement('input');
                    methodInput.type = "hidden";
                    methodInput.name = "_method";
                    methodInput.value = "DELETE";
                    form.appendChild(methodInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });

        });
    </script>
@endsection

@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">Users</h5>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal" id="create-btn"
                                    data-bs-target="#showModal"><i class="ri-add-line align-bottom me-1"></i> Add
                                    User</button>
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
                                        <th data-sort="id">ID</th>
                                        <th data-sort="firstname_lastname">User Name</th>
                                        <th data-sort="email">Email</th>
                                        <th data-sort="phonenumber">Phone Number</th>
                                        <th data-sort="position">Position</th>
                                        <th data-sort="role">Role</th>
                                        <th data-sort="status">Status</th>
                                        <th data-sort="action">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @forelse ($users as $user)
                                        <tr>
                                            <td class="id"><a class="fw-medium link-primary"># {{ $user->id }}</a>
                                            </td>
                                            <td class="firstname_lastname">{{ $user->firstname }} {{ $user->lastname }}</td>
                                            <td class="email">{{ $user->email }}</td>
                                            <td class="phonenumber">{{ $user->phonenumber }}</td>
                                            <td class="position">{{ $user->position }}</td>
                                            <td class="role">{{ $user->role }}</td>
                                            <td class="status">
                                                @if ($user->status)
                                                    <span
                                                        class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                                @else
                                                    <span class="badge bg-danger text-white text-uppercase">Deactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <ul class="list-inline hstack gap-2 mb-0">
                                                    <li class="list-inline-item" data-bs-toggle="tooltip"
                                                        data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                        <a href="#showModal" data-bs-toggle="modal"
                                                            class="text-primary d-inline-block edit-item-btn"
                                                            data-id="{{ $user->id }}"
                                                            data-firstname="{{ $user->firstname }}"
                                                            data-lastname="{{ $user->lastname }}"
                                                            data-email="{{ $user->email }}"
                                                            data-phonenumber="{{ $user->phonenumber }}"
                                                            data-position="{{ $user->position }}"
                                                            data-role="{{ $user->role }}"
                                                            data-status="{{ $user->status }}">
                                                            <i class="ri-pencil-fill fs-16"></i>
                                                        </a>
                                                    </li>
                                                    {{-- Delete Button --}}
                                                    <li class="list-inline-item" data-bs-toggle="tooltip"
                                                        data-bs-trigger="hover" data-bs-placement="top" title="Remove">
                                                        <button type="button"
                                                            class="text-danger d-inline-block border-0 bg-transparent p-0 delete-btn"
                                                            data-id="{{ $user->id }}">
                                                            <i class="ri-delete-bin-5-fill fs-16"></i>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No users found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    {{ $users->links('vendor.pagination.custom') }}
                    </div>
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

                                        {{-- Firstname --}}
                                        <div class="mb-3">
                                            <label class="form-label">User Firstname</label>
                                            <input type="text" name="firstname"
                                                class="form-control @error('firstname') is-invalid @enderror"
                                                placeholder="Enter firstname" value="{{ old('firstname') }}" required>
                                            @error('firstname')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Lastname --}}
                                        <div class="mb-3">
                                            <label class="form-label">User Lastname</label>
                                            <input type="text" name="lastname"
                                                class="form-control @error('lastname') is-invalid @enderror"
                                                placeholder="Enter lastname" value="{{ old('lastname') }}" required>
                                            @error('lastname')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Email --}}
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="Enter email" value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Phone --}}
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" name="phonenumber"
                                                class="form-control @error('phonenumber') is-invalid @enderror"
                                                placeholder="Enter phone number" value="{{ old('phonenumber') }}"
                                                required>
                                            @error('phonenumber')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Position --}}
                                        <div class="mb-3">
                                            <label class="form-label">Position</label>
                                            <input type="text" name="position" class="form-control"
                                                placeholder="Enter position" value="{{ old('position') }}">
                                        </div>

                                        {{-- Role --}}
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select name="role"
                                                class="form-control @error('role') is-invalid @enderror" required>
                                                <option value="">Select role</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role }}"
                                                        {{ old('role') == $role ? 'selected' : '' }}>
                                                        {{ ucfirst($role) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>


                                        {{-- Status --}}
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status"
                                                class="form-control @error('status') is-invalid @enderror" required>
                                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>
                                                    Deactive</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Password --}}
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" name="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="Enter password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                                            <button type="submit" class="btn btn-success">
                                                Create User
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
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
                                            <h4>Are you sure ?</h4>
                                            <p class="text-muted mx-4 mb-0">Are you sure you want to
                                                remove this record ?</p>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                        <button type="button" class="btn w-sm btn-light"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn w-sm btn-danger " id="delete-record">Yes,
                                            Delete It!</button>
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

    <!--ecommerce-customer init js -->
    <script src="{{ URL::asset('build/js/pages/ecommerce-customer-list.init.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const modal = document.getElementById('showModal');
            const form = modal.querySelector('form');
            const modalTitle = modal.querySelector('.modal-title');
            const submitButton = modal.querySelector('button[type="submit"]');

            // Add User butonuna tıklayınca modalı sıfırla (create)
            document.getElementById('create-btn').addEventListener('click', function() {
                form.reset();
                form.action = "{{ route('users.store') }}";
                form.method = "POST";
                submitButton.textContent = "Create User";
                modalTitle.textContent = "Add User";
            });

            // Edit butonları
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

                    // Form inputlarını doldur
                    form.firstname.value = firstname;
                    form.lastname.value = lastname;
                    form.email.value = email;
                    form.phonenumber.value = phonenumber;
                    form.position.value = position;
                    form.role.value = role;
                    form.status.value = status ? 1 : 0;

                    // Form action ve method ayarla
                    form.action = "/users/" + id;
                    let methodInput = form.querySelector('input[name="_method"]');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = "hidden";
                        methodInput.name = "_method";
                        form.appendChild(methodInput);
                    }
                    methodInput.value = "PUT";

                    // Buton ve başlık değiştir
                    submitButton.textContent = "Update User";
                    modalTitle.textContent = "Edit User";
                });
            });

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
                    // Dinamik form oluşturup submit et
                    const form = document.createElement('form');
                    form.method = "POST";
                    form.action = "/users/" + currentUserId;

                    // CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = "hidden";
                    csrfInput.name = "_token";
                    csrfInput.value = "{{ csrf_token() }}";
                    form.appendChild(csrfInput);

                    // DELETE method
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
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection

<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins.js') }}"></script>

{{-- Velzon core (navbar, menu, icon replace vs) --}}
<script src="{{ asset('build/js/velzon-core-custom.js') }}"></script>

{{-- Senin fullscreen + theme --}}
<script src="{{ asset('build/js/topbar-custom.js') }}"></script>

@yield('script')
@yield('script-bottom')
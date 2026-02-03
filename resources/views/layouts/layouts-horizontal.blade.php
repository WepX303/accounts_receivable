<!doctype html>
{{-- <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="horizontal" data-layout-style=""
    data-layout-position="fixed" data-topbar="light"> --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">


<head>
    <meta charset="utf-8" />
    <title>Accounts Receivable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Accounts Receivable" name="description" />

    <meta content="Themesbrand" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('build/images/favicon/budget.ico') }}?v=2">
    @include('layouts.head-css')

    <style>
        #page-loader {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.3);
            /* Beyaz yerine yarı saydam */
            backdrop-filter: blur(1000px);
            /* Blur efekti */
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity .3s ease, visibility .3s ease;
        }

        #page-loader.hide {
            opacity: 0;
            visibility: hidden;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #0d6efd;
            /* Bootstrap primary */
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        (function() {
            const htmlElement = document.documentElement;
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                htmlElement.setAttribute('data-bs-theme', savedTheme);
            } else {
                htmlElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        })();
    </script>
</head>

{{-- <body> --}}

<body data-layout="horizontal" data-layout-position="scroll" data-topbar="light">

    <div id="page-loader">
        <div class="spinner"></div>
    </div>


    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <!-- Start content -->
                <div class="container-fluid">
                    @yield('content')
                </div> <!-- content -->
            </div>
            @include('layouts.footer')
        </div>
        <!-- ============================================================== -->
        <!-- End Right content here -->
        <!-- ============================================================== -->
    </div>
    <!-- END wrapper -->

    @include('layouts.vendor-scripts')
    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (!loader) return;

            // küçük gecikme (daha pürüzsüz görünür)
            setTimeout(() => {
                loader.classList.add('hide');
            }, 200);
        });
    </script>



</body>

</html>

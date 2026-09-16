<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
		<meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ucfirst(AppSettings::get('app_name', 'App'))}} - {{ucfirst($title ?? '')}}</title>
		<meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="Phoenixcoded" />
  	<!-- Favicon -->
      <link rel="shortcut icon" type="image/x-icon" href="@if(!empty(AppSettings::get('logo'))) {{asset('storage/'.AppSettings::get('favicon'))}} @else{{asset('img/fav.png')}} @endif">

    <!-- prism css -->
    <link rel="stylesheet" href="{{ asset('assets/backend/css/plugins/prism-coy.css') }}">
    <!-- vendor css -->
    <link rel="stylesheet" href="{{ asset('assets/backend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backend/css/pcoded-horizontal.min.css') }}">

     <!-- Scripts -->
     {{-- <script src="{{ asset('js/app.js') }}" defer></script> --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Styles -->
    {{-- <link href="{{ asset('css/app.css') }}" rel="stylesheet"> --}}

    <style>
        :root {
            --sales-ink: #1d2b36;
            --sales-muted: #6d7d8a;
            --sales-teal: #1d8e9a;
            --sales-teal-dark: #0d6f69;
            --sales-mint: #e8f5f3;
            --sales-border: #dfe9f2;
            --sales-panel: #f9fbfc;
            --sales-panel-soft: #f3f7f9;
            --sales-canvas: #f5f8fa;
        }

        body {
            background: var(--sales-canvas);
            color: var(--sales-ink);
        }

        .pcoded-main-container,
        .pcoded-wrapper,
        .pcoded-content,
        .pcoded-inner-content,
        .main-body,
        .page-wrapper {
            background: transparent;
        }

        .page-wrapper {
            padding-top: 8px;
        }

        .card,
        .flat-card,
        .table-responsive,
        .alert,
        .modal-content {
            border: 1px solid var(--sales-border);
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .card {
            background: var(--sales-panel);
            overflow: hidden;
        }

        .card-header,
        .modal-header {
            background: var(--sales-panel-soft);
            border-bottom: 1px solid #e5edf3;
            color: var(--sales-ink);
        }

        .card-header h5,
        .card-header h6,
        .modal-title {
            color: var(--sales-ink);
            font-weight: 700;
        }

        .card-body {
            background: #fff;
        }

        .table {
            color: var(--sales-ink);
            background: #fff;
            border-color: #e4edf3;
        }

        .table thead th {
            background: var(--sales-panel-soft);
            color: #516574;
            border-bottom: 1px solid #dfeaf2;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .table td,
        .table th {
            border-color: #e8eff4;
            vertical-align: middle;
        }

        .form-control,
        .custom-select,
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            border: 1px solid #d7e3eb;
            border-radius: 10px;
            background: var(--sales-panel);
            color: var(--sales-ink);
        }

        .form-control:focus,
        .custom-select:focus {
            border-color: #8ad9d4;
            box-shadow: 0 0 0 0.2rem rgba(62, 194, 183, 0.14);
        }

        label {
            color: #5a6d7a;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .btn-primary,
        .btn-success,
        .btn-info,
        .btn-warning,
        .btn-danger,
        .btn-secondary {
            border: 0;
            border-radius: 10px;
            font-weight: 700;
        }

        .btn-primary,
        .btn-success,
        .btn-info {
            background: linear-gradient(135deg, #3ec2b7, var(--sales-teal));
            color: #fff;
        }

        .btn-primary:hover,
        .btn-success:hover,
        .btn-info:hover {
            background: linear-gradient(135deg, #2eaaa2, #167681);
            color: #fff;
        }

        .btn-light,
        .btn-outline-primary,
        .btn-outline-secondary {
            border-radius: 10px;
        }

        a {
            color: var(--sales-teal);
        }

        a:hover {
            color: var(--sales-teal-dark);
        }

        .select2,
        .select2-search__field,
        .select2-results__option {
            font-size: 1.1em !important;
        }

        .select2-selection__rendered {
            line-height: 3em !important;
        }

        .select2-container .select2-selection--single {
            height: 3em !important;
        }

        .select2-selection__arrow {
            height: 3em !important;
        }

    </style>
    <!-- page css -->
    @stack('page-css')
</head>

<body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ navigation menu ] start -->
    @include('layouts.navbar')
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    @include('layouts.header')
    <!-- [ Header ] end -->
    {{-- <div id="app"> --}}


    <main class="py-4 mt-5">
        <div class="pcoded-wrapper container mt-5">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">
                            @include('flash')
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    {{-- </div> --}}

    <!-- Required Js -->
    <script src="{{ asset('assets/backend/js/vendor-all.min.js') }}"></script>
    <script src="{{ asset('assets/backend/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/backend/js/pcoded.min.js') }}"></script>


    <!-- prism Js -->
    <script src="{{ asset('assets/backend/js/plugins/prism.js') }}"></script>


    <script src="{{ asset('assets/backend/js/horizontal-menu.js') }}"></script>
    <script>
        (function() {
            if ($('#layout-sidenav').hasClass('sidenav-horizontal') || window.layoutHelpers.isSmallScreen()) {
                return;
            }
            try {
                window.layoutHelpers._getSetting("Rtl")
                window.layoutHelpers.setCollapsed(
                    localStorage.getItem('layoutCollapsed') === 'true',
                    false
                );
            } catch (e) {}
        })();
        $(function() {
            $('#layout-sidenav').each(function() {
                new SideNav(this, {
                    orientation: $(this).hasClass('sidenav-horizontal') ? 'horizontal' : 'vertical'
                });
            });
            $('body').on('click', '.layout-sidenav-toggle', function(e) {
                e.preventDefault();
                window.layoutHelpers.toggleCollapsed();
                if (!window.layoutHelpers.isSmallScreen()) {
                    try {
                        localStorage.setItem('layoutCollapsed', String(window.layoutHelpers.isCollapsed()));
                    } catch (e) {}
                }
            });
        });
        // $(document).ready(function() {
        //     $("#pcoded").pcodedmenu({
        //         themelayout: 'horizontal',
        //         MenuTrigger: 'hover',
        //         SubMenuTrigger: 'hover',
        //     });
        // });
        $(document).ready(function() {
            $("#pcoded").pcodedmenu({
                themelayout: 'horizontal',
                FixedNavbarPosition: true,
                FixedHeaderPosition: true,
            });
        });

        $(document).ready(function(){

		// delete confirmation modal
		$('.deletebtn').on('click',function (){
			event.preventDefault();
			// jQuery.noConflict();
			$('#deleteConfirmModal').modal('show');
			var id = $(this).data('id');
			console.log(id);
			$('#delete_id').val(id);
		});

        $('.select2').select2({
				placeholder: 'Select an option'
			});


	});
    </script>

    <script src="{{ asset('assets/backend/js/analytics.js') }}"></script>

    @stack('page-js')
    @yield('script')

</body>

</html>

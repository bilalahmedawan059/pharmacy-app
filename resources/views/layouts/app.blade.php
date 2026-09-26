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

        body {
            margin: 0;
            background: #edf3f1;
            color: var(--sales-ink);
        }

        a:focus,
        a:focus-visible,
        button:focus,
        button:focus-visible,
        .btn:focus,
        .btn:focus-visible,
        .nav-link:focus,
        .nav-link:focus-visible,
        .dropdown-toggle:focus,
        .dropdown-toggle:focus-visible,
        .submenu-toggle:focus,
        .submenu-toggle:focus-visible,
        .form-control:focus,
        .custom-select:focus,
        .select2-selection:focus,
        .select2-selection:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            border-color: transparent !important;
        }

        .pharmapos-app-shell {
            min-height: 100vh;
            background: #edf3f1;
        }

        .pharmapos-layout {
            display: flex;
            min-height: 100vh;
            background: #edf3f1;
        }

        .pharmapos-main {
            flex: 1;
            min-width: 0;
            padding: 0 22px 26px;
        }

        .pharmapos-main > .pcoded-header {
            position: relative;
            top: auto;
            right: auto;
            left: auto;
            width: auto;
            margin: 0 -22px 20px;
            box-sizing: border-box;
        }

        .pharmapos-main-inner {
            background: transparent;
            /* min-height: 100%; */
        }

        .pharmapos-main-inner.dashboard-page {
            padding: 12px 12px 24px;
        }

        @media (max-width: 767px) {
            .pharmapos-main-inner.dashboard-page {
                padding: 8px 4px 20px;
            }
        }

        .page-wrapper {
            padding-top: 0;
            background: transparent;
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

        .table-search-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .table-search-control input {
            width: min(100%, 320px);
        }

        .table-export-buttons {
            display: flex;
            gap: 8px;
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
    <div class="pharmapos-app-shell">
        <div class="pharmapos-layout">
            @include('layouts.navbar')

            <main class="pharmapos-main">
                @include('layouts.header')
                <div class="pharmapos-main-inner {{ request()->routeIs('dashboard') || request()->path() === '/' ? 'dashboard-page' : '' }}">
                    @include('flash')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Required Js -->
    <script src="{{ asset('assets/backend/js/vendor-all.min.js') }}"></script>
    <script src="{{ asset('assets/backend/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/backend/js/pcoded.min.js') }}"></script>


    <!-- prism Js -->
    <script src="{{ asset('assets/backend/js/plugins/prism.js') }}"></script>


    <script src="{{ asset('assets/backend/js/horizontal-menu.js') }}"></script>
    <script>
        $(document).ready(function() {
            const $sidebar = $('.pharmapos-sidebar');
            const $toggle = $('#sidebarToggle');

            $('.pharmapos-has-submenu').each(function() {
                const $parent = $(this);
                const isOpen = $parent.hasClass('open');

                if (!isOpen) {
                    $parent.find('> .pharmapos-submenu').hide();
                }

                $parent.find('> .pharmapos-item-wrap .submenu-toggle').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $parent.toggleClass('open');
                    $parent.find('> .pharmapos-submenu').slideToggle(180);
                });
            });

            if (localStorage.getItem('pharmaposSidebarCollapsed') === 'true') {
                $sidebar.addClass('collapsed');
            }

            $toggle.on('click', function() {
                $sidebar.toggleClass('collapsed');
                const isCollapsed = $sidebar.hasClass('collapsed');
                localStorage.setItem('pharmaposSidebarCollapsed', String(isCollapsed));
            });
        });

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

    <script>
        $(function() {
            var exportUrlTemplate = @json(route('exports.download', ['dataset' => '__DATASET__', 'format' => '__FORMAT__']));
            var exportedTypes = {};

            $('table.js-searchable-table, table.js-exportable-table, .js-exportable-list').each(function() {
                var element = this;
                var $element = $(element);
                var isTable = element.tagName === 'TABLE';
                var $toolbar = $('<div class="table-search-control"></div>');

                if (isTable && $element.hasClass('js-searchable-table')) {
                    var $rows = $element.find('tbody tr');
                    var $search = $('<input>', {
                        type: 'search',
                        class: 'form-control',
                        placeholder: 'Search records...',
                        'aria-label': 'Search records'
                    });

                    $toolbar.append($search);
                    $search.on('input', function() {
                        var query = this.value.trim().toLocaleLowerCase();
                        $rows.each(function() {
                            $(this).toggle(this.textContent.toLocaleLowerCase().includes(query));
                        });
                    });
                }

                if ($element.hasClass('js-exportable-list') || $element.hasClass('js-exportable-table')) {
                    var exportType = $element.attr('data-export-type');
                    if (exportType && !exportedTypes[exportType]) {
                        exportedTypes[exportType] = true;
                        var $buttons = $('<div class="table-export-buttons"></div>');
                        ['xlsx', 'csv'].forEach(function(format) {
                            var url = exportUrlTemplate
                                .replace('__DATASET__', encodeURIComponent(exportType))
                                .replace('__FORMAT__', format);
                            var query = [];
                            var fromDate = $element.attr('data-from-date');
                            var toDate = $element.attr('data-to-date');
                            if (fromDate) query.push('from_date=' + encodeURIComponent(fromDate));
                            if (toDate) query.push('to_date=' + encodeURIComponent(toDate));
                            if (query.length) url += '?' + query.join('&');

                            $('<a>', {
                                class: 'btn btn-sm btn-outline-primary',
                                href: url,
                                text: format === 'xlsx' ? 'Excel' : 'CSV',
                                'aria-label': 'Export as ' + (format === 'xlsx' ? 'Excel' : 'CSV')
                            }).appendTo($buttons);
                        });
                        var exportTarget = $element.attr('data-export-target');
                        if (exportTarget) {
                            $(exportTarget).append($buttons);
                        } else {
                            $toolbar.append($buttons);
                        }
                    }
                }

                if ($toolbar.children().length) {
                    var $anchor = isTable ? $element.closest('.table-responsive') : $element;
                    $toolbar.insertBefore($anchor);
                }
            });
        });
    </script>

    @stack('page-js')
    @yield('script')

</body>

</html>

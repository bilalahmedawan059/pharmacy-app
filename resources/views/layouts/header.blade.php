<header class="navbar pcoded-header navbar-expand-lg navbar-light header-dark">
    <div class="container">
        <div class="m-header">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="feather icon-menu"></i>
            </button>

            <a href="{{route('dashboard')}}" class="b-brand">
                <img class="logo" width="120" style="width: 45px" src="@if(!empty(AppSettings::get('logo'))) {{asset('storage/'.AppSettings::get('logo'))}} @else{{asset('img/logo1.png')}} @endif" alt="Logo">
                <img src="{{ asset('assets/backend/images/logo-icon.png') }}" alt="" class="logo-thumb">
            </a>
            <a href="#!" class="mob-toggler">
                <i class="feather icon-more-vertical"></i>
            </a>

        </div>
        <div class="collapse navbar-collapse">
            {{-- <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a href="#!" class="pop-search"><i class="feather icon-search"></i></a>
                    <div class="search-bar">
                        <input type="text" class="form-control border-0 shadow-none" placeholder="Search hear">
                        <button type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </li>
            </ul> --}}
            
            @auth
                <ul class="navbar-nav ml-auto">
                    @role('super-admin')
                    <li class="nav-item mr-3">
                        <a href="{{ route('register') }}" class="nav-link" title="Pharmacy onboarding">
                            <i class="feather icon-plus-circle"></i> Onboarding
                        </a>
                    </li>
                    @endrole
                    <li>
                        <div class="dropdown">
                            <a class="dropdown-toggle" href="#" data-toggle="dropdown">
                                <i class="icon feather icon-bell"></i>
                                <span class="badge badge-pill badge-danger">{{auth()->user()->unReadNotifications->count()}}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right notification">
                                <div class="noti-head">
                                    <h6 class="d-inline-block m-b-0">Notifications</h6>
                                    @if (auth()->user()->unReadNotifications->count() > 0)
                                    <div class="float-right">
                                        <a href="{{route('mark-as-read')}}" class="m-r-10">mark all as read</a>
                                        <a href="#!">clear all</a>
                                    </div>
                                    @endif
                                </div>
                                <ul class="noti-body">

                                    @forelse (auth()->user()->unReadNotifications as $notification)
                                    @if ($loop->first)
                                    <li class="n-title">
                                        <p class="m-b-0">Stock Alert</p>
                                    </li>
                                    @endif
                                    <li class="notification">
                                        <a href="{{route('read')}}">
                                        <div class="media">
                                            <img class="img-radius" alt="Product image" src="{{asset('storage/purchases/' .$notification->data['image'])}}">
                                            <div class="media-body">
                                                <p><strong>{{$notification->data['product_name']}} </strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>{{$notification->created_at->diffForHumans()}}</span></p>
                                                <p>is out of stock {{$notification->data['quantity']}} left in quantity.</p>
                                            </div>
                                        </div>
                                        </a>
                                    </li>
                                @empty
                                <li class="notification text-center">
                                    <strong class="text-center">No Message</strong>
                                </li>
                                @endforelse
                                    {{-- <li class="n-title">
                                        <p class="m-b-0">NEW</p>
                                    </li>
                                    <li class="notification">
                                        <div class="media">
                                            <img class="img-radius" alt="Product image" src="{{asset('storage/purchases/' .$notification['image'])}}">
                                            <div class="media-body">
                                                <p><strong>Stock Alert</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>{{$notification->created_at->diffForHumans()}}</span></p>
                                                <p>{{$notification->data['product_name']}} is out of stock {{$notification->data['quantity']}} left in quantity.</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="n-title">
                                        <p class="m-b-0">EARLIER</p>
                                    </li>
                                    <li class="notification">
                                        <div class="media">
                                            <img class="img-radius" src="assets/images/user/avatar-2.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <p><strong>Joseph William</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>10 min</span></p>
                                                <p>Prchace New Theme and make payment</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="notification">
                                        <div class="media">
                                            <img class="img-radius" src="assets/images/user/avatar-1.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <p><strong>Sara Soudein</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>12 min</span></p>
                                                <p>currently login</p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="notification">
                                        <div class="media">
                                            <img class="img-radius" src="assets/images/user/avatar-2.jpg" alt="Generic placeholder image">
                                            <div class="media-body">
                                                <p><strong>Joseph William</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>30 min</span></p>
                                                <p>Prchace New Theme and make payment</p>
                                            </div>
                                        </div>
                                    </li> --}}
                                </ul>
                                <div class="noti-footer">
                                    <a href="#!">show all</a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="dropdown drp-user">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="feather icon-user"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right profile-notification">
                                <div class="pro-head">
                                    <img src="{{ asset('assets/backend/images/user/avatar-1.jpg') }}" class="img-radius" alt="User-Profile-Image">
                                    <span> {{ Auth::user()->name }}</span>
                                    <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                                document.getElementById('logout-form').submit();" class="dud-logout" title="Logout">
                                        <i class="feather icon-log-out"></i>
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                                <ul class="pro-body">
                                    <li><a href="{{route('profile')}}" class="dropdown-item"><i class="feather icon-user"></i> Profile</a></li>
                                    {{-- <li><a href="email_inbox.html" class="dropdown-item"><i class="feather icon-mail"></i> My Messages</a></li>
                                    <li><a href="auth-signin.html" class="dropdown-item"><i class="feather icon-lock"></i> Lock Screen</a></li> --}}
                                </ul>
                            </div>
                        </div>
                    </li>
                </ul>
            @endauth

        </div>
    </div>

<style>
    .pcoded-header.header-dark {
        background: linear-gradient(180deg, #111b1b 0%, #0d1717 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 0 8px 26px rgba(8, 17, 17, 0.18);
        min-height: 72px;
    }

    .pcoded-header .container {
        max-width: 100%;
        padding-left: 18px;
        padding-right: 18px;
    }

    .pcoded-header .m-header {
        display: flex;
        align-items: center;
        min-height: 72px;
    }

    .pcoded-header .b-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
        text-decoration: none;
    }

    .pcoded-header .nav-link,
    .pcoded-header .dropdown-toggle,
    .pcoded-header .icon,
    .pcoded-header .feather {
        color: rgba(255,255,255,0.9) !important;
    }

    .pcoded-header .dropdown-toggle::after {
        display: none !important;
        content: none !important;
    }

    .pcoded-header .dropdown-toggle .feather,
    .pcoded-header .dropdown-toggle .icon {
        font-size: 17px;
        line-height: 1;
    }

    .pcoded-header .navbar-nav.ml-auto {
        gap: 14px;
        align-items: center;
    }

    .pcoded-header .nav-link {
        padding: 10px 12px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .pcoded-header .nav-link:hover,
    .pcoded-header .dropdown-toggle:hover {
        background: rgba(255,255,255,0.06);
    }

    .pcoded-header .dropdown-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: transparent;
        border: 0;
        padding: 0;
        box-shadow: none;
    }

    .sidebar-toggle {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        cursor: pointer;
        opacity: 0.95;
    }

    .sidebar-toggle:hover,
    .pcoded-header .dropdown-toggle:hover {
        background: rgba(255,255,255,0.04);
    }

    .pcoded-header .badge-danger {
        position: absolute;
        top: -5px;
        right: -3px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 16px;
        width: 16px;
        height: 16px;
        padding: 0;
        border-radius: 50%;
        background: #e84141 !important;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 0 0 2px rgba(17, 27, 27, 0.9);
    }

    .pcoded-header .pro-head {
        background: #0f1d1d;
        color: #fff;
    }

    .pcoded-header .profile-notification {
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        overflow: hidden;
    }
</style>
</header>

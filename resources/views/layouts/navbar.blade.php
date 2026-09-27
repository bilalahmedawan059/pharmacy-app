<aside class="pharmapos-sidebar">
    <div class="pharmapos-brand">
        <div class="brand-box">🏥</div>
        <div>
            <div class="brand-name">PharmaPOS</div>
            <small>Inventory &amp; Point of Sale</small>
        </div>
    </div>

    <nav class="pharmapos-nav">
        <ul>
            @auth
                @can('view-dashboard')
                    <li class="{{ route_is('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}"><span class="nav-icon">🏠</span> <span class="nav-label">Dashboard</span></a>
                    </li>
                @endcan
            @endauth

            @can('view-products')
                <li class="pharmapos-has-submenu {{ route_is(('products')) || route_is(('add-product')) || route_is(('outstock')) || route_is(('expired')) || route_is(('edit-product')) ? 'active open' : '' }}">
                    <div class="pharmapos-item-wrap">
                        <a href="{{ route('products') }}"><span class="nav-icon">📦</span> <span class="nav-label">Product Catalog</span></a>
                        <button type="button" class="submenu-toggle" aria-label="Toggle Product Catalog submenu">▾</button>
                    </div>
                    <ul class="pharmapos-submenu">
                        @can('view-products')
                            <li class="{{ route_is('products') ? 'active' : '' }}"><a href="{{ route('products') }}">Medicines</a></li>
                        @endcan
                        @can('create-product')
                            <li class="{{ route_is('add-product') ? 'active' : '' }}"><a href="{{ route('add-product') }}">Add Medicine</a></li>
                        @endcan
                        @can('view-outstock-products')
                            <li class="{{ route_is('outstock') ? 'active' : '' }}"><a href="{{ route('outstock') }}">Out-Stock</a></li>
                        @endcan
                        @can('view-expired-products')
                            <li class="{{ route_is('expired') ? 'active' : '' }}"><a href="{{ route('expired') }}">Expired</a></li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('view-sales')
                <li class="{{ route_is(('sales')) || route_is(('sales-auto')) ? 'active' : '' }}">
                    <a href="{{ route('sales') }}"><span class="nav-icon">🛒</span> <span class="nav-label">Checkout</span></a>
                </li>
            @endcan

            @can('view-purchase')
                <li class="pharmapos-has-submenu {{ route_is(('purchases')) || route_is(('add-purchase')) || route_is(('edit-purchase')) ? 'active open' : '' }}">
                    <div class="pharmapos-item-wrap">
                        <a href="{{ route('purchases') }}"><span class="nav-icon">📊</span> <span class="nav-label">Inventory</span></a>
                        <button type="button" class="submenu-toggle" aria-label="Toggle Inventory submenu">▾</button>
                    </div>
                    <ul class="pharmapos-submenu">
                        <li class="{{ route_is('purchases') ? 'active' : '' }}"><a href="{{ route('purchases') }}">Stock Purchase</a></li>
                        @can('create-purchase')
                            <li class="{{ route_is('add-purchase') ? 'active' : '' }}"><a href="{{ route('add-purchase') }}">Add Stock</a></li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('view-category')
                <li class="{{ route_is('categories') ? 'active' : '' }}">
                    <a href="{{ route('categories') }}"><span class="nav-icon">📁</span> <span class="nav-label">Categories</span></a>
                </li>
            @endcan

            @can('view-supplier')
                <li class="pharmapos-has-submenu {{ route_is(('suppliers')) || route_is(('add-supplier')) || route_is(('edit-supplier')) ? 'active open' : '' }}">
                    <div class="pharmapos-item-wrap">
                        <a href="{{ route('suppliers') }}"><span class="nav-icon">👥</span> <span class="nav-label">Suppliers</span></a>
                        <button type="button" class="submenu-toggle" aria-label="Toggle Suppliers submenu">▾</button>
                    </div>
                    <ul class="pharmapos-submenu">
                        <li class="{{ route_is('suppliers') ? 'active' : '' }}"><a href="{{ route('suppliers') }}">Supplier</a></li>
                        @can('create-supplier')
                            <li class="{{ route_is('add-supplier') ? 'active' : '' }}"><a href="{{ route('add-supplier') }}">Add Supplier</a></li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('view-reports')
                <li class="{{ route_is(('reports')) ? 'active' : '' }}">
                    <a href="{{ route('reports') }}"><span class="nav-icon">📉</span> <span class="nav-label">Reports</span></a>
                </li>
            @endcan

            @can('view-users')
                <li class="{{ route_is('users') ? 'active' : '' }}">
                    <a href="{{ route('users') }}"><span class="nav-icon">👤</span> <span class="nav-label">Users</span></a>
                </li>
            @endcan

            @can('view-branches')
                <li class="{{ request()->is('branches*') ? 'active' : '' }}">
                    <a href="{{ route('branches.index') }}"><span class="nav-icon">📍</span> <span class="nav-label">Branches</span></a>
                </li>
            @endcan

            @if (auth()->check() && auth()->user()->hasRole('super-admin'))
                <li class="{{ route_is('roles') ? 'active' : '' }}">
                    <a href="{{ route('roles') }}"><span class="nav-icon">🛡️</span> <span class="nav-label">Role Management</span></a>
                </li>
                <li class="{{ route_is('permissions') ? 'active' : '' }}">
                    <a href="{{ route('permissions') }}"><span class="nav-icon">🔐</span> <span class="nav-label">Permissions</span></a>
                </li>
            @endif

            @auth
                @can('view-settings')
                    <li class="pharmapos-has-submenu {{ route_is(('settings')) || route_is(('backup.index')) ? 'active open' : '' }}">
                        <div class="pharmapos-item-wrap">
                            <a href="{{ route('settings') }}"><span class="nav-icon">⚙️</span> <span class="nav-label">Settings</span></a>
                            <button type="button" class="submenu-toggle" aria-label="Toggle Settings submenu">▾</button>
                        </div>
                        <ul class="pharmapos-submenu">
                            @can('view-settings')
                                <li class="{{ route_is('settings') ? 'active' : '' }}"><a href="{{ route('settings') }}">Settings</a></li>
                            @endcan
                            <li class="{{ route_is('backup.index') ? 'active' : '' }}"><a href="{{ route('backup.index') }}">Backups</a></li>
                        </ul>
                    </li>
                @endcan
            @endauth
        </ul>
    </nav>
</aside>

<style>
    .pharmapos-sidebar {
        position: sticky;
        top: 0;
        align-self: flex-start;
        width: 240px;
        background: linear-gradient(180deg, #072d2d 0%, #062b2a 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        color: #edf7f6;
        display: flex;
        flex-direction: column;
        padding: 16px 14px 18px;
        height: 100vh;
        min-height: 100vh;
        box-sizing: border-box;
        overflow-y: auto;
        transition: width 0.22s ease, padding 0.22s ease;
    }

    .pharmapos-sidebar.collapsed {
        width: 86px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .pharmapos-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 8px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 18px;
    }

    .brand-box {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: rgba(255,255,255,0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .brand-name {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.1;
    }

    .pharmapos-brand small {
        color: rgba(255,255,255,0.72);
        font-size: 11px;
    }

    .pharmapos-sidebar.collapsed .pharmapos-brand {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .pharmapos-sidebar.collapsed .brand-name,
    .pharmapos-sidebar.collapsed .pharmapos-brand small {
        display: none;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav a {
        justify-content: center;
        padding-left: 8px;
        padding-right: 8px;
        min-height: 46px;
        font-size: 0;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav a .nav-icon {
        margin-right: 0;
        font-size: 20px;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav li.active a {
        background: linear-gradient(135deg, rgba(30, 187, 156, 0.28), rgba(20, 160, 136, 0.18));
        box-shadow: inset 0 0 0 1px rgba(74, 223, 191, 0.36);
    }

    .pharmapos-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pharmapos-nav li {
        position: relative;
    }

    .pharmapos-item-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pharmapos-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 12px;
        border-radius: 10px;
        color: rgba(255,255,255,0.82);
        font-size: 17px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        flex: 1;
    }

    .pharmapos-nav a:hover {
        background: rgba(255,255,255,0.06);
        color: #fff;
    }

    .pharmapos-nav > ul > li.active > a,
    .pharmapos-nav > ul > li.active > .pharmapos-item-wrap > a {
        background: rgba(255,255,255,0.09);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.04);
    }

    .pharmapos-nav li.pharmapos-has-submenu.active > .pharmapos-item-wrap > .submenu-toggle {
        background: rgba(255,255,255,0.04);
    }

    .submenu-toggle {
        width: 24px;
        height: 24px;
        border: 0;
        background: transparent;
        color: rgba(255,255,255,0.8);
        font-size: 14px;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .pharmapos-has-submenu:not(.open) > .pharmapos-submenu {
        display: none;
    }

    .pharmapos-has-submenu.open > .pharmapos-submenu {
        display: block;
    }

    .pharmapos-has-submenu.open > .pharmapos-item-wrap .submenu-toggle {
        transform: rotate(180deg);
    }

    .pharmapos-submenu {
        list-style: none;
        margin: 6px 0 0 14px;
        padding: 0 0 0 12px;
        border-left: 1px solid rgba(255,255,255,0.08);
        display: block;
    }

    .pharmapos-submenu li a {
        padding: 8px 10px;
        font-size: 14px;
        color: rgba(255,255,255,0.75);
    }

    .pharmapos-submenu li.active > a {
        background: rgba(30, 187, 156, 0.12);
        color: #ffffff;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav > ul > li.active > .pharmapos-item-wrap > a,
    .pharmapos-sidebar.collapsed .pharmapos-nav > ul > li.active > a {
        background: linear-gradient(135deg, rgba(30, 187, 156, 0.28), rgba(20, 160, 136, 0.18));
        box-shadow: inset 0 0 0 1px rgba(74, 223, 191, 0.36);
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav > ul > li:not(.active) > .pharmapos-item-wrap > a,
    .pharmapos-sidebar.collapsed .pharmapos-nav > ul > li:not(.active) > a {
        background: transparent;
    }

    .nav-label {
        display: inline-block;
    }

    .nav-icon {
        width: 20px;
        text-align: center;
        opacity: 0.95;
    }

    .pharmapos-sidebar.collapsed .nav-label,
    .pharmapos-sidebar.collapsed .pharmapos-submenu,
    .pharmapos-sidebar.collapsed .brand-name,
    .pharmapos-sidebar.collapsed .pharmapos-brand small {
        display: none !important;
    }

    .pharmapos-sidebar.collapsed .pharmapos-brand {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav a {
        justify-content: center;
        padding-left: 8px;
        padding-right: 8px;
        min-height: 46px;
    }

    .pharmapos-sidebar.collapsed .pharmapos-nav a .nav-icon {
        margin-right: 0;
        font-size: 20px;
    }

    .pharmapos-sidebar.collapsed .pharmapos-item-wrap {
        width: 100%;
    }

    .pharmapos-sidebar.collapsed .pharmapos-item-wrap > a {
        width: 100%;
        min-width: 0;
    }

    .pharmapos-sidebar.collapsed .submenu-toggle {
        display: none;
    }

    @media (max-width: 991px) {
        .pharmapos-layout {
            display: block;
        }

        .pharmapos-sidebar {
            position: relative;
            width: 100%;
            height: auto;
            min-height: 100vh;
            overflow-y: visible;
            border-right: 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

    }
</style>

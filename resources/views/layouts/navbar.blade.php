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
                        <a href="{{ route('dashboard') }}"><span class="nav-icon">🏠</span> Dashboard</a>
                    </li>
                @endcan
            @endauth

            @can('view-products')
                <li class="{{ route_is(('products')) || route_is(('add-product')) || route_is(('outstock')) || route_is(('expired')) || route_is(('edit-product')) ? 'active' : '' }}">
                    <a href="{{ route('products') }}"><span class="nav-icon">📦</span> Product Catalog</a>
                </li>
            @endcan

            @can('view-sales')
                <li class="{{ route_is(('sales')) || route_is(('sales-auto')) ? 'active' : '' }}">
                    <a href="{{ route('sales') }}"><span class="nav-icon">🛒</span> Checkout</a>
                </li>
            @endcan

            @can('view-purchase')
                <li class="{{ route_is(('purchases')) || route_is(('add-purchase')) || route_is(('edit-purchase')) ? 'active' : '' }}">
                    <a href="{{ route('purchases') }}"><span class="nav-icon">📊</span> Inventory</a>
                </li>
            @endcan

            @can('view-category')
                <li class="{{ route_is('categories') ? 'active' : '' }}">
                    <a href="{{ route('categories') }}"><span class="nav-icon">📁</span> Categories</a>
                </li>
            @endcan

            @can('view-supplier')
                <li class="{{ route_is(('suppliers')) || route_is(('add-supplier')) || route_is(('edit-supplier')) ? 'active' : '' }}">
                    <a href="{{ route('suppliers') }}"><span class="nav-icon">👥</span> Suppliers</a>
                </li>
            @endcan

            @can('view-reports')
                <li class="{{ route_is(('reports')) ? 'active' : '' }}">
                    <a href="{{ route('reports') }}"><span class="nav-icon">📉</span> Reports</a>
                </li>
            @endcan

            @can('view-users')
                <li class="{{ route_is('users') ? 'active' : '' }}">
                    <a href="{{ route('users') }}"><span class="nav-icon">👤</span> Users</a>
                </li>
            @endcan

            @can('view-branches')
                <li class="{{ request()->is('branches*') ? 'active' : '' }}">
                    <a href="{{ route('branches.index') }}"><span class="nav-icon">📍</span> Branches</a>
                </li>
            @endcan

            @auth
                @can('view-settings')
                    <li class="{{ route_is(('settings')) || route_is(('backup.index')) ? 'active' : '' }}">
                        <a href="{{ route('settings') }}"><span class="nav-icon">⚙️</span> Settings</a>
                    </li>
                @endcan
            @endauth
        </ul>
    </nav>
</aside>

<style>
    .pharmapos-sidebar {
        width: 240px;
        background: linear-gradient(180deg, #072d2d 0%, #062b2a 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        color: #edf7f6;
        display: flex;
        flex-direction: column;
        padding: 16px 14px 18px;
        min-height: calc(100vh - 72px);
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

    .pharmapos-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
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
    }

    .pharmapos-nav a:hover {
        background: rgba(255,255,255,0.06);
        color: #fff;
    }

    .pharmapos-nav li.active a {
        background: rgba(255,255,255,0.09);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.04);
    }

    .nav-icon {
        width: 20px;
        text-align: center;
        opacity: 0.95;
    }

    @media (max-width: 991px) {
        .pharmapos-layout {
            display: block;
        }

        .pharmapos-sidebar {
            width: 100%;
            min-height: auto;
            border-right: 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
    }
</style>

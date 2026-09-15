<link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">

<aside class="sidebar">

    <div class="sidebar-logo">
        Wifi Dashboard
    </div>

    <nav class="sidebar-nav">

        <a href="{{ route('dashboard') }}" class="sidebar-link">
            <span>Dashboard</span>
        </a>

        <a href="{{ route('dashboard.users') }}" class="sidebar-link">
            <span>Users</span>
        </a>

        <a href="{{ route('dashboard.sessions') }}" class="sidebar-link">
            <span>Sessions</span>
        </a>

        <div class="sidebar-bottom">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="logout-btn">
                    Logout
                </button>
            </form>
        </div>

    </nav>

</aside>
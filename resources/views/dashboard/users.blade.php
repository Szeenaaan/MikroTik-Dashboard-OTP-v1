<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users - Mikrotik Dashboard</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
</head>

<body>

    <div class="dashboard">

        @include('dashboard.sidebar')

        <main class="main users-page">

            <div class="page-header">

                <div class="page-title">
                    <h1>Users</h1>
                </div>

                <a href="{{ route('dashboard.users.export', request()->query()) }}" class="export-button">
                    Export Excel
                </a>

            </div>

            @if(isset($users['error']))

                <div class="alert">
                    {{ $users['error'] }}
                </div>

            @else

                <div class="users-card">

                    <form method="GET" action="{{ route('dashboard.users') }}">

                        <div class="search-row">

                            <div class="search-group">
                                <input type="text" name="search"
                                    placeholder="Search MAC address, phone number or IP address"
                                    value="{{ request('search') }}">
                            </div>

                            <button type="submit" class="search-button">
                                Search
                            </button>

                        </div>

                        <div class="users-filters">

                            <div class="filter-group">
                                <label for="status">Status</label>

                                <select name="status" id="status">
                                    <option value="">All</option>

                                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>
                                        Active
                                    </option>

                                    <option value="BLOCKED" {{ request('status') === 'BLOCKED' ? 'selected' : '' }}>
                                        Blocked
                                    </option>
                                    <option value="BYPASSED" {{ request('status') === 'BYPASSED' ? 'selected' : '' }}>
                                        Bypassed
                                    </option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="from_date">From</label>

                                <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}">
                            </div>

                            <div class="filter-group">
                                <label for="to_date">To</label>

                                <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}">
                            </div>

                            <div class="filter-group">
                                <label for="order">Order</label>

                                <select name="order" id="order">
                                    <option value="desc" {{ request('order', 'desc') === 'desc' ? 'selected' : '' }}>
                                        Latest
                                    </option>

                                    <option value="asc" {{ request('order') === 'asc' ? 'selected' : '' }}>
                                        Oldest
                                    </option>
                                </select>
                            </div>

                            <div class="filter-actions">

                                <button type="submit" class="filter-button">
                                    Filter
                                </button>

                                <a href="{{ route('dashboard.users') }}" class="reset-button">
                                    Reset
                                </a>

                            </div>

                        </div>

                    </form>

                    <div class="users-table-wrapper">

                        <table class="users-table">

                            <thead>
                                <tr>
                                    <th>MAC Address</th>
                                    <th>Phone Number</th>
                                    <th>IP Address</th>
                                    <th>Last Login</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Sessions</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($users as $user)

                                                    <tr>

                                                        <td>
                                                            {{ $user['mac_address'] }}
                                                        </td>

                                                        <td>
                                                            {{ $user['phone_number'] ?? '-' }}
                                                        </td>

                                                        <td>
                                                            {{ $user['ip_address'] ?? '-' }}
                                                        </td>

                                                        <td>
                                                            {{ $user['last_login_at'] ?? '-' }}
                                                        </td>

                                                        <td>
                                                            {{ $user['download_bytes'] }}
                                                        </td>

                                                        <td>
                                                            {{ $user['upload_bytes'] }}
                                                        </td>

                                                        <td>
                                                            {{ $user['session_count'] }}
                                                        </td>

                                                        <td>
                                                            <span class="status {{ strtolower($user['status']) }}">
                                                                {{ $user['status'] }}
                                                            </span>
                                                        </td>

                                                        <td>

                                                            <form method="POST" action="{{ $user['status'] === 'BLOCKED'
                                    ? route('dashboard.users.unblock', $user['id'])
                                    : route('dashboard.users.block', $user['id']) }}" class="dashboard-action-form">

                                                                @csrf

                                                                <button type="submit"
                                                                    class="user-action {{ $user['status'] === 'BLOCKED' ? 'unblock' : 'block' }}">
                                                                    {{ $user['status'] === 'BLOCKED' ? 'Unblock' : 'Block' }}
                                                                </button>

                                                            </form>

                                                            <form method="POST" action="{{ $user['permanent_access']
                                    ? route('dashboard.users.remove-bypass', $user['id'])
                                    : route('dashboard.users.bypass', $user['id']) }}" class="dashboard-action-form">

                                                                @csrf

                                                                <button type="submit"
                                                                    class="user-action {{ $user['permanent_access'] ? 'block' : 'unblock' }}">
                                                                    {{ $user['permanent_access'] ? 'Remove Access' : 'Permanent Access' }}
                                                                </button>

                                                            </form>

                                                        </td>

                                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="9" class="empty-users">
                                            No users found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <div class="pagination">
                        {{ $users->withQueryString()->links() }}
                    </div>

                </div>

            @endif

        </main>

    </div>

    @include('dashboard.toast')
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/format.js') }}"></script>
    <script src="{{ asset('js/users.js') }}"></script>
    <script src="{{ asset('js/actions.js') }}"></script>
    <script src="{{ asset('js/heartbeat.js') }}"></script>

</body>

</html>
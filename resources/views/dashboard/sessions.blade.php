<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sessions - Mikrotik Dashboard</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sessions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
</head>

<body>

    <div class="dashboard">

        @include('dashboard.sidebar')

        <main class="main sessions-page">

            <div class="page-header">
                <div class="page-title">
                    <h1>Sessions</h1>
                </div>

                <a href="{{ route('dashboard.sessions.export', request()->query()) }}" class="export-button">
                    Export Excel
                </a>
            </div>

            @if(isset($sessions['error']))

                <div class="alert">
                    {{ $sessions['error'] }}
                </div>

            @else

                <div class="sessions-card">

                    <form method="GET" action="{{ route('dashboard.sessions') }}">

                        <div class="search-row">

                            <div class="search-group">
                                <input type="text" name="search"
                                    placeholder="Search MAC address, IP address, NAS IP or session ID"
                                    value="{{ request('search') }}">
                            </div>

                            <button type="submit" class="search-button">
                                Search
                            </button>

                        </div>

                        <div class="sessions-filters">

                            <div class="filter-group">
                                <label for="status">Status</label>

                                <select name="status" id="status">
                                    <option value="">All</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>
                                        Terminated
                                    </option>
                                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>
                                        Expired
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

                                <a href="{{ route('dashboard.sessions') }}" class="reset-button">
                                    Reset
                                </a>

                            </div>

                        </div>

                    </form>

                    <div class="sessions-table-wrapper">

                        <table class="sessions-table">

                            <thead>
                                <tr>
                                    <th>MAC Address</th>
                                    <th>Mobile Number</th>
                                    <th>IP Address</th>
                                    <th>Session Start</th>
                                    <th>Session End</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Session Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($sessions as $session)

                                    <tr>
                                        <td>{{ $session['mac'] }}</td>
                                        <td>{{ $session['radius_username'] }}</td>
                                        <td>{{ $session['ip'] ?? '-' }}</td>
                                        <td>{{ $session['session_start'] ?? '-' }}</td>
                                        <td>{{ $session['session_end'] ?? '-' }}</td>
                                        <td>{{ $session['download_bytes'] }}</td>
                                        <td>{{ $session['upload_bytes'] }}</td>
                                        <td>{{ $session['session_time'] ?? '-' }}</td>

                                        <td>
                                            <span class="status {{ strtolower($session['status']) }}">
                                                {{ $session['status'] }}
                                            </span>
                                        </td>

                                        <td>

                                           @if($session['status'] === 'active')

    <form
        action="{{ route('dashboard.sessions.terminate', $session['id']) }}"
        method="POST"
        class="dashboard-action-form"
    >
        @csrf

        <button type="submit" class="session-action terminate">
            Terminate
        </button>
    </form>

@elseif($session['status'] === 'terminated')

    <button type="button" class="session-action terminated" disabled>
        Terminated
    </button>

@else

    -

@endif

                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="11" class="empty-sessions">
                                            No sessions found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <div class="pagination">

                        @if($sessions->onFirstPage())
                            <span class="pagination-button disabled">
                                « Previous
                            </span>
                        @else
                            <a href="{{ $sessions->withQueryString()->previousPageUrl() }}" class="pagination-button">
                                « Previous
                            </a>
                        @endif

                        @if($sessions->hasMorePages())
                            <a href="{{ $sessions->withQueryString()->nextPageUrl() }}" class="pagination-button">
                                Next »
                            </a>
                        @else
                            <span class="pagination-button disabled">
                                Next »
                            </span>
                        @endif

                    </div>

                </div>

            @endif

        </main>

    </div>

    @include('dashboard.toast')
    <script src="{{ asset('js/sessions.js') }}"></script>
    <script src="{{ asset('js/format.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/actions.js') }}"></script>
    <script src="{{ asset('js/heartbeat.js') }}"></script>

</body>

</html>
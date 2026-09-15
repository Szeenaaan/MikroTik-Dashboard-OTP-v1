<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- <title>Mikrotik Dashboard</title> -->

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
</head>

<body>

    <div class="dashboard">

        @include('dashboard.sidebar')

        <main class="main">

            <div class="page-title">
                <!-- <h1>Mikrotik Dashboard</h1> -->
                <h2>Network usage overview</h2>
            </div>


            <div class="stats">

                <div class="card">
                    <span class="card-title">Total Data</span>

                    <strong class="card-value" id="totalData">
                        {{ $data['total_data'] }}
                    </strong>
                </div>


                <div class="card">
                    <span class="card-title">Download</span>

                    <strong class="card-value" id="downloadData">
                        {{ $data['download_data'] }}
                    </strong>
                </div>


                <div class="card">
                    <span class="card-title">Upload</span>

                    <strong class="card-value" id="uploadData">
                        {{ $data['upload_data'] }}
                    </strong>
                </div>

            </div>


            <div class="usage-card">

                <div class="usage-header">

                    <h2>Data Usage</h2>

                    <div class="periods">

                        <a href="{{ route('dashboard', ['period' => 'h']) }}">
                            1H
                        </a>

                        <a href="{{ route('dashboard', ['period' => 'd']) }}">
                            1D
                        </a>

                        <a href="{{ route('dashboard', ['period' => '3d']) }}">
                            3D
                        </a>

                        <a href="{{ route('dashboard', ['period' => 'week']) }}">
                            1W
                        </a>

                        <a href="{{ route('dashboard', ['period' => 'month']) }}">
                            1M
                        </a>

                    </div>

                </div>


                <div class="chart-container">

                    <canvas id="usageChart"></canvas>

                </div>

            </div>

        </main>

    </div>


    <script>
        const chartData = @json($chartData);
    </script>
    @include('dashboard.toast')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/toast.js') }}" defer></script>
    <script src="{{ asset('js/actions.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <script src="{{ asset('js/heartbeat.js') }}"></script>
</body>

</html>
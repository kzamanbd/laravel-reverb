<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Let's Discuss</title>

        <!-- Styles -->
        <link href="https://cdn.jsdelivr.net/npm/apexcharts@4.5.0/dist/apexcharts.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@4.5.0/dist/apexcharts.min.js"></script>
        {{-- for reverb --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.0.2/dist/echo.iife.min.js"></script>
        <script>
            const key = "{{ env('REVERB_APP_KEY') }}";
            const host = "{{ env('REVERB_HOST') }}";
            const port = parseInt("{{ env('REVERB_PORT', '6001') }}", 10);
            const schema = "{{ env('REVERB_SCHEME', 'https') }}";
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: key,
                cluster: 'mt1',
                wsHost: host,
                wsPort: port ?? 80,
                wssPort: port ?? 443,
                forceTLS: (schema ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        </script>
    </head>

    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div id="chart"></div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const cpuData = [];
                const ramData = [];
                const MAX_POINTS = 60; // Last 60 mins

                const options = {
                    chart: {
                        type: 'line',
                        height: 350,
                        animations: {
                            enabled: true,
                            easing: 'linear',
                            dynamicAnimation: { speed: 500 }
                        },
                        toolbar: { show: false },
                    },
                    series: [
                        { name: 'CPU Usage (%)', data: [] },
                        { name: 'RAM Usage (%)', data: [] }
                    ],
                    xaxis: { type: 'datetime' },
                    yaxis: { min: 0, max: 100 }
                };

                const chart = new ApexCharts(document.querySelector("#chart"), options);
                chart.render();

                // Listen to Laravel Reverb
                window.Echo.channel('realtime-data')
                    .listen('DataUpdateEvent', (e) => {
                        const time = new Date().getTime();

                        cpuData.push({ x: time, y: e.value.cpu });
                        ramData.push({ x: time, y: e.value.memory });

                        if (cpuData.length > MAX_POINTS) cpuData.shift();
                        if (ramData.length > MAX_POINTS) ramData.shift();

                        chart.updateSeries([
                            { data: cpuData },
                            { data: ramData }
                        ]);
                    });
            });
        </script>
    </body>

</html>
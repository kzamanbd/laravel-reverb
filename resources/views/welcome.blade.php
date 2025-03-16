<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Let's Discuss</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center min-h-screen">
            <h1 class="mb-8 text-4xl font-bold text-gray-800 dark:text-white">Let's Discuss. 💬</h1>
            <div id="chart"></div>
        </div>
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
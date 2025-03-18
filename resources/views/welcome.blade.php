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
        <div class="w-full min-h-screen">
            <h1 class="mb-8 text-4xl font-bold text-gray-800 dark:text-white">Let's Discuss. 💬</h1>
            <div id="resourceChart"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const cpuData = [];
                const ramData = [];
                const MAX_POINTS = 60; // Last 60 mins

                const resourceChartOptions = {
                    chart: {
                        type: 'area',
                        height: 350,
                        animations: {
                            enabled: true,
                            easing: 'linear',
                            dynamicAnimation: {
                                speed: 500
                            }
                        },
                        toolbar: {
                            show: false
                        },
                    },
                    series: [{
                        type: 'area',
                        name: 'CPU Usage (%)',
                        data: []
                    },
                    {
                        type: 'area',
                        name: 'RAM Usage (%)',
                        data: []
                    }
                    ],
                    colors: ['#F8F5FF', '#00e396'],
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            colors: ['#7239ea', '#00e396']
                        }
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            opacityFrom: 0.6,
                            opacityTo: 0.8,
                        }
                    },
                    stroke: {
                        curve: 'smooth',
                        show: true,
                        width: 3,
                        colors: ['#7239ea', '#00e396']
                    },
                    xaxis: {
                        type: 'datetime'
                    },
                    yaxis: {
                        min: 0,
                        max: 100
                    }
                };

                const resourceChart = new ApexCharts(document.querySelector("#resourceChart"), resourceChartOptions);
                resourceChart.render();

                // Listen to Laravel Reverb
                function cpuAndRamData(e) {
                    const time = new Date().getTime();

                    cpuData.push({
                        x: time,
                        y: e.value.cpu
                    });
                    ramData.push({
                        x: time,
                        y: e.value.memory
                    });

                    if (cpuData.length > MAX_POINTS) cpuData.shift();
                    if (ramData.length > MAX_POINTS) ramData.shift();

                    resourceChart.updateSeries([{
                        data: cpuData
                    },
                    {
                        data: ramData
                    }
                    ]);
                }
                window.Echo.channel('realtime-data').listen('ResourceMonitorEvent', cpuAndRamData);
            });
        </script>
    </body>

</html>
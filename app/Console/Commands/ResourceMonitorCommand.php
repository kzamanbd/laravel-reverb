<?php

namespace App\Console\Commands;

use App\Events\ResourceMonitorEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResourceMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:realtime-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch data update every second';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $os = PHP_OS_FAMILY;

        Log::info('request', [
            'username' =>  Cache::get('subscriber_username')
        ]);

        switch ($os) {
            case 'Darwin':
                $cpu = $this->getCpuUsageMac();
                $memory = $this->getMemoryUsageMac();
                break;
            case 'Linux':
                $cpu = $this->getCpuUsageLinux();
                $memory = $this->getMemoryUsageLinux();
                break;
            case 'Windows':
                $cpu = $this->getCpuUsageWindows();
                $memory = $this->getMemoryUsageWindows();
                break;
            default:
                $cpu = 0;
                $memory = 0;
                break;
        }

        broadcast(new ResourceMonitorEvent([
            'cpu' => $cpu,
            'memory' => $memory,
            'os' => $os
        ]));

        // Log::info("[$os] CPU: {$cpu}%, RAM: {$memory}% dispatched.");
    }
    // ✅ macOS methods
    private function getCpuUsageMac()
    {
        $cpuUsage = shell_exec("top -l 1 | grep 'CPU usage' | awk '{print $3}' | sed 's/%//'");
        $cpuUsage = floatval($cpuUsage); // Convert to float
        return $cpuUsage;
    }

    private function getMemoryUsageMac()
    {
        // Get RAM usage
        $memoryInfo = shell_exec("vm_stat");
        $memoryInfo = explode("\n", $memoryInfo);

        $pageSize = intval(shell_exec("pagesize"));
        $freePages = intval(explode(':', $memoryInfo[1])[1]);
        $activePages = intval(explode(':', $memoryInfo[2])[1]);
        $inactivePages = intval(explode(':', $memoryInfo[3])[1]);
        $speculativePages = intval(explode(':', $memoryInfo[4])[1]);
        $wiredPages = intval(explode(':', $memoryInfo[5])[1]);

        $usedMemory = ($activePages + $inactivePages + $wiredPages + $speculativePages) * $pageSize;
        $totalMemory = intval(shell_exec("sysctl hw.memsize | awk '{print $2}'"));
        $freeMemory = $freePages * $pageSize;

        $ramUsage = (($totalMemory - $freeMemory) / $totalMemory) * 100;

        return round($ramUsage, 2);
    }

    // ✅ Linux methods
    private function getCpuUsageLinux()
    {
        $load = sys_getloadavg();
        $cores = (int) shell_exec('nproc');
        return round($load[0] * 100 / $cores);
    }

    private function getMemoryUsageLinux()
    {
        $memInfo = file_get_contents("/proc/meminfo");
        preg_match("/MemTotal:\s+(\d+)/", $memInfo, $total);
        preg_match("/MemAvailable:\s+(\d+)/", $memInfo, $free);

        $totalMem = $total[1] ?? 1;
        $freeMem = $free[1] ?? 0;
        $usedMem = $totalMem - $freeMem;

        return round(($usedMem / $totalMem) * 100);
    }

    // ✅ Windows methods
    private function getCpuUsageWindows()
    {
        $cpuOutput = shell_exec('wmic cpu get loadpercentage /value');
        preg_match('/LoadPercentage=(\d+)/', $cpuOutput, $matches);
        return isset($matches[1]) ? intval($matches[1]) : 0;
    }

    private function getMemoryUsageWindows()
    {
        $memTotal = shell_exec('wmic ComputerSystem get TotalPhysicalMemory /value');
        $memFree = shell_exec('wmic OS get FreePhysicalMemory /value');

        preg_match('/TotalPhysicalMemory=(\d+)/', $memTotal, $totalMatch);
        preg_match('/FreePhysicalMemory=(\d+)/', $memFree, $freeMatch);

        $totalMem = isset($totalMatch[1]) ? intval($totalMatch[1]) : 1;
        $freeMem = isset($freeMatch[1]) ? intval($freeMatch[1]) * 1024 : 0;

        $usedMem = $totalMem - $freeMem;
        return round(($usedMem / $totalMem) * 100);
    }
}

<?php

namespace App\Console\Commands;

use App\Events\DataUpdateEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchDataUpdate extends Command
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

        broadcast(new DataUpdateEvent([
            'cpu' => $cpu,
            'memory' => $memory,
            'os' => $os
        ]));

        Log::info("[$os] CPU: {$cpu}%, RAM: {$memory}% dispatched.");
    }
    // ✅ macOS methods
    private function getCpuUsageMac()
    {
        $cpuOutput = shell_exec("ps -A -o %cpu | awk '{s+=$1} END {print s}'");
        return round(floatval($cpuOutput));
    }

    private function getMemoryUsageMac()
    {
        $vmStat = shell_exec("vm_stat");
        preg_match('/Pages free:\s+(\d+)\./', $vmStat, $freeMatches);
        preg_match('/Pages active:\s+(\d+)\./', $vmStat, $activeMatches);
        preg_match('/Pages inactive:\s+(\d+)\./', $vmStat, $inactiveMatches);
        preg_match('/Pages speculative:\s+(\d+)\./', $vmStat, $speculativeMatches);
        preg_match('/Pages wired down:\s+(\d+)\./', $vmStat, $wiredMatches);
        preg_match('/Pages purgeable:\s+(\d+)\./', $vmStat, $purgeableMatches);

        $pageSize = shell_exec("sysctl -n hw.pagesize");
        $pageSize = intval($pageSize);

        $free = ($freeMatches[1] ?? 0) + ($speculativeMatches[1] ?? 0);
        $active = $activeMatches[1] ?? 0;
        $inactive = $inactiveMatches[1] ?? 0;
        $wired = $wiredMatches[1] ?? 0;
        $purgeable = $purgeableMatches[1] ?? 0;

        $usedPages = $active + $inactive + $wired + $purgeable;
        $totalPages = $free + $usedPages;

        $usedMem = $usedPages * $pageSize;
        $totalMem = $totalPages * $pageSize;

        return round(($usedMem / $totalMem) * 100);
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

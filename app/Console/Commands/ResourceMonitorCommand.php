<?php

namespace App\Console\Commands;

use App\Events\ResourceMonitorEvent;
use Illuminate\Console\Command;

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
        ]))->toOthers();

        // Log::info("[$os] CPU: {$cpu}%, RAM: {$memory}% dispatched.");
    }
    private function getCpuUsageMac()
    {
        $cpuOutput = shell_exec("top -l 1 | grep 'CPU usage' | awk '{print $3}' | sed 's/%//'");
        return round(floatval($cpuOutput));
    }

    private function getMemoryUsageMac()
    {
        $cmd = "top -l 1 | grep 'PhysMem' | awk '{print $2}' | tr -d 'M'";
        $usedMemory = intval(trim(shell_exec($cmd)));

        $cmd = "sysctl hw.memsize | awk '{print $2}'";
        $totalMemory = intval(trim(shell_exec($cmd)));
        $totalMemoryMB = $totalMemory / (1024 * 1024);

        return round(($usedMemory / $totalMemoryMB) * 100, 2);
    }

    // ✅ Linux methods
    private function getCpuUsageLinux()
    {
        $cpuUsage = shell_exec("top -bn1 | grep \"Cpu(s)\" | awk '{print $2 + $4}'");

        return round($cpuUsage, 2);
    }

    private function getMemoryUsageLinux()
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);

        $memory_usage = round($mem[2] / $mem[1] * 100, 2);

        return $memory_usage;
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

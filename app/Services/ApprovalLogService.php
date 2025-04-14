<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class ApprovalLogService
{
    protected $logFilePath = 'approval_logs.json';
    public function logApproval(array $data): bool
    {
        try {
            $logEntry = array_merge($data, [
                'timestamp' => now()->toIso8601String(),
            ]);
            $logs = $this->getLogs();
            $logs[] = $logEntry;
            return $this->writeLogs($logs);
        } catch (\Exception $e) {
            Log::error('Failed to write approval log: ' . $e->getMessage());
            return false;
        }
    }
    public function getLogs(): array
    {
        try {
            if (Storage::exists($this->logFilePath)) {
                $content = Storage::get($this->logFilePath);
                return json_decode($content, true) ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to read approval logs: ' . $e->getMessage());
        }
        return [];
    }
    protected function writeLogs(array $logs): bool
    {
        try {
            if (count($logs) > 1000) {
                $logs = array_slice($logs, -1000);
            }
            Storage::put($this->logFilePath, json_encode($logs, JSON_PRETTY_PRINT));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to write approval logs: ' . $e->getMessage());
            return false;
        }
    }
} 
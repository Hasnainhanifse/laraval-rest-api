<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ApprovalLogService
{
    /**
     * The path to the approval logs JSON file
     *
     * @var string
     */
    protected $logFilePath = 'approval_logs.json';

    /**
     * Log an approval action
     *
     * @param array $data The data to log
     * @return bool Whether the log was successfully written
     */
    public function logApproval(array $data): bool
    {
        try {
            // Add timestamp to the log data
            $logEntry = array_merge($data, [
                'timestamp' => now()->toIso8601String(),
            ]);

            // Get existing logs or initialize empty array
            $logs = $this->getLogs();

            // Add new log entry
            $logs[] = $logEntry;

            // Write back to file
            return $this->writeLogs($logs);
        } catch (\Exception $e) {
            Log::error('Failed to write approval log: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all approval logs
     *
     * @return array
     */
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

    /**
     * Write logs to the JSON file
     *
     * @param array $logs
     * @return bool
     */
    protected function writeLogs(array $logs): bool
    {
        try {
            // Limit the number of logs to prevent the file from growing too large
            // Keep only the most recent 1000 logs
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
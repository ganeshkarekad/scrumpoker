<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class LogManagementService
{
    public function __construct(
        private string $projectDir,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get log directory path
     */
    public function getLogDirectory(): string
    {
        return $this->projectDir . '/var/log';
    }

    /**
     * Get all log files with their information
     */
    public function getLogFiles(): array
    {
        $logDir = $this->getLogDirectory();
        
        if (!is_dir($logDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($logDir)
            ->name('*.log')
            ->sortByModifiedTime()
            ->reverseSorting(); // Most recent first

        $files = [];
        foreach ($finder as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'size' => $file->getSize(),
                'modified' => new \DateTime('@' . $file->getMTime()),
                'readable_size' => $this->formatBytes($file->getSize()),
                'age_days' => $this->getFileAgeDays($file->getMTime())
            ];
        }

        return $files;
    }

    /**
     * Get log files older than specified days
     */
    public function getOldLogFiles(int $days = 15): array
    {
        $logDir = $this->getLogDirectory();
        
        if (!is_dir($logDir)) {
            return [];
        }

        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$days}D"));

        $finder = new Finder();
        $finder->files()
            ->in($logDir)
            ->name('*.log')
            ->date("< {$cutoffDate->format('Y-m-d')}")
            ->sortByModifiedTime();

        $files = [];
        foreach ($finder as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'size' => $file->getSize(),
                'modified' => new \DateTime('@' . $file->getMTime()),
                'readable_size' => $this->formatBytes($file->getSize()),
                'age_days' => $this->getFileAgeDays($file->getMTime())
            ];
        }

        return $files;
    }

    /**
     * Clean up old log files
     */
    public function cleanupOldLogs(int $days = 15, bool $dryRun = false): array
    {
        $oldFiles = $this->getOldLogFiles($days);
        $results = [
            'total_files' => count($oldFiles),
            'deleted_files' => 0,
            'deleted_size' => 0,
            'errors' => [],
            'deleted_list' => []
        ];

        if (empty($oldFiles)) {
            return $results;
        }

        foreach ($oldFiles as $file) {
            if ($dryRun) {
                $results['deleted_list'][] = $file['name'];
                $results['deleted_files']++;
                $results['deleted_size'] += $file['size'];
                continue;
            }

            try {
                if (unlink($file['path'])) {
                    $results['deleted_files']++;
                    $results['deleted_size'] += $file['size'];
                    $results['deleted_list'][] = $file['name'];
                    
                    $this->logger->info('Log file deleted during cleanup', [
                        'file' => $file['name'],
                        'size' => $file['size'],
                        'age_days' => $file['age_days']
                    ]);
                } else {
                    $error = "Failed to delete: {$file['name']}";
                    $results['errors'][] = $error;
                    $this->logger->warning('Failed to delete log file', [
                        'file' => $file['name'],
                        'error' => $error
                    ]);
                }
            } catch (\Exception $e) {
                $error = "Error deleting {$file['name']}: " . $e->getMessage();
                $results['errors'][] = $error;
                $this->logger->error('Exception during log file deletion', [
                    'file' => $file['name'],
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]);
            }
        }

        return $results;
    }

    /**
     * Get log statistics
     */
    public function getLogStatistics(): array
    {
        $files = $this->getLogFiles();
        $totalSize = 0;
        $oldestFile = null;
        $newestFile = null;
        $filesByAge = [
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'older' => 0
        ];

        foreach ($files as $file) {
            $totalSize += $file['size'];
            
            if ($oldestFile === null || $file['modified'] < $oldestFile['modified']) {
                $oldestFile = $file;
            }
            
            if ($newestFile === null || $file['modified'] > $newestFile['modified']) {
                $newestFile = $file;
            }

            $ageDays = $file['age_days'];
            if ($ageDays === 0) {
                $filesByAge['today']++;
            } elseif ($ageDays <= 7) {
                $filesByAge['week']++;
            } elseif ($ageDays <= 30) {
                $filesByAge['month']++;
            } else {
                $filesByAge['older']++;
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'readable_total_size' => $this->formatBytes($totalSize),
            'oldest_file' => $oldestFile,
            'newest_file' => $newestFile,
            'files_by_age' => $filesByAge,
            'cleanup_candidates' => count($this->getOldLogFiles(15))
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get file age in days
     */
    private function getFileAgeDays(int $timestamp): int
    {
        $fileDate = new \DateTime('@' . $timestamp);
        $now = new \DateTime();
        $diff = $now->diff($fileDate);
        
        return $diff->days;
    }
}

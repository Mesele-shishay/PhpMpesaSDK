<?php

namespace MesaSDK\PhpMpesa\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use DateTime;
use MesaSDK\PhpMpesa\Config;
use Throwable;

/**
 * Class MpesaLogger
 * 
 * A dedicated logging class for the Mpesa SDK that provides detailed logging
 * capabilities for all Mpesa operations, requests, and responses.
 * 
 * @package MesaSDK\PhpMpesa\Logging
 */
class MpesaLogger implements LoggerInterface
{
    /** @var string Directory where log files will be stored */
    private string $logDir;

    /** @var string Current log file path */
    private string $logFile;

    /** @var bool Whether to log to file */
    private bool $logToFile;

    /** @var bool Whether to log to console */
    private bool $logToConsole;

    /** @var string Minimum log level to record */
    private string $minLogLevel;

    /** @var Config The configuration instance */
    private Config $config;

    /** @var string|null Custom log format */
    private ?string $logFormat;

    /** @var int|null Maximum log file size in bytes */
    private ?int $maxFileSize;

    /** @var int|null Maximum number of log files to keep */
    private ?int $maxFiles;

    /** @var array Log levels and their severity */
    private const LOG_LEVELS = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7
    ];

    /**
     * MpesaLogger constructor.
     * 
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config)
    {
        // Store config instance
        $this->config = $config;

        // Get logging configuration with defaults
        $loggingConfig = $config->getLoggingConfig();

        // Initialize logger settings
        $this->logDir = $loggingConfig['log_dir'];
        $this->logToFile = $loggingConfig['log_to_file'];
        $this->logToConsole = $loggingConfig['log_to_console'];
        $this->minLogLevel = $loggingConfig['min_log_level'];
        $this->logFormat = $loggingConfig['log_format'];
        $this->maxFileSize = $loggingConfig['max_file_size'];
        $this->maxFiles = $loggingConfig['max_files'];

        // Initialize log directory if logging to file
        if ($this->logToFile) {
            $this->initializeLogDirectory();
            $this->setLogFile();
        }
    }

    /**
     * Set the log directory
     * 
     * @param string $dir Directory path
     * @return self
     */
    public function setLogDir(string $dir): self
    {
        $this->logDir = rtrim($dir, '/\\');
        if ($this->logToFile) {
            $this->initializeLogDirectory();
            $this->setLogFile();
        }
        return $this;
    }

    /**
     * Set whether to log to file
     * 
     * @param bool $enabled Whether to enable file logging
     * @return self
     */
    public function setLogToFile(bool $enabled): self
    {
        $this->logToFile = $enabled;
        if ($enabled) {
            $this->initializeLogDirectory();
            $this->setLogFile();
        }
        return $this;
    }

    /**
     * Set whether to log to console
     * 
     * @param bool $enabled Whether to enable console logging
     * @return self
     */
    public function setLogToConsole(bool $enabled): self
    {
        $this->logToConsole = $enabled;
        return $this;
    }

    /**
     * Set the minimum log level
     * 
     * @param string $level Minimum log level
     * @return self
     */
    public function setMinLogLevel(string $level): self
    {
        if (!isset(self::LOG_LEVELS[$level])) {
            throw new \InvalidArgumentException('Invalid log level');
        }
        $this->minLogLevel = $level;
        return $this;
    }

    /**
     * Get the current minimum log level
     * 
     * @return string
     */
    public function getMinLogLevel(): string
    {
        return $this->minLogLevel;
    }

    /**
     * Initialize the log directory
     */
    private function initializeLogDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    /**
     * Set the current log file path
     */
    private function setLogFile(): void
    {
        $this->logFile = $this->logDir . '/mpesa-' . date('Y-m-d') . '.log';
    }

    /**
     * Log an exception with full stack trace and context
     * 
     * @param Throwable $exception The exception to log
     * @param array $context Additional context
     */
    public function logException(Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        if ($exception instanceof \ErrorException) {
            $context['error_severity'] = $exception->getSeverity();
        }

        if ($exception->getPrevious()) {
            $context['previous_exception'] = [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
                'code' => $exception->getPrevious()->getCode()
            ];
        }

        $this->error(
            sprintf(
                'Exception: %s: %s in %s:%d',
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ),
            $context
        );
    }

    /**
     * Format a log message
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return string Formatted log message
     */
    private function formatMessage(string $level, string $message, array $context = []): string
    {
        if ($this->logFormat) {
            return $this->formatCustomMessage($level, $message, $context);
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s.v');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        return "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    }

    /**
     * Format a message using custom format
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return string Formatted log message
     */
    private function formatCustomMessage(string $level, string $message, array $context = []): string
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s.v');
        $replacements = [
            '%datetime%' => $timestamp,
            '%level%' => $level,
            '%message%' => $message,
            '%context%' => !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
        ];

        return strtr($this->logFormat, $replacements) . PHP_EOL;
    }

    /**
     * Write a message to the log file
     * 
     * @param string $message Formatted message to write
     */
    private function writeToFile(string $message): void
    {
        if (!$this->logToFile) {
            return;
        }

        // Check file size and rotate if necessary
        if ($this->maxFileSize && file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            $this->rotateLogFiles();
        }

        file_put_contents($this->logFile, $message, FILE_APPEND);
    }

    /**
     * Rotate log files
     */
    private function rotateLogFiles(): void
    {
        if (!$this->maxFiles) {
            return;
        }

        $baseFile = $this->logFile;
        $files = [];

        // Collect existing log files
        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $fileName = $baseFile . ($i > 0 ? '.' . $i : '');
            if (file_exists($fileName)) {
                $files[] = $fileName;
            }
        }

        // Rotate files
        foreach ($files as $i => $file) {
            $newFile = $baseFile . '.' . ($i + 1);
            rename($file, $newFile);
        }
    }

    /**
     * Check if a log level should be recorded
     * 
     * @param string $level Log level to check
     * @return bool Whether the level should be logged
     */
    private function shouldLog(string $level): bool
    {
        return self::LOG_LEVELS[$level] <= self::LOG_LEVELS[$this->minLogLevel];
    }

    /**
     * Write a message to the log
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $formattedMessage = $this->formatMessage($level, $message, $context);

        if ($this->logToFile) {
            $this->writeToFile($formattedMessage);
        }

        if ($this->logToConsole) {
            echo $formattedMessage;
        }
    }

    /**
     * Log API request details
     * 
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @param array $headers Request headers
     */
    public function logRequest(string $endpoint, array $payload, array $headers = []): void
    {
        $this->info('API Request', [
            'endpoint' => $endpoint,
            'payload' => $this->sanitizePayload($payload),
            'headers' => $this->sanitizeHeaders($headers)
        ]);
    }

    /**
     * Log API response details
     * 
     * @param string $endpoint API endpoint
     * @param mixed $response Response data
     * @param int $statusCode HTTP status code
     */
    public function logResponse(string $endpoint, $response, int $statusCode): void
    {
        $this->info('API Response', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response' => $response
        ]);
    }

    /**
     * Log authentication events
     * 
     * @param string $event Authentication event description
     * @param array $context Additional context
     */
    public function logAuth(string $event, array $context = []): void
    {
        $this->info('Authentication: ' . $event, $context);
    }

    /**
     * Sanitize sensitive data from payload
     * 
     * @param array $payload Request payload
     * @return array Sanitized payload
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveFields = [
            'Password',
            'SecurityCredential',
            'ConsumerSecret',
            'ConsumerKey'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '******';
            }
        }

        return $payload;
    }

    /**
     * Sanitize sensitive data from headers
     * 
     * @param array $headers Request headers
     * @return array Sanitized headers
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'Authorization'
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '******';
            }
        }

        return $headers;
    }

    // PSR-3 LoggerInterface implementation methods
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
<?php
namespace MesaSDK\PhpMpesa;

use Dotenv\Dotenv;

/**
 * Class Config
 * 
 * Configuration class for M-Pesa API integration that manages essential credentials
 * and settings required for making API requests to the Safaricom M-Pesa platform.
 * 
 * @package MesaSDK\PhpMpesa
 */
class Config
{
    /** @var string Base URL for the M-Pesa API endpoints */
    private string $base_url;

    /** @var string Consumer Key obtained from the M-Pesa Developer Portal */
    private string $consumer_key;

    /** @var string Consumer Secret obtained from the M-Pesa Developer Portal */
    private string $consumer_secret;

    /** @var string Passkey for generating transaction passwords */
    private string $passkey;

    /** @var string Business Shortcode/Till Number/Paybill Number */
    private string $shortcode;

    /** @var string Environment type ('sandbox' or 'production') */
    private string $environment;

    /** @var bool Whether to verify SSL certificates */
    private bool $verify_ssl;

    /** @var string Directory for storing log files */
    private string $log_dir;

    /** @var bool Whether to log to file */
    private bool $log_to_file;

    /** @var bool Whether to log to console */
    private bool $log_to_console;

    /** @var string Minimum log level to record */
    private string $min_log_level;

    /** @var string Custom log format */
    private ?string $log_format;

    /** @var int Maximum log file size in bytes */
    private ?int $max_file_size;

    /** @var int Maximum number of log files to keep */
    private ?int $max_files;

    /** @var int Request timeout in seconds */
    private int $request_timeout = 30;

    /** @var int Maximum number of retry attempts */
    private int $max_retries = 3;

    /** @var int Delay between retries in milliseconds */
    private int $retry_delay = 1000;

    /** @var bool Whether to use configuration caching */
    private bool $use_cache = false;

    /** @var string Cache directory path */
    private string $cache_dir;

    /** @var int Cache TTL in seconds */
    private int $cache_ttl = 3600;

    /**
     * Config constructor.
     * 
     * Initializes a new configuration instance with M-Pesa API credentials.
     * If no parameters are provided and a .env file exists, it will attempt to load from environment variables.
     * 
     * Expected environment variables:
     * - MPESA_ENVIRONMENT=sandbox|production
     * - MPESA_CONSUMER_KEY=your_consumer_key
     * - MPESA_CONSUMER_SECRET=your_consumer_secret
     * - MPESA_PASSKEY=your_passkey
     * - MPESA_SHORTCODE=your_shortcode
     * - MPESA_LOG_DIR=logs
     * - MPESA_LOG_TO_FILE=true
     * - MPESA_LOG_TO_CONSOLE=false
     * - MPESA_MIN_LOG_LEVEL=debug
     * 
     * @param string|null $base_url Base URL for the M-Pesa API endpoints
     * @param string|null $consumer_key Consumer Key from the M-Pesa Developer Portal
     * @param string|null $consumer_secret Consumer Secret from the M-Pesa Developer Portal
     * @param string|null $passkey Passkey for generating transaction passwords
     * @param string|null $shortcode Business Shortcode/Till Number/Paybill Number
     * @param string|null $environment Environment type ('sandbox' or 'production')
     * @param array $options Additional configuration options
     */
    public function __construct(
        string $base_url = null,
        string $consumer_key = null,
        string $consumer_secret = null,
        string $passkey = null,
        string $shortcode = null,
        string $environment = null,
        array $options = []
    ) {
        // Try to load from environment if no parameters provided
        if ($this->shouldLoadFromEnv($base_url, $consumer_key, $consumer_secret, $passkey, $shortcode, $environment)) {
            $this->loadFromEnv();
        } else {
            $this->base_url = $base_url ?? 'https://apisandbox.safaricom.et';
            $this->consumer_key = $consumer_key ?? '';
            $this->consumer_secret = $consumer_secret ?? '';
            $this->passkey = $passkey ?? '';
            $this->shortcode = $shortcode ?? '';
            $this->environment = $environment ?? 'sandbox';
        }

        $this->verify_ssl = true;

        // Initialize default logging configuration
        $this->setLoggingConfig([
            'log_dir' => 'logs',
            'log_to_file' => true,
            'log_to_console' => false,
            'min_log_level' => 'debug',
            'log_format' => null,
            'max_file_size' => null,
            'max_files' => null
        ]);

        // Set additional options if provided
        if (!empty($options)) {
            $this->setOptions($options);
        }

        // Initialize cache directory
        $this->cache_dir = sys_get_temp_dir() . '/mpesa-sdk-cache';
    }

    /**
     * Validate the configuration
     * 
     * @throws \InvalidArgumentException When required parameters are missing or invalid
     */
    public function validate(): void
    {
        if (empty($this->consumer_key)) {
            throw new \InvalidArgumentException('Consumer Key is required');
        }

        if (empty($this->consumer_secret)) {
            throw new \InvalidArgumentException('Consumer Secret is required');
        }

        if (!in_array($this->environment, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException('Environment must be either "sandbox" or "production"');
        }

        // Validate timeout and retry settings
        if ($this->request_timeout < 1) {
            throw new \InvalidArgumentException('Request timeout must be at least 1 second');
        }

        if ($this->max_retries < 0) {
            throw new \InvalidArgumentException('Maximum retries cannot be negative');
        }

        if ($this->retry_delay < 0) {
            throw new \InvalidArgumentException('Retry delay cannot be negative');
        }

        // Validate cache configuration if enabled
        if ($this->use_cache) {
            if (!is_dir($this->cache_dir) || !is_writable($this->cache_dir)) {
                throw new \RuntimeException('Cache directory is not writable: ' . $this->cache_dir);
            }

            if ($this->cache_ttl < 0) {
                throw new \InvalidArgumentException('Cache TTL cannot be negative');
            }
        }
    }

    /**
     * Set logging configuration
     * 
     * @param array $config Logging configuration array with the following keys:
     *                     - log_dir: Directory for storing log files (default: 'logs')
     *                     - log_to_file: Whether to log to file (default: true)
     *                     - log_to_console: Whether to log to console (default: false)
     *                     - min_log_level: Minimum log level to record (default: 'debug')
     *                     - log_format: Custom log format (default: null)
     *                     - max_file_size: Maximum log file size in bytes (default: null)
     *                     - max_files: Maximum number of log files to keep (default: null)
     * @return self
     */
    public function setLoggingConfig(array $config): self
    {
        // Define default values
        $defaults = [
            'log_dir' => 'logs',
            'log_to_file' => true,
            'log_to_console' => false,
            'min_log_level' => 'debug',
            'log_format' => null,
            'max_file_size' => null,
            'max_files' => null
        ];

        // Merge provided config with defaults
        $mergedConfig = array_merge($defaults, $config);

        // Set the properties
        $this->log_dir = $mergedConfig['log_dir'];
        $this->log_to_file = $mergedConfig['log_to_file'];
        $this->log_to_console = $mergedConfig['log_to_console'];
        $this->min_log_level = $mergedConfig['min_log_level'];
        $this->log_format = $mergedConfig['log_format'];
        $this->max_file_size = $mergedConfig['max_file_size'];
        $this->max_files = $mergedConfig['max_files'];

        return $this;
    }

    /**
     * Get logging configuration
     * 
     * @return array Current logging configuration
     */
    public function getLoggingConfig(): array
    {
        return [
            'log_dir' => $this->log_dir,
            'log_to_file' => $this->log_to_file,
            'log_to_console' => $this->log_to_console,
            'min_log_level' => $this->min_log_level,
            // Add any additional logging configuration that might have been set
            'log_format' => $this->log_format ?? null,
            'max_file_size' => $this->max_file_size ?? null,
            'max_files' => $this->max_files ?? null
        ];
    }

    public function getVerifySSL(): bool
    {
        return $this->verify_ssl;
    }

    public function setVerifySSL(bool $verify_ssl): self
    {
        $this->verify_ssl = $verify_ssl;
        return $this;
    }

    /**
     * Check if configuration should be loaded from environment variables
     */
    private function shouldLoadFromEnv(...$params): bool
    {
        return empty(array_filter($params, fn($param) => !is_null($param)));
    }

    /**
     * Load configuration from environment variables
     */
    private function loadFromEnv(): void
    {
        if (file_exists(getcwd() . '/.env')) {
            $dotenv = Dotenv::createImmutable(getcwd());
            $dotenv->load();
        }

        $this->base_url = $_ENV['MPESA_BASE_URL'] ?? 'https://apisandbox.safaricom.et';
        $this->consumer_key = $_ENV['MPESA_CONSUMER_KEY'] ?? '';
        $this->consumer_secret = $_ENV['MPESA_CONSUMER_SECRET'] ?? '';
        $this->passkey = $_ENV['MPESA_PASSKEY'] ?? '';
        $this->shortcode = $_ENV['MPESA_SHORTCODE'] ?? '';
        $this->environment = $_ENV['MPESA_ENVIRONMENT'] ?? 'sandbox';

        // Load logging configuration from environment
        $this->log_dir = $_ENV['MPESA_LOG_DIR'] ?? 'logs';
        $this->log_to_file = filter_var($_ENV['MPESA_LOG_TO_FILE'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $this->log_to_console = filter_var($_ENV['MPESA_LOG_TO_CONSOLE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $this->min_log_level = $_ENV['MPESA_MIN_LOG_LEVEL'] ?? 'debug';
    }

    /**
     * Get the base URL for API endpoints
     * 
     * @return string The configured base URL
     */
    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    /**
     * Set the base URL for API endpoints
     * 
     * @param string $base_url The new base URL to set
     * @return self Returns the current instance for method chaining
     */
    public function setBaseUrl(string $base_url): self
    {
        $this->base_url = $base_url;
        return $this;
    }

    /**
     * Get the consumer key
     * 
     * @return string The configured consumer key
     */
    public function getConsumerKey(): string
    {
        return $this->consumer_key;
    }

    /**
     * Set the consumer key
     * 
     * @param string $consumer_key The new consumer key to set
     * @return self Returns the current instance for method chaining
     */
    public function setConsumerKey(string $consumer_key): self
    {
        $this->consumer_key = $consumer_key;
        return $this;
    }

    /**
     * Get the consumer secret
     * 
     * @return string The configured consumer secret
     */
    public function getConsumerSecret(): string
    {
        return $this->consumer_secret;
    }

    /**
     * Set the consumer secret
     * 
     * @param string $consumer_secret The new consumer secret to set
     * @return self Returns the current instance for method chaining
     */
    public function setConsumerSecret(string $consumer_secret): self
    {
        $this->consumer_secret = $consumer_secret;
        return $this;
    }

    /**
     * Get the passkey used for transaction password generation
     * 
     * @return string The configured passkey
     */
    public function getPasskey(): string
    {
        return $this->passkey;
    }

    /**
     * Set the passkey for transaction password generation
     * 
     * @param string $passkey The new passkey to set
     * @return self Returns the current instance for method chaining
     */
    public function setPasskey(string $passkey): self
    {
        $this->passkey = $passkey;
        return $this;
    }

    /**
     * Get the business shortcode/till number/paybill number
     * 
     * @return string The configured shortcode
     */
    public function getShortcode(): string
    {
        return $this->shortcode;
    }

    /**
     * Set the business shortcode/till number/paybill number
     * 
     * @param string $shortcode The new shortcode to set
     * @return self Returns the current instance for method chaining
     */
    public function setShortcode(string $shortcode): self
    {
        $this->shortcode = $shortcode;
        return $this;
    }

    /**
     * Get the current environment type
     * 
     * @return string The current environment ('sandbox' or 'production')
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Set the environment type and automatically update the base URL
     * 
     * @param string $environment The environment type ('sandbox' or 'production')
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If an invalid environment type is provided
     */
    public function setEnvironment(string $environment): self
    {
        $environment = strtolower($environment);
        if (!in_array($environment, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException("Environment must be either 'sandbox' or 'production'");
        }

        $this->environment = $environment;

        // Update base URL based on environment
        if ($environment === 'production') {
            $this->base_url = "https://apis.safaricom.et";
        } else {
            $this->base_url = "https://apisandbox.safaricom.et";
        }

        return $this;
    }

    /**
     * Get the value of a configuration key from environment variables or internal state.
     * 
     * @param string $key The configuration key to retrieve (can be with or without MPESA_ prefix)
     * @param mixed $default The default value if the key is not found
     * @return mixed The configuration value or default if not found
     */
    public function get(string $key, $default = null)
    {
        // Add MPESA_ prefix if not present
        $envKey = !str_starts_with($key, 'MPESA_') ? 'MPESA_' . $key : $key;

        // First try environment variables
        $value = $_ENV[$envKey] ?? $_SERVER[$envKey] ?? null;

        // If not found in environment, try internal state
        if ($value === null) {
            // Map environment keys to property names
            $propertyMap = [
                'MPESA_ENVIRONMENT' => 'environment',
                'MPESA_BASE_URL' => 'base_url',
                'MPESA_CONSUMER_KEY' => 'consumer_key',
                'MPESA_CONSUMER_SECRET' => 'consumer_secret',
                'MPESA_PASSKEY' => 'passkey',
                'MPESA_SHORTCODE' => 'shortcode',
                'MPESA_LOG_DIR' => 'log_dir',
                'MPESA_LOG_TO_FILE' => 'log_to_file',
                'MPESA_LOG_TO_CONSOLE' => 'log_to_console',
                'MPESA_MIN_LOG_LEVEL' => 'min_log_level'
            ];

            // Convert environment key to property name
            $property = $propertyMap[$envKey] ?? strtolower(str_replace('MPESA_', '', $envKey));

            // Get value from property if it exists
            if (property_exists($this, $property)) {
                $value = $this->$property;
            }
        }

        return $value ?? $default;
    }

    /**
     * Set multiple configuration options at once
     *
     * @param array $options Configuration options
     * @return self
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Set request timeout
     *
     * @param int $timeout Timeout in seconds
     * @return self
     */
    public function setRequestTimeout(int $timeout): self
    {
        $this->request_timeout = $timeout;
        return $this;
    }

    /**
     * Get request timeout
     *
     * @return int
     */
    public function getRequestTimeout(): int
    {
        return $this->request_timeout;
    }

    /**
     * Set retry configuration
     *
     * @param int $max_retries Maximum number of retry attempts
     * @param int $retry_delay Delay between retries in milliseconds
     * @return self
     */
    public function setRetryConfig(int $max_retries, int $retry_delay): self
    {
        $this->max_retries = $max_retries;
        $this->retry_delay = $retry_delay;
        return $this;
    }

    /**
     * Get retry configuration
     *
     * @return array
     */
    public function getRetryConfig(): array
    {
        return [
            'max_retries' => $this->max_retries,
            'retry_delay' => $this->retry_delay
        ];
    }

    /**
     * Enable or disable configuration caching
     *
     * @param bool $use_cache Whether to use caching
     * @param string|null $cache_dir Custom cache directory
     * @param int|null $cache_ttl Cache TTL in seconds
     * @return self
     */
    public function setCaching(bool $use_cache, ?string $cache_dir = null, ?int $cache_ttl = null): self
    {
        $this->use_cache = $use_cache;

        if ($cache_dir !== null) {
            $this->cache_dir = $cache_dir;
        }

        if ($cache_ttl !== null) {
            $this->cache_ttl = $cache_ttl;
        }

        // Create cache directory if it doesn't exist
        if ($this->use_cache && !is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }

        return $this;
    }

    /**
     * Get caching configuration
     *
     * @return array
     */
    public function getCachingConfig(): array
    {
        return [
            'enabled' => $this->use_cache,
            'directory' => $this->cache_dir,
            'ttl' => $this->cache_ttl
        ];
    }
}
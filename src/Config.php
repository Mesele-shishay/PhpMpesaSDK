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
class Config {
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
     * 
     * @param string|null $base_url The base URL for API endpoints (defaults to sandbox environment)
     * @param string|null $consumer_key The consumer key from M-Pesa developer portal
     * @param string|null $consumer_secret The consumer secret from M-Pesa developer portal
     * @param string|null $passkey The passkey for transaction password generation
     * @param string|null $shortcode The business shortcode/till number/paybill number
     * @param string|null $environment The environment type ('sandbox' or 'production')
     */
    public function __construct(
        ?string $base_url = null,
        ?string $consumer_key = null,
        ?string $consumer_secret = null,
        ?string $passkey = null,
        ?string $shortcode = null,
        ?string $environment = null
    ) {
        // Try to load from .env if no parameters are provided and .env file exists
        if ($this->shouldLoadFromEnv($consumer_key, $consumer_secret, $passkey, $shortcode)) {
            $this->loadFromEnv();
        } else {
            $this->base_url = $base_url ?? "https://apisandbox.safaricom.et";
            $this->consumer_key = $consumer_key ?? "";
            $this->consumer_secret = $consumer_secret ?? "";
            $this->passkey = $passkey ?? "";
            $this->shortcode = $shortcode ?? "";
            $this->setEnvironment($environment ?? "sandbox");
        }
    }

    /**
     * Check if configuration should be loaded from environment variables
     * 
     * @param string|null ...$params Configuration parameters to check
     * @return bool True if should load from env, false otherwise
     */
    private function shouldLoadFromEnv(?string ...$params): bool {
        // Check if all parameters are null and .env file exists
        return empty(array_filter($params, fn($param) => $param !== null)) && 
        file_exists(getcwd() . '/.env');
    }

    /**
     * Load configuration from environment variables
     * 
     * @return void
     */
    private function loadFromEnv(): void {
        try {
            $dotenv = Dotenv::createImmutable(getcwd());
            $dotenv->safeLoad();

            // Set environment first as it affects base_url
            $this->setEnvironment(
                $_ENV['MPESA_ENVIRONMENT'] ?? 'sandbox'
            );

            // The base_url is automatically set by setEnvironment()
            $this->consumer_key = $_ENV['MPESA_CONSUMER_KEY'] ?? '';
            $this->consumer_secret = $_ENV['MPESA_CONSUMER_SECRET'] ?? '';
            $this->passkey = $_ENV['MPESA_PASSKEY'] ?? '';
            $this->shortcode = $_ENV['MPESA_SHORTCODE'] ?? '';
        } catch (\Exception $e) {
            // Fallback to default values if loading fails
            $this->setEnvironment('sandbox');
            $this->consumer_key = '';
            $this->consumer_secret = '';
            $this->passkey = '';
            $this->shortcode = '';
        }
    }

    /**
     * Get the base URL for API endpoints
     * 
     * @return string The configured base URL
     */
    public function getBaseUrl(): string {
        return $this->base_url;
    }

    /**
     * Set the base URL for API endpoints
     * 
     * @param string $base_url The new base URL to set
     * @return self Returns the current instance for method chaining
     */
    public function setBaseUrl(string $base_url): self {
        $this->base_url = $base_url;
        return $this;
    }

    /**
     * Get the consumer key
     * 
     * @return string The configured consumer key
     */
    public function getConsumerKey(): string {
        return $this->consumer_key;
    }

    /**
     * Set the consumer key
     * 
     * @param string $consumer_key The new consumer key to set
     * @return self Returns the current instance for method chaining
     */
    public function setConsumerKey(string $consumer_key): self {
        $this->consumer_key = $consumer_key;
        return $this;
    }

    /**
     * Get the consumer secret
     * 
     * @return string The configured consumer secret
     */
    public function getConsumerSecret(): string {
        return $this->consumer_secret;
    }

    /**
     * Set the consumer secret
     * 
     * @param string $consumer_secret The new consumer secret to set
     * @return self Returns the current instance for method chaining
     */
    public function setConsumerSecret(string $consumer_secret): self {
        $this->consumer_secret = $consumer_secret;
        return $this;
    }

    /**
     * Get the passkey used for transaction password generation
     * 
     * @return string The configured passkey
     */
    public function getPasskey(): string {
        return $this->passkey;
    }

    /**
     * Set the passkey for transaction password generation
     * 
     * @param string $passkey The new passkey to set
     * @return self Returns the current instance for method chaining
     */
    public function setPasskey(string $passkey): self {
        $this->passkey = $passkey;
        return $this;
    }

    /**
     * Get the business shortcode/till number/paybill number
     * 
     * @return string The configured shortcode
     */
    public function getShortcode(): string {
        return $this->shortcode;
    }

    /**
     * Set the business shortcode/till number/paybill number
     * 
     * @param string $shortcode The new shortcode to set
     * @return self Returns the current instance for method chaining
     */
    public function setShortcode(string $shortcode): self {
        $this->shortcode = $shortcode;
        return $this;
    }

    /**
     * Get the current environment type
     * 
     * @return string The current environment ('sandbox' or 'production')
     */
    public function getEnvironment(): string {
        return $this->environment;
    }

    /**
     * Set the environment type and automatically update the base URL
     * 
     * @param string $environment The environment type ('sandbox' or 'production')
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If an invalid environment type is provided
     */
    public function setEnvironment(string $environment): self {
        $environment = strtolower($environment);
        if (!in_array($environment, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException("Environment must be either 'sandbox' or 'production'");
        }
        
        $this->environment = $environment;
        
        // Update base URL based on environment
        if ($environment === 'production') {
            $this->base_url = "https://apisandbox.safaricom.et";
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
    public function get(string $key, $default = null) {
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
                'MPESA_SHORTCODE' => 'shortcode'
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
}
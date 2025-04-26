<?php
class Encryption {
    private static $key = null;
    private static $cipher = "aes-256-cbc";
    
    public static function initialize() {
        if (self::$key === null) {
            // Use absolute path to config file
            $configPath = __DIR__ . '/config.php';
            if (!file_exists($configPath)) {
                throw new Exception("Configuration file not found");
            }
            $config = include($configPath);
            self::$key = base64_decode($config['encryption_key']);
            
            // If no key exists, generate one and save it
            if (!self::$key) {
                self::$key = openssl_random_pseudo_bytes(32);
                // Save the key to config file
                file_put_contents($configPath, '<?php return ["encryption_key" => "' . base64_encode(self::$key) . '"];');
            }
        }
    }
    
    public static function encrypt($data) {
        self::initialize();
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt($data) {
        self::initialize();
        $data = base64_decode($data);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        return openssl_decrypt($encrypted, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
    }
}
?>
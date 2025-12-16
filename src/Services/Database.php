<?php
namespace App\Services;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $db = $config['database'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $instance = new self();
            self::$instance = $instance->pdo;
        }
        return self::$instance;
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

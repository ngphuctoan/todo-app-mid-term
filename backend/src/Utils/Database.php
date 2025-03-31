<?php
namespace App\Utils;

use PDO;
use PDOException;

use Psr\Http\Message\ResponseInterface as Response;

class Database {
    private static $pdo = null;

    public static function connect(): PDO {
        if (!self::$pdo) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=mysql;dbname=" . $_ENV["DB_NAME"],
                    $_ENV["DB_USER"], $_ENV["DB_PASS"],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $error) {
                throw new \RuntimeException("Connection to MySQL database failed.", 500);
            }
        }

        return self::$pdo;
    }
}
?>
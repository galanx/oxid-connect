<?php

namespace Makaira\Connect;

/**
 * Class PdoDatabase
 * @package Makaira\Connect
 */
class PdoDatabase implements DatabaseInterface
{
    /** @var \PDO */
    private $connection;

    /**
     * PdoDatabase constructor.
     */
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $host
     * @param $db
     * @param $isUTF8
     * @return array
     */
    public static function buildMySqlDsn($host, $db, $isUTF8)
    {
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
        } else {
            $port = null;
        }
        $dsn = [
            'host=' . $host,
            'dbname=' . $db,
        ];
        if (isset($port)) {
            $dsn[] = 'port=' . $port;
        }
        if ($isUTF8) {
            $dsn[] = 'charset=utf8';
        }
        $dsn = 'mysql:' . implode(';', $dsn);
        return $dsn;
    }

    /**
     * Query database.
     * @param string $query
     * @param array $parameters
     * @return array
     */
    public function query($query, array $parameters = array())
    {
        $stmt = $this->connection->prepare($query);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue(":$key", $value, $this->getPDOType($value));
        }
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new \Exception($error[2], $error[1]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPDOType($value)
    {
        if (is_int($value)) {
            return \PDO::PARAM_INT;
        } elseif (!isset($value)) {
            return \PDO::PARAM_NULL;
        } else {
            return \PDO::PARAM_STR;
        }
    }
}

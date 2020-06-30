<?php

namespace Amp\Sql;

abstract class ConnectionConfig
{
    const KEY_MAP = ['hostname' => 'host', 'username' => 'user', 'pass' => 'password', 'database' => 'db', 'dbname' => 'db'];
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var string|null */
    private $user;
    /** @var string|null */
    private $password;
    /** @var string|null */
    private $database;
    /**
     * Parses a connection string into an array of keys and values given.
     *
     * @param string $connectionString
     * @param string[] $keymap Map of alternative key names to canonical key names.
     *
     * @return string[]
     */
    protected static function parseConnectionString(string $connectionString, array $keymap = self::KEY_MAP) : array
    {
        $values = [];
        $params = \explode(";", $connectionString);
        if (\count($params) === 1) {
            // Attempt to explode on a space if no ';' are found.
            $params = \explode(" ", $connectionString);
        }
        foreach ($params as $param) {
            list($key, $value) = \array_map("trim", \explode("=", $param, 2) + [1 => null]);
            if (isset($keymap[$key])) {
                $key = $keymap[$key];
            }
            $values[$key] = $value;
        }
        if (\preg_match('/^(.+):(\\d{1,5})$/', $values["host"] ?? "", $matches)) {
            $values["host"] = $matches[1];
            $values["port"] = $matches[2];
        }
        return $values;
    }
    public function __construct(string $host, int $port, string $user = null, string $password = null, string $database = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }
    public final function getHost() : string
    {
        return $this->host;
    }
    public final function withHost(string $host) : self
    {
        $new = clone $this;
        $new->host = $host;
        return $new;
    }
    public final function getPort() : int
    {
        return $this->port;
    }
    public final function withPort(int $port) : self
    {
        $new = clone $this;
        $new->port = $port;
        return $new;
    }
    /**
     * @return string|null
     */
    public final function getUser()
    {
        return $this->user;
    }
    public final function withUser(string $user = null) : self
    {
        $new = clone $this;
        $new->user = $user;
        return $new;
    }
    /**
     * @return string|null
     */
    public final function getPassword()
    {
        return $this->password;
    }
    public final function withPassword(string $password = null) : self
    {
        $new = clone $this;
        $new->password = $password;
        return $new;
    }
    /**
     * @return string|null
     */
    public final function getDatabase()
    {
        return $this->database;
    }
    public final function withDatabase(string $database = null) : self
    {
        $new = clone $this;
        $new->database = $database;
        return $new;
    }
}
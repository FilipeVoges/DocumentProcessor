<?php

namespace App\Modules\Connection;

/**
 * Class Database
 * @package App\Modules\Connection
 */
class Database
{
    /**
     * @var string
     */
    private $server = DB_HOST;

    /**
     * @var string
     */
    protected $database = DB_DATABASE;

    /**
     * @var string
     */
    private $user = DB_USER;

    /**
     * @var string
     */
    private $password = DB_PASSWORD;

    /**
     * @var string
     */
    private $driver = DB_DRIVER;

    /**
     * @var string
     */
    private $charset = DB_CHARSET;

    /**
     * @var Database
     */
    private Database $db;

    /**
     * @var Database
     */
    private static Database $instance;

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->server = DB_HOST;
        $this->database = DB_DATABASE;
        $this->user = DB_USER;
        $this->password = DB_PASSWORD;
        $this->driver = DB_DRIVER;
        $this->charset = DB_CHARSET;
        $this->port = DB_PORT;

        $this->connect();
    }

    /**
     * @return Database
     */
    public static function getInstance()
    {
        self::$instance = new Database();

        return self::$instance;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     *
     */
    private function __clone()
    {
    }

    /**
     *
     */
    private function __wakeup()
    {
    }

    /**
     * @param $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @param $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @param $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    private function getServer()
    {
        return $this->server;
    }

    /**
     * @return mixed
     */
    protected function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return mixed
     */
    private function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getLastInsertId()
    {
        if (is_null($this->db)) {
            $this->connect();
        }

        return $this->db->lastInsertId();
    }

    /**
     *
     * @throws \Exception
     */
    private function connect()
    {
        try {
            $this->db = new \PDO("$this->driver:host=$this->server;port=$this->port;dbname=$this->database;charset=$this->charset;", $this->user, $this->password);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param null $sql
     * @return mixed
     * @throws \Exception
     */
    public function getQuery($sql = null)
    {
        if (is_null($sql)) {
            throw new \Exception('SQL não informado!');
        }

        if (is_null($this->db)) {
            $this->connect();
        }

        $query = $this->db->query($sql);
        if (!$query) {
            throw new \Exception("Erro de SQL." . "-> {$sql} -> " . $this->getErrorMessage(), 500);
        }

        return $query;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        if (is_null($this->db)) {
            $this->connect();
        }

        $errorInfo = $this->db->errorInfo();
        return $errorInfo[2];

    }

    /**
     * @param $query
     * @return mixed
     */
    public function getFetchRow($query)
    {
        return $query->fetch(\PDO::FETCH_BOTH);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function getFetchArray($query)
    {
        return $query->fetch(\PDO::FETCH_BOTH);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function getFetchAssoc($query)
    {
        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function getFetchObject($query)
    {
        return $query->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * @param $stmt
     * @return int
     */
    public function getTotal($stmt)
    {
        $total = $this->getFetchAll($stmt);

        return count($total);
    }

    /**
     *
     * @param string $type (possible values: array, assoc, object)
     * @param $query
     * @return void
     */
    public function getFetchAll($stmt, $type = 'assoc')
    {
        $return = [];
        switch ($type) {
            case 'array':
                while ($row = $this->getFetchArray($stmt)) {
                    $return[] = $row;
                }
                break;
            case 'object':
                while ($row = $this->getFetchObject($stmt)) {
                    $return[] = $row;
                }
                break;
            case 'assoc':
            default:
                while ($row = $this->getFetchAssoc($stmt)) {
                    $return[] = $row;
                }
                break;
        }
        return $return;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function getAffectedRows($query)
    {
        return $query->rowCount();
    }

    /**
     * @param $query
     */
    public function clear($query)
    {
        $query->closeCursor();
    }

    /**
     *
     */
    public function disconnect()
    {
        if (isset($this->db)) {
            $this->db = null;
        }
    }

    /**
     * @param array $params
     * @return bool|string
     */
    public function getSQLInsert($params = [])
    {
        if (!is_array($params) || empty($params)) {
            return false;
        }

        $table = isset($params['table']) ? strtolower($params['table']) : null;
        $fields = isset($params['fields']) ? $params['fields'] : [];;

        if (is_null($table) || empty($fields)) {
            return false;
        }

        $tSQL = $table;

        $cSQL = [];
        $vSQL = [];
        foreach ($fields as $key => $val) {
            $cSQL[] = addslashes(strtolower($key));
            $vSQL[] = (is_null($val)) ? 'null' : "'" . addslashes($val) . "'";
        }

        $cSQL = implode(", ", $cSQL);
        $vSQL = implode(", ", $vSQL);

        $SQL = "INSERT INTO {$tSQL} ({$cSQL}) VALUES ({$vSQL}) ";

        return $SQL;
    }

    /**
     * @param array $params
     * @return bool|string
     */
    public function getSQLUpdate($params = [])
    {
        if (!is_array($params) || empty($params)) {
            return false;
        }

        $table = isset($params['table']) ? strtolower($params['table']) : null;
        $fields = isset($params['fields']) ? $params['fields'] : [];;
        $filters = isset($params['filters']) ? $params['filters'] : [];

        if (is_null($table) || empty($fields)) {
            return false;
        }

        $tSQL = $table;

        $cSQL = $fields;
        if (is_array($fields)) {
            $cSQL = [];
            foreach ($fields as $key => $val) {
                $cSQL[] = addslashes(strtolower($key)) . " =" . ((is_null($val)) ? 'null' : "'" . addslashes($val) . "'");
            }
            $cSQL = implode(", ", $cSQL);
        }

        $fSQL = "";
        if (is_array($filters) && !empty($filters)) {
            $fSQL = buildWhereQuery($filters);
        } elseif (!empty($filters)) {
            $fSQL = $filters;
        }

        $SQL = "UPDATE {$tSQL} SET {$cSQL} ";
        $SQL .= !empty($fSQL) ? " WHERE {$fSQL} " : "";

        return $SQL . ';';
    }

    /**
     * @param $params
     * @return bool|string
     */
    public function getSQLDelete($params)
    {
        if (!is_array($params) || empty($params)) {
            return false;
        }

        $table = isset($params['table']) ? strtolower($params['table']) : null;
        $filters = isset($params['filters']) ? $params['filters'] : [];

        if (is_null($table)) {
            return false;
        }

        $tSQL = $table;

        $fSQL = "";
        if (is_array($filters) && !empty($filters)) {
            $fSQL = buildWhereQuery($filters);
        } elseif (!empty($filters)) {
            $fSQL = $filters;
        }

        $SQL = "DELETE FROM {$tSQL} ";
        $SQL .= !empty($fSQL) ? " WHERE {$fSQL} " : "";

        return $SQL;
    }
}

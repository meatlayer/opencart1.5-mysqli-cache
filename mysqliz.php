<?php
//namespace DB;
use Cache;

final class mysqliz {
    private $mysqli_handler;
    private $cache;
    private $cachedquery;

    public function __construct($hostname, $username, $password, $database) {
        //$this->cache = new Cache(DB_CACHED_EXPIRE);
        $cacheExpire = defined('DB_CACHED_EXPIRE') && DB_CACHED_EXPIRE ? DB_CACHED_EXPIRE : 3600;
        $this->cache = new Cache($cacheExpire);
        $this->mysqli_handler = new \mysqli($hostname, $username, $password, $database);

        if ($this->mysqli_handler->connect_error) {
            trigger_error('Error: Could not make a database link (' . $this->mysqli_handler->connect_errno . ') ' . $this->mysqli_handler->connect_error);
        }

        $this->mysqli_handler->query("SET NAMES 'utf8'");
        $this->mysqli_handler->query("SET CHARACTER SET utf8");
        $this->mysqli_handler->query("SET CHARACTER_SET_CONNECTION=utf8");
        $this->mysqli_handler->query("SET SQL_MODE = ''");
    }

    public function query($sql) {
        $isSelect = stripos($sql, 'SELECT ') === 0;
        $md5query = md5($sql);

        if ($isSelect) {
            if ($query = $this->cache->get('sql_' . $md5query)) {
                if ($query->sql === $sql) {
                    $resetFlag = $this->cache->get('sql_globalresetcache');
                    if (!$resetFlag || $resetFlag <= $query->time) {
                        $this->cachedquery = $query;
                        return $query;
                    }
                }
            }
        }

        $result = $this->mysqli_handler->query($sql, MYSQLI_STORE_RESULT);
        if ($result !== false) {
            if (is_object($result)) {
                $data = [];
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $data[] = $row;
                }
                $result->close();

                $query = new \stdClass();
                $query->row = isset($data[0]) ? $data[0] : [];
                $query->rows = $data;
                $query->num_rows = count($data);

                if ($isSelect) {
                    $query->sql = $sql;
                    $query->time = time();
                    $this->cache->set('sql_' . $md5query, $query);
                }

                unset($this->cachedquery);
                return $query;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . $this->mysqli_handler->error . '<br />Error No: ' . $this->mysqli_handler->errno . '<br />' . $sql);
            exit();
        }
    }

    public function escape($value) {
        return $this->mysqli_handler->real_escape_string($value);
    }

    public function countAffected() {
        if (isset($this->cachedquery) && $this->cachedquery) {
            return $this->cachedquery->num_rows;
        } else {
            return $this->mysqli_handler->affected_rows;
        }
    }

    public function getLastId() {
        return $this->mysqli_handler->insert_id;
    }

    public function __destruct() {
        $this->mysqli_handler->close();
    }
}
?>

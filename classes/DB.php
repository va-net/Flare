<?php

class DB 
{

    private static $_instance = null;
    private $_pdo, 
            $_query, 
            $_error = false, 
            $_results, 
            $_count = 0;

    private function __construct() 
    {

        try {
            $this->_pdo = new PDO('mysql:host='.Config::get('mysql/host').';dbname='.Config::get('mysql/db'), Config::get('mysql/username'), Config::get('mysql/password'));
        } catch(PDOException $e) {
            die($e->getMessage());
        }

    }

    public static function getInstance() 
    {

        if (!isset(self::$_instance)) {
            self::$_instance = new DB();
        }
        return self::$_instance;

    }

    public static function newInstance() 
    {

        return new DB();

    }

    public function query($sql, $params = array())
    {

        $this->_error = false;
        if ($this->_query = $this->_pdo->prepare($sql)) {
            $x = 1;
            if (count($params)) {
                foreach ($params as $param) {
                    $this->_query->bindValue($x, $param);
                    $x++;
                }
            }

            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            } else {
                $this->_error = true;
            }

        }

        return $this;

    }

    public function getTable($table)
    {

        $sql = "SELECT * FROM {$table}";
        if (!$this->query($sql)->error()) {
            return $this;
        }
        return $this;

    }

    private function action($action, $table, $where = array(), $order = false) 
    {
    
        if (count($where) === 3) {
            $operators = array('=', '<', '>', '<=', '>=');

            $field = $where[0];
            $operator = $where[1];
            $value = $where[2];

            if (in_array($operator, $operators)) {
                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";

                if ($order) {
                    $orderby = $order[0];
                    $direction = $order[1];
                    $sql .= " ORDER BY {$orderby} {$direction}";
                }

                if (!$this->query($sql, array($value))->error()) {
                    return $this;
                }
            }
        }

        return $this;
        
    }

    public function error()
    {

        return $this->_error;
        
    }

    public function get($table, $where, $order = false)
    {

        return $this->action('SELECT *', $table, $where, $order);

    }

    public function delete($table, $where) 
    {

        return $this->action('DELETE', $table, $where);

    }

    public function insert($table, $fields = array())
    {

        $keys = array_keys($fields);
        $values = '';
        $x = 1;

        foreach($fields as $field) {
            $values .= '?';

            if ($x < count($fields)) {
                $values .= ', ';
            }
            $x++;
        }

        $sql = "INSERT INTO {$table} (`" . implode('`, `', $keys)  . "`) VALUES ({$values})";

        if (!$this->query($sql, $fields)->error()) {
            return $this;
        }

        return $this;

    }

    public function update($table, $id, $where = 'id', $fields = array())
    {

        $set = '';
        $x = 1;

        foreach($fields as $name => $value) {
            $set .= "{$name} = ?";

            if ($x < count($fields)) {
                $set .= ', ';
            }
            $x++;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE {$where} = {$id}";

        if (!$this->query($sql, $fields)->error()) {
            return $this;
        }

        return $this;

    }


    public function count()
    {

        return $this->_count;

    }

    public function results()
    {

        return $this->_results;

    }

    public function first()
    {

        return $this->results()[0];

    }

    public function getAll($table) 
    {

        return $this->query("SELECT * FROM {$table}");

    }

}
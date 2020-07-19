<?php

namespace Kernel;


class DB
{

    private $connection;

    private $server_name;
    private $server_port;
    private $username;
    private $password;
    private $db_name;

    public $state;
    public $error;
    public $on_demand;

    function __construct($server_name, $server_port, $username, $password, $db_name, $on_demand = false)
    {
        $this->server_name = $server_name;
        $this->server_port = $server_port;
        $this->on_demand = $on_demand;

        $this->db_name = $db_name;
        $this->username = $username;
        $this->password = $password;

        $this->error = null;
        $this->state = 'created';
    }

    public function connect(){
        try{
            $this->connection = new \mysqli($this->server_name, $this->username, $this->password, $this->db_name);
        }
        catch(\Exception $e){}

        if ($this->connection->connect_error) {
            $this->error = $this->connection->connect_error;
            $this->state = 'error';
            return;
        }

        $this->connection->autocommit(!$this->on_demand);
        $this->state = 'connected';
    }

    public function disconnect(){
        if($this->state !== 'connected') return;
        $this->connection->close();
    }

    public function commit(){
        if($this->on_demand) return true;

        if (!$this->connection->commit()) {
//            $this->error = $this->connection->connect_error;
            $this->error = 'Transaction commit failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function begin_transaction(){
        if($this->on_demand) return true;

        if (!$this->connection->begin_transaction()) {
//            $this->error = $this->connection->connect_error;
            $this->error = 'Begin Transaction failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function rollback(){
        if($this->on_demand) return false;

        if (!$this->connection->rollback()) {
//            $this->error = $this->connection->connect_error;
            $this->error = 'Rollback Transaction failed';
            $this->state = 'error';
            return false;
        }

        return true;
    }

    public function gSelect(string $table, array $fields, string $where_clause){
        $content = !empty($fields) ? implode('`,`', $fields) : '*' ;
        $content = !empty($fields) ? "`$content`" : $content ;

        $sql = "SELECT $content" .
            " FROM $table" .
            " WHERE $where_clause LIMIT 1;" ;

        $result = $this->connection->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                yield $row;
            }
        }
        else {
            return null;
        }
    }

    public function oneSelect(string $table, array $fields, string $where_clause){
        $content = !empty($fields) ? implode('`,`', $fields) : '*' ;
        $content = !empty($fields) ? "`$content`" : $content ;

        $sql = "SELECT $content" .
            " FROM $table" .
            " WHERE $where_clause LIMIT 1;" ;

        $result = $this->connection->query($sql);

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        else {
            return null;
        }
    }


    public function drop_table(string $table): ?bool
    {
        $sql = "DROP TABLE IF EXISTS $table;";
        $this->connection->query($sql);
        return true;
    }

    public function create_table(string $table, array $fields): ?bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS $table " .
            " (" . implode(',', $fields) . ");";
        $this->connection->query($sql);
        return true;
    }

    public function insert(string $table, array $fields, array $values): ?bool
    {
        $sql = "INSERT INTO `$table`" .
            " (`" . implode('`,`', $fields) . "`)" .
            " VALUES ('" . implode("','", $values) . "');";
        $this->connection->query($sql);
        return true; // TODO: return insert_id
    }

    public function update(string $table, array $fields, array $values, string $where_clause): ?bool
    {
        $data = array_combine($fields,$values);
        $content = [];
        foreach ($data as $key => $val) {
            $content[] = "`$key` = '$val'";
        }
        $content = implode(',', $content);

        $sql = "UPDATE `$table`" .
            " SET $content" .
            " WHERE $where_clause;" ;
        $this->connection->query($sql);
        return true;
    }

    # TODO: where_clause must be associative array, to check field in insert or update
    public function insertOrUpdate(string $table, array $fields, array $values, string $where_clause): ?bool
    {
        $record = $this->oneSelect($table, $fields, $where_clause);
        if(is_null($record)) { //insert
            return $this->insert($table, $fields, $values);
        }
        else{ //update
            return $this->update($table, $fields, $values, $where_clause);
        }
    }

    public function increase(string $table, array $fields, string $where_clause): ?bool
    {
        $record = $this->oneSelect($table, $fields, $where_clause);
        if(is_null($record)) { //insert
            return $this->insert($table, $fields, str_split(str_repeat('1',count($fields))));
        }
        else{ //update
            $values = [];
            foreach($fields as $field){
                $values[$field] = ((int) $record[$field]) + 1;
            }
            return $this->update($table, $fields, $values, $where_clause);
        }
    }
}
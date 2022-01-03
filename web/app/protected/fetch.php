<?php

namespace Database\Fetch;

class Fetcher {
    public $__db;
	public function __construct($conn){
        $this->__db = $conn;
	}

    function fetch_table_singlerow($find_specific_in_table, $table_name, $where_column_name) {
        $stmt = $this->__db->prepare("SELECT * FROM " . $table_name . " WHERE " . $where_column_name . " = :find");
        $stmt->bindParam(":find", $find_specific_in_table);
        $stmt->execute();

        return ($stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC));
    }

    function fetch_file($username, $file_name) {
        $stmt = $this->__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND file_name = :filename");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":filename", $file_name);
        $stmt->execute();

        return ($stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC));
    }

    function user_exists($user) {
        $stmt = $this->__db->prepare("SELECT username FROM users WHERE username = :username");
        $stmt->bindParam(":username", $user);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }
}
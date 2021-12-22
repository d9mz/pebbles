<?php

namespace Database\Fetch;

class Fetcher {
    public $__db;
	public function __construct($conn){
        $this->__db = $conn;
	}

    function fetch_table_singlerow($username, $table_name, $column_name) {
        $stmt = $this->__db->prepare("SELECT * FROM " . $table_name . " WHERE " . $column_name . " = :username");
        $stmt->bindParam(":username", $username);
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
}
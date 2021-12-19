<?php

namespace Database\Update;

class Updater {
    public $__db;
	public function __construct($conn){
        $this->__db = $conn;
	}

    function update_row($username, $row_name, $new) {
        $stmt = $this->__db->prepare("UPDATE users SET " . $row_name . " = :new WHERE username = :username");
        $stmt->bindParam(":new", $new);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return true;
    }
}
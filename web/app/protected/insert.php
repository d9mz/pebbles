<?php

namespace Database\Insert;

class Inserter {
    public $__db;
	public function __construct($conn){
        $this->__db = $conn;
	}

    function insert_row($table_name, array $columns = array(), array $values = array()) {
        $_columns = $columns;
        $_values = $values;

        $columns_pre = array_map(function($value) { return ':' . $value; }, $columns);
        $columns = implode(", ", $columns);
        $columns_pre = implode(", ", $columns_pre);

        $stmt = $this->__db->prepare("INSERT INTO " . $table_name . " (" . $columns . ") VALUES (" . $columns_pre . ")");

        /*
            $stmt = $this->__db->prepare("INSERT INTO " . $table_name . " (" . $columns . ") VALUES (" . ")" );
            // INSERT INTO playlists (title, description, rid, author) VALUES (:title, :desc, :rid, :username)
        */

        foreach (array_combine($_values, $_columns) as $value => $column) {
            $column = ":" . $column;
            $stmt->bindParam($column, $value);
            echo $column . " " . $value . "<br>";
            echo "\$stmt->bindParam('" . $column . "'" . ", '" . $value . "');<br>";
        }

        if($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
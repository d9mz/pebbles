<?php

namespace Database\Fetch;

class Fetcher {
    public $__db;
	public function __construct($conn){
        $this->__db = $conn;
	}

    function fetch_video_views(string $id) {
        $stmt = $this->__db->prepare("SELECT * FROM views WHERE videoid = :id");
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
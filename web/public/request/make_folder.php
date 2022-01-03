<?php
require '../../app/vendor/autoload.php';
require '../../app/protected/hardcoded.php';
require '../../app/protected/config.inc.php';
require '../../app/protected/fetch.php';
require '../../app/protected/update.php';
require '../../app/protected/delete.php';
require '../../app/protected/insert.php';
require '../../app/protected/formatting.php';

$router = new \Bramus\Router\Router();
$formatter = new \Misc\Formatter\Formatter();
$fetch = new \Database\Fetch\Fetcher($__db);
$insert = new \Database\Insert\Inserter($__db);

//header("Content-type: application/json");

$request = (object) [
    "username" => $_SESSION['domainname'],
    "content" => "",
    "folder_name" => $_POST['folder_name'],

    "error"    => (object) [
        "message" => ""
    ],
];

if(substr($request->folder_name , -1) != '/'){
    $request->folder_name = $request->folder_name . "/";
}

$file = $fetch->fetch_file($_SESSION['domainname'], $request->folder_name);

if(isset($file['id']))
    $request->error->message = "A folder with the same name exists!";

if(empty(trim($request->folder_name)))
    $request->error->message = "Your folder does not exist";

if($request->error->message == "") {
    $stmt = $__db->prepare("INSERT INTO files (file_name, contents, belongs_to, parent, type) VALUES (:folder_name, :contents, :belongs_to, '/', 'folder')");
    $stmt->bindParam(":folder_name", $request->folder_name);
    $stmt->bindParam(":contents", $request->content);
    $stmt->bindParam(":belongs_to", $request->username);
    $stmt->execute();
} else {
    $_SESSION['error'] = $request->error;
}

header("Location: /edit_site");
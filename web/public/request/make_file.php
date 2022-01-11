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
    "content" => "<p>What's up</p>",
    "filename" => $_POST['file_name'],

    "error"    => (object) [
        "message" => ""
    ],
];

$file = $fetch->fetch_file($_SESSION['domainname'], $request->filename);

if(isset($file['id']))
    $request->error->message = "A file with the same name exists!";

if(empty(trim($request->filename)))
    $request->error->message = "Your file does not exist";

if(isset($_GET['dir']) && empty(trim($_GET['dir'])))
    $request->error->message = "Directory not valid";
else if(isset($_GET['dir']) && !empty(trim($_GET['dir'])))
    $request->directory = $_GET['dir'];

if($request->error->message == "") {
    if(isset($request->directory)) {
        $stmt = $__db->prepare("INSERT INTO files (file_name, contents, belongs_to, parent) VALUES (:file_name, :contents, :belongs_to, :parent)");
        $stmt->bindParam(":file_name", $request->filename);
        $stmt->bindParam(":contents", $request->content);
        $stmt->bindParam(":belongs_to", $request->username);
        $stmt->bindParam(":parent", $request->directory);
        $stmt->execute();
    } else {
        $stmt = $__db->prepare("INSERT INTO files (file_name, contents, belongs_to) VALUES (:file_name, :contents, :belongs_to)");
        $stmt->bindParam(":file_name", $request->filename);
        $stmt->bindParam(":contents", $request->content);
        $stmt->bindParam(":belongs_to", $request->username);
        $stmt->execute();
    }
} else {
    $_SESSION['error'] = $request->error;
}

// print_r($request);
header("Location: /edit_site");
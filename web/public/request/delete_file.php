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
    "filename" => $_GET['file'],

    "error"    => (object) [
        "message" => ""
    ],
];

$file = $fetch->fetch_file($_SESSION['domainname'], $_GET['file']);

if(!isset($request->filename) && empty(trim($request->filename))) {
    $request->error->message = "Your file does not exist";
} 

if($file['belongs_to'] != $_SESSION['domainname'])
    $request->error->message = "This file does not belong to you.";

if($request->error->message == "") {
    $stmt = $__db->prepare("DELETE FROM files WHERE file_name=:file_name AND belongs_to=:belongs_to");
    $stmt->execute(array(
      ':file_name' => $request->filename,
      ':belongs_to' => $request->username,
    ));
}

header("Location: /edit_site");
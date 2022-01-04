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
    "content" => $_POST['contents'],
    "filename" => $_GET['file'],

    "error"    => (object) [
        "message" => ""
    ],
];

$file = $fetch->fetch_file($_SESSION['domainname'], $_GET['file']);

if (!empty($_POST['token'])) {
    if (hash_equals($_SESSION['token'], $_POST['token'])) {
        if(!isset($file['id'])) {
            $request->error->message = "Your file does not exist";
        } else if($file['belongs_to'] != $_SESSION['domainname']) {
            $request->error->message = "Your file does not exist";
        }
        
        if($request->error->message == "") {
            $stmt = $__db->prepare("UPDATE files SET contents = :content WHERE file_name = :filename AND belongs_to = :belongs_to");
            $stmt->bindParam(":content", $request->content);
            $stmt->bindParam(":filename", $request->filename);
            $stmt->bindParam(":belongs_to", $request->username);
            $stmt->execute();
        } else {
            $_SESSION['error'] = $request->error;
        }
    } else {
        $request->error->message = "Invalid CSRF token.";
        $_SESSION['error'] = $request->error;
    }
}

header("Location: /edit_file?file=" . $request->filename);
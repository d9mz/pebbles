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
    "username" => $_SESSION['siteusername'],
    "content"  => @$_POST['contents'],
    "bio"      => @$_POST['bio'],
    "action"   => $_GET['action'],

    "error"    => (object) [
        "message" => ""
    ],
];

if($request->error->message == "") {
    if($request->action == "bio") {
        $stmt = $__db->prepare("UPDATE users SET user_bio = :bio WHERE username = :username");
        $stmt->bindParam(":bio", $request->bio);
        $stmt->bindParam(":username", $request->username);
        $stmt->execute();
    }

    header("Location: /user/" . $request->username);
} else {
    $_SESSION['error'] = $request->error;
    header("Location: /user/" . $request->username);
}
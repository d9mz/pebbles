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
    "following" => $_GET['to'],

    "error"    => (object) [
        "message" => ""
    ],
];

$stmt = $__db->prepare("SELECT * FROM follower WHERE following = :following AND from_user = :from_user");
$stmt->bindParam(":following", $request->following);
$stmt->bindParam(":from_user", $request->username);
$stmt->execute();

$follows = $stmt->rowCount();

if($follows != 0)
    $request->error->message = "You are already following this person.";

if(empty(trim($request->following)))
    $request->error->message = "The 'to' paramater is not well formed.";

if(!$fetch->user_exists($request->following))
    $request->error->message = "The user you are trying to follow does not exist.";

if($request->error->message == "") {
    $stmt = $__db->prepare("INSERT INTO follower (following, from_user) VALUES (:following, :from_user)");
    $stmt->bindParam(":following", $request->following);
    $stmt->bindParam(":from_user", $request->username);
    $stmt->execute();
} else {
    $_SESSION['error'] = $request->error;
}

header("Location: /user/" . $request->following);
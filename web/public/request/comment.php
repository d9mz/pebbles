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
    "comment" => $_POST['comment'],
    "to_user" => $_GET['user'],

    "error"    => (object) [
        "message" => ""
    ],
];

if(empty(trim($request->comment)))
    $request->error->message = "Your comment cannot be empty.";

if($request->error->message == "") {
    $stmt = $__db->prepare("INSERT INTO profile_comments (comment_author, comment_text, comment_to) VALUES (:comment_author, :comment_text, :comment_to)");
    $stmt->bindParam(":comment_author", $request->username);
    $stmt->bindParam(":comment_text", $request->comment);
    $stmt->bindParam(":comment_to", $request->to_user);
    $stmt->execute();
} else {
    $_SESSION['error'] = $request->error;
}

header("Location: /user/" . $_GET['user']);
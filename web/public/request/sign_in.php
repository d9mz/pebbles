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
    "username" => trim(strtolower($formatter->remove_emoji($_POST['pebble_username']))),
    "password" => $_POST['pebble_password'],
    "password_hash" => password_hash($_POST['pebble_password'], PASSWORD_DEFAULT),

    "error"    => (object) [
        "message" => ""
    ],
];

if(empty($request->username))
    $request->error->message = "Your username cannot be empty.";

if(!preg_match('/^[A-Za-z][A-Za-z0-9]{5,31}$/', $request->username))
    $request->error->message = "Your username is too long/short or it contains special characters.";

$stmt = $__db->prepare("SELECT password FROM users WHERE username = :username");
$stmt->bindParam(":username", $request->username);
$stmt->execute();

if(!$stmt->rowCount()){ 
    { $request->error->message = "Incorrect username or password!"; } }

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!isset($row['password'])) 
    { $request->error->message = "Incorrect username or password!"; } 
else 
    { $request->returned_password = $row['password']; }

if(!@password_verify($request->password, $request->returned_password)) 
    { $request->error->message = "Incorrect username or password!"; }

if($request->error->message == "") {
    $_SESSION['siteusername'] = $request->username;
    $user = $fetch->fetch_table_singlerow($request->username, "users", "username");
    $_SESSION['domainname'] = $user['domain'];
    header("Location: /");
} else {
    $_SESSION['error'] = $request->error;
    header("Location: /sign_in");
}

echo(json_encode($request));
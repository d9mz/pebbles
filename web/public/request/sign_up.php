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
    "sitename" => trim(strtolower($formatter->remove_emoji($_POST['pebble_site_name']))),
    "password" => $_POST['pebble_password'],
    "color"    => $formatter->rand_color(),
    "content"  => "<p>What's up</p>",
    "filename" => "index.html",
    "password_hash" => password_hash($_POST['pebble_password'], PASSWORD_DEFAULT),

    "error"    => (object) [
        "message" => ""
    ],
];

/*
if(!isset($_POST['g-recaptcha-response']))
    $request->error->message = "Your recaptcha response is not set.";

if(!$formatter->validateCaptcha($recaptcha_private_key, $_POST['g-recaptcha-response'])) 
    $request->error->message = "Invalid Recaptcha";
*/

// die("Registration temporarily closed");

if(empty($request->username))
    $request->error->message = "Your username cannot be empty.";

if(empty($request->sitename))
    $request->error->message = "Your site name cannot be empty.";

if(!preg_match('/^[\w]{5,20}+$/', $request->username))
    $request->error->message = "Your username is too long/short or it contains special characters.";

if(!preg_match('/^[\w]{5,20}+$/', $request->sitename))
    $request->error->message = "Your site name is too long/short or it contains special characters.";

$stmt = $__db->prepare("SELECT username FROM users WHERE username = lower(:username)");
$stmt->bindParam(":username", $request->username);
$stmt->execute();
if($stmt->rowCount()) 
    { $request->error->message = "There's already a user with that same username!"; }

$stmt = $__db->prepare("SELECT domain FROM users WHERE domain = lower(:domain)");
$stmt->bindParam(":domain", $request->sitename);
$stmt->execute();
if($stmt->rowCount()) 
    { $request->error->message = "There's already a user with that same domain name!"; }

if($request->error->message == "") {
    $stmt = $__db->prepare("INSERT INTO users (username, password, domain, orgin_ip, color) VALUES (:username, :password, :domain, 'ip', :color)");
    $stmt->bindParam(":username", $request->username);
    $stmt->bindParam(":password", $request->password_hash);
    $stmt->bindParam(":domain", $request->sitename);
    $stmt->bindParam(":color", $request->color);
    $stmt->execute();

    $_SESSION['siteusername'] = $request->username;
    $_SESSION['domainname'] = $request->sitename;

    $stmt = $__db->prepare("INSERT INTO files (file_name, contents, belongs_to) VALUES (:file_name, :contents, :belongs_to)");
    $stmt->bindParam(":file_name", $request->filename);
    $stmt->bindParam(":contents", $request->content);
    $stmt->bindParam(":belongs_to", $request->sitename);
    $stmt->execute();

    header("Location: /");
} else {
    $_SESSION['error'] = $request->error;
    header("Location: /sign_up");
}

// echo(json_encode($request));
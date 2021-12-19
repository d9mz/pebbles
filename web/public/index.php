<?php
require '../app/vendor/autoload.php';
require '../app/protected/hardcoded.php';
require '../app/protected/config.inc.php';
require '../app/protected/fetch.php';
require '../app/protected/update.php';
require '../app/protected/delete.php';
require '../app/protected/insert.php';
require '../app/protected/formatting.php';

$router = new \Bramus\Router\Router();
$formatter = new \Misc\Formatter\Formatter();
$fetch = new \Database\Fetch\Fetcher($__db);
$insert = new \Database\Insert\Inserter($__db);
$loader = new \Twig\Loader\FilesystemLoader('twig/templates');
$twig = new \Twig\Environment($loader, 
	['debug' => true] // disable manually in production
);

$userinfo = [
	"logged_in"       => false,
	"unread_messages" => 0,
	"profile_picture" => "default.jpg",
	"username"        => "joseph",
];

$metadata = [
    "og_context"        => [
        "title"            => "SubRocks 2011",
        "description"      => "SubRocks is a site dedicated to bring back the 2011 layout of YouTube.",
        "url"              => "localhost",
        "picture"          => "asd.png",
    ],

    "user_context"      => [
        "site_context"     => "",
        // fill out
    ],
];

$router->get('/', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('index.twig', 
        array(

        )
    );
});

$router->get('/sign_up', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('sign_up.twig', 
        array(

        )
    );
});

$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    echo "PAGE DOESN'T EXIST";
});

$twig->addGlobal('metadata',  $metadata);
$twig->addGlobal('userinfo',  $userinfo);
$twig->addGlobal('hardcoded', $init    );
$router->run();
?>
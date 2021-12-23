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
$updater = new \Database\Update\Updater($__db);
$fetch = new \Database\Fetch\Fetcher($__db);
$insert = new \Database\Insert\Inserter($__db);
$loader = new \Twig\Loader\FilesystemLoader('twig/templates');
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$config->set('CSS.Trusted', true); // allow any css
$config->set('HTML.Allowed','div[style]');
$purifier = new HTMLPurifier($config);
$twig = new \Twig\Environment($loader, 
	['debug' => true] // disable manually in production
);

if(isset($_SESSION['domainname']) && isset($_SESSION['siteusername'])) {
    $stmt = $__db->prepare("UPDATE users SET last_login = now() WHERE username = :username");
    $stmt->bindParam(":username", $_SESSION['siteusername']);
    $stmt->execute();
}

$metadata = [
    "og_context"        => [
        "title"            => "Pebbles",
        "description"      => "Pebbles is a site making site",
        "url"              => "localhost",
        "picture"          => "asd.png",
    ],

    "user_context"      => [
        "sites_on_site" => 0,
        "files_served"  => 0,
    ],
];

$sites_search = $__db->prepare("SELECT id FROM users");
$sites_search->execute();
$metadata["user_context"]["sites_on_site"] = $sites_search->rowCount();

$files_served = $__db->prepare("SELECT id FROM files");
$files_served->execute();
$metadata["user_context"]["files_served"] = $files_served->rowCount();

$router->get('/', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $sites_search = $__db->prepare("SELECT * FROM users ORDER BY last_login DESC LIMIT 20");
	$sites_search->execute();
	
	while($site = $sites_search->fetch(PDO::FETCH_ASSOC)) { 
		$sites[] = $site;
	}

	$sites['rows'] = $sites_search->rowCount();

    echo $twig->render('index.twig', 
        array(
            'sites' => $sites,
        )
    );
});

$router->get('/sign_up', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('sign_up.twig', 
        array(

        )
    );
});

$router->get('/sign_in', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('sign_in.twig', 
        array(

        )
    );
});

$router->get('/edit_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $file = $fetch->fetch_file($_SESSION['domainname'], $_GET['file']);

    echo $twig->render('edit_file.twig', 
        array(
            'file' => $file,
        )
    );
});

$router->get('/new_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('new_file.twig', 
        array(

        )
    );
});

$router->get('/upload_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('upload_file.twig', 
        array(

        )
    );
});

$router->get('/edit_site', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username ORDER BY id DESC");
    $files_search->bindParam(":username", $_SESSION['domainname']);
	$files_search->execute();
	
	while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
        $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
		$files[] = $file;
	}

	$files['rows'] = $files_search->rowCount();

    echo $twig->render('edit_site.twig', 
        array(
            'files_row' => $files,
        )
    );
});

$router->get('/site/(\w+)/(.*)', function($username, $file_name) use ($twig, $__db, $formatter, $insert, $fetch, $purifier) { 
    $user = $fetch->fetch_table_singlerow($username, "users", "username");
    $file = $fetch->fetch_file($username, $file_name);
    $directory = explode("/", $file_name);

    if(!isset($file['id'])) 
        die("This file does not exist!");

    $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
       
    if($file['ext'] == "css") {
        $file['mime_type'] = "text/css";
    } else if($file['ext'] == "gif") {
        $file['mime_type'] = "image/gif";
        $file['contents']  = file_get_contents("assets/img/" . $file['file_name']);
    } else if($file['ext'] == "jpg" || $file['ext'] == "jpeg") {
        $file['mime_type'] = "image/jpeg";
        $file['contents']  = file_get_contents("assets/img/" . $file['file_name']);
    } else if($file['ext'] == "png") {
        $file['mime_type'] = "image/png";
        $file['contents']  = file_get_contents("assets/img/" . $file['file_name']);
    }
    
    header("Content-type: " . $file['mime_type']);
    // echo $purifier->purify($file['contents']);
    echo $file['contents'];
});

$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    echo "PAGE DOESN'T EXIST";
});

$twig->addGlobal('metadata',  $metadata);
$twig->addGlobal('session',   $_SESSION);
$twig->addGlobal('hardcoded', $init    );
unset($_SESSION['error']);
$router->run();
?>
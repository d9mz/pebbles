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

$twig->addFunction(
    new \Twig\TwigFunction(
        'form_token',
        function($lock_to = null) {
            if (empty($_SESSION['token'])) {
                $_SESSION['token'] = bin2hex(random_bytes(32));
            }
            if (empty($_SESSION['token2'])) {
                $_SESSION['token2'] = random_bytes(32);
            }
            if (empty($lock_to)) {
                return $_SESSION['token'];
            }
            return hash_hmac('sha256', $lock_to, $_SESSION['token2']);
        }
    )
);

$usersess = @$_SESSION['siteusername'];
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

    echo $twig->render('index.twig', array('sites' => $sites,));
});

$router->get('/sign_up', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('sign_up.twig', array());
});

$router->get('/sign_in', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('sign_in.twig', array());
});

$router->get('/search', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $sites_search = $__db->prepare("SELECT * FROM users WHERE username LIKE :search_query ORDER BY id DESC, featured LIMIT 100");
    $search = "%" . $_GET['search_query'] . "%";
    $sites_search->bindParam(":search_query", $search);
	$sites_search->execute();
	
	while($site = $sites_search->fetch(PDO::FETCH_ASSOC)) { 
		$sites[] = $site;
	}

	$sites['rows'] = $sites_search->rowCount();
    
    echo $twig->render('search.twig', array(
        'sites' => $sites,
    ));
});

$router->get('/potd', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $sites_search = $__db->prepare("SELECT * FROM users WHERE featured = 'ye' ORDER BY id DESC, featured LIMIT 20");
	$sites_search->execute();
	
	while($site = $sites_search->fetch(PDO::FETCH_ASSOC)) { 
		$sites[] = $site;
	}

	$sites['rows'] = $sites_search->rowCount();

    echo $twig->render('potd.twig', array('sites' => $sites,));
});

$router->get('/edit_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $file = $fetch->fetch_file($_SESSION['domainname'], $_GET['file']);

    echo $twig->render('edit_file.twig', 
        array(
            'file' => $file,
        )
    );
});

$router->get('/ajax_edit_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    $file = $fetch->fetch_file($_SESSION['domainname'], $_GET['file']);

    echo $twig->render('ajax_edit_file.twig', 
        array(
            'file' => $file,
        )
    );
});

$router->get('/new_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('new_file.twig', 
        array(
            'current_dir' => @$_GET['dir'],
        )
    );
});

$router->get('/new_folder', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('new_folder.twig', 
        array(
            'current_dir' => @$_GET['dir'],
        )
    );
});

$router->get('/upload_file', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    echo $twig->render('upload_file.twig', 
        array(
            'current_dir' => @$_GET['dir'],
        )
    );
});

$router->get('/edit_site', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    if(!isset($_GET['dir'])) {
        $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND parent = '/' ORDER BY type DESC");
        $files_search->bindParam(":username", $_SESSION['domainname']);
        $files_search->execute();
        
        while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
            $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            $files[] = $file;
        }

        $files['rows'] = $files_search->rowCount();
    } else {
        $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND parent = :parent ORDER BY type DESC");
        $files_search->bindParam(":username", $_SESSION['domainname']);
        $files_search->bindParam(":parent", $_GET['dir']);
        $files_search->execute();
        
        while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
            $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            $files[] = $file;
        }

        $files['rows'] = $files_search->rowCount();
    }

    echo $twig->render('edit_site.twig', 
        array(
            'current_dir' => @$_GET['dir'],
            'files_row' => $files,
        )
    );
});

$router->get('/ajax_edit_site', function() use ($twig, $__db, $formatter, $insert, $fetch) { 
    if(!isset($_GET['dir'])) {
        $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND parent = '/' ORDER BY type DESC");
        $files_search->bindParam(":username", $_SESSION['domainname']);
        $files_search->execute();
        
        while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
            $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            $files[] = $file;
        }

        $files['rows'] = $files_search->rowCount();
    } else {
        $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND parent = :parent ORDER BY type DESC");
        $files_search->bindParam(":username", $_SESSION['domainname']);
        $files_search->bindParam(":parent", $_GET['dir']);
        $files_search->execute();
        
        while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
            $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            $files[] = $file;
        }

        $files['rows'] = $files_search->rowCount();
    }

    echo $twig->render('ajax_edit_site.twig', 
        array(
            'current_dir' => @$_GET['dir'],
            'files_row' => $files,
        )
    );
});

$router->get('/user/(\w+)', function($username) use ($twig, $__db, $formatter, $insert, $fetch, $purifier, $usersess) { 
    if(!$fetch->user_exists($username)) {
        $request = (object) [
            "error" => (object) [
                "message" => "This user does not exist.",
            ],
        ];
        
        $_SESSION['error'] = $request->error;
        header("Location: /");
    }

    $user_info = $fetch->fetch_table_singlerow($username, "users", "username");

    $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username ORDER BY id DESC");
    $files_search->bindParam(":username", $user_info['domain']);
	$files_search->execute();
	
	while($file = $files_search->fetch(PDO::FETCH_ASSOC)) { 
        $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
		$files[] = $file;
	}

	$files['rows'] = $files_search->rowCount();

    $comments_search = $__db->prepare("SELECT * FROM profile_comments WHERE comment_to = :username ORDER BY id DESC");
    $comments_search->bindParam(":username", $user_info['username']);
	$comments_search->execute();
	
	while($comment = $comments_search->fetch(PDO::FETCH_ASSOC)) { 
		$comments[] = $comment;
	}

	$comments['rows'] = $comments_search->rowCount();

    $followers_search = $__db->prepare("SELECT * FROM follower WHERE following = :following");
    $followers_search->bindParam(":following", $user_info['username']);
	$followers_search->execute();
	
	while($follow = $followers_search->fetch(PDO::FETCH_ASSOC)) { 
		$followers[] = $follow;
	}

	$followers['rows'] = $followers_search->rowCount();
    
    $stmt = $__db->prepare("SELECT * FROM follower WHERE following = :following AND from_user = :from_user");
    $stmt->bindParam(":following", $user_info['username']);
    $stmt->bindParam(":from_user", $usersess);
    $stmt->execute();
    
    $follows = $stmt->rowCount();

    echo $twig->render('user.twig', 
        array(
            'follows'   => $follows,
            'followers' => $followers,
            'user_info' => $user_info,
            'files_row' => $files,
            'comments'  => $comments,
        )
    );
});

$router->get('/site/(\w+)/(.*)', function($username, $file_name) use ($twig, $__db, $formatter, $insert, $fetch, $purifier) { 
    $directory = explode("/", $file_name);
    $user = $fetch->fetch_table_singlerow($username, "users", "username");
    $file = $fetch->fetch_file($username, $file_name);

    if(count($directory) > 1) {
        $file = end($directory);
        array_pop($directory);
        $directory = implode("/", $directory);
        $directory = "/" . $directory . "/";

        $files_search = $__db->prepare("SELECT * FROM files WHERE belongs_to = :username AND file_name = :file_name AND parent = :parent ORDER BY id DESC");
        $files_search->bindParam(":username", $username);
        $files_search->bindParam(":file_name", $file);
        $files_search->bindParam(":parent", $directory);
        $files_search->execute();

        $file = $files_search->fetch(PDO::FETCH_ASSOC);
    } else {
        $file = $fetch->fetch_file($username, $file_name);
    }

    if(!isset($file['id'])) 
        die("<marquee>This file does not exist!</marquee>");

    $file['ext'] = pathinfo($file['file_name'], PATHINFO_EXTENSION);
       
    if($file['ext'] == "css") {
        $file['mime_type'] = "text/css";
    } else if($file['ext'] == "js") {
        $file['mime_type'] = "text/javascript";
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

$router->set404(function() use ($twig) {
    echo $twig->render('404.twig', array());
});

$twig->addGlobal('metadata',   $metadata);
$twig->addGlobal('session',    $_SESSION);
$twig->addGlobal('get',        $_GET    );
$twig->addGlobal('hardcoded',  $init    );
$twig->addGlobal('r_site_key', $recaptcha_site_key);
unset($_SESSION['error']);
$router->run();
?>
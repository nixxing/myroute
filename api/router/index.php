<?php

/**
 * BOOTSTRAP
 * ===============
 */

	// Requires
	require_once __DIR__ . '/ikdoeict/routing/router.php';
    require_once '../response/ikdoeict/rest/response.php';

    // include Plonk & PlonkWebsite
    require_once './library/config.php';
    require_once './library/plonk/plonk.php';
    require_once './library/plonk/database/database.php';

   //always get headers
    /*if (!function_exists('apache_request_headers')) {
        eval('
            function apache_request_headers() {
                foreach($_SERVER as $key=>$value) {
                    if (substr($key,0,5)=="HTTP_") {
                        $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                        $out[$key]=$value;
                    }
                }
                return $out;
            }
        ');
    }*/

	// Create a router and a response
	$router = new Ikdoeict\Routing\Router();
    $response = new Ikdoeict\Rest\Response();

	// Before middleware
	$router->before('GET|POST|PUT|DELETE', 'admin/.*', function() {
		if (!isset($_SESSION['userId'])) {
			header('location: /assets/07/examples/router');
			exit();
		}
	});

    /*$router->before('GET|POST|PUT|DELETE', '.*', function() use ($response) {
        $headers = apache_request_headers();
        if(!isset($headers['X-Api-Key']) || $headers['X-Api-Key'] == '') {
            $response->setStatus(401);
            $response->setContent('Missing or invalid API Key.');
            $response->finish();
        }
    });*/

	// Override the 404
	$router->set404(function() {
		header('HTTP/1.1 404 Not Found');
		echo 'Uh oh - route not found!';
	});



/**
 * ROUTING
 * ===============
 */


	// Index
	$router->get('', function() {
        echo 'Welcome to my website!<br />Allowed routes: /routes, /routes/{id}, /route_info/{id}, /route_info/{id}';
    });

	// Routes
	$router->get('routes/', function() {
        // get DB instance
        $db = PlonkDB::getDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $items  = $db->retrieve('SELECT * FROM routes');
        print json_encode($items);
	});

	// Routes id
	$router->get('routes/\d+', function($id) {
        $db = PlonkDB::getDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $items  = $db->retrieve('SELECT * FROM routes WHERE id=' . $id);

        echo json_encode($items);
	});

    // Route_info id
    $router->get('route_info/\d+', function($id) {
        $db = PlonkDB::getDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $items  = $db->retrieve('SELECT * FROM route_info WHERE route_id=' . $id);

        echo json_encode($items);
    });

    // Add routes
    $router->post('routes/', function() {
        $options = $_POST;

        $options['route_name'] = isset($options['route_name']) && $options['route_name'] != '' ? $options['route_name'] : 'fail';
        $options['city'] = isset($options['city']) && $options['city'] != '' ? $options['city'] : 'fail';

        $db = PlonkDB::getDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $item = $db->insert('routes', $options);
    });

    // Add checkpoints
    $router->post('checkpoints/', function() {
        $options = $_POST;

        $db = PlonkDB::getDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $item = $db->insert('route_info', $options);
    });

// Admin index
	$router->get('admin/', function() {
		echo '(admin login form here)';
	});

	// Admin subpages
	$router->get('admin/.*', function() {
		echo 'This should only be visible if you are logged in';
	});



/**
 * RUN FORREST RUN!
 * ===============
 */

	$router->run(function() {
		//echo '<br /><br /><em>(we are done here)</em>';
	});
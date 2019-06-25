<?php
        
require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

include('config.php');

$app->post("/usuario", function() use($db, $app) {

	$json = $app->request->post("json");
	$data = json_decode($json, true);

	$fecha = date('U');

	$url = "SELECT * FROM usuarios WHERE nick = '". $data["nick"] ."' AND pass= '". $data["pass"] ."'";

	$query = $db->query($url);
	$usuario = $query->fetch_object();

	$usuario->pass = ":D";

	$numero = $query->num_rows;

	if ($numero == 1) {	
		
		$token = password_hash($data["nick"], PASSWORD_DEFAULT);

		$result = array(
			"status" => "success",
			"token" => $token,
			"usuario" => $usuario );			
	} else {
		$result = array(
			"status" => "error",
			"message" => "El usuario no existe"
		);
	}	

	echo json_encode($result);
});

$app->run();
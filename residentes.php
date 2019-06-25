<?php
        
require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

include('config.php');

$app->get("/residentes", function() use($db, $app) {
	$query = $db->query("SELECT * FROM residentes");
	$numero = $query->num_rows;
	$vecinos = array();

	while ($fila = $query->fetch_assoc()) {
		$vecinos[] = $fila;
	}

	$result = array(
		"status" => "success",
		"data" => $vecinos
	);

	echo json_encode($result);
});

$app->get("/residentes/total", function() use($db, $app) {
	$query = $db->query("SELECT * FROM residentes");
	$numero = $query->num_rows;	

	$result = array(
		"total" => $numero
	);

	echo json_encode($result);
});

$app->get("/residente/:id", function($id) use($db, $app) {
	$query = $db->query("SELECT * FROM residentes WHERE id_residente = $id");	
	$vecino = $query->fetch_assoc();

	if ($query->num_rows == 1) {
		$result = array(
			"status" => "success",
			"residente" => $vecino);
	} else {
		$result = array(
			"status" => "error",
			"message" => "El residente no existe"
		);
	}

	echo json_encode($result);
});

$app->post("/residente", function() use ($db, $app) {	

	$json = $app->request->post("json");
	$data = json_decode($json, true);
	
	$query = "INSERT INTO residentes VALUES (NULL,"
			. "'{$data["nombre"]}',"
			. "'{$data["cedula"]}',"
			. "'{$data["correo"]}',"
			. "'{$data["telefono"]}', "
			. "{$data["inmueble_id"]}"
			. ")";
	
	$insert = $db->query($query);
	
	if ($insert) {
		$result = array(
			"status" => "success",
			"message" => "Vecino creado correctamente.",
			"residente" => [
				"nombre" => "{$data["nombre"]}"
			]
		);
	} else {
		$result = array(
			"status" => "error", 
			"titulo" => "No se pudo añadir el residente :/",
			"message" => "$db->error", 
			"query" => "$query"
		);
	}

	echo json_encode($result);
});

$app->put("/residente/:id", function($id) use($db, $app) {

	$json = $app->request->post("json");
	$data = json_decode($json, true);

	$query = "UPDATE residentes SET "
			. "nombre = '{$data["nombre"]}', "
			. "cedula = '{$data["cedula"]}', "
			. "correo = '{$data["correo"]}', "
			. "telefono = '{$data["telefono"]}', "
			. "inmueble_id = {$data["inmueble_id"]} "
			. " WHERE id_residente =  $id ";
	$update = $db->query($query);

	if ($update) {
		$result = array(
			"status" => "success", 
			"message" => "El residente se ha actualizado correctamente"
		);
	} else {
		$result = array(
			"status" => "error", 
			"titulo" => "No se pudo añadir el residente :/",
			"message" => "$db->error", 
			"query" => "$query"
		);
	}

	echo json_encode($result);
});

$app->run();
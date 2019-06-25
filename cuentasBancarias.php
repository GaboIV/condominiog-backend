<?php
        
require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

include('config.php');

$app->get("/bancos", function() use($db, $app) {
	$query = $db->query("SELECT * FROM `bancos` ORDER BY nombre ASC");
	$cuentas = array();

	while ($fila = $query->fetch_assoc()) {
		$cuentas[] = $fila;
	}

	$result = array(
		"status" => "success",
		"bancos" => $cuentas
	);

	echo json_encode($result);
});

$app->get("/cuentasBancarias", function() use($db, $app) {
	$query = $db->query("SELECT * FROM cuentas");
	$numero = $query->num_rows;
	$cuentas = array();

	$i = 0;

	while ($fila = $query->fetch_assoc()) {
		
		$cuentas[$i] = $fila;

		if ($fila['tipo_cuenta_id'] == 1) {
			$tipo_cuenta = "Cuenta Corriente";
			$cuentas[$i]['tipo_cuenta_id'] = array(
				"id_tipo_cuenta" => $fila['tipo_cuenta_id'],
				"nombre" => $tipo_cuenta
			);
			$query2 = $db->query("SELECT * FROM bancos WHERE id_banco = '". $fila['banco_id'] . "'");

			while ($fila2 = $query2->fetch_assoc()) {
				$datos_banco[] = $fila2;
				$cuentas[$i]['banco_id'] = array(
					"id_banco" => $fila2['id_banco'],
					"nombre" => $fila2['nombre']
				);
			}
		}

		$i++;
		
	}	

	$result = array(
		"status" => "success",
		"cuentas" => $cuentas
	);

	echo json_encode($result);
});

$app->post("/cuentaBancaria", function() use ($db, $app) {	

	$json = $app->request->post("json");
	$data = json_decode($json, true);
	
	$query = "INSERT INTO cuentas VALUES (NULL,"
			. "'{$data["nombre"]}',"
			. "'{$data["numero"]}',"
			. "'{$data["documento"]}',"
			. "'{$data["correo"]}',"
			. "{$data["tipo_cuenta_id"]},"
			. "{$data["banco_id"]}"
			. ")";
	
	$insert = $db->query($query);
	
	if ($insert) {
		$result = array(
			"status" => "success",
			"message" => "Cuenta creada correctamente.",
			"cuenta" => [
				"numero" => "{$data["numero"]}"
			]
		);
	} else {
		$result = array(
			"status" => "error", 
			"titulo" => "No se pudo añadir la cuenta :/",
			"message" => "$db->error", 
			"query" => "$query"
		);
	}

	echo json_encode($result);
});

$app->run();
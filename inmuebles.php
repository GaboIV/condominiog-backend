<?php

require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

include('config.php');

$app->get("/inmuebles", function() use($db, $app) {
	$query = $db->query("SELECT * FROM inmuebles");
	$inmuebles = array();
	$i = 0;
	while ($fila = $query->fetch_assoc()) {
		$inmuebles[$i] = $fila;

		$query2 = $db->query("SELECT * FROM residentes WHERE inmueble_id = '". $fila['id_inmueble'] . "'");
		$arroz = array();

		while ($fila2 = $query2->fetch_assoc()) {
			$arroz[] = $fila2;
		}

		$inmuebles[$i]["vecinos"] = $arroz;

		$i++;
	}

	$result = array(
		"status" => "success",
		"inmuebles" => $inmuebles
	);

	echo json_encode($result);
});

$app->post("/inmueble", function() use($db, $app) {

	$json = $app->request->post("json");
	$data = json_decode($json, true);

	$query = "INSERT INTO inmuebles (casa, saldo) VALUES ("
			. "'{$data["casa"]}',"
			. " {$data["saldo"]}"
			. ")";

	$insert = $db->query($query);

	if ($insert) {
		$result = array(
			"status" => "success",
			"message" => "Inmueble creado correctamente.");
	} else {
		$result = array(
			"status" => "error", 
			"titulo" => "Error al añadir inmueble", 
			"message" => "$db->error", 
			"query" => "$query");
	}

	echo json_encode($result);
});

$app->get("/inmueble/estado/:id", function($id) use($db, $app) {
	$nrecibos = 0;
	$query = $db->query("SELECT * FROM transacciones WHERE inmueble_id = $id ORDER BY id_transaccion DESC");
	
	if ($query) {
		$numero = $query->num_rows;	

		if ( $numero > 0) {
			$transacciones = array();
			$i = 0;
			while ($fila = $query->fetch_assoc()) {
				$transacciones[$i] = $fila;

				$query3 = $db->query("SELECT * FROM gestiones WHERE id_gestion = '". $fila['gestion'] ."'");
				$fila3 = $query3->fetch_assoc();
				$transacciones[$i]['gestion'] = $fila3;	
				
				$transacciones[$i]['debe'] = number_format($transacciones[$i]['debe'], 2, ',', '.');
				$transacciones[$i]['haber'] = number_format($transacciones[$i]['haber'], 2, ',', '.');
				$transacciones[$i]['saldo'] = number_format($transacciones[$i]['saldo'], 2, ',', '.');

				$i++;
			}

			$query4 = $db->query("SELECT * FROM documento WHERE inmueble_id = $id ");
			if ($query4) {
				$nrecibos = $query4->num_rows;	
			}

			$result = array(
				"status" => "success",
				"transacciones" => $transacciones,
				"recibos" => $nrecibos
			);
		} else {
			$result = array(
				"status" => "error", 
				"titulo" => "No hay elementos", 
				);
		}
	} else {
		$result = array(
			"status" => "error", 
			"titulo" => "Error al buscar transacciones", 
			"message" => "$db->error", 
			"query" => "$query");
	}

	echo json_encode($result);
});

$app->run();
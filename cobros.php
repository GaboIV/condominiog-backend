<?php
        
require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

include('config.php');

    $app->get("/cobros", function() use($db, $app) {
        $query = $db->query("SELECT * FROM `cobros` ORDER BY id_cobro DESC");
        $cobros = array();

        $i = 0;

        while ($fila = $query->fetch_assoc()) {
            $cobros[$i] = $fila;

            $query2 = $db->query("SELECT * FROM tipo_cobro WHERE id_tipo_cobro = '". $fila['tipo_cobro_id'] . "'");

			while ($fila2 = $query2->fetch_assoc()) {
				$cobros[$i]['tipo_cobro_id'] = array(
					"id_tipo_cobro" => $fila2['id_tipo_cobro'],
					"nombre" => $fila2['nombre']
				);
            }

            $query3 = $db->query("SELECT * FROM cobro_inmueble WHERE cobro_id = '". $fila['id_cobro'] . "'");
            $numero3 = $query3->num_rows;	

            $query4 = $db->query("SELECT * FROM cobro_inmueble WHERE cobro_id = '". $fila['id_cobro'] . "' AND estatus = '1'");
            $numero4 = $query4->num_rows;	

            if ( $numero4 == '0') {
                $porcentaje = 0;
            } else {
                $porcentaje = ($numero4 * 100) / $numero3;
            }

            $cobros[$i]['total'] = $numero3;
            $cobros[$i]['pagado'] = $numero4;
            $cobros[$i]['porcentaje'] = $porcentaje;
            
            $i++;
        }

        $result = array(
            "status" => "success",
            "cobros" => $cobros
        );

        echo json_encode($result);
    });

    $app->get("/cobro/:id", function($id) use($db, $app) {
        $query = $db->query("SELECT * FROM cobros WHERE id_cobro = $id");   

        $i = 0;

        $fila = $query->fetch_assoc();
        $cobro[$i] = $fila;

        $query2 = $db->query("SELECT * FROM cobro_inmueble WHERE cobro_id = '". $fila['id_cobro'] . "' AND estatus = '1'");
        $totalPagado = $query2->num_rows;			
        
        $bolosPagado = $totalPagado * $fila['monto']; 
        $bolosPagado = number_format($bolosPagado, 2, ',', '.');

        $query3 = $db->query("SELECT * FROM cobro_inmueble WHERE cobro_id = '". $fila['id_cobro'] . "' AND estatus != '1'");
        $totalFaltante = $query3->num_rows;			
        
        $bolosFaltante = $totalFaltante * $fila['monto'];  
        $bolosFaltante = number_format($bolosFaltante, 2, ',', '.');    

        $result = array(
            "status" => "success",
            "totalPagado" => "$totalPagado",
            "bolosPagado" => "$bolosPagado",
            "totalFaltante" => "$totalFaltante",
            "bolosFaltante" => "$bolosFaltante",
            "cobro" => $cobro[$i]
        );

        echo json_encode($result);
    });

    $app->post("/cobro", function() use ($db, $app) {	

        $json = $app->request->post("json");
        $data = json_decode($json, true);

        $fecha_act = date('Y-m-d H:i:s');

        $monto = $data["monto"];
        $monto_sistema = 0;

        $tipo_cobro = $data["tipo_cobro_id"];

        if ( $tipo_cobro == '1') {
            $monto_sistema = $monto * 0.03;
        } else {
            $monto_sistema = $monto * 0.01;
        }

        $descripcion_sistema = "Cobro de Sistema de " . $data["descripcion"];
        
        $query = "INSERT INTO cobros VALUES (NULL,"
                . "'{$data["monto"]}',"
                . "'{$data["descripcion"]}',"
                . "'{$data["fecha"]}',"
                . "'{$data["limite"]}', "
                . "'{$data["tipo_cobro_id"]}',"
                . "'0'"
                . ")";
        
        $insert = $db->query($query);
        
        if ($insert) {

            $id_cobro = $db->insert_id;           

            $query7 = "INSERT INTO cobros VALUES (NULL,"
                    . "'$monto_sistema',"
                    . "'$descripcion_sistema',"
                    . "'{$data["fecha"]}',"
                    . "'{$data["limite"]}', "
                    . "'3',"
                    . "'$id_cobro'"
                    . ")";
            
            $insert7 = $db->query($query7);

            if ($insert7) {
                $id_cobro_sistema = $db->insert_id;
            }

            $query2 = $db->query("SELECT * FROM inmuebles");
            $nro_inmuebles = $query2->num_rows;
            
            while ($fila2 = $query2->fetch_assoc()) {
                $id_inmueble = $fila2['id_inmueble'];
                $saldo = $fila2['saldo'];

                $nuevo_saldo = $saldo - $monto;

                if ($nuevo_saldo >= 0) {
                    $estatus = 1;
                    $pagado = $monto;  

                    $monto_total = $monto + $monto_sistema;

                    if ( $saldo >= $monto_total ) {
                        $estatus_doc = "1";                        
                    } else {
                        $estatus_doc = "2";
                    }
                    
                    $query10 = "INSERT INTO documento VALUES (NULL,"
                        . "'$id_cobro',"
                        . "'$id_cobro_sistema',"
                        . "'$fecha_act',"
                        . "'$id_inmueble',"
                        . "'0', "
                        . "'$estatus_doc'"
                        . ")";

                    $insert10 = $db->query($query10);

                    if ($insert10) {
                        $id_documento = $db->insert_id;

                        if ( $estatus_doc == "1") {
                            $URL = "http://softwareg.com.ve/pdf/ejemplo.php?doc=".$id_documento;                            
                        } else {
                            $URL = "";
                        }

                        $query11 = "INSERT INTO documento_pago VALUES (NULL,"
                            . "'$id_documento',"
                            . "'$pagado',"
                            . "'$fecha_act'"
                            . ")";

                        $insert11 = $db->query($query11);

                        if ( $insert11 ) { }
                    }

                    $nuevo_saldo_sistema = $nuevo_saldo - $monto_sistema;

                    if ($nuevo_saldo_sistema >= 0) {
                        $estatus_sistema = 1;
                        $pagado_sistema = $monto_sistema;

                        $query12 = "INSERT INTO documento_pago VALUES (NULL,"
                            . "'$id_documento',"
                            . "'$pagado_sistema',"
                            . "'$fecha_act'"
                            . ")";

                        $insert12 = $db->query($query12);

                        if ( $insert12 ) { }
                    } else {
                        if ($nuevo_saldo >= 0) {
                            $estatus_sistema = 2;
                            $pagado_sistema = $nuevo_saldo;

                            $query12 = "INSERT INTO documento_pago VALUES (NULL,"
                                . "'$id_documento',"
                                . "'$pagado_sistema',"
                                . "'$fecha_act'"
                                . ")";

                            $insert12 = $db->query($query12);

                            if ( $insert12 ) { }
                        } else {
                            $estatus_sistema = 2;
                            $pagado_sistema = 0;
                        }                    
                    }

                    if ( $URL != "") {
                        $result = file_get_contents($URL);
                    }  
                } else {
                    if ($saldo >= 0) {
                        $estatus = 2;
                        $pagado = $saldo;

                        if ( $saldo > 0) {
                            $query10 = "INSERT INTO documento VALUES (NULL,"
                                . "'$id_cobro',"
                                . "'$id_cobro_sistema',"
                                . "'$fecha_act',"
                                . "'$id_inmueble',"
                                . "'0', "
                                . "'2'"
                                . ")";

                            $insert10 = $db->query($query10);

                            if ($insert10) {
                                $id_documento = $db->insert_id;

                                $query11 = "INSERT INTO documento_pago VALUES (NULL,"
                                    . "'$id_documento',"
                                    . "'$pagado',"
                                    . "'$fecha_act'"
                                    . ")";

                                $insert11 = $db->query($query11);
                            }
                        }                        
                    } else {
                        $estatus = 2;
                        $pagado = 0;
                    } 

                    $nuevo_saldo_sistema = $nuevo_saldo - $monto_sistema;
                    
                    $estatus_sistema = 2;
                    $pagado_sistema = 0;
                }

                $query3 = "INSERT INTO cobro_inmueble VALUES (NULL,"
                . "'{$pagado}',"
                . "'{$estatus}',"
                . "'0',"
                . "'{$id_cobro}', "
                . "'{$id_inmueble}'"
                . ")";

                $insert3 = $db->query($query3);

                $j = 0;

                if ($insert3) {

                    $query6 = "INSERT INTO cobro_inmueble VALUES (NULL,"
                    . "'$pagado_sistema',"
                    . "'$estatus_sistema',"
                    . "'0',"
                    . "'$id_cobro_sistema', "
                    . "'$id_inmueble'"
                    . ")";

                    $insert6 = $db->query($query6);

                    $k = 0;

                    if ($insert3) {

                        $query4 = "UPDATE inmuebles SET "
                                . "saldo = '{$nuevo_saldo_sistema}' "
                                . " WHERE id_inmueble =  $id_inmueble ";
                        $update4 = $db->query($query4);

                        if ($update4) {

                            $query5 = "INSERT INTO transacciones VALUES (NULL,"
                                    . "'Cobro de Condominio',"
                                    . "3,"                    
                                    . "'{$fecha_act}', "
                                    . "'{$monto}', "
                                    . "0, "
                                    . "'{$nuevo_saldo}',"
                                    . "'{$id_inmueble}'"
                                    . ")";
                            
                            $insert5 = $db->query($query5);
                            
                            if ($insert5) {
                                $query9 = "INSERT INTO transacciones VALUES (NULL,"
                                    . "'Cobro de Sistema',"
                                    . "3,"                    
                                    . "'{$fecha_act}', "
                                    . "'{$monto_sistema}', "
                                    . "0, "
                                    . "'{$nuevo_saldo_sistema}',"
                                    . "'{$id_inmueble}'"
                                    . ")";
                            
                                $insert9 = $db->query($query9);

                            } else {
                                $resultado = "$db->error";
                            }
                        }
                    }
                }

                $j++;
            }

            $result = array(
                "status" => "success",
                "cobro" => [
                    "nombre" => "{$data["descripcion"]}"
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

$app->run();
<?php
        
require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

$authLlave = function( $route ) use ( $app ) {
    $params = $route -> getParams();
    echo $params['llave'].'<br />';
};

include('config.php');

    // $app->get("/pagos/:llave", $authLlave, function($llave) use($db, $app) {
    $app->get("/pagos", function() use($db, $app) {
        $query = $db->query("SELECT * FROM `pagos` ORDER BY registro DESC");
        $pagos = array();

        $i = 0;

        while ($fila = $query->fetch_assoc()) {
            $pagos[$i] = $fila;

            $query2 = $db->query("SELECT * FROM bancos WHERE id_banco = '". $fila['banco_id'] . "'");
			while ($fila2 = $query2->fetch_assoc()) {
				$pagos[$i]['banco_id'] = array(
					"id_banco" => $fila2['id_banco'],
					"nombre" => $fila2['nombre']
				);
            }

            $query3 = $db->query("SELECT * FROM inmuebles WHERE id_inmueble = '". $fila['inmueble_id'] . "'");
			while ($fila3 = $query3->fetch_assoc()) {
				$pagos[$i]['inmueble_id'] = array(
					"id_inmueble" => $fila3['id_inmueble'],
					"casa" => $fila3['casa']
				);
            }

            $query4 = $db->query("SELECT * FROM cuentas WHERE id_cuenta = '". $fila['cuenta_id'] . "'");
			while ($fila4 = $query4->fetch_assoc()) {
				$pagos[$i]['cuenta_id'] = array(
					"id_cuenta" => $fila4['id_cuenta'],
					"numero" => $fila4['numero']
				);
            }
            
            $i++;
        }

        $result = array(
            "status" => "success",
            "pagos" => $pagos
        );

        echo json_encode($result);
    });

    $app->post("/pago", function() use ($db, $app) {	

        $json = $app->request->post("json");
        $data = json_decode($json, true);

        $fecha1 = date('d-m-Y');
        $fecha2 = date('h:i:s');

        $fecha_act = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO pagos VALUES (NULL,"
                . "'{$data["monto"]}',"
                . "'{$data["cedula"]}',"
                . "'{$data["fecha_realizada"]}',"
                . "'{$data["referencia"]}', "
                . "'$fecha_act', "
                . "'0', "
                . "{$data["inmueble_id"]},"
                . "{$data["banco_id"]},"
                . "{$data["cuenta_id"]}"
                . ")";
        
        $insert = $db->query($query);
        
        if ($insert) {

            $query3 = $db->query("SELECT * FROM inmuebles WHERE id_inmueble = '". $data['inmueble_id'] . "'");
            while ($fila3 = $query3->fetch_assoc()) {
                $saldo = $fila3['saldo'];
            }

            $query4 = "INSERT INTO transacciones VALUES (NULL,"
                    . "'Notificación de Pago',"
                    . "'1',"                    
                    . "'$fecha_act', "
                    . "'0', "
                    . "'0', "
                    . "{$saldo},"
                    . "{$data['inmueble_id']}"
                    . ")";
            
            $insert4 = $db->query($query4);
            
            if ($insert4) { }

            // $query3 = $db->query("SELECT * FROM `cuentas` WHERE id_cuenta = '". $data['cuenta_id'] . "'");
            // $banco = array();
            // $cuenta[0] = $query3->fetch_assoc();

            // $query4 = $db->query("SELECT * FROM `inmuebles` WHERE id_inmueble = '". $data['inmueble_id'] . "'");
            // $banco = array();
            // $inmueble[0] = $query4->fetch_assoc();

            // $query5 = $db->query("SELECT * FROM `bancos` WHERE id_banco = '". $data['banco_id'] . "'");
            // $banco = array();
            // $banco[0] = $query5->fetch_assoc();

            // $pago_f = number_format($data["monto"], 2, ',', '.');

            // $query2 = $db->query("SELECT * FROM `residentes` WHERE inmueble_id = '". $data['inmueble_id'] . "'");
            // $residente = array();
    
            // $i = 0;    
            // while ($fila2 = $query2->fetch_assoc()) {
            //     $residente[$i] = $fila2;
            
            //     $para = $residente[$i]['correo'];

            //     $título = 'Notificación de Pago Enviada desde el Sistema de Condominio de Mene Grande';

            //     $mensaje = '
            //         <img src="http://i64.tinypic.com/28bvhu8.png" width="100%">
            //         <p>Propietario de inmueble: <b>'. $inmueble[0]['casa'] .'</b></p>
            //         <p>Le informamos que el '. $fecha1 .' a las '. $fecha2 .' ha sido notificada una transacción por concepto de abono en cuenta desde el Sistema de Condominio Mene Grande según las siguientes características:</p>
            //         <br>
            //         <table>
            //             <tr>
            //                 <td>Banco de Origen de Fondos:</td>
            //                 <td>'. $banco[0]['nombre'] .'</td>
            //             </tr>
            //             <tr>
            //                 <td>Cédula de titular:</td>
            //                 <td>V-'. $data["cedula"] .'</td>
            //             </tr>
            //             <tr>
            //                 <td>Cuenta de destino:</td>
            //                 <td>'. $cuenta[0]['numero'] .'</td>
            //             </tr>
            //             <tr>
            //                 <td>Monto del abono:</td>
            //                 <td>Bs. '. $pago_f .'</td>
            //             </tr>
            //             <tr>
            //                 <td>Nro de referencia:</td>
            //                 <td>'. $data["referencia"] .'</td>
            //             </tr>
            //             <tr>
            //                 <td>Fecha de transacción:</td>
            //                 <td>'. $data["fecha_realizada"] .'</td>
            //             </tr>
            //         </table>
            //         <br>
            //         <p>Las Transferencias y/o pagos a Terceros hacia otro banco realizadas antes de las 9:00 a.m. de un día hábil serán abonadas antes de las 12 m del mismo día, mientras que las transacciones realizadas después de las 9:00 a.m. se abonarán el día hábil siguiente. El tiempo que tome el Banco destino en realizar el abono no es responsabilidad del Tesorero que maneja el Sistema.</p>
            //         <p>Si desconoces esta operación o tienes alguna duda, comunícate con nosotros a través del Sistema de Condominio de Mene Grande a través del telefóno (Telf.: 0426-285.8771) donde gustosamente te atenderemos.</p>
            //         <p>En Sistema de Condominio Mene Grande trabajamos continuamente para ofrecer innovadores productos y servicios, así como una excelente calidad de atención que permita a nuestros clientes rentabilizar sus inversiones y hacer realidad sus controles financieros.</p>
            //     ';

            //     $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
            //     $cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";

            //     $cabeceras .= 'To: '.$inmueble[$i]['nombre'].' <'.$inmueble[$i]['correo'].'>' . "\r\n";
            //     $cabeceras .= 'From: Sistema de Condominio Mene Grande <avisos@softwareg.com.ve>' . "\r\n";
            //     $cabeceras .= 'Cc: avisos@softwareg.com.ve' . "\r\n";
            //     $cabeceras .= 'Bcc: avisos@softwareg.com.ve' . "\r\n";

            //     // Enviarlo
            //     if (mail($para, $título, $mensaje, $cabeceras)) {
            //          $mailSms = "Excelente";
            //      } else {
            //          $mailSms = error_get_last()['message'];
            //      }
            // }            

            $result = array(
                "status" => "success",
                // "mensaje" => "$mensaje",
                // "mailSms" => "$mailSms",
                "pago" => [
                    "nombre" => "{$data["referencia"]}"
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

    $app->get("/pago/:id", function($id) use($db, $app) {
        $query = $db->query("SELECT * FROM pagos WHERE id_pago = $id");   

        $i = 0;

        $fila = $query->fetch_assoc();

        $pago[$i] = $fila;

        $query2 = $db->query("SELECT * FROM bancos WHERE id_banco = '". $fila['banco_id'] . "'");
        while ($fila2 = $query2->fetch_assoc()) {
            $pago[$i]['banco_id'] = array(
                "id_banco" => $fila2['id_banco'],
                "nombre" => $fila2['nombre']
            );
        }

        $query3 = $db->query("SELECT * FROM inmuebles WHERE id_inmueble = '". $fila['inmueble_id'] . "'");
        while ($fila3 = $query3->fetch_assoc()) {
            $pago[$i]['inmueble_id'] = array(
                "id_inmueble" => $fila3['id_inmueble'],
                "casa" => $fila3['casa']
            );
        }

        $query4 = $db->query("SELECT * FROM cuentas WHERE id_cuenta = '". $fila['cuenta_id'] . "'");
        while ($fila4 = $query4->fetch_assoc()) {
            $pago[$i]['cuenta_id'] = array(
                "id_cuenta" => $fila4['id_cuenta'],
                "numero" => $fila4['numero'],
                "nombre" => $fila4['nombre']
            );
        }        

        $result = array(
            "status" => "success",
            "pago" => $pago[$i]
        );

        echo json_encode($result);
    });

    $app->put("/pago/:id", function($id) use($db, $app) {

        $fecha_act = date('Y-m-d H:i:s');

        $json = $app->request->post("json");
        $data = json_decode($json, true);

        $query = "UPDATE pagos SET "
                . "estatus = '{$data["estatus"]}' "
                . " WHERE id_pago =  $id ";
        $update = $db->query($query);

        $query2 = $db->query("SELECT * FROM pagos WHERE id_pago = $id");
        $fila2 = $query2->fetch_assoc();

        $id_inmueble = $fila2['inmueble_id'];

        $query5 = $db->query("SELECT * FROM inmuebles WHERE id_inmueble = $id_inmueble");
        $fila5 = $query5->fetch_assoc();

        $saldo = $fila5['saldo'];

        $monto_p = $fila2['monto'];

        if ($update) {           

            $nuevoSaldo = $saldo + $monto_p;

            $query4 = "INSERT INTO transacciones VALUES (NULL,"
                    . "'Aprobación de Pago',"
                    . "'2',"                    
                    . "'$fecha_act', "
                    . "'0', "
                    . "{$monto_p}, "
                    . "{$nuevoSaldo},"
                    . "{$fila2['inmueble_id']}"
                    . ")";
            
            $insert4 = $db->query($query4);
            
            if ($insert4) {
                $query7 = "UPDATE inmuebles SET "
                        . "saldo = '{$nuevoSaldo}' "
                        . " WHERE id_inmueble =  $id_inmueble ";
                $update7 = $db->query($query7);

                if ($update7) {
                    $resultados = $nuevoSaldo;
                }
            }            
            
            if ($data['estatus'] == '1') {
                $query3 = $db->query("SELECT * FROM cobro_inmueble WHERE estatus = '2' AND inmueble_id = '". $id_inmueble . "' ORDER BY id_cobro_inmueble ASC");

                if ($query3->num_rows > 0) {
                    while ($fila3 = $query3->fetch_assoc()) {
                        unset($id_documento);

                        $dependencia_cobro = '0';

                        $id_cobro_inmueble = $fila3['id_cobro_inmueble'];
                        $pagado = $fila3['pagado'];
                        $id_cobro = $fila3['cobro_id'];                                       
    
                        $query4 = $db->query("SELECT * FROM cobros WHERE id_cobro = $id_cobro");
                        $fila4 = $query4->fetch_assoc();

                        $dependencia_cobro = $fila4['dependencia_cobro'];    
                        $monto_c = $fila4['monto'];
    
                        $restante = $monto_c - $pagado;                    
    
                        if ($restante > $monto_p) {
                            $nuevo_pagado = $pagado + $monto_p;  
                            
                            $query6 = "UPDATE cobro_inmueble SET "
                                    . "pagado = '{$nuevo_pagado}' "
                                    . " WHERE id_cobro_inmueble =  $id_cobro_inmueble ";
                            $update6 = $db->query($query6);                                                   
    
                            if ($update6) {

                                if ($dependencia_cobro == '0') {
                                    $query8 = $db->query("SELECT * FROM documento WHERE cobro_id = $id_cobro AND inmueble_id = $id_inmueble");
                                    $fila8 = $query8->fetch_assoc();
    
                                    if ($query8->num_rows > 0) {
                                        $id_documento = $fila8['id_documento'];
    
                                        $query9 = "INSERT INTO documento_pago VALUES (NULL,"
                                            . "'$id_documento',"
                                            . "'$monto_p',"
                                            . "'$fecha_act'"
                                            . ")";
    
                                        $insert9 = $db->query($query9);
    
                                        if ($insert9) { }
                                    } else {
                                        $id_cobro_sistema = $id_cobro + 1;
    
                                        $query11 = "INSERT INTO documento VALUES (NULL,"
                                            . "'$id_cobro',"
                                            . "'$id_cobro_sistema',"
                                            . "'$fecha_act',"
                                            . "'$id_inmueble',"
                                            . "'0', "
                                            . "'2'"
                                            . ")";
    
                                        $insert11 = $db->query($query11);
    
                                        if ($insert11) {
                                            $id_documento = $db->insert_id;
    
                                            $query12 = "INSERT INTO documento_pago VALUES (NULL,"
                                                . "'$id_documento',"
                                                . "'$nuevo_pagado',"
                                                . "'$fecha_act'"
                                                . ")";
    
                                            $insert12 = $db->query($query12);
                                        }
                                    }  
                                } else {
                                    $query14 = $db->query("SELECT * FROM documento WHERE cobro_id = $id_cobro AND inmueble_id = $id_inmueble");
                                    $fila14 = $query14->fetch_assoc();
    
                                    if ($query14->num_rows > 0) {
                                        $id_documento = $fila14['id_documento'];

                                        $query13 = "INSERT INTO documento_pago VALUES (NULL,"
                                            . "'$id_documento',"
                                            . "'$nuevo_pagado',"
                                            . "'$fecha_act'"
                                            . ")";

                                        $insert13 = $db->query($query13);
                                    }
                                }                                
                            }
    
                            break;
                        } else {
                            $nuevo_pagado = $monto_c;
                            $monto_p = $monto_p - $restante;
    
                            $query6 = "UPDATE cobro_inmueble SET "
                                    . "estatus = '1', "
                                    . "pagado = '{$nuevo_pagado}' "
                                    . " WHERE id_cobro_inmueble =  $id_cobro_inmueble ";
                            $update6 = $db->query($query6);
    
                            if ($update6) {

                                if ($dependencia_cobro == '0') {
                                    $query8 = $db->query("SELECT * FROM documento WHERE cobro_id = $id_cobro AND inmueble_id = $id_inmueble");
                                    $fila8 = $query8->fetch_assoc();
    
                                    if ($query8->num_rows > 0) {
                                        $id_documento = $fila8['id_documento'];
    
                                        $query9 = "INSERT INTO documento_pago VALUES (NULL,"
                                            . "'$id_documento',"
                                            . "'$restante',"
                                            . "'$fecha_act'"
                                            . ")";
    
                                        $insert9 = $db->query($query9);
    
                                        if ($insert9) { }
                                    } else {
                                        $id_cobro_sistema = $id_cobro + 1;
    
                                        $query11 = "INSERT INTO documento VALUES (NULL,"
                                            . "'$id_cobro',"
                                            . "'$id_cobro_sistema',"
                                            . "'$fecha_act',"
                                            . "'$id_inmueble',"
                                            . "'0', "
                                            . "'2'"
                                            . ")";
    
                                        $insert11 = $db->query($query11);
    
                                        if ($insert11) {
                                            $id_documento = $db->insert_id;
    
                                            $query12 = "INSERT INTO documento_pago VALUES (NULL,"
                                                . "'$id_documento',"
                                                . "'$nuevo_pagado',"
                                                . "'$fecha_act'"
                                                . ")";
    
                                            $insert12 = $db->query($query12);
                                        }
                                    }  
                                } else {

                                    $query13 = $db->query("SELECT * FROM documento WHERE cobro_sistema_id = $id_cobro AND inmueble_id = $id_inmueble");
                                    $fila13 = $query13->fetch_assoc();
    
                                    if ($query13->num_rows > 0) {
                                        $id_documento = $fila13['id_documento'];

                                        $query12 = "INSERT INTO documento_pago VALUES (NULL,"
                                            . "'$id_documento',"
                                            . "'$restante',"
                                            . "'$fecha_act'"
                                            . ")";

                                        $insert12 = $db->query($query12);

                                        if ($insert12) {
                                            $query10 = "UPDATE documento SET "
                                                    . "estatus = '1' "
                                                    . " WHERE id_documento =  $id_documento ";
                                            $update10 = $db->query($query10);
    
                                            if ($update10) {
                                                $URL = "http://localhost/pdf/ejemplo.php?doc=".$id_documento;
                                                $result = file_get_contents($URL);
                                            }
                                        }
                                    }
                                }                     
                            }  
                            
                            if ($monto_p == '0') { break; }

                        }
                    }                
                } else {
                    
                }
            }		    

            $result = array(
                "status" => "success", 
                "message" => "El pago fue rechazado correctamente"
            );
            
        } else {
            $result = array(
                "status" => "error", 
                "titulo" => "No se pudo actualizar el pago :/",
                "message" => "$db->error", 
                "query" => "$query"
            );
        }

        echo json_encode($result);
    });

$app->run();
<?php
include 'digito_verificador.php';
class xml{
	public function xmlFactura($fecha,$correo,$secuencial,$codigo,$cantidad,$descripcion,$preciou,$descuento,$preciot,$subtotal,$iva12,$total,$numero_cedula_receptor,$nombres_receptor,$nombre_empresa,$direccion,$tipo_identificacion,$estableciminento_f,$punto_emision_f,$porcentaje_iva_f,$numero_identidad_emisor,$contabilidad,$codigo_formas_pago,$iduser,$tipo_ambiente,$codigos_impuestos){
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->formatOutput = true;
		   include "../../../../coneccion.php";

        mysqli_set_charset($conection, 'utf8'); //linea a colocar

           $query_resultados_emmisor = mysqli_query($conection,"SELECT * FROM comprobantes
     WHERE id_emisor= '$iduser'");
     $data__emmisor=mysqli_fetch_array($query_resultados_emmisor);
     $id_receptor        = $data__emmisor['id_receptor'];
     $nombres_receptor   = $data__emmisor['nombres_receptor'];


				 $query_lista_t = mysqli_query($conection,"SELECT SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as
				 'compra_total', SUM(((comprobantes.iva_producto))) AS 'iva_general',
				 SUM(((comprobantes.precio_neto)+(comprobantes.iva_producto))) AS 'precioncluido_iva', SUM(((comprobantes.descuento))) AS 'descuento_general'
				 FROM `comprobantes`
				 WHERE comprobantes.id_emisor = '$iduser'  ");
				 $data_lista_t=mysqli_fetch_array($query_lista_t);
				 $compra_total_dt = round(($data_lista_t['compra_total']),2);
				 $iva_general_dt = round(($data_lista_t['iva_general']),2);
				 $descuento_general = round(($data_lista_t['descuento_general']),2);
		//PRIMERA PARTE
		$xml_fac = $xml->createElement('factura');
		$cabecera = $xml->createAttribute('id');
		$cabecera->value = 'comprobante';
		$cabecerav = $xml->createAttribute('version');
		$cabecerav->value = '1.0.0';
		$xml_inf = $xml->createElement('infoTributaria');
		$xml_amb = $xml->createElement('ambiente','2');
		$xml_tip = $xml->createElement('tipoEmision','1');
		$xml_raz = $xml->createElement('razonSocial',$nombre_empresa);
		$xml_nom = $xml->createElement('nombreComercial',$nombre_empresa);
		$xml_ruc = $xml->createElement('ruc',$numero_identidad_emisor);
		$fecha_actual = date("d-m-Y");
		$fechasf =  str_replace("-","",date("d-m-Y",strtotime($fecha_actual." -0 hours")));
		$dig = new modulo();
		$clave_acceso= $fechasf.'01'.$numero_identidad_emisor.'2'.$estableciminento_f.$punto_emision_f.$secuencial.'123456781';
		$clave_acceso =  str_replace(" ","",$clave_acceso);
		$xml_cla = $xml->createElement('claveAcceso',$clave_acceso.$dig->getMod11Dv($clave_acceso));
		$xml_doc = $xml->createElement('codDoc','01');
		$xml_est = $xml->createElement('estab', $estableciminento_f);
		$xml_emi = $xml->createElement('ptoEmi', $punto_emision_f);
		$xml_sec = $xml->createElement('secuencial',$secuencial);
		$xml_dir = $xml->createElement('dirMatriz',$direccion);

		//SEGUNDA PARTE
		$xml_def = $xml->createElement('infoFactura');
		$xml_fec = $xml->createElement('fechaEmision',$fecha);
		$xml_des = $xml->createElement('dirEstablecimiento',$direccion);
		//$xml_con = $xml->createElement('contribuyenteEspecial','NO');
		$xml_obl = $xml->createElement('obligadoContabilidad',$contabilidad);
		$xml_ide = $xml->createElement('tipoIdentificacionComprador',$tipo_identificacion);
		$xml_rco = $xml->createElement('razonSocialComprador',$nombres_receptor);
		$xml_idc = $xml->createElement('identificacionComprador',$numero_cedula_receptor);
		$xml_tsi = $xml->createElement('totalSinImpuestos',$preciot-$descuento_general);
		$xml_tds = $xml->createElement('totalDescuento',$descuento_general);

		$xml_imp = $xml->createElement('totalConImpuestos');
		$b = 1;
		$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '2'");
		$result_lista= mysqli_num_rows($query_protector);
		  if ($result_lista > 0) {
				$query_resultados = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.12*(comprobantes.valor_unidad))) as 'iva',SUM(((comprobantes.descuento))) as 'descuento_grupo'    FROM comprobantes
				WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '2'  ");
				$b = 1;
	 	while ($resultados = mysqli_fetch_array($query_resultados)) {
			$xml_tim[$b] = $xml->createElement('totalImpuesto');
			$xml_tco[$b] = $xml->createElement('codigo',$resultados['codigos_impuestos']);
			$xml_cpr[$b] = $xml->createElement('codigoPorcentaje','2');//aqui va si es que va con el 0% de iva o el 12% iva
			$xml_bas[$b] = $xml->createElement('baseImponible',round($resultados['base_imponible']-$resultados['descuento_grupo'],2));
			$xml_val[$b] = $xml->createElement('valor',round($resultados['iva'],2));
			$b = $b+1;

    }
		}

		$t = $b;

		$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '0'");
		$result_lista= mysqli_num_rows($query_protector);
		 if ($result_lista > 0) {
			 $query_resultados2 = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.0*(comprobantes.valor_unidad))) as 'iva',SUM(((comprobantes.descuento))) as 'descuento_grupo'     FROM comprobantes
			 WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '0'  ");
		while ($resultados = mysqli_fetch_array($query_resultados2)) {
			$xml_tim[$t] = $xml->createElement('totalImpuesto');
			$xml_tco[$t] = $xml->createElement('codigo',$resultados['codigos_impuestos']);
			$xml_cpr[$t] = $xml->createElement('codigoPorcentaje','0');//aqui va si es que va con el 0% de iva o el 12% iva
			$xml_bas[$t] = $xml->createElement('baseImponible',round($resultados['base_imponible']-$resultados['descuento_grupo'],2));
			$xml_val[$t] = $xml->createElement('valor',round($resultados['iva'],2));
			$t = $t+1;
      }
		}

		$h = $t;

		$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '6'");
		$result_lista= mysqli_num_rows($query_protector);
		 if ($result_lista > 0) {
			 $query_resultados2 = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.0*(comprobantes.valor_unidad))) as 'iva',SUM(((comprobantes.descuento))) as 'descuento_grupo'     FROM comprobantes
			 WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '6'  ");
		while ($resultados = mysqli_fetch_array($query_resultados2)) {
			$xml_tim[$h] = $xml->createElement('totalImpuesto');
			$xml_tco[$h] = $xml->createElement('codigo',$resultados['codigos_impuestos']);
			$xml_cpr[$h] = $xml->createElement('codigoPorcentaje','6');//aqui va si es que va con el 0% de iva o el 12% iva
			$xml_bas[$h] = $xml->createElement('baseImponible',round($resultados['base_imponible']-$resultados['descuento_grupo'],2));
			$xml_val[$h] = $xml->createElement('valor',round($resultados['iva'],2));
			$h = $h+1;
			}
		}


		$hg = $h;

		$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '7'");
		$result_lista= mysqli_num_rows($query_protector);
		 if ($result_lista > 0) {
			 $query_resultados2 = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.0*(comprobantes.valor_unidad))) as 'iva',SUM(((comprobantes.descuento))) as 'descuento_grupo'     FROM comprobantes
			 WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '7'  ");
		while ($resultados = mysqli_fetch_array($query_resultados2)) {
			$xml_tim[$hg] = $xml->createElement('totalImpuesto');
			$xml_tco[$hg] = $xml->createElement('codigo',$resultados['codigos_impuestos']);
			$xml_cpr[$hg] = $xml->createElement('codigoPorcentaje','7');//aqui va si es que va con el 0% de iva o el 12% iva
			$xml_bas[$hg] = $xml->createElement('baseImponible',round($resultados['base_imponible']-$resultados['descuento_grupo'],2));
			$xml_val[$hg] = $xml->createElement('valor',round($resultados['iva'],2));
			$hg = $hg+1;
			}
		}




		//PARTE 2.3
		$xml_pro = $xml->createElement('propina','0.00');
		$xml_imt = $xml->createElement('importeTotal',round((($compra_total_dt+$iva_general_dt)-$descuento_general),2));
		$xml_mon = $xml->createElement('moneda','DOLAR');


		//PARTE PAGOS
		$xml_pgs = $xml->createElement('pagos');
		$xml_pag = $xml->createElement('pago');
		$xml_fpa = $xml->createElement('formaPago',$codigo_formas_pago);
		$xml_tot = $xml->createElement('total',round(($total-$descuento_general),2));
		$xml_pla = $xml->createElement('plazo','1');
		$xml_uti = $xml->createElement('unidadTiempo','dias');



		$xml_dts = $xml->createElement('detalles');
		$query_resultados = mysqli_query($conection,"SELECT * FROM comprobantes
			WHERE id_emisor= '$iduser'");
		$a = 1;
	 				while ($resultados = mysqli_fetch_array($query_resultados)) {
							$xml_det[$a] = $xml->createElement('detalle');
							$xml_cop[$a] = $xml->createElement('codigoPrincipal',$resultados['id_producto']);
							$xml_dcr[$a] = $xml->createElement('descripcion',($resultados['descripcion_producto']));
							$xml_can[$a] = $xml->createElement('cantidad',$resultados['cantidad_producto']);
							$xml_pru[$a] = $xml->createElement('precioUnitario',round($resultados['valor_unidad'],2));
							$xml_dsc[$a] = $xml->createElement('descuento',round($resultados['descuento'],2));
							$xml_tsm[$a] = $xml->createElement('precioTotalSinImpuesto',round((($resultados['cantidad_producto']*$resultados['valor_unidad'])-$resultados['descuento']),2));
							$xml_ips[$a] = $xml->createElement('impuestos');
							$xml_ipt[$a] = $xml->createElement('impuesto');
							$xml_cdg[$a] = $xml->createElement('codigo',$resultados['codigos_impuestos']);
							$xml_cpt[$a] = $xml->createElement('codigoPorcentaje',$resultados['tipo_ambiente']);
							if ($resultados['tipo_ambiente'] == 2) {
								$tarifa = 12;
							}else {
								$tarifa= 0;
							}

							$xml_trf[$a] = $xml->createElement('tarifa',$tarifa);
							$xml_bsi[$a] = $xml->createElement('baseImponible',round((($resultados['cantidad_producto']*$resultados['valor_unidad'])-$resultados['descuento']),2));
							$xml_vlr[$a] = $xml->createElement('valor',round((($resultados['cantidad_producto']*$resultados['valor_unidad'])*$tarifa/100),2));

							$a = $a +1;
	 				}

		//INFO ADICIONAL
		$xml_ifa = $xml->createElement('infoAdicional');
		$xml_cp1 = $xml->createElement('campoAdicional',$correo);
		$atributo = $xml->createAttribute('nombre');
		$atributo->value = 'email';

		 //PRIMERA PARTE
		$xml_inf->appendChild($xml_amb);
		$xml_inf->appendChild($xml_tip);
		$xml_inf->appendChild($xml_raz);
		$xml_inf->appendChild($xml_nom);
		$xml_inf->appendChild($xml_ruc);
		$xml_inf->appendChild($xml_cla);
		$xml_inf->appendChild($xml_doc);
		$xml_inf->appendChild($xml_est);
		$xml_inf->appendChild($xml_emi);
		$xml_inf->appendChild($xml_sec);
		$xml_inf->appendChild($xml_dir);
		$xml_fac->appendChild($xml_inf);

		//SEGUNDA PARTE
		$xml_def->appendChild($xml_fec);
		$xml_def->appendChild($xml_des);
		//$xml_def->appendChild($xml_con);
		$xml_def->appendChild($xml_obl);
		$xml_def->appendChild($xml_ide);
		$xml_def->appendChild($xml_rco);
		$xml_def->appendChild($xml_idc);
		$xml_def->appendChild($xml_tsi);
		$xml_def->appendChild($xml_tds);
		$xml_def->appendChild($xml_imp);

		$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '2' ");
		$result_lista= mysqli_num_rows($query_protector);
				$b = 1;
				if ($result_lista > 0) {
					$query_resultados = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.12*(comprobantes.valor_unidad))) as 'iva'    FROM comprobantes
					WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '2'  ");
					while ($resultados = mysqli_fetch_array($query_resultados)) {
						$xml_imp->appendChild($xml_tim[$b]);
						$xml_tim[$b]->appendChild($xml_tco[$b]);
						$xml_tim[$b]->appendChild($xml_cpr[$b]);
						$xml_tim[$b]->appendChild($xml_bas[$b]);
						$xml_tim[$b]->appendChild($xml_val[$b]);
							$b = $b+1;
					}
					}
					$t = $b;

					$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '0' ");
					$result_lista= mysqli_num_rows($query_protector);
							if ($result_lista > 0) {
								$query_resultados = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.12*(comprobantes.valor_unidad))) as 'iva'    FROM comprobantes
								WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '0'  ");
								while ($resultados = mysqli_fetch_array($query_resultados)) {
									$xml_imp->appendChild($xml_tim[$t]);
									$xml_tim[$t]->appendChild($xml_tco[$t]);
									$xml_tim[$t]->appendChild($xml_cpr[$t]);
									$xml_tim[$t]->appendChild($xml_bas[$t]);
									$xml_tim[$t]->appendChild($xml_val[$t]);
										$t = $t+1;
								}
							}

							$h = $t;

							$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '6' ");
							$result_lista= mysqli_num_rows($query_protector);
									if ($result_lista > 0) {
										$query_resultados = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.12*(comprobantes.valor_unidad))) as 'iva'    FROM comprobantes
										WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '6'  ");
										while ($resultados = mysqli_fetch_array($query_resultados)) {
											$xml_imp->appendChild($xml_tim[$h]);
											$xml_tim[$h]->appendChild($xml_tco[$h]);
											$xml_tim[$h]->appendChild($xml_cpr[$h]);
											$xml_tim[$h]->appendChild($xml_bas[$h]);
											$xml_tim[$h]->appendChild($xml_val[$h]);
												$h = $h+1;
										}
									}

									$hg = $h;

									$query_protector = mysqli_query($conection,"SELECT*FROM comprobantes WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '7' ");
									$result_lista= mysqli_num_rows($query_protector);
											if ($result_lista > 0) {
												$query_resultados = mysqli_query($conection,"SELECT comprobantes.id_emisor,comprobantes.codigos_impuestos,SUM(((comprobantes.cantidad_producto)*(comprobantes.valor_unidad))) as 'base_imponible',SUM(((comprobantes.cantidad_producto)*0.12*(comprobantes.valor_unidad))) as 'iva'    FROM comprobantes
												WHERE id_emisor= '$iduser' AND comprobantes.tipo_ambiente = '7'  ");
												while ($resultados = mysqli_fetch_array($query_resultados)) {
													$xml_imp->appendChild($xml_tim[$hg]);
													$xml_tim[$hg]->appendChild($xml_tco[$hg]);
													$xml_tim[$hg]->appendChild($xml_cpr[$hg]);
													$xml_tim[$hg]->appendChild($xml_bas[$hg]);
													$xml_tim[$hg]->appendChild($xml_val[$hg]);
														$hg = $hg+1;
												}
											}


		$xml_fac->appendChild($xml_def);



		//SEGUNDA PARTE 2.3

		$xml_def->appendChild($xml_pro);
		$xml_def->appendChild($xml_imt);
		$xml_def->appendChild($xml_mon);



		$xml_def->appendChild($xml_pgs);
		$xml_pgs->appendChild($xml_pag);
		$xml_pag->appendChild($xml_fpa);
		$xml_pag->appendChild($xml_tot);
		$xml_pag->appendChild($xml_pla);
		$xml_pag->appendChild($xml_uti);

		$xml_fac->appendChild($xml_dts);
		$query_resultados = mysqli_query($conection,"SELECT * FROM comprobantes
			WHERE id_emisor= '$iduser'  ");
							 $a = 1;
					$result_lista= mysqli_num_rows($query_resultados);
					 if ($result_lista > 0) {
					while ($resultados = mysqli_fetch_array($query_resultados)) {
						$xml_dts->appendChild($xml_det[$a]);
						$xml_det[$a]->appendChild($xml_cop[$a]);
						$xml_det[$a]->appendChild($xml_dcr[$a]);
						$xml_det[$a]->appendChild($xml_can[$a]);
						$xml_det[$a]->appendChild($xml_pru[$a]);
						$xml_det[$a]->appendChild($xml_dsc[$a]);
						$xml_det[$a]->appendChild($xml_tsm[$a]);
						$xml_det[$a]->appendChild($xml_ips[$a]);
						$xml_ips[$a]->appendChild($xml_ipt[$a]);
						$xml_ipt[$a]->appendChild($xml_cdg[$a]);
						$xml_ipt[$a]->appendChild($xml_cpt[$a]);
						$xml_ipt[$a]->appendChild($xml_trf[$a]);
						$xml_ipt[$a]->appendChild($xml_bsi[$a]);
						$xml_ipt[$a]->appendChild($xml_vlr[$a]);
							$a = $a+1;
					}
					}



		$xml_fac->appendChild($xml_ifa);
		$xml_ifa->appendChild($xml_cp1);
		$xml_cp1->appendChild($atributo);





		$xml_fac->appendChild($cabecera);
		$xml_fac->appendChild($cabecerav);
		$xml->appendChild($xml_fac);


		//$xml->save("file.xml");
		//print_r ($xml->saveXML());
		$xml->save('../comprobantes/no_firmados/'.$iduser.'.xml');


		//"./no_firmado/".$xml_cla.".xml"



	}
}




?>

<?php
$ruta = explode("/", $_GET['views']);
if (!isset($ruta[1]) || $ruta[1] == "") {
    header("location:" . BASE_URL . "movimientos");
}

// =================== INICIA cURL ===================
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Movimiento.php?tipo=buscar_movimento_id&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&data=" . $ruta[1],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "x-rapidapi-host: " . BASE_URL_SERVER,
        "x-rapidapi-key: XXXX"
    ),
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
// =================== FIN cURL ===================

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

    // ========= Clase personalizada con header + footer =========
    class MYPDF extends TCPDF {
        public function Header() {
            $logoIzq = __DIR__ . '/../../public/assets/img/logo_ayacucho_der.png';
            $logoDer = __DIR__ . '/../../public/assets/img/logo_ayacucho_izq.png';
            // Imágenes ajustadas al mismo tamaño
            $this->Image($logoIzq, 10, 10, 25, 25, '', '', '', false, 300);
            $this->Image($logoDer, 175, 10, 25, 25, '', '', '', false, 300);
            // Encabezado institucional
            $this->SetY(12);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 5, 'GOBIERNO REGIONAL DE AYACUCHO', 0, 1, 'C');
            $this->Cell(0, 5, 'DIRECCIÓN REGIONAL DE EDUCACIÓN DE AYACUCHO', 0, 1, 'C');
            $this->Cell(0, 5, 'DIRECCIÓN DE ADMINISTRACIÓN', 0, 1, 'C');
        }

        public function Footer() {
            $this->SetY(-20);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 0, '', 'T', 1, 'C');
            $this->Cell(0, 5, 'Instituto Superior Tecnológico Huanta', 0, 1, 'C');
            $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $respuesta = json_decode($response);

    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $fecha = new DateTime();
    $dia = $fecha->format('d');
    $mes = $meses[(int)$fecha->format('m')];
    $anio = $fecha->format('Y');

    $contenido_pdf = '
    <style>
       
        .titulo-principal {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
            border-bottom: 1px solid #000;
            margin-bottom: 15px;
        }
        .section {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .label {
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9.5pt;
        }
        thead {
            background-color: #f0f0f0;
        }
        th {
            border: 1px solid #444;
            padding: 6px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #666;
            padding: 5px;
            text-align: center;
        }
        .fecha {
            margin-top: 30px;
            text-align: right;
            font-style: italic;
            font-size: 10pt;
        }
        .firma-tabla {
            width: 100%;
            margin-top: 60px;
        }
        .firma-tabla td {
            width: 50%;
            text-align: center;
            border: none;
            font-size: 10pt;
            padding-top: 20px;
        }
        .firma-linea {
            display: inline-block;
            border-top: 1px solid #000;
            width: 80%;
            margin-bottom: 5px;
        }
    </style>

    <div class="titulo-principal">PAPELETA DE ROTACIÓN DE BIENES</div>

    <div class="section">
        <div><span class="label">ENTIDAD:</span> DIRECCIÓN REGIONAL DE EDUCACIÓN - AYACUCHO</div>
        <div><span class="label">ÁREA:</span> OFICINA DE ADMINISTRACIÓN</div>
        <div><span class="label">ORIGEN:</span> ' . $respuesta->amb_origen->codigo . ' - ' . $respuesta->amb_origen->detalle . '</div>
        <div><span class="label">DESTINO:</span> ' . $respuesta->amb_destino->codigo . ' - ' . $respuesta->amb_destino->detalle . '</div>
        <div><span class="label">MOTIVO (*):</span> ' . $respuesta->movimiento->descripcion . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ITEM</th>
                <th>CÓDIGO PATRIMONIAL</th>
                <th>NOMBRE DEL BIEN</th>
                <th>MARCA</th>
                <th>COLOR</th>
                <th>MODELO</th>
                <th>ESTADO</th>
            </tr>
        </thead>
        <tbody>';

    $contador = 1;
    foreach ($respuesta->detalle as $bien) {
        $contenido_pdf .= "<tr>";
        $contenido_pdf .= "<td>$contador</td>";
        $contenido_pdf .= "<td>$bien->cod_patrimonial</td>";
        $contenido_pdf .= "<td>$bien->denominacion</td>";
        $contenido_pdf .= "<td>$bien->marca</td>";
        $contenido_pdf .= "<td>$bien->color</td>";
        $contenido_pdf .= "<td>$bien->modelo</td>";
        $contenido_pdf .= "<td>$bien->estado_conservacion</td>";
        $contenido_pdf .= "</tr>";
        $contador++;
    }

    $contenido_pdf .= '
        </tbody>
    </table>
 <tr>
  <tr>
    <div class="fecha">
        Ayacucho, ' . $dia . ' de ' . $mes . ' del ' . $anio . '
    </div>

    <table class="firma-tabla">
        <tr>
            <td>
                <div class="firma-linea"></div>
                ENTREGUÉ CONFORME
            </td>
            <td>
                <div class="firma-linea"></div>
                RECIBÍ CONFORME
            </td>
        </tr>
    </table>
    ';

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('DPW');
    $pdf->SetAuthor('Ore Parejas Juan Julian');
    $pdf->SetTitle('Reporte de Movimiento');
    $pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    $pdf->writeHTML($contenido_pdf, true, false, true, false, '');
    ob_clean();
    $pdf->Output('reporte_movimiento.pdf', 'I');
}                 
?>

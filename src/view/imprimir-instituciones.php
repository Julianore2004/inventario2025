<?php
session_start();
if (!isset($_SESSION['sesion_id'])) {
    header("location:" . BASE_URL . "login");
    exit();
}

// =================== INICIA cURL ===================
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Institucion.php?tipo=reporte_general",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => array(
        'sesion' => $_SESSION['sesion_id'],
        'token' => $_SESSION['sesion_token']
    ),
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

    if ($respuesta && $respuesta->status) {
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
                font-size: 16pt;
                font-weight: bold;
                text-align: center;
                padding: 15px 0;
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
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
                font-size: 9pt;
            }
            thead {
                background-color: #f0f0f0;
            }
            th {
                border: 1px solid #444;
                padding: 8px;
                font-weight: bold;
                text-align: center;
                background-color: #e0e0e0;
            }
            td {
                border: 1px solid #666;
                padding: 6px;
                text-align: left;
                font-size: 8.5pt;
            }
            .fecha {
                margin-top: 30px;
                text-align: right;
                font-style: italic;
                font-size: 10pt;
            }
            .total-registros {
                margin-bottom: 15px;
                font-weight: bold;
                text-align: center;
                font-size: 12pt;
            }
            .numero-centro {
                text-align: center;
            }
        </style>

        <div class="titulo-principal">REPORTE GENERAL DE INSTITUCIONES EDUCATIVAS</div>

        <div class="section">
            <div><span class="label">ENTIDAD:</span> DIRECCIÓN REGIONAL DE EDUCACIÓN - AYACUCHO</div>
            <div><span class="label">ÁREA:</span> OFICINA DE ADMINISTRACIÓN</div>
            <div><span class="label">REPORTE:</span> LISTADO COMPLETO DE INSTITUCIONES REGISTRADAS</div>
        </div>

        <div class="total-registros">
            TOTAL DE INSTITUCIONES: ' . count($respuesta->contenido) . '
        </div>

        <table>
            <thead>
                <tr>
                    <th width="8%">N°</th>
                    <th width="18%">CÓDIGO MODULAR</th>
                    <th width="15%">RUC</th>
                    <th width="25%">BENEFICIARIO</th>
                    <th width="34%">NOMBRE DE LA INSTITUCIÓN</th>
                </tr>
            </thead>
            <tbody>';

        $contador = 1;
        foreach ($respuesta->contenido as $institucion) {
            $contenido_pdf .= "<tr>";
            $contenido_pdf .= "<td class='numero-centro' width='8%'>$contador</td>";
            $contenido_pdf .= "<td class='numero-centro' width='18%'>" . $institucion->cod_modular . "</td>";
            $contenido_pdf .= "<td class='numero-centro' width='15%'>" . $institucion->ruc . "</td>";
            $contenido_pdf .= "<td width='25%'>" . $institucion->beneficiario . "</td>";
            $contenido_pdf .= "<td width='34%'>" . $institucion->nombre . "</td>";
            $contenido_pdf .= "</tr>";
            $contador++;
        }

        $contenido_pdf .= '
            </tbody>
        </table>

        <div class="fecha">
            Ayacucho, ' . $dia . ' de ' . $mes . ' del ' . $anio . '
        </div>
        ';

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('DPW');
        $pdf->SetAuthor('Sistema de Inventario');
        $pdf->SetTitle('Reporte General de Instituciones');
        $pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->writeHTML($contenido_pdf, true, false, true, false, '');
        ob_clean();
        $pdf->Output('reporte_instituciones_general.pdf', 'I');
    } else {
        echo "Error: No se pudieron obtener los datos de las instituciones.";
    }
}
?>
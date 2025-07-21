<?php
$ruta = explode("/", $_GET['views']);
if (!isset($ruta[1]) || $ruta[1] == "") {
    header("location:" . BASE_URL . "instituciones");
    exit;
}

// =================== INICIA cURL ===================
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Institucion.php?tipo=buscar_institucion_id&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&data=" . $ruta[1],
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
// ========== CONTENIDO HTML ==========
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
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 10pt;
    }
    .info-table td {
        padding: 6px;
        border: 1px solid #ccc;
    }
    .info-table .label {
        font-weight: bold;
        background-color: #f0f0f0;
        width: 30%;
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

<div class="titulo-principal">FICHA DE INSTITUCIÓN</div>

<table class="info-table">
    <tr>
        <td class="label">BENEFICIARIO</td>
        <td>' . htmlspecialchars($institucion->beneficiario) . '</td>
    </tr>
    <tr>
        <td class="label">CÓDIGO MODULAR</td>
        <td>' . htmlspecialchars($institucion->cod_modular) . '</td>
    </tr>
    <tr>
        <td class="label">RUC</td>
        <td>' . htmlspecialchars($institucion->ruc) . '</td>
    </tr>
    <tr>
        <td class="label">NOMBRE</td>
        <td>' . htmlspecialchars($institucion->nombre) . '</td>
    </tr>
</table>

<div class="fecha">
    Ayacucho, ' . $dia . ' de ' . $mes . ' del ' . $anio . '
</div>

<table class="firma-tabla">
    <tr>
        <td>
            <div class="firma-linea"></div>
            RESPONSABLE DE DATOS
        </td>
        <td>
            <div class="firma-linea"></div>
            VERIFICADO POR
        </td>
    </tr>
</table>
';


$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('DPW');
$pdf->SetAuthor('Ore Parejas Juan Julian');
$pdf->SetTitle('Reporte de Usuario');
$pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();
$pdf->writeHTML($contenido_pdf, true, false, true, false, '');
ob_clean();
$pdf->Output('reporte_usuario.pdf', 'I');
?>


<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// CONSULTA A LA API
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Bien.php?tipo=listar_todos_bienes&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&ies=1",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => "GET"
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "Error cURL: $err";
    exit;
}

$json_start = strpos($response, '{');
if ($json_start !== false) {
    $clean_response = substr($response, $json_start);
} else {
    echo "No se encontró JSON válido en la respuesta";
    exit;
}

$data = json_decode($clean_response);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error al decodificar JSON: " . json_last_error_msg();
    exit;
}

if (!$data || !isset($data->status) || !$data->status) {
    echo "No se encontraron bienes o error en la respuesta.";
    if ($data && isset($data->msg)) {
        echo " Mensaje: " . $data->msg;
    }
    exit;
}

// FECHA
$meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$fecha = new DateTime();
$dia = $fecha->format('d');
$mes = $meses[(int)$fecha->format('m')];
$anio = $fecha->format('Y');

// TCPDF PERSONALIZADO
class MYPDF extends TCPDF {
    public function Header() {
        $logo_izq = 'https://oportunidadeslaborales.uladech.edu.pe/wp-content/uploads/2021/09/GOBIERNO-REGIONAL-DE-AYACUCHO.jpg';
        $logo_der = 'https://gra.regionayacucho.gob.pe/_next/image?url=%2Flogos%2Fdrea.png&w=640&q=75';
        $html = '
        <table style="width:100%;border-bottom:2px solid #333;">
            <tr>
                <td width="15%" align="center"><img src="' . $logo_izq . '" width="60"/></td>
                <td width="70%" align="center">
                    <div style="font-size:10px;"><strong>GOBIERNO REGIONAL DE AYACUCHO</strong></div>
                    <div style="font-size:12px;"><strong>DIRECCIÓN REGIONAL DE EDUCACIÓN DE AYACUCHO</strong></div>
                    <div style="font-size:8px;">DIRECCIÓN DE ADMINISTRACIÓN</div>
                </td>
                <td width="15%" align="center"><img src="' . $logo_der . '" width="60"/></td>
            </tr>
        </table>';
        $this->writeHTML($html, true, false, true, false, '');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(10, 40, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetFont('helvetica', '', 8);
$pdf->AddPage('L');

// TÍTULO
$html = "
<h2 style='text-align:center;font-size:13pt;'>INVENTARIO GENERAL DE BIENES</h2>
<p style='text-align:right;font-size:9pt;'>Ayacucho, $dia de $mes del $anio</p>";

// ESTILOS MODERNOS
$html .= '
<style>
th {
    background-color: #e6f0fa;
    font-weight: bold;
    border: 1px solid #ccc;
    text-align: center;
    vertical-align: middle;
    font-size: 7pt;
    padding: 3px;
}
td {
    border: 1px solid #ddd;
    font-size: 7pt;
    padding: 3px;
    vertical-align: middle;
    text-align: center;
}
td.left {
    text-align: left;
}
</style>

<table cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th width="3%">#</th>
            <th width="8%">Código Patrimonial</th>
            <th width="15%">Denominación</th>
            <th width="8%">Marca</th>
            <th width="8%">Modelo</th>
            <th width="8%">Tipo</th>
            <th width="6%">Color</th>
            <th width="8%">Serie</th>
            <th width="8%">Dimensiones</th>
            <th width="6%">Valor</th>
            <th width="6%">Situación</th>
            <th width="8%">Estado</th>
            <th width="8%">Ambiente</th>
        </tr>
    </thead>
    <tbody>';

// LLENADO
$contador = 1;
foreach ($data->data as $bien) {
    $html .= '<tr>';
    $html .= '<td width="3%">' . $contador . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->cod_patrimonial ?: 'S/C') . '</td>';
    $html .= '<td width="15%" class="left">' . htmlspecialchars($bien->denominacion) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->marca) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->modelo) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->tipo) . '</td>';
    $html .= '<td width="6%">' . htmlspecialchars($bien->color) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->serie) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->dimensiones) . '</td>';
    $html .= '<td width="6%">S/. ' . number_format($bien->valor, 2) . '</td>';
    $html .= '<td width="6%">' . htmlspecialchars($bien->situacion) . '</td>';
    $html .= '<td width="8%">' . htmlspecialchars($bien->estado_conservacion) . '</td>';
    $html .= '<td width="8%" class="left">' . htmlspecialchars($bien->ambiente_codigo . ' - ' . $bien->ambiente_detalle) . '</td>';
    $html .= '</tr>';
    $contador++;
}

$html .= '</tbody></table>';

// GENERAR PDF
$pdf->writeHTML($html, true, false, true, false, '');
ob_clean();
$pdf->Output("inventario-bienes-general.pdf", "I");
?>

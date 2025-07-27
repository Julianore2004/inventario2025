<?php

require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// CONSULTA A LA API
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Ambiente.php?tipo=listar_todos_ambientes&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'],
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
    echo "No se encontraron ambientes o error en la respuesta.";
    if ($data && isset($data->msg)) {
        echo " Mensaje: " . $data->msg;
    }
    exit;
}

// FECHA ACTUAL
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
        <table style="width:100%;border-bottom:1px solid #666;">
            <tr>
                <td width="15%" align="center"><img src="' . $logo_izq . '" width="50"/></td>
                <td width="70%" align="center">
                    <div style="font-size:11px;font-weight:bold;">GOBIERNO REGIONAL DE AYACUCHO</div>
                    <div style="font-size:13px;font-weight:bold;">DIRECCIÓN REGIONAL DE EDUCACIÓN DE AYACUCHO</div>
                    <div style="font-size:9px;">Dirección de Administración</div>
                </td>
                <td width="15%" align="center"><img src="' . $logo_der . '" width="50"/></td>
            </tr>
        </table><br>';
        $this->writeHTML($html, true, false, true, false, '');
    }
}

$pdf = new MYPDF();
$pdf->SetMargins(12, 40, 12);
$pdf->SetHeaderMargin(8);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetFont('helvetica', '', 9);
$pdf->AddPage('P');

// TÍTULO Y FECHA
$html = "
<h2 style='text-align:center;font-size:14pt;'>LISTADO DE AMBIENTES</h2>
<p style='text-align:right;font-size:9pt;'>Ayacucho, $dia de $mes del $anio</p>
";

// ESTILOS + TABLA CON WIDTH EN `<td>`
$html .= '
<style>
th {
    background-color: #e6f0fa;
    color: #000;
    font-weight: bold;
    border: 1px solid #ccc;
    font-size: 8.5pt;
    padding: 4px;
    text-align: center;
}
td {
    border: 1px solid #ddd;
    font-size: 8pt;
    padding: 3px;
    vertical-align: middle;
}
td.left-align {
    text-align: left;
}
td.center {
    text-align: center;
}
</style>

<table cellspacing="0" cellpadding="3" width="100%">
    <thead>
        <tr>
            <th width="6%">N°</th>
            <th width="34%">Institución Educativa</th>
            <th width="14%">Código</th>
            <th width="46%">Detalle del Ambiente</th>
        </tr>
    </thead>
    <tbody>';

// LLENAR FILAS
$contador = 1;
foreach ($data->data as $ambiente) {
    $html .= '<tr>';
    $html .= '<td width="6%" class="center">' . $contador . '</td>';
    $html .= '<td width="34%"  class="left-align">' . htmlspecialchars($ambiente->institucion_nombre ?: 'Sin institución') . '</td>';
    $html .= '<td width="14%" class="center">' . ($ambiente->codigo ?: 'S/C') . '</td>';
    $html .= '<td width="46%" class="left-align">' . htmlspecialchars($ambiente->detalle ?: 'Sin detalle') . '</td>';
    $html .= '</tr>';
    $contador++;
}

$html .= '
    </tbody>
</table>';

// RESUMEN FINAL
$total_ambientes = count($data->data);
$html .= "<br><p style='text-align:right;font-size:9pt;'><strong>Total de Ambientes: $total_ambientes</strong></p>";

// ESCRIBIR AL PDF
$pdf->writeHTML($html, true, false, true, false, '');
ob_clean();
$pdf->Output("listado-ambientes-educativos.pdf", "I");
?>

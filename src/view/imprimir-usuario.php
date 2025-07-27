<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// CONSULTA A LA API
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Usuario.php?tipo=listar_todos_usuarios&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'],
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
    echo "No se encontraron usuarios o error en la respuesta.";
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
$pdf->AddPage('P');

// TÍTULO
$html = "
<h2 style='text-align:center;font-size:13pt;'>LISTADO DE USUARIOS DEL SISTEMA</h2>
<p style='text-align:right;font-size:9pt;'>Ayacucho, $dia de $mes del $anio</p>";

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
            <th width="8%">#</th>
            <th width="15%">DNI</th>
            <th width="35%">Nombres y Apellidos</th>
            <th width="27%">Correo Electrónico</th>
            <th width="15%">Estado</th>
        </tr>
    </thead>
    <tbody>';

// LLENADO
$contador = 1;
$usuarios_activos = 0;
$usuarios_inactivos = 0;

foreach ($data->data as $usuario) {
    if ($usuario->estado == '1') {
        $estado_texto = '<span style="color:green;"><strong>ACTIVO</strong></span>';
        $usuarios_activos++;
    } elseif ($usuario->estado == '0') {
        $estado_texto = '<span style="color:red;"><strong>INACTIVO</strong></span>';
        $usuarios_inactivos++;
    } else {
        $estado_texto = '<span style="color:gray;">N/D</span>';
    }

    $html .= '<tr>';
    $html .= '<td width="8%">' . $contador . '</td>';
    $html .= '<td width="15%">' . htmlspecialchars($usuario->dni ?: 'S/DNI') . '</td>';
    $html .= '<td width="35%" class="left">' . htmlspecialchars($usuario->nombres_apellidos ?: 'Sin nombre') . '</td>';
    $html .= '<td width="27%" class="left">' . htmlspecialchars($usuario->correo ?: 'Sin correo') . '</td>';
    $html .= '<td width="15%">' . $estado_texto . '</td>';
    $html .= '</tr>';
    $contador++;
}

$html .= '</tbody></table>';

// RESUMEN FINAL
$total_usuarios = count($data->data);
$html .= "<br><br><table style='width:100%; font-size:8pt;'>
    <tr><td align='right'><strong>Total de Usuarios:</strong> $total_usuarios</td></tr>
    <tr><td align='right' style='color:green;'><strong>Usuarios Activos:</strong> $usuarios_activos</td></tr>
    <tr><td align='right' style='color:red;'><strong>Usuarios Inactivos:</strong> $usuarios_inactivos</td></tr>
</table>";

// MOSTRAR PDF
$pdf->writeHTML($html, true, false, true, false, '');
ob_clean();
$pdf->Output("listado-usuarios-sistema.pdf", "I");
?>

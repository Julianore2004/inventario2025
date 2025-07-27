<?php
session_start();
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// CONSULTA A LA API
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Movimiento.php?tipo=listar_todos_movimientos&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&ies=1",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => "GET"
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "Error: $err";
    exit;
}
$data = json_decode($response);
if (!$data->status) {
    echo "No se encontraron movimientos.";
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
$pdf->AddPage('L'); // Horizontal

// TÍTULO Y FECHA
$html = "<h2 style='text-align:center;font-size:13pt;'>REPORTE GENERAL DE MOVIMIENTOS DE BIENES</h2>";
$html .= "<p style='text-align:right;font-size:9pt;'>Ayacucho, $dia de $mes del $anio</p>";

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
            <th width="10%">Fecha</th>
            <th width="12%">Origen</th>
            <th width="12%">Destino</th>
            <th width="12%">Responsable</th>
            <th width="14%">Descripción</th>
            <th width="10%">Cod. Patrimonial</th>
            <th width="15%">Bien</th>
            <th width="6%">Marca</th>
            <th width="6%">Estado</th>
        </tr>
    </thead>
    <tbody>';

// LLENADO DE FILAS
$contador = 1;
foreach ($data->data as $mov) {
    foreach ($mov->detalle as $bien) {
        $html .= '<tr>';
        $html .= '<td width="3%">' . $contador . '</td>';
        $html .= '<td width="10%">' . htmlspecialchars($mov->movimiento->fecha_registro) . '</td>';
        $html .= '<td width="12%" class="left">' . htmlspecialchars($mov->origen->codigo . ' - ' . $mov->origen->detalle) . '</td>';
        $html .= '<td width="12%" class="left">' . htmlspecialchars($mov->destino->codigo . ' - ' . $mov->destino->detalle) . '</td>';
        $html .= '<td width="12%" class="left">' . htmlspecialchars($mov->usuario->nombres_apellidos) . '</td>';
        $html .= '<td width="14%" class="left">' . htmlspecialchars($mov->movimiento->descripcion) . '</td>';
        $html .= '<td width="10%">' . htmlspecialchars($bien->cod_patrimonial ?: 'S/C') . '</td>';
        $html .= '<td width="15%" class="left">' . htmlspecialchars($bien->denominacion) . '</td>';
        $html .= '<td width="6%">' . htmlspecialchars($bien->marca) . '</td>';
        $html .= '<td width="6%">' . htmlspecialchars($bien->estado_conservacion) . '</td>';
        $html .= '</tr>';
        $contador++;
    }
}

$html .= '</tbody></table>';

// ESCRIBIR HTML EN EL PDF
$pdf->writeHTML($html, true, false, true, false, '');
ob_clean();
$pdf->Output("reporte-movimientos-bienes.pdf", "I");
?>

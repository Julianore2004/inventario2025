<?php
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// Validar parámetro
$ruta = explode("/", $_GET['views']);
if (!isset($ruta[1]) || $ruta[1] == "") {
    header("Location:" . BASE_URL . "movimientos");
    exit();
}

// Obtener datos del movimiento vía cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Movimiento.php?tipo=buscar_movimiento_id&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&data=" . $ruta[1],
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

if ($err) {
    echo "cURL Error #:" . $err;
    exit();
}

$respueta = json_decode($response);

// Fecha formateada
$fecha_movimiento = new DateTime($respueta->movimiento->fecha_registro);
$meses_es = [
    "January" => "enero", "February" => "febrero", "March" => "marzo", "April" => "abril",
    "May" => "mayo", "June" => "junio", "July" => "julio", "August" => "agosto",
    "September" => "septiembre", "October" => "octubre", "November" => "noviembre", "December" => "diciembre"
];
$mes_actual = $fecha_movimiento->format("F");
$fecha_formateada = $fecha_movimiento->format("j") . " de " . $meses_es[$mes_actual] . " del " . $fecha_movimiento->format("Y");

// Extender la clase TCPDF para crear Header y Footer personalizados
class MYPDF extends TCPDF {
    
    // Header personalizado
    public function Header() {
        // Logo izquierdo - Logo del Gobierno Regional de Ayacucho
        $logo_izq = '../../assets/images/logo_ayacucho_izq.png'; // Ruta exacta del logo izquierdo
        if (file_exists($logo_izq)) {
            $this->Image($logo_izq, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Logo derecho - Logo de la Dirección Regional de Educación
        $logo_der = '../../assets/images/logo_ayacucho_der.png'; // Ruta exacta del logo derecho
        if (file_exists($logo_der)) {
            $this->Image($logo_der, 170, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Configurar fuente para el encabezado
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(100, 100, 100);
        
        // Título principal centrado
        $this->SetY(12);
        $this->Cell(0, 8, 'GOBIERNO REGIONAL DE AYACUCHO', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 6, 'DIRECCIÓN REGIONAL DE EDUCACIÓN DE AYACUCHO', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 6, 'DIRECCIÓN DE ADMINISTRACIÓN', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        // Línea decorativa
        $this->SetY(38);
        $this->SetDrawColor(150, 150, 150);
        $this->Line(15, 38, 195, 38);
        $this->Line(15, 39, 195, 39);
        
        // Anexo
        $this->SetY(45);
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'ANEXO - 4 -', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        // Resetear color de texto
        $this->SetTextColor(0, 0, 0);
    }
    
    // Footer personalizado
    public function Footer() {
        // Posición a 15 mm del final
        $this->SetY(-15);
        
        // Configurar fuente
        $this->SetFont('helvetica', 'I', 10);
        
        // Número de página
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Generar HTML con tabla simple
$contenido_pdf = '
<h2 style="text-align:center; text-transform:uppercase;">PAPELETA DE ROTACIÓN DE BIENES</h2>

<p><strong>ENTIDAD</strong> : DIRECCIÓN REGIONAL DE EDUCACIÓN - AYACUCHO</p>
<p><strong>ÁREA</strong> : OFICINA DE ADMINISTRACIÓN</p>
<p><strong>ORIGEN</strong> : ' . $respueta->amb_origen->codigo . ' - ' . $respueta->amb_origen->detalle . '</p>
<p><strong>DESTINO</strong> : ' . $respueta->amb_destino->codigo . ' - ' . $respueta->amb_destino->detalle . '</p>
<p><strong>MOTIVO (*)</strong> : ' . $respueta->movimiento->descripcion . '</p>

<table border="1" cellpadding="5" cellspacing="0" width="100%" style="margin-top: 10px; font-size: 12px;">
<tr style="background-color:#f0f0f0; font-weight:bold;">
    <th>ITEM</th>
    <th>CÓDIGO PATRIMONIAL</th>
    <th>NOMBRE DEL BIEN</th>
    <th>MARCA</th>
    <th>COLOR</th>
    <th>MODELO</th>
    <th>ESTADO</th>
</tr>
';

if (empty($respueta->detalle)) {
    $contenido_pdf .= '<tr><td colspan="7" style="text-align:center;">No hay movimientos para mostrar</td></tr>';
} else {
    $contador = 1;
    foreach ($respueta->detalle as $bien) {
        $contenido_pdf .= '
        <tr>
            <td>' . $contador++ . '</td>
            <td>' . $bien->cod_patrimonial . '</td>
            <td>' . $bien->denominacion . '</td>
            <td>' . $bien->marca . '</td>
            <td>' . $bien->color . '</td>
            <td>' . $bien->modelo . '</td>
            <td>' . $bien->estado_conservacion . '</td>
        </tr>';
    }
}

$contenido_pdf .= '</table>';

$contenido_pdf .= '
<p style="text-align:right; margin-top:20px;"><strong>Ayacucho, ' . $fecha_formateada . '</strong></p>

<table width="100%" style="margin-top:60px; font-size: 12px;">
<tr>
    <td style="text-align:center;">
        ------------------------------<br>ENTREGUÉ CONFORME
    </td>
    <td style="text-align:center;">
        ------------------------------<br>RECIBÍ CONFORME
    </td>
</tr>
</table>
';

// Crear el PDF usando la clase personalizada
$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ORE PAREJAS, Juan Julian');
$pdf->SetTitle('Papeleta de Rotación de Bienes');
$pdf->SetSubject('Gobierno Regional de Ayacucho');
$pdf->SetKeywords('Papeleta, Rotación, Bienes, Ayacucho');

// Configurar márgenes (considerando el header personalizado)
$pdf->SetMargins(15, 60, 15); // Margen superior aumentado para el header
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(15);

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, 25);

// Configurar fuente
$pdf->SetFont('helvetica', '', 11);

// Agregar página
$pdf->AddPage();

// Escribir el contenido HTML
$pdf->writeHTML($contenido_pdf, true, false, true, false, '');

// Salida del PDF
$pdf->Output('papeleta_rotacion.pdf', 'I');

?>
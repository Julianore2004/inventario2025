<?php
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');
require_once('../model/admin-sesionModel.php');
require_once('../model/admin-usuarioModel.php');

// Verificar sesión
session_start();
$objSesion = new SessionModel();
$objUsuario = new UsuarioModel();

$id_sesion = $_GET['sesion'];
$token = $_GET['token'];

if (!$objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
    die("Error: Sesión no válida.");
}

$id_usuario = $_GET['data'];
$usuario = $objUsuario->buscarUsuarioById($id_usuario);

if (!$usuario) {
    die("Error: Usuario no encontrado.");
}

// Clase personalizada con header y footer
class MYPDF extends TCPDF {
    public function Header() {
        $logoIzq = __DIR__ . '/../../public/assets/img/logo_ayacucho_der.png';
        $logoDer = __DIR__ . '/../../public/assets/img/logo_ayacucho_izq.png';
        $this->Image($logoIzq, 10, 10, 25, 25, '', '', '', false, 300);
        $this->Image($logoDer, 175, 10, 25, 25, '', '', '', false, 300);

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
</style>
<div class="titulo-principal">INFORMACIÓN DEL USUARIO</div>
<div class="section">
    <div><span class="label">DNI:</span> ' . $usuario->dni . '</div>
    <div><span class="label">Nombres y Apellidos:</span> ' . $usuario->nombres_apellidos . '</div>
    <div><span class="label">Correo:</span> ' . $usuario->correo . '</div>
    <div><span class="label">Teléfono:</span> ' . $usuario->telefono . '</div>
    <div><span class="label">Estado:</span> ' . ($usuario->estado == 1 ? 'Activo' : 'Inactivo') . '</div>
</div>
<div class="fecha">
    Ayacucho, ' . $dia . ' de ' . $mes . ' del ' . $anio . '
</div>
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

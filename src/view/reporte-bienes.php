<?php
$ruta = explode("/", $_GET['views']);
if (!isset($ruta[1]) || $ruta[1] == "") {
    header("location:" . BASE_URL . "bienes");
}

// =================== INICIA cURL ===================
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Bien.php?tipo=listar_bienes_ordenados_tabla&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&data=" . $ruta[1],
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
} 
require './vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear un nuevo documento
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("yo")
    ->setLastModifiedBy("yo")
    ->setTitle("yo")
    ->setDescription("yo");

$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle('Hoja 1');

// Llenar columna A con puro 1 la B con X, la C con numeros del AL 10, LA D CON =, la E CON LA MULTIPLICAION DE A + C
for ($fila = 1; $fila <= 10; $fila++) {
$activeWorksheet->setCellValue('A' . $fila, 1);
$activeWorksheet->setCellValue('B' . $fila, 'X');
$activeWorksheet->setCellValue('C' . $fila, $fila);
$activeWorksheet->setCellValue('D' . $fila, '=');
$activeWorksheet->setCellValue('E' . $fila, '=A' . $fila . '+C' . $fila);
}

//TABLA DE MULTIPLICAR DE 1 AL 12 TAREA



// Llenar las columnas A hasta AD (30 columnas) con los números del 1 al 30 en la fila 1
// for ($i = 0; $i < 100; $i++) {
    // $columna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1); // Convierte 1 → A, 2 → B, ..., 30 → AD
   //  $activeWorksheet->setCellValue($columna . '1', $i + 1);
// }

// Guardar archivo
$writer = new Xlsx($spreadsheet);
$writer->save('hello world.xlsx');

// MOSTRAR LOS DATOS DE LOS BIENES QUE ESTEN EN LA BASE DE DATOS

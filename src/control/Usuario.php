<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // Ajusta la ruta según la estructura de tu proyecto

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
session_start();
require_once('../model/admin-sesionModel.php');
require_once('../model/admin-usuarioModel.php');
require_once('../model/adminModel.php');

$tipo = $_GET['tipo'];

//instanciar la clase categoria model
$objSesion = new SessionModel();
$objUsuario = new UsuarioModel();
$objAdmin = new AdminModel();

//variables de sesion
$id_sesion = $_POST['sesion'];
$token = $_POST['token'];

if ($tipo == "validar_datos_reset_password") {
    $id_email = $_POST['id'];
    $token_email = $_POST['token'];

    $arr_Respuesta = array('status' => false, 'msg' => 'link Caducado');
    $datos_usuario = $objUsuario->buscarUsuarioById($id_email);

    if ($datos_usuario && $datos_usuario->reset_password == 1 && password_verify($datos_usuario->token_password, $token_email)) {
        $arr_Respuesta = array('status' => true, 'msg' => 'OK');
    } else {
        // NUEVO: Limpiar sesión también cuando el link está expirado/inválido
        session_destroy();
        session_start();

        $arr_Respuesta = array('status' => false, 'msg' => 'link Caducado');
    }
    echo json_encode($arr_Respuesta);
}

// Nueva funcionalidad para actualizar la contraseña
if ($tipo == "actualizar_password_reset") {
    $id_usuario = $_POST['id'];
    $nueva_password = $_POST['password'];
    $token_verificacion = $_POST['token'];

    $arr_Respuesta = array('status' => false, 'msg' => 'Error al actualizar contraseña');

    try {
        // Verificar que el usuario y token sean válidos
        $datos_usuario = $objUsuario->buscarUsuarioById($id_usuario);

        if (
            $datos_usuario && $datos_usuario->reset_password == 1 &&
            password_verify($datos_usuario->token_password, $token_verificacion)
        ) {

            // Actualizar contraseña y resetear campos de recuperación
            $resultado = $objUsuario->actualizarPasswordYResetearToken($id_usuario, $nueva_password);

            if ($resultado) {
                // Limpiar cualquier sesión activa
                session_destroy();
                session_start();

                $arr_Respuesta = array(
                    'status' => true,
                    'msg' => 'Contraseña actualizada correctamente'
                );
            } else {
                $arr_Respuesta = array(
                    'status' => false,
                    'msg' => 'Error al guardar en base de datos'
                );
            }
        } else {
            // NUEVO: Limpiar sesión también cuando el token es inválido
            session_destroy();
            session_start();

            $arr_Respuesta = array(
                'status' => false,
                'msg' => 'Token inválido o expirado'
            );
        }
    } catch (Exception $e) {
        $arr_Respuesta = array(
            'status' => false,
            'msg' => 'Error del servidor: ' . $e->getMessage()
        );
    }

    echo json_encode($arr_Respuesta);
}



if ($tipo == "listar_usuarios_ordenados_tabla") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_dni = $_POST['busqueda_tabla_dni'];
        $busqueda_tabla_nomap = $_POST['busqueda_tabla_nomap'];
        $busqueda_tabla_estado = $_POST['busqueda_tabla_estado'];

        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $busqueda_filtro = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla_filtro($busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);
        $arr_Usuario = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);

        $arr_contenido = [];
        if (!empty($arr_Usuario)) {
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                $arr_contenido[$i] = (object) [];
                $arr_contenido[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido[$i]->dni = $arr_Usuario[$i]->dni;
                $arr_contenido[$i]->nombres_apellidos = $arr_Usuario[$i]->nombres_apellidos;
                $arr_contenido[$i]->correo = $arr_Usuario[$i]->correo;
                $arr_contenido[$i]->telefono = $arr_Usuario[$i]->telefono;
                $arr_contenido[$i]->estado = $arr_Usuario[$i]->estado;
                $opciones = '<button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Usuario[$i]->id . '"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-info" title="Resetear Contraseña" onclick="reset_password(' . $arr_Usuario[$i]->id . ')"><i class="fa fa-key"></i></button>
                           ';
                $arr_contenido[$i]->options = $opciones;
            }
            $arr_Respuesta['total'] = count($busqueda_filtro);
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}


if ($tipo == "registrar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $dni = $_POST['dni'];
            $apellidos_nombres = $_POST['apellidos_nombres'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $password = $_POST['password'];

            if ($dni == "" || $apellidos_nombres == "" || $correo == "" || $telefono == "" || $password == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Usuario ya se encuentra registrado');
                } else {
                    $id_usuario = $objUsuario->registrarUsuario($dni, $apellidos_nombres, $correo, $telefono, $password);
                    if ($id_usuario > 0) {
                        // array con los id de los sistemas al que tendra el acceso con su rol registrado
                        // caso de administrador y director
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Registro Exitoso');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al registrar producto');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "actualizar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $id = $_POST['data'];
            $dni = $_POST['dni'];
            $nombres_apellidos = $_POST['nombres_apellidos'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $estado = $_POST['estado'];

            if ($id == "" || $dni == "" || $nombres_apellidos == "" || $correo == "" || $telefono == "" || $estado == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    if ($arr_Usuario->id == $id) {
                        $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                        if ($consulta) {
                            $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                        } else {
                            $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                        }
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'dni ya esta registrado');
                    }
                } else {
                    $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                    if ($consulta) {
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "reiniciar_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $id_usuario = $_POST['id'];
        $password = $objAdmin->generar_llave(10);
        $pass_secure = password_hash($password, PASSWORD_DEFAULT);
        $actualizar = $objUsuario->actualizarPassword($id_usuario, $pass_secure);
        if ($actualizar) {
            $arr_Respuesta = array('status' => true, 'mensaje' => 'Contraseña actualizado correctamente a: ' . $password);
        } else {
            $arr_Respuesta = array('status' => false, 'mensaje' => 'Hubo un problema al actualizar la contraseña, intente nuevamente');
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "send_email_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {

        $datos_sesion = $objSesion->buscarSesionLoginById($id_sesion);
        $datos_usuario = $objUsuario->buscarUsuarioById($datos_sesion->id_usuario);
        $llave = $objAdmin->generar_llave(30);
        $token = password_hash($llave, PASSWORD_DEFAULT);
        $update = $objUsuario->updateResetPassword($datos_sesion->id_usuario, $llave, 1);
        if ($update) {

            //Load Composer's autoloader (created by composer, not included with PHPMailer)

            //Create an instance; passing `true` enables exceptions
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host = 'mail.importecsolutions.com';                     //Set the SMTP server to send through
                $mail->SMTPAuth = true;                                   //Enable SMTP authentication
                $mail->Username = 'julianore@importecsolutions.com';                     //SMTP username
                $mail->Password = 'dy4X,6;!i*G!';                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                //Recipients

                $mail->setFrom('julianore@importecsolutions.com', 'Cambio de Contraseña', 'importecsolutions.com');
                $mail->addAddress($datos_usuario->correo, $datos_usuario->nombres_apellidos, 'Cambio de Contraseña');     //Add a recipient


                //Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Cambio de Contraseña - Sistema de inventario'; //Set email format to HTML
                $mail->Body = '<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TECHNOVA - Cambio de Contraseña</title>
        <style>
            /* Mobile First Responsive Design */
            * {
                box-sizing: border-box;
            }
    
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                margin: 0;
                padding: 15px;
                min-height: 100vh;
            }
    
            .main-container {
                max-width: 650px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
            }
    
            .email-wrapper {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                border: 1px solid #e2e8f0;
            }
    
            .header {
                background: linear-gradient(135deg, #1a1c29 0%, #2d3748 100%);
                padding: 30px 20px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
    
            .header-content {
                position: relative;
                z-index: 2;
            }
    
            .logo {
                color: white;
                font-size: 28px;
                font-weight: bold;
                margin: 0 0 10px 0;
                letter-spacing: 1px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
    
            .divider {
                width: 60px;
                height: 3px;
                background: linear-gradient(90deg, #667eea, #764ba2);
                margin: 10px auto;
            }
    
            .tagline {
                color: rgba(255, 255, 255, 0.85);
                font-size: 12px;
                margin: 10px 0 0 0;
                font-weight: 500;
                letter-spacing: 1px;
                text-transform: uppercase;
            }
    
            .security-section {
                text-align: center;
                padding: 25px 0 20px 0;
                background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            }
    
            .security-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
                position: relative;
            }
    
            .icon-emoji {
                font-size: 30px;
                position: relative;
                z-index: 2;
                color: white;
            }
    
            .content {
                padding: 0 20px 30px 20px;
                background: white;
                position: relative;
            }
    
            .title-section {
                text-align: center;
                margin-bottom: 25px;
            }
    
            .main-title {
                font-size: 20px;
                font-weight: bold;
                color: #1a202c;
                margin: 0 0 10px 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
    
            .title-divider {
                width: 80px;
                height: 2px;
                background: linear-gradient(90deg, #667eea, #764ba2);
                margin: 0 auto;
            }
    
            .message-box {
                background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 25px;
                border: 1px solid #e2e8f0;
                position: relative;
            }
    
            .message-text {
                font-size: 14px;
                color: #4a5568;
                margin: 0;
                line-height: 1.6;
            }
    
            .cta-section {
                text-align: center;
                margin: 30px 0;
            }
    
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 25px;
                font-weight: bold;
                font-size: 14px;
                box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
    
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
                margin: 25px 0;
            }
    
            .stat-card {
                text-align: center;
                padding: 15px 10px;
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
                border-radius: 8px;
                border: 1px solid rgba(102, 126, 234, 0.15);
            }
    
            .stat-number {
                font-size: 18px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 5px;
            }
    
            .stat-label {
                font-size: 10px;
                color: #64748b;
                font-weight: 500;
            }
    
            .security-info {
                background: linear-gradient(135deg, #f0fff4 0%, #f7fafc 100%);
                border: 2px solid #68d391;
                border-radius: 10px;
                padding: 20px;
                margin: 25px 0;
            }
    
            .security-title {
                font-size: 16px;
                font-weight: bold;
                color: #2f855a;
                margin: 0 0 15px 0;
            }
    
            .security-items {
                display: grid;
                gap: 8px;
            }
    
            .security-item {
                display: flex;
                align-items: flex-start;
                padding: 8px;
                background: rgba(255, 255, 255, 0.7);
                border-radius: 6px;
                border-left: 4px solid;
            }
    
            .security-item.warning {
                border-left-color: #fbbf24;
            }
    
            .security-item.success {
                border-left-color: #10b981;
            }
    
            .security-item.info {
                border-left-color: #3b82f6;
            }
    
            .security-item.purple {
                border-left-color: #8b5cf6;
            }
    
            .security-item.orange {
                border-left-color: #f59e0b;
            }
    
            .security-emoji {
                font-size: 14px;
                margin-right: 8px;
                flex-shrink: 0;
            }
    
            .security-text {
                font-size: 11px;
                color: #374151;
                font-weight: 500;
            }
    
            .alert-box {
                background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                border-radius: 10px;
                padding: 15px;
                border: 1px solid #f59e0b;
                margin-bottom: 20px;
            }
    
            .alert-text {
                font-size: 12px;
                color: #92400e;
                margin: 0;
                line-height: 1.5;
            }
    
            .security-badge {
                text-align: center;
                margin: 25px 0;
            }
    
            .badge {
                display: inline-block;
                background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
                color: white;
                padding: 8px 18px;
                border-radius: 25px;
                font-size: 12px;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
    
            .footer {
                background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
                color: #a0aec0;
                padding: 25px 20px;
                text-align: center;
            }
    
            .footer-content {
                position: relative;
                z-index: 2;
            }
    
            .footer-logo {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 10px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
    
            .footer-description {
                font-size: 13px;
                margin: 0 0 20px 0;
                line-height: 1.5;
                color: #cbd5e0;
            }
    
            .contact-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
                margin: 20px 0;
            }
    
            .contact-card {
                background: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                padding: 10px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
    
            .contact-title {
                font-size: 11px;
                color: #667eea;
                font-weight: bold;
                margin-bottom: 3px;
            }
    
            .contact-info {
                font-size: 10px;
                line-height: 1.4;
            }
    
            .footer-legal {
                font-size: 10px;
                color: #718096;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                padding-top: 15px;
                margin-top: 15px;
            }
    
            .footer-legal p {
                margin: 0 0 8px 0;
            }
    
            .footer-legal a {
                color: #667eea;
                text-decoration: none;
                margin: 0 5px;
            }
    
            /* Mobile Responsive Styles */
            @media screen and (max-width: 768px) {
                body {
                    padding: 10px;
                }
    
                .header {
                    padding: 25px 15px;
                }
    
                .logo {
                    font-size: 24px;
                    letter-spacing: 1px;
                }
    
                .tagline {
                    font-size: 11px;
                }
    
                .security-section {
                    padding: 20px 0 15px 0;
                }
    
                .security-icon {
                    width: 70px;
                    height: 70px;
                }
    
                .icon-emoji {
                    font-size: 25px;
                }
    
                .content {
                    padding: 0 15px 25px 15px;
                }
    
                .main-title {
                    font-size: 18px;
                }
    
                .message-box {
                    padding: 15px;
                }
    
                .message-text {
                    font-size: 13px;
                }
    
                .cta-button {
                    padding: 10px 20px;
                    font-size: 13px;
                }
    
                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 8px;
                }
    
                .stat-number {
                    font-size: 16px;
                }
    
                .stat-label {
                    font-size: 9px;
                }
    
                .security-info {
                    padding: 15px;
                }
    
                .security-title {
                    font-size: 15px;
                }
    
                .security-text {
                    font-size: 10px;
                }
    
                .alert-text {
                    font-size: 11px;
                }
    
                .countdown-item {
                    padding: 6px 10px;
                    font-size: 12px;
                }
    
                .contact-grid {
                    grid-template-columns: 1fr;
                }
    
                .footer {
                    padding: 20px 15px;
                }
    
                .footer-logo {
                    font-size: 18px;
                }
    
                .footer-description {
                    font-size: 12px;
                }
            }
    
            @media screen and (max-width: 480px) {
                .email-wrapper {
                    border-radius: 8px;
                }
    
                .stats-grid {
                    grid-template-columns: 1fr;
                }
    
                .security-items {
                    gap: 6px;
                }
    
                .security-item {
                    padding: 6px;
                }
    
                .security-emoji {
                    font-size: 12px;
                    margin-right: 6px;
                }
    
                .security-text {
                    font-size: 9px;
                }
    
                .countdown-item {
                    padding: 5px 8px;
                    font-size: 10px;
                }
    
                .security-icon {
                    width: 60px;
                    height: 60px;
                }
    
                .icon-emoji {
                    font-size: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <!-- Main Container -->
            <div class="email-wrapper">
                <!-- Header -->
                <div class="header">
                    <div class="header-content">
                        <h1 class="logo">TECHNOVA</h1>
                        <div class="divider"></div>
                        <p class="tagline">Tecnología innovadora para el futuro</p>
                    </div>
                </div>
    
                <!-- Security Icon Section -->
                <div class="security-section">
                    <div class="security-icon">
                        <span class="icon-emoji">🔐</span>
                    </div>
                </div>
    
                <!-- Main Content -->
                <div class="content">
                    <!-- Title Section -->
                    <div class="title-section">
                        <h2 class="main-title">Solicitud de cambio de contraseña</h2>
                        <div class="title-divider"></div>
                    </div>
    
                    <div class="message-box">
                        <p class="message-text">
                            <strong style="color: #2d3748;">Hola ' . $datos_usuario->nombres_apellidos . '</strong><br><br>
                            Hemos recibido una solicitud para <span style="color: #667eea; font-weight: bold;">cambiar la contraseña</span> de tu cuenta en TECHNOVA.
                            Por tu seguridad, necesitamos verificar que fuiste tú quien realizó esta solicitud.
                        </p>
                    </div>
    
                    <!-- CTA Button -->
                    <div class="cta-section">
                        <a href="' . BASE_URL . 'reset-password/?data=' . $datos_usuario->id . '&data2=' . urlencode($token) . '" class="cta-button">
                            <span>🔒 Cambiar contraseña ahora</span>
                        </a>
                    </div>
    
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">256</div>
                            <div class="stat-label">Cifrado de Bits</div>
                        </div>
    
                        <div class="stat-card">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Seguridad</div>
                        </div>
    
                        <div class="stat-card">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
    
                        <div class="stat-card">
                            <div class="stat-number">ISO</div>
                            <div class="stat-label">Certificado</div>
                        </div>
                    </div>
    
                    <!-- Security Info -->
                    <div class="security-info">
                        <h3 class="security-title">
                            🛡️ Información de seguridad importante
                        </h3>
                        <div class="security-items">
                            <div class="security-item warning">
                                <span class="security-emoji">⏰</span>
                                <span class="security-text">Este enlace <strong>expirará en 24 horas</strong> por motivos de seguridad</span>
                            </div>
                            <div class="security-item success">
                                <span class="security-emoji">✋</span>
                                <span class="security-text">Si no solicitaste este cambio, <strong>ignora este mensaje</strong> completamente</span>
                            </div>
                            <div class="security-item info">
                                <span class="security-emoji">🔒</span>
                                <span class="security-text">Nunca compartas tus <strong>credenciales con terceros</strong> o por teléfono</span>
                            </div>
                            <div class="security-item purple">
                                <span class="security-emoji">🔐</span>
                                <span class="security-text">Usa contraseñas <strong>seguras con números, símbolos y mayúsculas</strong></span>
                            </div>
                            <div class="security-item orange">
                                <span class="security-emoji">📱</span>
                                <span class="security-text">Considera activar la <strong>autenticación de dos factores</strong> (2FA)</span>
                            </div>
                        </div>
                    </div>
    
                    <div class="alert-box">
                        <p class="alert-text">
                            <strong>⚠️ Importante:</strong> Si no solicitaste este cambio o tienes alguna duda, contacta inmediatamente con nuestro
                            equipo de soporte disponible <strong>24/7</strong> para tu seguridad.
                        </p>
                    </div>
    
                    <!-- Security Badge -->
                    <div class="security-badge">
                        <div class="badge">
                            🔐 Tu seguridad es nuestra prioridad #1 🔐
                        </div>
                    </div>
                </div>
    
                <!-- Footer -->
                <div class="footer">
                    <div class="footer-content">
                        <div class="footer-logo">TECHNOVA</div>
                        <p class="footer-description">
                            Tu tienda de confianza para productos electrónicos de última generación<br>
                            <strong style="color: white;">Innovación • Calidad • Seguridad</strong>
                        </p>
    
                        <!-- Contact Info Cards -->
                        <div class="contact-grid">
                            <div class="contact-card">
                                <div class="contact-title">📍 Dirección</div>
                                <div class="contact-info">Av. Tecnología Digital 123<br>Torre Innovation, Piso 15</div>
                            </div>
                            <div class="contact-card">
                                <div class="contact-title">📞 Soporte 24/7</div>
                                <div class="contact-info">+1 (800) TECH-NOW</div>
                            </div>
                            <div class="contact-card">
                                <div class="contact-title">🌐 Web</div>
                                <div class="contact-info">www.technova.com</div>
                            </div>
                        </div>
    
                        <div class="footer-legal">
                            <p><strong>© 2024 TECHNOVA</strong> - Todos los derechos reservados</p>
                            <p>Este es un mensaje automatizado del sistema de seguridad. <strong>No respondas a este email.</strong></p>
                            <p>
                                <a href="#">Administrar preferencias</a> |
                                <a href="#">Política de privacidad</a> |
                                <a href="#">Términos de servicio</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';


                $mail->send();
                echo 'Message has been sent';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "fallo al actualizar";
        }
        //print_r($token);

    }

}


if ($tipo == "listar_todos_usuarios") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');

    // Manejar parámetros de sesión tanto por GET como por POST
    $id_sesion_param = isset($_GET['sesion']) ? $_GET['sesion'] : $id_sesion;
    $token_param = isset($_GET['token']) ? $_GET['token'] : $token;

    if ($objSesion->verificar_sesion_si_activa($id_sesion_param, $token_param)) {
        // Respuesta
        $arr_Respuesta = array('status' => false, 'data' => '');
        $arr_Usuario = $objUsuario->listarTodosLosUsuarios();
        $arr_contenido = [];

        if (!empty($arr_Usuario)) {
            // Recorrer usuarios y preparar datos para el PDF
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                $arr_contenido[$i] = (object) [];
                $arr_contenido[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido[$i]->dni = $arr_Usuario[$i]->dni;
                $arr_contenido[$i]->nombres_apellidos = $arr_Usuario[$i]->nombres_apellidos;
                $arr_contenido[$i]->correo = $arr_Usuario[$i]->correo;
                $arr_contenido[$i]->telefono = $arr_Usuario[$i]->telefono;
                $arr_contenido[$i]->estado = $arr_Usuario[$i]->estado;
            }

            $arr_Respuesta['status'] = true;
            $arr_Respuesta['data'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}

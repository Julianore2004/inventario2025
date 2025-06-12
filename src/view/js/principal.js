// Mostrar el popup de carga
function mostrarPopupCarga() {
    const popup = document.getElementById('popup-carga');
    if (popup) {
        popup.style.display = 'flex';
    }
}
// Ocultar el popup de carga
function ocultarPopupCarga() {
    const popup = document.getElementById('popup-carga');
    if (popup) {
        popup.style.display = 'none';
    }
}
//funcion en caso de session acudacada
async function alerta_sesion() {
    Swal.fire({
        type: 'error',
        title: 'Error de Sesión',
        text: "Sesión Caducada, Por favor inicie sesión",
        confirmButtonClass: 'btn btn-confirm mt-2',
        footer: '',
        timer: 1000
    });
    location.replace(base_url + "login");
}
// cargar elementos de menu
async function cargar_institucion_menu(id_ies = 0) {
    const formData = new FormData();
    formData.append('sesion', session_session);
    formData.append('token', token_token);
    try {
        let respuesta = await fetch(base_url_server + 'src/control/Institucion.php?tipo=listar', {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            body: formData
        });
        let json = await respuesta.json();
        if (json.status) {
            let datos = json.contenido;
            let contenido = '';
            let sede = '';
            datos.forEach(item => {
                if (id_ies == item.id) {
                    sede = item.nombre;
                }
                contenido += `<button href="javascript:void(0);" class="dropdown-item notify-item" onclick="actualizar_ies_menu(${item.id});">${item.nombre}</button>`;
            });
            document.getElementById('contenido_menu_ies').innerHTML = contenido;
            document.getElementById('menu_ies').innerHTML = sede;
        }
        //console.log(respuesta);
    } catch (e) {
        console.log("Error al cargar categorias" + e);
    }

}
async function cargar_datos_menu(sede) {
    cargar_institucion_menu(sede);
}
// actualizar elementos del menu
async function actualizar_ies_menu(id) {
    const formData = new FormData();
    formData.append('id_ies', id);
    try {
        let respuesta = await fetch(base_url + 'src/control/sesion_cliente.php?tipo=actualizar_ies_sesion', {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            body: formData
        });
        let json = await respuesta.json();
        if (json.status) {
            location.reload();
        }
        //console.log(respuesta);
    } catch (e) {
        console.log("Error al cargar instituciones" + e);
    }
}
function generar_paginacion(total, cantidad_mostrar) {
    let actual = document.getElementById('pagina').value;
    let paginas = Math.ceil(total / cantidad_mostrar);
    let paginacion = '<li class="page-item';
    if (actual == 1) {
        paginacion += ' disabled';
    }
    paginacion += ' "><button class="page-link waves-effect" onclick="numero_pagina(1);">Inicio</button></li>';
    paginacion += '<li class="page-item ';
    if (actual == 1) {
        paginacion += ' disabled';
    }
    paginacion += '"><button class="page-link waves-effect" onclick="numero_pagina(' + (actual - 1) + ');">Anterior</button></li>';
    if (actual > 4) {
        var iin = (actual - 2);
    } else {
        var iin = 1;
    }
    for (let index = iin; index <= paginas; index++) {
        if ((paginas - 7) > index) {
            var n_n = iin + 5;
        }
        if (index == n_n) {
            var nn = actual + 1;
            paginacion += '<li class="page-item"><button class="page-link" onclick="numero_pagina(' + nn + ')">...</button></li>';
            index = paginas - 2;
        }
        paginacion += '<li class="page-item ';
        if (actual == index) {
            paginacion += "active";
        }
        paginacion += '" ><button class="page-link" onclick="numero_pagina(' + index + ');">' + index + '</button></li>';
    }
    paginacion += '<li class="page-item ';
    if (actual >= paginas) {
        paginacion += "disabled";
    }
    paginacion += '"><button class="page-link" onclick="numero_pagina(' + (parseInt(actual) + 1) + ');">Siguiente</button></li>';

    paginacion += '<li class="page-item ';
    if (actual >= paginas) {
        paginacion += "disabled";
    }
    paginacion += '"><button class="page-link" onclick="numero_pagina(' + paginas + ');">Final</button></li>';
    return paginacion;
}
function generar_texto_paginacion(total, cantidad_mostrar) {
    let actual = document.getElementById('pagina').value;
    let paginas = Math.ceil(total / cantidad_mostrar);
    let iniciar = (actual - 1) * cantidad_mostrar;
    if (actual < paginas) {

        var texto = '<label>Mostrando del ' + (parseInt(iniciar) + 1) + ' al ' + ((parseInt(iniciar) + 1) + 9) + ' de un total de ' + total + ' registros</label>';
    } else {
        var texto = '<label>Mostrando del ' + (parseInt(iniciar) + 1) + ' al ' + total + ' de un total de ' + total + ' registros</label>';
    }
    return texto;
}
// ---------------------------------------------  DATOS DE CARGA PARA FILTRO DE BUSQUEDA -----------------------------------------------
//cargar programas de estudio
function cargar_ambientes_filtro(datos, form = 'busqueda_tabla_ambiente', filtro = 'filtro_ambiente') {
    let ambiente_actual = document.getElementById(filtro).value;
    lista_ambiente = `<option value="0">TODOS</option>`;
    datos.forEach(ambiente => {
        pe_selected = "";
        if (ambiente.id == ambiente_actual) {
            pe_selected = "selected";
        }
        lista_ambiente += `<option value="${ambiente.id}" ${pe_selected}>${ambiente.detalle}</option>`;
    });
    document.getElementById(form).innerHTML = lista_ambiente;
}
//cargar programas de estudio
function cargar_sede_filtro(sedes) {
    let sede_actual = document.getElementById('sede_actual_filtro').value;
    lista_sede = `<option value="0">TODOS</option>`;
    sedes.forEach(sede => {
        sede_selected = "";
        if (sede.id == sede_actual) {
            sede_selected = "selected";
        }
        lista_sede += `<option value="${sede.id}" ${sede_selected}>${sede.nombre}</option>`;
    });
    document.getElementById('busqueda_tabla_sede').innerHTML = lista_sede;
}



// ------------------------------------------- FIN DE DATOS DE CARGA PARA FILTRO DE BUSQUEDA -----------------------------------------------

async function validar_datos_reset_password() {

    let id = document.getElementById('data').value;
    let token = document.getElementById('data2').value;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('token', token);
    formData.append('sesion', '');

    try {
    let respuesta = await fetch(base_url + 'src/control/Usuario.php?tipo=validar_datos_reset_password', {
        method: 'POST',
        mode: 'cors',
        cache: 'no-cache',
        body: formData
    });
    let json = await respuesta.json();
    if (json.status == false) {
        Swal.fire({
            type: 'error',
            title: 'Error de Link',
            text: "Link caducado, Verifique su correo",
            confirmButtonClass: 'btn btn-confirm mt-2',
            footer: '',
            timer: 1000
        });
        
        let formulario = document.getElementById('frm_reset_password');
        formulario.innerHTML = `
            <div style="
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
                text-align: center;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                margin: 20px auto;
                max-width: 500px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            ">
                <div style="
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 20px;
                    box-shadow: 0 4px 20px rgba(255, 107, 107, 0.3);
                ">
                    <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                </div>
                
                <h2 style="
                    color: #2c3e50;
                    font-size: 28px;
                    font-weight: 700;
                    margin: 0 0 12px 0;
                    letter-spacing: -0.5px;
                ">
                    Link Caducado
                </h2>
                
                <p style="
                    color: #7f8c8d;
                    font-size: 16px;
                    line-height: 1.6;
                    margin: 0 0 30px 0;
                    max-width: 400px;
                ">
                    El enlace de recuperación ha expirado. Por favor, solicite un nuevo enlace de recuperación desde la página de inicio de sesión.
                </p>
                
                <button onclick="window.location.href = base_url + 'login.php'" style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 14px 32px;
                    font-size: 16px;
                    font-weight: 600;
                    border-radius: 50px;
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    outline: none;
                    position: relative;
                    overflow: hidden;
                " 
                onmouseover="
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.6)';
                "
                onmouseout="
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 15px rgba(102, 126, 234, 0.4)';
                "
                onmousedown="this.style.transform = 'translateY(0) scale(0.98)';"
                onmouseup="this.style.transform = 'translateY(-2px) scale(1)';">
                    <span style="position: relative; z-index: 1;">
                        Ir al Login
                    </span>
                </button>
                
                <div style="
                    margin-top: 25px;
                    padding-top: 20px;
                    border-top: 1px solid rgba(127, 140, 141, 0.2);
                    width: 100%;
                ">
                    <p style="
                        color: #95a5a6;
                        font-size: 14px;
                        margin: 0;
                        font-style: italic;
                    ">
                        ¿Necesitas ayuda? Contacta con soporte técnico
                    </p>
                </div>
            </div>
        `;
   
        // Opcional: redirección automática después de 5 segundos
        // setTimeout(() => {
        //     window.location.href = base_url + "login";
        // }, 5000);
    }
    //console.log(respuesta);
} catch (e) {
    console.log("Error al cargar" + e);
}
}
function validar_imputs_password() {
    let pass1 = document.getElementById('password').value.trim();
    let pass2 = document.getElementById('password1').value.trim();


    if (pass1.length < 8 && pass2.length < 8) {
        Swal.fire({
            type: 'error',
            title: 'Error',
            text: 'La contraseña debe tener al menos 8 caracteres',
            footer: '',
            timer: 1500
        });
        return false;
    }

    // Validar complejidad de la contraseña
    if (!/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/.test(pass1)) {
        Swal.fire({
            type: 'error',
            title: 'Error',
            text: 'La contraseña debe contener al menos una letra mayúscula, una letra minúscula, un número y un carácter especial',
            footer: '',
            timer: 1500
        });
        return false;
    }

    // Si todo está bien, actualizar contraseña
    actualizar_password();
    return true;
}



async function actualizar_password() {
    let id = document.getElementById('data').value;
    let password = document.getElementById('password').value;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('password', password);

    try {
        let respuesta = await fetch(base_url + 'src/control/Usuario.php?tipo=actualizar_password', {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            body: formData
        });
        let json = await respuesta.json();
        if (json.status) {
            Swal.fire({
                type: 'success',
                title: 'Éxito',
                text: json.msg,
                footer: '',
                timer: 1500
            }).then(() => {
                window.location.href = base_url + 'login';
            });
        } else {
            Swal.fire({
                type: 'error',
                title: 'Error',
                text: json.msg,
                footer: '',
                timer: 1500
            });
        }
    } catch (e) {
        console.log("Error al actualizar la contraseña: " + e);
        Swal.fire({
            type: 'error',
            title: 'Error',
            text: 'Error de conexión al servidor',
            footer: '',
            timer: 1500
        });
    }
}


 //enviar informacion de password y id al controlador usuario
    // en el controlador recibir informacion y encriptar la nueva contraseña
    // guardar en base de datos y actualizar campo de reset_password= 0 y token_password= 'vacio'
    // notificar a usuario sobre el estado del proceso con alertas

     //TAREA ENVIAR INFORMACION DE PASSWORD Y ID AL CONTROLADOR USUARIO
 // RESIVIR INFORMACION Y ENCRIPTAR LA NUEVA CONTRASEÑA 
 // GUARDAR EN BASE DE DAROS Y ACTUALIZAR CAMPO DE RESET_PASSWORD = 0 Y TOKEN_PASSWORD = ''
 // NOTIFICAR A USUARIO SOBRE EL ESTADO DEL PROCESO CON ALERTA

        
  
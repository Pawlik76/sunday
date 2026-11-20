const formulario = document.getElementById("formulario");

formulario.addEventListener("submit", function(event) {

    event.preventDefault();

    const usuario = document.getElementById("usuario").value.trim();
    const correo = document.getElementById("correo").value.trim();
    const telefono = document.getElementById("telefono").value.trim();
    const fecha = document.getElementById("fecha").value;
    const password = document.getElementById("password").value;
    const confirmarPassword =
        document.getElementById("confirmarPassword").value;
    const privacidad = document.getElementById("privacidad").checked;
    const mensaje = document.getElementById("mensaje");

    // EXPRESIONES REGULARES

    const regexUsuario = /^[a-zA-Z0-9_]{4,20}$/;

    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const regexTelefono = /^\d{10}$/;

    const regexPassword =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._#-])[A-Za-z\d@$!%*?&._#-]{8,}$/;

    mensaje.style.color = "#fca5a5";

    // VALIDAR USUARIO

    if (!regexUsuario.test(usuario)) {
        mensaje.textContent =
            "El usuario debe tener entre 4 y 20 caracteres. Usa letras, números o guion bajo.";
        return;
    }

    // VALIDAR CORREO

    if (!regexCorreo.test(correo)) {
        mensaje.textContent =
            "Ingresa un correo electrónico válido.";
        return;
    }

    // VALIDAR TELEFONO

    if (!regexTelefono.test(telefono)) {
        mensaje.textContent =
            "El teléfono debe contener exactamente 10 números.";
        return;
    }

    // VALIDAR FECHA

    if (!fecha || new Date(fecha + "T00:00:00") > new Date()) {
        mensaje.textContent =
            "Ingresa una fecha de nacimiento válida.";
        return;
    }

    // VALIDAR CONTRASEÑA

    if (!regexPassword.test(password)) {
        mensaje.textContent =
            "La contraseña requiere 8 caracteres, mayúscula, minúscula, número y símbolo.";
        return;
    }

    // CONFIRMAR CONTRASEÑA

    if (password !== confirmarPassword) {
        mensaje.textContent =
            "Las contraseñas no coinciden.";
        return;
    }

    // VALIDAR PRIVACIDAD

    if (!privacidad) {
        mensaje.textContent =
            "Debes aceptar el aviso de privacidad para continuar.";
        return;
    }

    // REGISTRO VALIDADO

    mensaje.style.color = "#34d399";

    mensaje.textContent =
        "¡Registro validado correctamente! Redirigiendo...";

    console.log("Usuario:", usuario);
    console.log("Correo:", correo);
    console.log("Teléfono:", telefono);
    console.log("Fecha de nacimiento:", fecha);

    // GUARDAR DATOS TEMPORALMENTE

    localStorage.setItem("usuarioRegistrado", usuario);
    localStorage.setItem("correoRegistrado", correo);
    localStorage.setItem("passwordRegistrada", password);

    // REDIRECCIONAR

    setTimeout(function() {
        window.location.href = "index.html";
    }, 1500);

});
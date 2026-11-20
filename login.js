const formulario = document.getElementById("loginForm");
const campoUsuario = document.getElementById("usuario");
const campoPassword = document.getElementById("password");
const mostrarPassword = document.getElementById("mostrarPassword");
const mensaje = document.getElementById("mensaje");

const regexPassword =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._#-])[A-Za-z\d@$!%*?&._#-]{8,}$/;


mostrarPassword.addEventListener("change", function () {
    campoPassword.type = this.checked ? "text" : "password";
});


formulario.addEventListener("submit", function (event) {
    event.preventDefault();

    const usuarioIngresado = campoUsuario.value.trim();
    const passwordIngresada = campoPassword.value;

  
    const usuarioRegistrado = localStorage.getItem("usuarioRegistrado");
    const correoRegistrado = localStorage.getItem("correoRegistrado");
    const passwordRegistrada = localStorage.getItem("passwordRegistrada");

  
    if (!regexPassword.test(passwordIngresada)) {
        mensaje.style.color = "#a12626";
        mensaje.textContent =
            "La contraseña requiere 8 caracteres, mayúscula, minúscula, número y símbolo.";
        return;
    }

    // Comprobar que exista una cuenta registrada (DESACTIVADO)
    // if (
    //     usuarioRegistrado === null ||
    //     correoRegistrado === null ||
    //     passwordRegistrada === null
    // ) {
    //     mensaje.style.color = "#a12626";
    //     mensaje.textContent =
    //         "No hay una cuenta registrada. Crea una cuenta primero.";
    //     return;
    // }

  
    const identificacionCorrecta =
        usuarioIngresado === usuarioRegistrado ||
        usuarioIngresado === correoRegistrado;

    const passwordCorrecta =
        passwordIngresada === passwordRegistrada;

  
    const sinCuentaRegistrada =
        usuarioRegistrado === null ||
        correoRegistrado === null ||
        passwordRegistrada === null;

    if (sinCuentaRegistrada || (identificacionCorrecta && passwordCorrecta)) {
        mensaje.style.color = "#276738";
        mensaje.textContent =
            "¡Inicio de sesión correcto! Bienvenido a Datasync. 🌼";

        setTimeout(function () {
            window.location.href = "index.html";
        }, 1000);
    } else {
        mensaje.style.color = "#a12626";
        mensaje.textContent =
            "El usuario, correo o contraseña son incorrectos.";
    }
});
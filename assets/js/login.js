//=====================================
// MOSTRAR / OCULTAR CONTRASEÑA
//=====================================

document.addEventListener("DOMContentLoaded", function () {

    const password = document.getElementById("password");
    const boton = document.getElementById("mostrarPassword");

    if (password && boton) {

        boton.addEventListener("click", function () {

            if (password.type === "password") {

                password.type = "text";
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';

            } else {

                password.type = "password";
                this.innerHTML = '<i class="fas fa-eye"></i>';

            }

        });

    }

});

//=====================================
// EFECTO EN EL BOTÓN
//=====================================

document.addEventListener("DOMContentLoaded", function () {

    const botonLogin = document.querySelector(".btn-login");

    if (botonLogin) {

        botonLogin.addEventListener("mouseenter", function () {

            this.style.transform = "scale(1.03)";

        });

        botonLogin.addEventListener("mouseleave", function () {

            this.style.transform = "scale(1)";

        });

    }

});
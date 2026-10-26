//==============================
// DATATABLE
//==============================

$(document).ready(function () {

    //==============================
    // TABLA INVENTARIO
    //==============================

    if ($("#tablaInventario").length) {

        $("#tablaInventario").DataTable({

            searching: false,
            lengthChange: false,
            info: true,
            paging: true,
            ordering: true,
            responsive: true,

            language: {
                url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json"
            }

        });

    }

    //==============================
    // TABLA MOVIMIENTOS
    //==============================

    if ($("#tablaMovimientos").length) {

        $("#tablaMovimientos").DataTable({

            searching: true,
            lengthChange: false,
            info: true,
            paging: true,
            ordering: true,
            responsive: true,

            language: {
                url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json"
            }

        });

    }

});

//==============================
// ELIMINAR PRODUCTO
//==============================

function eliminarProducto(id) {

    Swal.fire({

        title: "¿Eliminar producto?",

        text: "Esta acción no se puede deshacer.",

        icon: "warning",

        showCancelButton: true,

        confirmButtonColor: "#C99700",

        cancelButtonColor: "#6c757d",

        confirmButtonText: "Sí, eliminar",

        cancelButtonText: "Cancelar"

    }).then((result) => {

        if (result.isConfirmed) {

            window.location.href = "../actions/productos/eliminar_producto.php?id=" + id;

        }

    });

}

//==============================
// GRAFICA DASHBOARD
//==============================

if (typeof Chart !== "undefined") {

    const grafica = document.getElementById("grafica");

    if (grafica) {

        new Chart(grafica, {

            type: "bar",

            data: {

                labels: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],

                datasets: [

                    {

                        label: "Entradas",

                        data: [12, 18, 8, 25, 30, 15, 22, 26, 19, 15, 11, 20],

                        backgroundColor: "#002855"

                    },

                    {

                        label: "Salidas",

                        data: [5, 9, 4, 8, 10, 6, 8, 12, 6, 5, 3, 7],

                        backgroundColor: "#C99700"

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false

            }

        });

    }

}


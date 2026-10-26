<!-- =========================================================
     FOOTER / LIBRERÍAS
========================================================= -->


<!-- jQuery -->

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- DataTables -->

<script
    src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js">
</script>


<!-- SweetAlert2 -->

<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</script>


<script>

/*
=========================================================
ANIMACIONES BÁSICAS
=========================================================
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const botones =
            document.querySelectorAll(
                ".btn"
            );

        botones.forEach(
            function (boton) {

                boton.addEventListener(
                    "mousedown",
                    function () {

                        boton.style.transform =
                            "scale(0.98)";

                    }
                );

                boton.addEventListener(
                    "mouseup",
                    function () {

                        boton.style.transform =
                            "";

                    }
                );

                boton.addEventListener(
                    "mouseleave",
                    function () {

                        boton.style.transform =
                            "";

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>
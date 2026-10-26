<?php

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE CORREO JAVERSTOCK
|--------------------------------------------------------------------------
| No compartas este archivo públicamente.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MICROSOFT 365 / OUTLOOK
|--------------------------------------------------------------------------
*/

define('SMTP_HOST', 'smtp.office365.com');

define('SMTP_PORT', 587);

define('SMTP_ENCRYPTION', 'tls');


/*
|--------------------------------------------------------------------------
| CUENTA QUE ENVIARÁ LOS REPORTES
|--------------------------------------------------------------------------
*/

define(
    'SMTP_USERNAME',
    'TU_CORREO@javeriana.edu.co'
);

define(
    'SMTP_PASSWORD',
    'TU_CONTRASEÑA_SMTP'
);


/*
|--------------------------------------------------------------------------
| REMITENTE
|--------------------------------------------------------------------------
*/

define(
    'SMTP_FROM_EMAIL',
    'TU_CORREO@javeriana.edu.co'
);

define(
    'SMTP_FROM_NAME',
    'JaverStock - Sistema de Gestión de Inventario'
);

?>
<?php
//PAGINA SOLO PARA PROCESAMIENTO
// evita que se pueda entrar a la pagina
// Metodo mas seguro es dejarlo por fuera de la rama principal
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acceso denegado");
}

// Obtiene el email enviado desde JS
$email = $_POST["email"];

$email;

?>

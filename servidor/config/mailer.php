<?php

/**
 * Configura una instancia de PHPMailer lista para enviar
 * usando las credenciales SMTP del .env.
 *
 * @return PHPMailer\PHPMailer\PHPMailer
 */
function crearMailer()
{
    require_once __DIR__ . '/env.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    $host = getenv('MAIL_HOST');
    $port = getenv('MAIL_PORT') ?: 587;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Username = getenv('MAIL_USER');
    $mail->Password = getenv('MAIL_PASS');
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $port;

    // Conectar por IPv4: algunos entornos devuelven AAAA primero en DNS
    // y el host IPv6 termina inaccesible (error "Invalid hostentry").
    $ipv4 = @gethostbyname($host);
    $mail->Host = ($ipv4 !== $host && filter_var($ipv4, FILTER_VALIDATE_IP))
        ? $ipv4
        : $host;
    $mail->Hostname = $host;

    // Validar el certificado TLS contra el hostname real, aunque la
    // conexión se haga contra la dirección IPv4 (peer_name).
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
            'peer_name'         => $host,
        ],
    ];

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $mail->setFrom(
        getenv('MAIL_FROM') ?: 'no-reply@sporta.com',
        'Sporta'
    );

    return $mail;
}

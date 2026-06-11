<?php
$file = __DIR__ . '/mezzix.crt';
if (file_exists($file)) {
    header('Content-Type: application/x-x509-ca-cert');
    header('Content-Disposition: attachment; filename="mezzix.crt"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
http_response_code(404);
echo 'Certificado no encontrado';

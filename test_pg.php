<?php
$passwords = ['postgres', 'root', 'admin', '123456', '', 'password'];
$connected = false;

foreach ($passwords as $pwd) {
    try {
        $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=school_db', 'postgres', $pwd, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "CONECTADO_SCHOOL_DB:$pwd\n";
        $connected = true;
        break;
    } catch (Throwable $e) {
        echo "Tentativa '$pwd' falhou: " . $e->getMessage() . "\n";
    }
}

if (!$connected) {
    echo "Tentando banco padrão postgres...\n";
    foreach ($passwords as $pwd) {
        try {
            $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'postgres', $pwd, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "CONECTADO_POSTGRES:$pwd\n";
            $connected = true;
            break;
        } catch (Throwable $e) {
            // falhou
        }
    }
}

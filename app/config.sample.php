<?php
// Zkopírujte tento soubor jako app/config.php a upravte hodnoty.
// app/config.php se necommituje do gitu (obsahuje hesla).

return [
    // Veřejná adresa webu bez lomítka na konci.
    'base_url' => 'https://fyziotrek.cz',

    // Databáze: 'sqlite' (výchozí, nic se nenastavuje) nebo 'mysql'.
    'db' => [
        'driver' => 'sqlite',
        'sqlite_path' => __DIR__ . '/../data/fyziotrek.sqlite',

        // Pro MySQL / MariaDB (údaje najdete v administraci WEDOS):
        'host' => 'localhost',
        'name' => '',
        'user' => '',
        'password' => '',
    ],

    'mail' => [
        // Odesílatel všech e-mailů. Na WEDOS musí být adresa na vaší doméně.
        'from' => 'info@fyziotrek.cz',
        'from_name' => 'Fyziotrek',
        // Při vývoji: e-maily neodesílat, jen zapisovat do data/mail.log.
        'log_only' => false,
    ],

    // Při vývoji zobrazovat chyby (na produkci nechte false).
    'debug' => false,
];

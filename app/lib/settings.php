<?php
declare(strict_types=1);

/** Editable site settings with their defaults. */
function settings_defaults(): array
{
    return [
        'notify_emails' => 'info@fyziotrek.cz',
        'contact_email' => 'info@fyziotrek.cz',
        'contact_1_name' => 'Eliška Vrátná',
        'contact_1_phone' => '608 039 214',
        'contact_2_name' => 'Barbora Homonická Pyšková',
        'contact_2_phone' => '721 904 201',
        'address' => 'Děkanská vinice I 987/5, 140 00 Praha 4',
        'facebook_url' => 'https://www.facebook.com/fyziotrek',
        'storno_terms' => '<p><strong>Platné storno podmínky vztahující se k závazné objednávce služby (účasti na kurzu):</strong></p>'
            . '<ol>'
            . '<li>V případě odstoupení od kupní smlouvy v časovém období delším jak 37 dní před datem sjednaného plnění služby (tj. den konání kurzu), bude činit návrat částky prodávajícím kupujícímu ve výši 70 % z ceny sjednané služby.</li>'
            . '<li>V případě odstoupení od kupní smlouvy v časovém období 36 až 22 dní před datem sjednaného plnění služby (tj. den konání kurzu), bude činit návrat částky prodávajícím kupujícímu ve výši 40 % z ceny sjednané služby.</li>'
            . '<li>V případě odstoupení od kupní smlouvy v časovém období 21 dní až 7 dní před datem sjednaného plnění služby (tj. den konání kurzu), bude činit návrat částky prodávajícím kupujícímu ve výši 20 % z ceny sjednané služby.</li>'
            . '<li>V případě odstoupení od kupní smlouvy v časovém období méně než 7 dnů před datem sjednaného plnění služby (tj. den konání kurzu), bude činit návrat částky prodávajícím kupujícímu ve výši 10 % z ceny sjednané služby.</li>'
            . '<li>V případě zrušení služby prodávajícím bude činit návrat částky prodávajícím kupujícímu ve výši 100 % z ceny sjednané služby.</li>'
            . '</ol>'
            . '<p>Storno podmínky jsou platné od 1. 1. 2024.</p>',
        'application_confirmation' => "Dobrý den,\n\nděkujeme za přihlášku na kurz {kurz}. Přihlášku jsme přijali a brzy se vám ozveme s dalšími informacemi a platebními údaji.\n\nTým Fyziotrek",
    ];
}

function setting(string $name): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = settings_defaults();
        foreach (db_all('SELECT name, value FROM settings') as $row) {
            $cache[$row['name']] = $row['value'];
        }
    }
    return (string) ($cache[$name] ?? '');
}

function save_setting(string $name, string $value): void
{
    db_exec('DELETE FROM settings WHERE name = ?', [$name]);
    db_insert('settings', ['name' => $name, 'value' => $value]);
}

function tel_href(string $phone): string
{
    $digits = preg_replace('~[^\d+]~', '', $phone);
    if ($digits !== '' && $digits[0] !== '+') {
        $digits = '+420' . $digits;
    }
    return 'tel:' . $digits;
}

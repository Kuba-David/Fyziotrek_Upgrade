<?php
declare(strict_types=1);

function site_home(): void
{
    render('home', [
        'title' => 'Fyziotrek – vaše cesta ke vzdělání',
        'description' => 'Odborné kurzy a semináře pro fyzioterapeuty: Mulligan koncept, dry needling, neurodynamika, ComplexCore+ a další.',
        'courses' => published_courses(),
        'flash' => flash(),
    ]);
}

function site_course(string $slug): void
{
    $course = find_course_by_slug($slug) ?? not_found();
    render('course', [
        'title' => $course['title'] . ' – Fyziotrek',
        'description' => $course['summary'],
        'course' => $course,
    ]);
}

function site_apply(string $slug): void
{
    $course = find_course_by_slug($slug) ?? not_found();
    $terms = open_terms($course);
    $errors = [];
    $values = [];

    if (is_post() && course_accepts_applications($course)) {
        verify_csrf();
        $values = [
            'name' => post('name'),
            'email' => post('email'),
            'phone' => post('phone'),
            'birth_date' => post('birth_date'),
            'billing' => post('billing'),
            'is_student' => post('is_student') === '1',
            'needs_translation' => post('needs_translation'),
            'discount_code' => post('discount_code'),
            'referral' => post('referral'),
            'note' => post('note'),
            'consent_marketing' => post('consent_marketing') === '1',
            'consent_storno' => post('consent_storno') === '1',
            'terms' => array_map('intval', post_array('terms')),
        ];

        // Honeypot: real people never see or fill this field.
        if (post('website') !== '') {
            redirect('kurzy/' . $course['slug'] . '/prihlaska/odeslano');
        }
        if ($values['name'] === '') {
            $errors['name'] = 'Vyplňte prosím jméno.';
        }
        if (!valid_email($values['email'])) {
            $errors['email'] = 'Zadejte platný e-mail.';
        }
        if ($values['phone'] === '') {
            $errors['phone'] = 'Vyplňte prosím telefon.';
        }
        $chosen = array_values(array_filter($terms, fn ($t) => in_array((int) $t['id'], $values['terms'], true)));
        if ($terms && !$chosen) {
            $errors['terms'] = 'Vyberte alespoň jeden termín.';
        }
        if ($course['ask_translation'] && !in_array($values['needs_translation'], ['0', '1'], true)) {
            $errors['needs_translation'] = 'Vyberte, zda potřebujete překlad.';
        }
        if (!$values['consent_storno']) {
            $errors['consent_storno'] = 'Pro odeslání je potřeba souhlasit se storno podmínkami.';
        }
        if (!$errors && !rate_limit('apply', 10, 3600)) {
            $errors['form'] = 'Odeslali jste příliš mnoho přihlášek. Zkuste to prosím později nebo nám napište e-mail.';
        }

        if (!$errors) {
            $termLabels = array_map(fn ($t) => trim($t['label'] . ': ' . $t['date_text'], ': '), $chosen);
            $application = [
                'course_id' => $course['id'],
                'course_title' => $course['title'],
                'terms' => implode("\n", $termLabels),
                'name' => mb_substr($values['name'], 0, 200),
                'email' => mb_substr($values['email'], 0, 200),
                'phone' => mb_substr($values['phone'], 0, 50),
                'birth_date' => mb_substr($values['birth_date'], 0, 30),
                'billing' => mb_substr($values['billing'], 0, 1000),
                'is_student' => $values['is_student'] ? 1 : 0,
                'needs_translation' => $course['ask_translation'] ? (int) $values['needs_translation'] : null,
                'discount_code' => mb_substr($values['discount_code'], 0, 60),
                'referral' => mb_substr($values['referral'], 0, 200),
                'note' => mb_substr($values['note'], 0, 2000),
                'consent_marketing' => $values['consent_marketing'] ? 1 : 0,
                'status' => 'nova',
                'admin_note' => '',
                'ip' => client_ip(),
                'created_at' => now(),
            ];
            $application['id'] = db_insert('applications', $application);
            notify_new_application($application);
            redirect('kurzy/' . $course['slug'] . '/prihlaska/odeslano');
        }
    }

    render('apply', [
        'title' => 'Přihláška – ' . $course['title'],
        'course' => $course,
        'terms' => $terms,
        'errors' => $errors,
        'values' => $values,
    ]);
}

function site_apply_done(string $slug): void
{
    $course = find_course_by_slug($slug) ?? not_found();
    render('apply_done', ['title' => 'Přihláška odeslána', 'course' => $course]);
}

function notify_new_application(array $a): void
{
    $yesNo = fn ($v) => $v ? 'ano' : 'ne';
    $lines = [
        'Kurz: ' . $a['course_title'],
        'Termín: ' . ($a['terms'] !== '' ? str_replace("\n", ', ', $a['terms']) : '(bez termínu – zájemce)'),
        '',
        'Jméno: ' . $a['name'],
        'E-mail: ' . $a['email'],
        'Telefon: ' . $a['phone'],
        'Datum narození: ' . $a['birth_date'],
        'IČ / fakturační adresa: ' . $a['billing'],
        'Student: ' . $yesNo($a['is_student']),
    ];
    if ($a['needs_translation'] !== null) {
        $lines[] = 'Překlad: ' . $yesNo($a['needs_translation']);
    }
    $lines[] = 'Slevový kód: ' . $a['discount_code'];
    $lines[] = 'Odkud se o nás dozvěděl/a: ' . $a['referral'];
    $lines[] = 'Poznámka: ' . $a['note'];
    $lines[] = 'Souhlas se zasíláním novinek: ' . $yesNo($a['consent_marketing']);
    $lines[] = '';
    $lines[] = 'Detail v administraci: ' . absolute_url('admin/prihlasky/' . $a['id']);

    send_mail(setting('notify_emails'), 'Nová přihláška: ' . $a['course_title'] . ' – ' . $a['name'], implode("\n", $lines), $a['email']);

    $confirmation = str_replace('{kurz}', $a['course_title'], setting('application_confirmation'));
    $confirmation .= "\n\n---\nShrnutí přihlášky\n" . implode("\n", array_slice($lines, 0, -2));
    send_mail($a['email'], 'Přihláška přijata: ' . $a['course_title'], $confirmation, setting('contact_email'));
}

function site_contact(): void
{
    if (!is_post()) {
        redirect('/#kontakty');
    }
    verify_csrf();
    $data = ['name' => post('name'), 'email' => post('email'), 'phone' => post('phone'), 'message' => post('message')];

    if (post('website') !== '') {
        flash('Děkujeme, zpráva byla odeslána.');
        redirect('/#kontakty');
    }
    if ($data['name'] === '' || !valid_email($data['email']) || $data['message'] === '') {
        $_SESSION['old'] = $data;
        flash('Vyplňte prosím jméno, platný e-mail a zprávu.', 'error');
        redirect('/#kontakty');
    }
    if (!rate_limit('contact', 5, 3600)) {
        flash('Odeslali jste příliš mnoho zpráv. Zkuste to prosím později.', 'error');
        redirect('/#kontakty');
    }

    db_insert('messages', [
        'name' => mb_substr($data['name'], 0, 200),
        'email' => mb_substr($data['email'], 0, 200),
        'phone' => mb_substr($data['phone'], 0, 50),
        'message' => mb_substr($data['message'], 0, 5000),
        'is_read' => 0,
        'ip' => client_ip(),
        'created_at' => now(),
    ]);
    $body = "Jméno: {$data['name']}\nE-mail: {$data['email']}\nTelefon: {$data['phone']}\n\n{$data['message']}";
    send_mail(setting('notify_emails'), 'Dotaz z webu: ' . $data['name'], $body, $data['email']);

    unset($_SESSION['old']);
    flash('Děkujeme, zpráva byla odeslána. Ozveme se vám co nejdříve.');
    redirect('/#kontakty');
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo '  <url><loc>' . e(absolute_url('/')) . "</loc></url>\n";
    foreach (db_all('SELECT slug, updated_at FROM courses WHERE is_published = 1 ORDER BY sort_order') as $c) {
        echo '  <url><loc>' . e(absolute_url('kurzy/' . $c['slug'])) . '</loc><lastmod>' . e(substr($c['updated_at'], 0, 10)) . "</lastmod></url>\n";
    }
    echo '</urlset>';
}

function site_robots(): void
{
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nDisallow: /admin\n\nSitemap: " . absolute_url('sitemap.xml') . "\n";
}

<?php
declare(strict_types=1);

function admin_render(string $view, array $data = []): void
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    render('admin/' . $view, $data + ['user' => current_user(), 'flash' => flash()], 'admin/layout');
}

function admin_dashboard(): void
{
    require_login();
    redirect('admin/kurzy');
}

/** First run: create the first administrator account. Only works while no user exists. */
function admin_setup(): void
{
    if (users_exist()) {
        redirect('admin/prihlaseni');
    }
    $error = null;
    if (is_post()) {
        verify_csrf();
        $name = post('name');
        $email = mb_strtolower(post('email'));
        $password = (string) ($_POST['password'] ?? '');
        $error = match (true) {
            $name === '' => 'Vyplňte jméno.',
            !valid_email($email) => 'Zadejte platný e-mail.',
            default => password_problem($password),
        };
        if (!$error && !users_exist()) {
            db_insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => now(),
            ]);
            attempt_login($email, $password);
            if (post('import_seed') === '1' && !db_value('SELECT COUNT(*) FROM courses')) {
                require_once APP_DIR . '/lib/seed.php';
                import_seed_courses();
            }
            flash('Vítejte! Účet je vytvořený.');
            redirect('admin/kurzy');
        }
    }
    render('admin/setup', ['title' => 'Nastavení administrace', 'error' => $error], 'admin/layout_simple');
}

function admin_login(): void
{
    if (current_user()) {
        redirect('admin/kurzy');
    }
    if (!users_exist()) {
        redirect('admin/setup');
    }
    $error = null;
    if (is_post()) {
        verify_csrf();
        if (!rate_limit('login', 8, 900)) {
            $error = 'Příliš mnoho pokusů o přihlášení. Zkuste to za 15 minut.';
        } elseif (attempt_login(post('email'), (string) ($_POST['password'] ?? ''))) {
            redirect('admin/kurzy');
        } else {
            $error = 'Nesprávný e-mail nebo heslo.';
        }
    }
    header('X-Robots-Tag: noindex, nofollow');
    render('admin/login', ['title' => 'Přihlášení', 'error' => $error], 'admin/layout_simple');
}

function admin_forgot_password(): void
{
    $sent = false;
    $error = null;
    if (is_post()) {
        verify_csrf();
        if (!rate_limit('reset', 5, 3600)) {
            $error = 'Příliš mnoho žádostí. Zkuste to prosím za hodinu.';
        } else {
            send_password_reset(post('email'));
            $sent = true;
        }
    }
    header('X-Robots-Tag: noindex, nofollow');
    render('admin/forgot', ['title' => 'Zapomenuté heslo', 'sent' => $sent, 'error' => $error], 'admin/layout_simple');
}

function admin_reset_password(): void
{
    $token = (string) ($_GET['token'] ?? '');
    $user = find_user_by_reset_token($token);
    $error = null;
    if ($user && is_post()) {
        verify_csrf();
        $password = (string) ($_POST['password'] ?? '');
        $error = password_problem($password);
        if (!$error) {
            db_update('users', (int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'reset_token_hash' => null,
                'reset_expires_at' => null,
            ]);
            attempt_login($user['email'], $password);
            flash('Nové heslo je nastavené.');
            redirect('admin/kurzy');
        }
    }
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    render('admin/reset', ['title' => 'Nové heslo', 'valid' => (bool) $user, 'error' => $error], 'admin/layout_simple');
}

function admin_logout(): void
{
    if (is_post()) {
        verify_csrf();
        logout();
    }
    redirect('admin/prihlaseni');
}

/* ---------- Courses ---------- */

function admin_courses(): void
{
    require_login();
    $counts = [];
    foreach (db_all("SELECT course_id, COUNT(*) AS total, SUM(CASE WHEN status = 'nova' THEN 1 ELSE 0 END) AS fresh FROM applications GROUP BY course_id") as $row) {
        $counts[$row['course_id']] = $row;
    }
    admin_render('courses', ['title' => 'Kurzy', 'courses' => all_courses(), 'counts' => $counts]);
}

function admin_course_edit(?string $id = null): void
{
    require_login();
    $course = $id ? (find_course((int) $id) ?? not_found()) : null;
    $errors = [];

    if (is_post()) {
        verify_csrf();
        [$data, $terms, $errors] = course_from_request($course);

        if (!$errors) {
            try {
                $image = store_uploaded_image('image');
                if ($image) {
                    if ($course && $course['image']) {
                        delete_uploaded_image($course['image']);
                    }
                    $data['image'] = $image;
                } elseif (post('remove_image') === '1' && $course && $course['image']) {
                    delete_uploaded_image($course['image']);
                    $data['image'] = '';
                }
            } catch (RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        $newFiles = [];
        if (!$errors) {
            try {
                foreach (uploaded_files_list('new_files') as $file) {
                    $label = pathinfo($file['name'], PATHINFO_FILENAME);
                    $newFiles[] = ['label' => str_replace(['_', '-'], ' ', $label), 'path' => store_uploaded_document($file)];
                }
            } catch (RuntimeException $e) {
                foreach ($newFiles as $stored) {
                    delete_uploaded_file($stored['path']);
                }
                $errors['files'] = $e->getMessage();
            }
        }

        if (!$errors) {
            $newId = save_course($course ? (int) $course['id'] : null, $data, $terms);
            save_course_files($newId, post_array('files'), $newFiles);
            flash($course ? 'Změny jsou uložené a už jsou vidět na webu.' : 'Kurz byl vytvořen.');
            redirect('admin/kurzy/' . $newId);
        }
        // Re-render with what was submitted, so nothing typed gets lost.
        $course = array_merge($course ?? [], $data, ['terms' => $terms, 'id' => $course['id'] ?? null, 'image' => $course['image'] ?? '', 'files' => $course['files'] ?? []]);
    }

    admin_render('course_form', [
        'title' => $course && !empty($course['id']) ? 'Upravit kurz' : 'Nový kurz',
        'course' => $course ?? course_defaults(),
        'errors' => $errors,
    ]);
}

function course_defaults(): array
{
    return [
        'id' => null, 'title' => '', 'slug' => '', 'summary' => '', 'badge' => '', 'image' => '',
        'location' => '', 'lecturer' => '', 'translation' => '', 'price' => '', 'price_note' => '',
        'accommodation' => '', 'credits' => '', 'description' => '', 'ask_translation' => 0,
        'registration_open' => 1, 'is_published' => 0, 'legacy_paths' => '', 'files' => [],
        'terms' => [['id' => null, 'label' => '', 'date_text' => '', 'start_date' => '', 'end_date' => '', 'registration_open' => 1, 'content' => '']],
    ];
}

/** Validate and normalise the course form. Returns [course data, terms, errors]. */
function course_from_request(?array $existing): array
{
    $errors = [];
    $title = post('title');
    if ($title === '') {
        $errors['title'] = 'Kurz musí mít název.';
    }
    $slugInput = post('slug') !== '' ? post('slug') : $title;
    $existingId = $existing ? (int) $existing['id'] : null;
    $slug = unique_slug($slugInput, $existingId);

    $data = [
        'title' => mb_substr($title, 0, 200),
        'slug' => $slug,
        'summary' => mb_substr(post('summary'), 0, 600),
        'badge' => mb_substr(post('badge'), 0, 120),
        'location' => post('location'),
        'lecturer' => post('lecturer'),
        'translation' => post('translation'),
        'price' => post('price'),
        'price_note' => post('price_note'),
        'accommodation' => post('accommodation'),
        'credits' => post('credits'),
        'description' => sanitize_html((string) ($_POST['description'] ?? '')),
        'ask_translation' => post('ask_translation') === '1' ? 1 : 0,
        'registration_open' => post('registration_open') === '1' ? 1 : 0,
        'is_published' => post('is_published') === '1' ? 1 : 0,
    ];

    $terms = [];
    foreach (post_array('terms') as $term) {
        $i = count($terms);
        if (!is_array($term)) {
            continue;
        }
        $t = fn (string $k) => is_string($term[$k] ?? null) ? trim($term[$k]) : '';
        $row = [
            'id' => (int) ($term['id'] ?? 0) ?: null,
            'label' => mb_substr($t('label'), 0, 200),
            'date_text' => mb_substr($t('date_text'), 0, 200),
            'start_date' => preg_match('~^\d{4}-\d{2}-\d{2}$~', $t('start_date')) ? $t('start_date') : '',
            'end_date' => preg_match('~^\d{4}-\d{2}-\d{2}$~', $t('end_date')) ? $t('end_date') : '',
            'registration_open' => $t('registration_open') === '1' ? 1 : 0,
            'content' => sanitize_html($t('content')),
        ];
        if ($row['label'] === '' && $row['date_text'] === '' && $row['content'] === '' && $row['start_date'] === '') {
            continue; // an empty, untouched term row
        }
        if ($row['date_text'] === '' && $row['start_date']) {
            $row['date_text'] = format_date($row['start_date']) . ($row['end_date'] && $row['end_date'] !== $row['start_date'] ? ' – ' . format_date($row['end_date']) : '');
        }
        if ($row['date_text'] === '') {
            $errors["terms.$i"] = 'U termínu vyplňte datum (nebo text typu „připravujeme“).';
        }
        if ($row['start_date'] && $row['end_date'] && $row['end_date'] < $row['start_date']) {
            $errors["terms.$i"] = 'Konec termínu je dřív než jeho začátek.';
        }
        $terms[] = $row;
    }

    return [$data, $terms, $errors];
}

function admin_course_action(string $id, string $action): void
{
    require_login();
    if (!is_post()) {
        redirect('admin/kurzy');
    }
    verify_csrf();
    $course = find_course((int) $id) ?? not_found();

    switch ($action) {
        case 'smazat':
            delete_course((int) $course['id']);
            flash('Kurz „' . $course['title'] . '“ byl smazán. Přihlášky na něj zůstaly v přehledu přihlášek.');
            break;
        case 'duplikovat':
            $newId = duplicate_course((int) $course['id']);
            flash('Vytvořila se kopie kurzu. Upravte termíny a pak ji zveřejněte.');
            redirect('admin/kurzy/' . $newId);
        case 'nahoru':
        case 'dolu':
            move_course((int) $course['id'], $action === 'nahoru' ? 'up' : 'down');
            break;
        case 'zverejnit':
            db_exec('UPDATE courses SET is_published = ?, updated_at = ? WHERE id = ?', [$course['is_published'] ? 0 : 1, now(), $course['id']]);
            flash($course['is_published'] ? 'Kurz je skrytý – na webu ho nikdo neuvidí.' : 'Kurz je zveřejněný na webu.');
            break;
    }
    redirect('admin/kurzy');
}

/* ---------- Applications ---------- */

function applications_filter(): array
{
    $where = [];
    $params = [];
    $courseId = (int) ($_GET['kurz'] ?? 0);
    $status = (string) ($_GET['stav'] ?? '');
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($courseId) {
        $where[] = 'course_id = ?';
        $params[] = $courseId;
    }
    if (isset(APPLICATION_STATUSES[$status])) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params, compact('courseId', 'status', 'q')];
}

function admin_applications(): void
{
    require_login();
    [$where, $params, $filter] = applications_filter();
    $applications = db_all("SELECT * FROM applications $where ORDER BY created_at DESC, id DESC", $params);
    admin_render('applications', [
        'title' => 'Přihlášky',
        'applications' => $applications,
        'filter' => $filter,
        'courses' => db_all('SELECT id, title FROM courses ORDER BY sort_order, id'),
    ]);
}

function admin_applications_export(): void
{
    require_login();
    [$where, $params] = applications_filter();
    $rows = db_all("SELECT * FROM applications $where ORDER BY created_at, id", $params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="prihlasky-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel opens the Czech characters correctly
    $columns = [
        'created_at' => 'Přijato', 'course_title' => 'Kurz', 'terms' => 'Termín', 'status' => 'Stav',
        'name' => 'Jméno', 'email' => 'E-mail', 'phone' => 'Telefon', 'birth_date' => 'Datum narození',
        'billing' => 'IČ / fakturace', 'is_student' => 'Student', 'needs_translation' => 'Překlad',
        'discount_code' => 'Slevový kód', 'referral' => 'Odkud o nás ví', 'note' => 'Poznámka',
        'consent_marketing' => 'Souhlas s novinkami', 'admin_note' => 'Interní poznámka',
    ];
    fputcsv($out, array_values($columns), ';', '"', '');
    foreach ($rows as $row) {
        $line = [];
        foreach (array_keys($columns) as $key) {
            $value = $row[$key];
            $value = match ($key) {
                'status' => APPLICATION_STATUSES[$value] ?? $value,
                'is_student', 'consent_marketing' => $value ? 'ano' : 'ne',
                'needs_translation' => $value === null ? '' : ($value ? 'ano' : 'ne'),
                'created_at' => format_datetime($value),
                'terms' => str_replace("\n", ', ', (string) $value),
                default => (string) $value,
            };
            // Neutralise spreadsheet formulas typed into the public form.
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                $value = "'" . $value;
            }
            $line[] = $value;
        }
        fputcsv($out, $line, ';', '"', '');
    }
    fclose($out);
}

function admin_application(string $id): void
{
    require_login();
    $application = db_one('SELECT * FROM applications WHERE id = ?', [(int) $id]) ?? not_found();

    if (is_post()) {
        verify_csrf();
        if (post('action') === 'delete') {
            db_exec('DELETE FROM applications WHERE id = ?', [$application['id']]);
            flash('Přihláška byla smazána.');
            redirect('admin/prihlasky');
        }
        $status = post('status');
        db_update('applications', (int) $application['id'], [
            'status' => isset(APPLICATION_STATUSES[$status]) ? $status : $application['status'],
            'admin_note' => mb_substr(post('admin_note'), 0, 5000),
        ]);
        flash('Přihláška je uložená.');
        redirect('admin/prihlasky/' . $application['id']);
    }

    admin_render('application', ['title' => 'Přihláška – ' . $application['name'], 'a' => $application]);
}

/* ---------- Messages ---------- */

function admin_messages(): void
{
    require_login();
    $messages = db_all('SELECT * FROM messages ORDER BY created_at DESC, id DESC');
    db_exec('UPDATE messages SET is_read = 1 WHERE is_read = 0');
    admin_render('messages', ['title' => 'Zprávy z webu', 'messages' => $messages]);
}

function admin_message_delete(string $id): void
{
    require_login();
    if (is_post()) {
        verify_csrf();
        db_exec('DELETE FROM messages WHERE id = ?', [(int) $id]);
        flash('Zpráva byla smazána.');
    }
    redirect('admin/zpravy');
}

/* ---------- Settings & users ---------- */

function admin_settings(): void
{
    require_login();
    if (is_post()) {
        verify_csrf();
        foreach (array_keys(settings_defaults()) as $name) {
            $value = $name === 'storno_terms' ? sanitize_html((string) ($_POST[$name] ?? '')) : post($name);
            save_setting($name, $value);
        }
        flash('Nastavení je uložené.');
        redirect('admin/nastaveni');
    }
    $values = [];
    foreach (array_keys(settings_defaults()) as $name) {
        $values[$name] = setting($name);
    }
    admin_render('settings', ['title' => 'Nastavení', 'values' => $values]);
}

function admin_users(): void
{
    $me = require_login();
    $error = null;
    if (is_post()) {
        verify_csrf();
        $action = post('action');
        if ($action === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $row = db_one('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
            $error = !password_verify($current, $row['password_hash']) ? 'Současné heslo nesouhlasí.' : password_problem($new);
            if (!$error) {
                db_update('users', (int) $me['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
                flash('Heslo bylo změněno.');
                redirect('admin/uzivatele');
            }
        } elseif ($action === 'add') {
            $email = mb_strtolower(post('email'));
            $password = (string) ($_POST['password'] ?? '');
            $error = match (true) {
                post('name') === '' => 'Vyplňte jméno.',
                !valid_email($email) => 'Zadejte platný e-mail.',
                (bool) db_value('SELECT id FROM users WHERE email = ?', [$email]) => 'Uživatel s tímto e-mailem už existuje.',
                default => password_problem($password),
            };
            if (!$error) {
                db_insert('users', ['name' => post('name'), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'created_at' => now()]);
                flash('Uživatel byl přidán. Pošlete mu e-mail a heslo bezpečnou cestou.');
                redirect('admin/uzivatele');
            }
        } elseif ($action === 'delete') {
            $id = (int) post('id');
            if ($id === (int) $me['id']) {
                $error = 'Sami sebe smazat nemůžete.';
            } else {
                db_exec('DELETE FROM users WHERE id = ?', [$id]);
                flash('Uživatel byl odebrán.');
                redirect('admin/uzivatele');
            }
        }
    }
    admin_render('users', [
        'title' => 'Uživatelé',
        'users' => db_all('SELECT id, name, email, last_login_at FROM users ORDER BY name'),
        'error' => $error,
    ]);
}

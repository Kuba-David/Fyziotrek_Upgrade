<?php
/** @var array $a */
$yesNo = fn ($v) => $v ? 'ano' : 'ne';
$rows = [
    'Kurz' => e($a['course_title']) . ($a['course_id'] ? ' <a class="small" href="' . e(url('admin/kurzy/' . $a['course_id'])) . '">(upravit kurz)</a>' : ' <span class="muted small">(kurz byl smazán)</span>'),
    'Termín' => $a['terms'] !== '' ? nl2br(e($a['terms'])) : '<span class="muted">bez termínu – zájemce</span>',
    'E-mail' => '<a href="mailto:' . e($a['email']) . '">' . e($a['email']) . '</a>',
    'Telefon' => '<a href="' . e(tel_href($a['phone'])) . '">' . e($a['phone']) . '</a>',
    'Datum narození' => e($a['birth_date']),
    'IČ / fakturace' => nl2br(e($a['billing'])),
    'Student' => $yesNo($a['is_student']),
    'Překlad' => $a['needs_translation'] === null ? '<span class="muted">–</span>' : $yesNo($a['needs_translation']),
    'Slevový kód' => e($a['discount_code']),
    'Odkud o nás ví' => e($a['referral']),
    'Poznámka' => nl2br(e($a['note'])),
    'Souhlas s novinkami' => $yesNo($a['consent_marketing']),
    'Přijato' => e(format_datetime($a['created_at'])),
];
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('admin/prihlasky')) ?>">← Zpět na přihlášky</a>
        <h1><?= e($a['name']) ?></h1>
    </div>
    <a class="btn btn--ghost" href="mailto:<?= e($a['email']) ?>?subject=<?= rawurlencode($a['course_title']) ?>">Napsat e-mail</a>
</div>

<div class="split">
    <section class="panel">
        <dl class="details">
            <?php foreach ($rows as $label => $value): ?>
                <?php if ($value === '') continue; ?>
                <div><dt><?= e($label) ?></dt><dd><?= $value ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>

    <aside>
        <form method="post" class="panel stack">
            <?= csrf_field() ?>
            <h2 class="panel__title">Zpracování</h2>
            <label class="field">
                <span class="field__label">Stav</span>
                <select name="status">
                    <?php foreach (APPLICATION_STATUSES as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $a['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field__label">Interní poznámka</span>
                <textarea name="admin_note" rows="5" placeholder="např. zaplaceno 12. 3., faktura č. …"><?= e($a['admin_note']) ?></textarea>
                <span class="field__hint">Vidíte ji jen vy v administraci.</span>
            </label>
            <button class="btn btn--primary btn--block" type="submit">Uložit</button>
        </form>
        <form method="post" class="danger-zone" data-confirm="Opravdu smazat přihlášku od <?= e($a['name']) ?>? Tohle nejde vrátit.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button class="link-button danger" type="submit">Smazat přihlášku</button>
        </form>
    </aside>
</div>

<?php
/** @var array $applications */
/** @var array $filter */
/** @var array $courses */
$query = http_build_query(array_filter(['kurz' => $filter['courseId'] ?: null, 'stav' => $filter['status'] ?: null, 'q' => $filter['q'] ?: null]));
?>
<div class="page-head">
    <div>
        <h1>Přihlášky</h1>
        <p class="muted">Všechny přihlášky z webu. Kliknutím otevřete detail, kde můžete změnit stav a připsat poznámku.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('admin/prihlasky/export' . ($query ? '?' . $query : ''))) ?>">⬇ Stáhnout pro Excel</a>
</div>

<form class="filters" method="get">
    <label class="field">
        <span class="field__label">Kurz</span>
        <select name="kurz" onchange="this.form.submit()">
            <option value="">Všechny kurzy</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['id'] ?>"<?= $filter['courseId'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Stav</span>
        <select name="stav" onchange="this.form.submit()">
            <option value="">Všechny stavy</option>
            <?php foreach (APPLICATION_STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filter['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Hledat</span>
        <input type="search" name="q" value="<?= e($filter['q']) ?>" placeholder="jméno, e-mail, telefon">
    </label>
    <button class="btn" type="submit">Filtrovat</button>
    <?php if ($query): ?><a class="btn btn--ghost" href="<?= e(url('admin/prihlasky')) ?>">Zrušit filtr</a><?php endif; ?>
</form>

<?php if (!$applications): ?>
    <div class="empty-state"><p>Žádné přihlášky<?= $query ? ' neodpovídají filtru' : ' zatím nedorazily' ?>.</p></div>
<?php else: ?>
    <p class="muted small">Celkem <?= count($applications) ?></p>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Přijato</th><th>Jméno</th><th>Kurz a termín</th><th>Kontakt</th><th>Stav</th></tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $a): ?>
                <tr class="<?= $a['status'] === 'nova' ? 'is-new' : '' ?>">
                    <td class="nowrap"><?= e(format_datetime($a['created_at'])) ?></td>
                    <td><a class="strong" href="<?= e(url('admin/prihlasky/' . $a['id'])) ?>"><?= e($a['name']) ?></a><?= $a['is_student'] ? ' <span class="tag tag--muted">student</span>' : '' ?></td>
                    <td><?= e($a['course_title']) ?><br><span class="muted small"><?= e(str_replace("\n", ', ', $a['terms'])) ?></span></td>
                    <td><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a><br><span class="small"><?= e($a['phone']) ?></span></td>
                    <td><span class="status status--<?= e($a['status']) ?>"><?= e(APPLICATION_STATUSES[$a['status']] ?? $a['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

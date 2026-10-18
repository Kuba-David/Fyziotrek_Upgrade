<?php
/** @var array $courses */
/** @var array $counts */
$action = fn (array $c, string $a) => e(url('admin/kurzy/' . $c['id'] . '/' . $a));
?>
<div class="page-head">
    <div>
        <h1>Kurzy</h1>
        <p class="muted">Pořadí v tomto seznamu je stejné jako na webu. Kurz upravíte kliknutím na jeho název.</p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('admin/kurzy/novy')) ?>">+ Přidat kurz</a>
</div>

<?php if (!$courses): ?>
    <div class="empty-state">
        <p>Zatím tu není žádný kurz.</p>
        <a class="btn btn--primary" href="<?= e(url('admin/kurzy/novy')) ?>">Přidat první kurz</a>
    </div>
<?php else: ?>
    <ul class="course-rows">
        <?php foreach ($courses as $i => $c): ?>
            <?php $next = next_term($c); $count = $counts[$c['id']] ?? null; ?>
            <li class="course-row<?= $c['is_published'] ? '' : ' is-hidden' ?>">
                <div class="course-row__order">
                    <form method="post" action="<?= $action($c, 'nahoru') ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" title="Posunout výš" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
                    <form method="post" action="<?= $action($c, 'dolu') ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" title="Posunout níž" <?= $i === count($courses) - 1 ? 'disabled' : '' ?>>↓</button></form>
                </div>
                <div class="course-row__main">
                    <a class="course-row__title" href="<?= e(url('admin/kurzy/' . $c['id'])) ?>"><?= e($c['title']) ?></a>
                    <div class="course-row__meta">
                        <?php if (!$c['is_published']): ?><span class="tag tag--muted">Skrytý</span><?php endif; ?>
                        <?php if (!$c['registration_open']): ?><span class="tag tag--warn">Přihlašování uzavřeno</span><?php endif; ?>
                        <?php if (course_is_past($c)): ?><span class="tag tag--muted" title="Všechny termíny už proběhly – na webu je kurz v sekci proběhlých">Proběhlo</span><?php endif; ?>
                        <?php if ($next): ?>
                            <span>Nejbližší termín: <strong><?= e(format_date($next['start_date'])) ?></strong></span>
                        <?php elseif ($c['terms']): ?>
                            <span><?= e($c['terms'][0]['date_text']) ?></span>
                        <?php else: ?>
                            <span class="muted">Bez termínu</span>
                        <?php endif; ?>
                        <?php if ($count): ?>
                            <a href="<?= e(url('admin/prihlasky?kurz=' . $c['id'])) ?>"><?= (int) $count['total'] ?> přihlášek<?= $count['fresh'] ? ' (' . (int) $count['fresh'] . ' nových)' : '' ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="course-row__actions">
                    <a class="btn btn--small" href="<?= e(url('admin/kurzy/' . $c['id'])) ?>">Upravit</a>
                    <?php if ($c['is_published']): ?>
                        <a class="btn btn--small btn--ghost" href="<?= e(url('kurzy/' . $c['slug'])) ?>" target="_blank" rel="noopener">Na webu ↗</a>
                    <?php endif; ?>
                    <details class="menu">
                        <summary class="btn btn--small btn--ghost" aria-label="Další akce">•••</summary>
                        <div class="menu__panel">
                            <form method="post" action="<?= $action($c, 'zverejnit') ?>"><?= csrf_field() ?>
                                <button type="submit"><?= $c['is_published'] ? 'Skrýt z webu' : 'Zveřejnit na webu' ?></button>
                            </form>
                            <form method="post" action="<?= $action($c, 'duplikovat') ?>"><?= csrf_field() ?>
                                <button type="submit">Vytvořit kopii</button>
                            </form>
                            <form method="post" action="<?= $action($c, 'smazat') ?>" data-confirm="Opravdu smazat kurz „<?= e($c['title']) ?>“? Tohle nejde vrátit. (Pokud ho chcete jen dočasně schovat, použijte „Skrýt z webu“.)"><?= csrf_field() ?>
                                <button type="submit" class="danger">Smazat kurz</button>
                            </form>
                        </div>
                    </details>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

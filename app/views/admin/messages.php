<?php /** @var array $messages */ ?>
<div class="page-head">
    <div>
        <h1>Zprávy z webu</h1>
        <p class="muted">Dotazy z kontaktního formuláře. Každá zpráva zároveň přišla e-mailem.</p>
    </div>
</div>

<?php if (!$messages): ?>
    <div class="empty-state"><p>Zatím žádné zprávy.</p></div>
<?php else: ?>
    <div class="message-list">
        <?php foreach ($messages as $m): ?>
            <article class="panel message<?= $m['is_read'] ? '' : ' is-new' ?>">
                <header class="message__head">
                    <div>
                        <strong><?= e($m['name']) ?></strong>
                        · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
                        <?php if ($m['phone']): ?> · <a href="<?= e(tel_href($m['phone'])) ?>"><?= e($m['phone']) ?></a><?php endif; ?>
                    </div>
                    <span class="muted small"><?= e(format_datetime($m['created_at'])) ?></span>
                </header>
                <p class="message__body"><?= nl2br(e($m['message'])) ?></p>
                <footer class="message__foot">
                    <a class="btn btn--small" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: dotaz z webu Fyziotrek') ?>">Odpovědět</a>
                    <form method="post" action="<?= e(url('admin/zpravy/' . $m['id'] . '/smazat')) ?>" data-confirm="Smazat tuto zprávu?">
                        <?= csrf_field() ?>
                        <button class="link-button danger" type="submit">Smazat</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

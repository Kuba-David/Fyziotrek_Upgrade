<?php
/** @var array $course */
$canApply = course_accepts_applications($course);
$applyUrl = url('kurzy/' . $course['slug'] . '/prihlaska');
$facts = array_filter([
    'Místo konání' => $course['location'],
    'Lektor' => $course['lecturer'],
    'Překlad' => $course['translation'],
    'Kredity' => $course['credits'],
    'Ubytování' => $course['accommodation'],
]);
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Drobečková navigace">
            <a href="<?= e(url('/')) ?>">Úvod</a> <span aria-hidden="true">/</span>
            <a href="<?= e(url('/#kurzy')) ?>">Kurzy</a>
        </nav>
        <?php if ($course['badge']): ?><p class="badge badge--light"><?= e($course['badge']) ?></p><?php endif; ?>
        <h1 class="page-hero__title"><?= e($course['title']) ?></h1>
        <?php if ($course['summary']): ?><p class="page-hero__lead"><?= e($course['summary']) ?></p><?php endif; ?>
    </div>
</section>

<div class="container course-layout">
    <aside class="course-facts" aria-label="Základní informace">
        <?php if ($course['image']): ?>
            <img class="course-facts__image" src="<?= e(url($course['image'])) ?>" alt="">
        <?php endif; ?>
        <?php if ($course['terms']): ?>
            <h2 class="course-facts__heading">Termíny</h2>
            <ul class="term-list term-list--large">
                <?php foreach ($course['terms'] as $term): ?>
                    <li class="term-list__item<?= $term['is_past'] ? ' is-past' : '' ?>">
                        <span>
                            <?php if ($term['label']): ?><strong><?= e($term['label']) ?>:</strong><br><?php endif; ?>
                            <?= e($term['date_text']) ?>
                            <?php if ($term['is_past']): ?><em>(proběhlo)</em><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($course['price']): ?>
            <h2 class="course-facts__heading">Cena</h2>
            <p class="course-facts__price"><?= format_text($course['price']) ?></p>
            <?php if ($course['price_note']): ?><p class="course-facts__note"><?= format_text($course['price_note']) ?></p><?php endif; ?>
        <?php endif; ?>

        <?php if ($facts): ?>
            <dl class="facts">
                <?php foreach ($facts as $label => $value): ?>
                    <div class="facts__row">
                        <dt><?= e($label) ?></dt>
                        <dd><?= format_text($value) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>

        <?php if ($course['files']): ?>
            <h2 class="course-facts__heading">Ke stažení</h2>
            <ul class="downloads">
                <?php foreach ($course['files'] as $file): ?>
                    <li>
                        <a href="<?= e(url($file['path'])) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 16 7 11l1.4-1.45 2.6 2.6V4h2v8.15l2.6-2.6L17 11l-5 5Zm-6 4q-.83 0-1.41-.59Q4 18.83 4 18v-3h2v3h12v-3h2v3q0 .83-.59 1.41Q18.83 20 18 20H6Z"/></svg>
                            <span><?= e($file['label']) ?> <small><?= e(strtoupper(pathinfo($file['path'], PATHINFO_EXTENSION))) ?></small></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($canApply): ?>
            <a class="button button--large button--block" href="<?= e($applyUrl) ?>"><?= open_terms($course) ? 'Přihlásit se na kurz' : 'Mám zájem o kurz' ?></a>
        <?php else: ?>
            <p class="course-facts__closed">Přihlašování na tento kurz je momentálně uzavřené. Pro informace nás <a href="<?= e(url('/#kontakty')) ?>">kontaktujte</a>.</p>
        <?php endif; ?>
    </aside>

    <article class="course-content">
        <?php if ($course['description']): ?>
            <div class="rich rich--lead"><?= render_rich($course['description']) ?></div>
        <?php endif; ?>

        <?php foreach ($course['terms'] as $term): ?>
            <?php if (trim($term['content']) === '') continue; ?>
            <section class="term-section<?= $term['is_past'] ? ' is-past' : '' ?>">
                <header class="term-section__head">
                    <h2><?= e($term['label'] ?: $course['title']) ?></h2>
                    <p><?= e($term['date_text']) ?><?= $term['is_past'] ? ' – proběhlo' : '' ?></p>
                </header>
                <div class="rich"><?= render_rich($term['content']) ?></div>
            </section>
        <?php endforeach; ?>

        <?php if ($canApply): ?>
            <div class="cta-box">
                <div>
                    <h2>Zaujal vás kurz?</h2>
                    <p>Vyplnění přihlášky zabere pár minut. Ozveme se vám s potvrzením a platebními údaji.</p>
                </div>
                <a class="button button--large button--light" href="<?= e($applyUrl) ?>">Přejít k přihlášce</a>
            </div>
        <?php endif; ?>
    </article>
</div>

<?php if ($canApply): ?>
    <div class="sticky-cta" data-sticky-cta>
        <span class="sticky-cta__title"><?= e($course['title']) ?></span>
        <a class="button" href="<?= e($applyUrl) ?>">Přihlásit se</a>
    </div>
<?php endif; ?>

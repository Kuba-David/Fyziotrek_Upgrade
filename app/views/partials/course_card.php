<?php
/** @var array $course */
$next = next_term($course);
$detailUrl = url('kurzy/' . $course['slug']);
?>
<article class="course-card course-card--v<?= (int) $course['id'] % 3 ?><?= course_is_past($course) ? ' course-card--past' : '' ?>">
    <a class="course-card__media" href="<?= e($detailUrl) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($course['image']): ?>
            <img src="<?= e(url($course['image'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="course-card__placeholder"><?= e(mb_substr($course['title'], 0, 1)) ?></span>
        <?php endif; ?>
        <?php if ($next): ?>
            <span class="course-card__date">
                <span class="course-card__date-label">Nejbližší termín</span>
                <?= e(format_date($next['start_date'])) ?>
            </span>
        <?php endif; ?>
    </a>
    <div class="course-card__body">
        <?php if ($course['badge']): ?>
            <p class="badge"><?= e($course['badge']) ?></p>
        <?php endif; ?>
        <h3 class="course-card__title"><a href="<?= e($detailUrl) ?>"><?= e($course['title']) ?></a></h3>

        <?php if ($course['terms']): ?>
            <ul class="term-list">
                <?php foreach ($course['terms'] as $term): ?>
                    <li class="term-list__item<?= $term['is_past'] ? ' is-past' : '' ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7Zm-2 8h14v10H5V10Z"/></svg>
                        <span>
                            <?php if ($term['label']): ?><strong><?= e($term['label']) ?>:</strong> <?php endif; ?>
                            <?= e($term['date_text']) ?>
                            <?php if ($term['is_past']): ?><em>(proběhlo)</em><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($course['location']): ?>
            <p class="course-card__meta">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>
                <span><?= e(preg_replace('~\s*\(?https?://\S+\)?~', '', $course['location'])) ?></span>
            </p>
        <?php endif; ?>

        <?php if ($course['summary']): ?>
            <p class="course-card__summary"><?= e($course['summary']) ?></p>
        <?php endif; ?>

        <div class="course-card__actions">
            <a class="button button--ghost" href="<?= e($detailUrl) ?>">Více o kurzu</a>
            <?php if (course_accepts_applications($course)): ?>
                <a class="button" href="<?= e($detailUrl . '/prihlaska') ?>"><?= open_terms($course) ? 'Přihlásit se' : 'Mám zájem' ?></a>
            <?php endif; ?>
        </div>
    </div>
</article>

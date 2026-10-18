<?php /** @var array $course */ ?>
<section class="section">
    <div class="container container--narrow done">
        <div class="done__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2Z"/></svg>
        </div>
        <h1 class="section__title">Děkujeme, přihláška je odeslaná</h1>
        <p class="section__lead">Přihlášku na kurz <strong><?= e($course['title']) ?></strong> jsme přijali. Na váš e-mail jsme poslali potvrzení a brzy se vám ozveme s dalšími informacemi.</p>
        <p class="form__hint">Potvrzení nedorazilo? Zkontrolujte složku se spamem, případně nám napište na <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>.</p>
        <a class="button button--large" href="<?= e(url('/#kurzy')) ?>">Zpět na přehled kurzů</a>
    </div>
</section>

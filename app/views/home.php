<?php
/** @var array $courses */
/** @var ?array $flash */
?>
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__text">
            <p class="eyebrow">Vzdělávání pro fyzioterapeuty</p>
            <h1 class="hero__title">Vaše cesta ke&nbsp;<span class="text-gradient">vzdělání</span></h1>
            <p class="hero__lead">Pořádáme odborné kurzy a semináře s mezinárodními lektory – od Mulligan konceptu přes dry needling až po onkologickou rehabilitaci.</p>
            <div class="hero__actions">
                <a class="button button--large" href="#kurzy">Přehled kurzů</a>
                <a class="button button--large button--ghost" href="#kontakty">Napište nám</a>
            </div>
        </div>
        <div class="hero__visual" aria-hidden="true">
            <img src="<?= e(url('assets/img/logo-full.svg')) ?>" alt="" width="406" height="262">
        </div>
    </div>
</section>

<section class="section" id="kurzy">
    <div class="container">
        <div class="section__head">
            <p class="eyebrow">Kurzy a semináře</p>
            <h2 class="section__title">Připravované kurzy</h2>
            <p class="section__lead">Vyberte si kurz, podívejte se na program a rovnou se přihlaste. Počet míst je u většiny kurzů omezený.</p>
        </div>

        <?php
        $upcoming = array_filter($courses, fn ($c) => !course_is_past($c));
        $past = array_filter($courses, 'course_is_past');
        ?>
        <?php if ($upcoming): ?>
            <div class="course-grid">
                <?php foreach ($upcoming as $course): ?>
                    <?= render_to_string('partials/course_card', ['course' => $course]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">Momentálně nemáme vypsané žádné nové termíny. Sledujte nás na Facebooku nebo nám napište.</p>
        <?php endif; ?>

        <?php if ($past): ?>
            <div class="past-courses">
                <h3 class="past-courses__title">Kurzy, které už proběhly</h3>
                <p class="past-courses__lead">Máte o některý zájem? Napište nám – dáme vědět, až vypíšeme další termín.</p>
                <div class="course-grid">
                    <?php foreach ($past as $course): ?>
                        <?= render_to_string('partials/course_card', ['course' => $course]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tinted" id="treninky">
    <div class="container">
        <div class="section__head">
            <p class="eyebrow">Pohyb pod dohledem odborníka</p>
            <h2 class="section__title">Tréninky</h2>
        </div>
        <div class="feature-grid">
            <figure class="feature">
                <img src="<?= e(url('assets/img/trenink-skupina.webp')) ?>" alt="Skupinová lekce cvičení" loading="lazy" width="1000" height="708">
                <figcaption>
                    <h3>Skupinové lekce</h3>
                    <p>Cvičení v malých skupinách zaměřené na sílu, stabilitu a správné pohybové vzory.</p>
                </figcaption>
            </figure>
            <figure class="feature">
                <img src="<?= e(url('assets/img/trenink-tymy.webp')) ?>" alt="Fyzioterapeut při práci se sportovním týmem" loading="lazy" width="800" height="1067">
                <figcaption>
                    <h3>Vedení tréninku týmů</h3>
                    <p>Spolupráce se sportovními týmy – prevence zranění, regenerace a kondiční příprava.</p>
                </figcaption>
            </figure>
            <figure class="feature">
                <img src="<?= e(url('assets/img/trenink-individual.webp')) ?>" alt="Individuální trénink s fyzioterapeutkou" loading="lazy" width="1000" height="666">
                <figcaption>
                    <h3>Individuální tréninky</h3>
                    <p>Trénink na míru vašim cílům a zdravotnímu stavu pod vedením fyzioterapeuta.</p>
                </figcaption>
            </figure>
        </div>
    </div>
</section>

<section class="section" id="fyzioterapie">
    <div class="container">
        <div class="section__head">
            <p class="eyebrow">Fyzioterapie</p>
            <h2 class="section__title">Tento projekt vám přinášejí</h2>
        </div>
        <div class="partners">
            <a class="partner" href="http://www.bohemiafyzio.cz" target="_blank" rel="noopener">
                <img src="<?= e(url('assets/img/bohemia.svg')) ?>" alt="Bohemia – fyzioterapie a masáže" loading="lazy" width="377" height="92">
            </a>
            <span class="partners__amp" aria-hidden="true">&amp;</span>
            <a class="partner" href="https://bodytherapist.cz/" target="_blank" rel="noopener">
                <img src="<?= e(url('assets/img/bodytherapist.png')) ?>" alt="Body Therapist" loading="lazy" width="272" height="134">
            </a>
        </div>
        <p class="partners__caption">Spolupracujeme také s</p>
        <div class="partners partners--small">
            <a class="partner" href="https://www.complexcore.at" target="_blank" rel="noopener"><img src="<?= e(url('assets/img/complexcore.png')) ?>" alt="ComplexCore+" loading="lazy" width="275" height="44"></a>
            <a class="partner" href="http://www.jahoda-sports.com/" target="_blank" rel="noopener"><img src="<?= e(url('assets/img/jahoda.png')) ?>" alt="Jahoda Sports" loading="lazy" width="162" height="92"></a>
            <a class="partner" href="https://dryneedling.co.za/" target="_blank" rel="noopener"><img src="<?= e(url('assets/img/odns.png')) ?>" alt="ODNS – dry needling" loading="lazy" width="92" height="107"></a>
            <span class="partner"><img src="<?= e(url('assets/img/mulligan-cmp.webp')) ?>" alt="Mulligan – Certified Mulligan Practitioner" loading="lazy" width="526" height="197"></span>
        </div>
    </div>
</section>

<section class="section section--dark" id="kontakty">
    <div class="container contact">
        <div class="contact__info">
            <p class="eyebrow eyebrow--light">Kontakt</p>
            <h2 class="section__title">Nebojte se nás zeptat</h2>
            <ul class="contact-list">
                <?php foreach ([1, 2] as $i): ?>
                    <?php if (setting("contact_{$i}_name")): ?>
                        <li>
                            <span class="contact-list__label"><?= e(setting("contact_{$i}_name")) ?></span>
                            <a href="<?= e(tel_href(setting("contact_{$i}_phone"))) ?>"><?= e(setting("contact_{$i}_phone")) ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li>
                    <span class="contact-list__label">E-mail</span>
                    <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a>
                </li>
                <li>
                    <span class="contact-list__label">Adresa</span>
                    <span><?= e(setting('address')) ?></span>
                </li>
            </ul>
        </div>

        <form class="form contact__form" method="post" action="<?= e(url('kontakt')) ?>">
            <?= csrf_field() ?>
            <?php if ($flash): ?>
                <p class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
            <?php endif; ?>
            <div class="form__row form__row--2">
                <label class="field">
                    <span class="field__label">Jméno *</span>
                    <input type="text" name="name" required autocomplete="name" value="<?= e(old('name')) ?>">
                </label>
                <label class="field">
                    <span class="field__label">Telefon</span>
                    <input type="tel" name="phone" autocomplete="tel" value="<?= e(old('phone')) ?>">
                </label>
            </div>
            <label class="field">
                <span class="field__label">E-mail *</span>
                <input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>">
            </label>
            <label class="field">
                <span class="field__label">Zpráva *</span>
                <textarea name="message" rows="5" required><?= e(old('message')) ?></textarea>
            </label>
            <label class="hp" aria-hidden="true">Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <button class="button button--large button--light" type="submit">Odeslat zprávu</button>
        </form>
    </div>
</section>
<?php unset($_SESSION['old']); ?>

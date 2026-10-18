<?php
/** @var array $course */
/** @var array $terms */
/** @var array $errors */
/** @var array $values */
$v = fn (string $key) => e(is_string($values[$key] ?? null) ? $values[$key] : '');
$checked = fn (string $key) => !empty($values[$key]) ? ' checked' : '';
$err = function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<span class="field__error">' . e($errors[$key]) . '</span>' : '';
};
$selectedTerms = $values['terms'] ?? (count($terms) === 1 ? [(int) $terms[0]['id']] : []);
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Drobečková navigace">
            <a href="<?= e(url('/')) ?>">Úvod</a> <span aria-hidden="true">/</span>
            <a href="<?= e(url('kurzy/' . $course['slug'])) ?>"><?= e($course['title']) ?></a>
        </nav>
        <h1 class="page-hero__title">Přihláška na kurz</h1>
        <p class="page-hero__lead"><?= e($course['title']) ?></p>
    </div>
</section>

<div class="container container--narrow section section--tight">
    <?php if (!course_accepts_applications($course)): ?>
        <p class="alert alert--error">Přihlašování na tento kurz je momentálně uzavřené. Pro informace nás <a href="<?= e(url('/#kontakty')) ?>">kontaktujte</a>.</p>
    <?php else: ?>
        <?php if ($errors): ?>
            <p class="alert alert--error" role="alert">
                <?= e($errors['form'] ?? 'Přihlášku se nepodařilo odeslat – zkontrolujte prosím označená pole.') ?>
            </p>
        <?php endif; ?>

        <form class="form form--card" method="post" novalidate>
            <?= csrf_field() ?>

            <fieldset class="form__section">
                <legend>Termín</legend>
                <?php if ($terms): ?>
                    <div class="choice-list">
                        <?php foreach ($terms as $term): ?>
                            <label class="choice">
                                <input type="checkbox" name="terms[]" value="<?= (int) $term['id'] ?>"<?= in_array((int) $term['id'], $selectedTerms, true) ? ' checked' : '' ?>>
                                <span>
                                    <?php if ($term['label']): ?><strong><?= e($term['label']) ?></strong><br><?php endif; ?>
                                    <?= e($term['date_text']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $err('terms') ?>
                <?php else: ?>
                    <p class="form__hint">Termín kurzu zatím není stanovený. Vyplňte přihlášku a ozveme se vám, jakmile termín vypíšeme.</p>
                <?php endif; ?>
            </fieldset>

            <fieldset class="form__section">
                <legend>Kontaktní údaje</legend>
                <label class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
                    <span class="field__label">Titul, jméno a příjmení *</span>
                    <input type="text" name="name" required autocomplete="name" value="<?= $v('name') ?>">
                    <?= $err('name') ?>
                </label>
                <div class="form__row form__row--2">
                    <label class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                        <span class="field__label">E-mail *</span>
                        <input type="email" name="email" required autocomplete="email" value="<?= $v('email') ?>">
                        <?= $err('email') ?>
                    </label>
                    <label class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
                        <span class="field__label">Telefon *</span>
                        <input type="tel" name="phone" required autocomplete="tel" value="<?= $v('phone') ?>">
                        <?= $err('phone') ?>
                    </label>
                </div>
                <label class="field">
                    <span class="field__label">Datum narození</span>
                    <input type="text" name="birth_date" placeholder="např. 1. 1. 1990" value="<?= $v('birth_date') ?>">
                </label>
                <label class="field">
                    <span class="field__label">IČ, fakturační adresa</span>
                    <textarea name="billing" rows="3" placeholder="Vyplňte, pokud chcete fakturu na firmu nebo jinou adresu."><?= $v('billing') ?></textarea>
                </label>
            </fieldset>

            <fieldset class="form__section">
                <legend>Další informace</legend>
                <label class="choice choice--inline">
                    <input type="checkbox" name="is_student" value="1"<?= $checked('is_student') ?>>
                    <span>Jsem student/ka</span>
                </label>

                <?php if ($course['ask_translation']): ?>
                    <div class="field<?= isset($errors['needs_translation']) ? ' has-error' : '' ?>">
                        <span class="field__label">Potřebuji překlad *</span>
                        <div class="choice-row">
                            <label class="choice choice--inline"><input type="radio" name="needs_translation" value="1"<?= ($values['needs_translation'] ?? '') === '1' ? ' checked' : '' ?>> <span>Ano</span></label>
                            <label class="choice choice--inline"><input type="radio" name="needs_translation" value="0"<?= ($values['needs_translation'] ?? '') === '0' ? ' checked' : '' ?>> <span>Ne</span></label>
                        </div>
                        <?= $err('needs_translation') ?>
                    </div>
                <?php endif; ?>

                <div class="form__row form__row--2">
                    <label class="field">
                        <span class="field__label">Slevový kód</span>
                        <input type="text" name="discount_code" value="<?= $v('discount_code') ?>">
                    </label>
                    <label class="field">
                        <span class="field__label">Odkud jste se o nás dozvěděli?</span>
                        <input type="text" name="referral" value="<?= $v('referral') ?>">
                    </label>
                </div>
                <label class="field">
                    <span class="field__label">Poznámka</span>
                    <textarea name="note" rows="3"><?= $v('note') ?></textarea>
                </label>
            </fieldset>

            <fieldset class="form__section">
                <legend>Souhlasy</legend>
                <details class="storno">
                    <summary>Zobrazit storno podmínky</summary>
                    <div class="rich"><?= setting('storno_terms') ?></div>
                </details>
                <label class="choice<?= isset($errors['consent_storno']) ? ' has-error' : '' ?>">
                    <input type="checkbox" name="consent_storno" value="1" required<?= $checked('consent_storno') ?>>
                    <span>Souhlasím se storno podmínkami. *</span>
                </label>
                <?= $err('consent_storno') ?>
                <label class="choice">
                    <input type="checkbox" name="consent_marketing" value="1"<?= $checked('consent_marketing') ?>>
                    <span>Souhlasím s tím, že organizátor kurzu může používat můj e-mail a telefonní číslo pro zasílání informací o kurzu a dalších nabídkách. Souhlas mohu kdykoli odvolat na adrese <?= e(setting('contact_email')) ?>. Organizátor nebude sdílet mé kontaktní údaje s třetími stranami bez mého souhlasu.</span>
                </label>
            </fieldset>

            <label class="hp" aria-hidden="true">Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <button class="button button--large button--block" type="submit">Závazně přihlásit</button>
            <p class="form__hint form__hint--center">Pole označená * jsou povinná. Po odeslání vám přijde potvrzení e-mailem.</p>
        </form>
    <?php endif; ?>
</div>

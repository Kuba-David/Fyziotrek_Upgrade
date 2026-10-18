<?php /** @var array $values */ ?>
<div class="page-head">
    <div>
        <h1>Nastavení</h1>
        <p class="muted">Kontakty zobrazené na webu, kam chodí upozornění a texty v přihláškách.</p>
    </div>
</div>

<form method="post" class="settings-form">
    <?= csrf_field() ?>
    <section class="panel">
        <h2 class="panel__title">Upozornění</h2>
        <label class="field">
            <span class="field__label">Kam posílat nové přihlášky a dotazy</span>
            <input type="text" name="notify_emails" value="<?= e($values['notify_emails']) ?>">
            <span class="field__hint">Víc adres oddělte čárkou.</span>
        </label>
        <label class="field">
            <span class="field__label">Potvrzovací e-mail pro přihlášeného</span>
            <textarea name="application_confirmation" rows="7"><?= e($values['application_confirmation']) ?></textarea>
            <span class="field__hint">Místo <code>{kurz}</code> se doplní název kurzu. Pod text se automaticky přidá shrnutí přihlášky.</span>
        </label>
    </section>

    <section class="panel">
        <h2 class="panel__title">Kontakty na webu</h2>
        <div class="grid grid--2">
            <label class="field"><span class="field__label">Kontakt 1 – jméno</span><input type="text" name="contact_1_name" value="<?= e($values['contact_1_name']) ?>"></label>
            <label class="field"><span class="field__label">Kontakt 1 – telefon</span><input type="text" name="contact_1_phone" value="<?= e($values['contact_1_phone']) ?>"></label>
            <label class="field"><span class="field__label">Kontakt 2 – jméno</span><input type="text" name="contact_2_name" value="<?= e($values['contact_2_name']) ?>"></label>
            <label class="field"><span class="field__label">Kontakt 2 – telefon</span><input type="text" name="contact_2_phone" value="<?= e($values['contact_2_phone']) ?>"></label>
            <label class="field"><span class="field__label">E-mail</span><input type="email" name="contact_email" value="<?= e($values['contact_email']) ?>"></label>
            <label class="field"><span class="field__label">Facebook (odkaz)</span><input type="url" name="facebook_url" value="<?= e($values['facebook_url']) ?>"></label>
            <label class="field grid__full"><span class="field__label">Adresa</span><input type="text" name="address" value="<?= e($values['address']) ?>"></label>
        </div>
    </section>

    <section class="panel">
        <h2 class="panel__title">Storno podmínky</h2>
        <p class="muted">Zobrazí se v každé přihlášce; přihlašovaný s nimi musí souhlasit.</p>
        <div class="editor" data-editor>
            <textarea name="storno_terms" hidden><?= e(html_for_editor($values['storno_terms'])) ?></textarea>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Uložit nastavení</button>
    </div>
</form>

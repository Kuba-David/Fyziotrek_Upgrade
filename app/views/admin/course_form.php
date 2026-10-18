<?php
/** @var array $course */
/** @var array $errors */
$isNew = empty($course['id']);
$err = fn (string $key) => isset($errors[$key]) ? '<span class="field__error">' . e($errors[$key]) . '</span>' : '';
$checked = fn ($value) => $value ? ' checked' : '';

$renderTerm = function (array $term, string|int $index) use ($errors, $checked): string {
    ob_start(); ?>
    <div class="term-card" data-term>
        <div class="term-card__head">
            <strong class="term-card__title" data-term-title><?= e(trim(($term['label'] ?? '') . ' ' . ($term['date_text'] ?? '')) ?: 'Nový termín') ?></strong>
            <div class="term-card__tools">
                <button type="button" class="icon-btn" data-term-up title="Posunout výš">↑</button>
                <button type="button" class="icon-btn" data-term-down title="Posunout níž">↓</button>
                <button type="button" class="icon-btn icon-btn--danger" data-term-remove title="Odebrat termín">✕</button>
            </div>
        </div>
        <input type="hidden" name="terms[<?= $index ?>][id]" value="<?= e((string) ($term['id'] ?? '')) ?>">
        <div class="grid grid--3">
            <label class="field">
                <span class="field__label">Označení <span class="muted">(nepovinné)</span></span>
                <input type="text" name="terms[<?= $index ?>][label]" value="<?= e($term['label'] ?? '') ?>" placeholder="např. Horní kvadrant, LEVEL 2" data-term-label>
            </label>
            <label class="field">
                <span class="field__label">Začátek</span>
                <input type="date" name="terms[<?= $index ?>][start_date]" value="<?= e($term['start_date'] ?? '') ?>" data-term-start>
            </label>
            <label class="field">
                <span class="field__label">Konec</span>
                <input type="date" name="terms[<?= $index ?>][end_date]" value="<?= e($term['end_date'] ?? '') ?>" data-term-end>
            </label>
        </div>
        <label class="field">
            <span class="field__label">Jak se termín zobrazí na webu</span>
            <input type="text" name="terms[<?= $index ?>][date_text]" value="<?= e($term['date_text'] ?? '') ?>" placeholder="např. 27.–29. 11. 2026, nebo „připravujeme v roce 2027“" data-term-text>
            <span class="field__hint">Když necháte prázdné, doplní se samo z data začátku a konce. Termín bez data (např. „připravujeme“) se nikdy nepřesune mezi proběhlé.</span>
        </label>
        <?= isset($errors["terms.$index"]) ? '<span class="field__error">' . e($errors["terms.$index"]) . '</span>' : '' ?>
        <label class="check">
            <input type="hidden" name="terms[<?= $index ?>][registration_open]" value="0">
            <input type="checkbox" name="terms[<?= $index ?>][registration_open]" value="1"<?= $checked($term['registration_open'] ?? 1) ?>>
            <span>Na tento termín se dá přihlásit</span>
        </label>
        <details class="term-card__content">
            <summary>Program a obsah tohoto termínu <span class="muted">(nepovinné)</span></summary>
            <div class="editor" data-editor>
                <textarea name="terms[<?= $index ?>][content]" hidden><?= e(html_for_editor($term['content'] ?? '')) ?></textarea>
            </div>
        </details>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('admin/kurzy')) ?>">← Zpět na kurzy</a>
        <h1><?= $isNew ? 'Nový kurz' : e($course['title']) ?></h1>
    </div>
    <?php if (!$isNew && $course['is_published']): ?>
        <a class="btn btn--ghost" href="<?= e(url('kurzy/' . $course['slug'])) ?>" target="_blank" rel="noopener">Zobrazit na webu ↗</a>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
    <p class="notice notice--error">Kurz se neuložil – opravte prosím označená pole.</p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="course-form" data-course-form>
    <?= csrf_field() ?>
    <div class="course-form__main">
        <section class="panel">
            <h2 class="panel__title">Základní informace</h2>
            <label class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
                <span class="field__label">Název kurzu *</span>
                <input type="text" name="title" required value="<?= e($course['title']) ?>" data-slug-source>
                <?= $err('title') ?>
            </label>
            <label class="field">
                <span class="field__label">Krátký popis na kartičku <span class="muted">(2–3 věty)</span></span>
                <textarea name="summary" rows="3" maxlength="600"><?= e($course['summary']) ?></textarea>
                <span class="field__hint">Zobrazí se v přehledu kurzů na úvodní stránce a ve výsledcích Googlu.</span>
            </label>
            <div class="grid grid--2">
                <label class="field">
                    <span class="field__label">Zvýrazněná poznámka <span class="muted">(nepovinné)</span></span>
                    <input type="text" name="badge" maxlength="120" value="<?= e($course['badge']) ?>" placeholder="např. Počet míst je omezen!">
                </label>
                <label class="field">
                    <span class="field__label">Adresa stránky</span>
                    <span class="input-prefix"><span><?= e(url('kurzy/')) ?></span><input type="text" name="slug" value="<?= e($course['slug']) ?>" placeholder="vytvoří se z názvu" data-slug></span>
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel__title">Termíny</h2>
            <p class="muted">Kurz může mít víc termínů nebo částí (např. horní a dolní kvadrant). Termíny, které už proběhly, se na webu automaticky přeškrtnou a nedá se na ně přihlásit.</p>
            <div class="terms" data-terms>
                <?php foreach (($course['terms'] ?: []) as $i => $term): ?>
                    <?= $renderTerm($term, $i) ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn--ghost" data-term-add>+ Přidat termín</button>
            <template data-term-template><?= $renderTerm(['registration_open' => 1], '__INDEX__') ?></template>
        </section>

        <section class="panel">
            <h2 class="panel__title">Praktické informace</h2>
            <p class="muted">Vyplňte jen to, co ke kurzu patří – prázdná pole se na webu nezobrazí. Odkazy (https://…) se samy změní na klikací.</p>
            <div class="grid grid--2">
                <label class="field">
                    <span class="field__label">Místo konání</span>
                    <textarea name="location" rows="2"><?= e($course['location']) ?></textarea>
                </label>
                <label class="field">
                    <span class="field__label">Lektor</span>
                    <textarea name="lecturer" rows="2"><?= e($course['lecturer']) ?></textarea>
                </label>
                <label class="field">
                    <span class="field__label">Cena</span>
                    <textarea name="price" rows="2" placeholder="např. 12 000 Kč"><?= e($course['price']) ?></textarea>
                </label>
                <label class="field">
                    <span class="field__label">Slevy a poznámka k ceně</span>
                    <textarea name="price_note" rows="2"><?= e($course['price_note']) ?></textarea>
                </label>
                <label class="field">
                    <span class="field__label">Překlad</span>
                    <input type="text" name="translation" value="<?= e($course['translation']) ?>">
                </label>
                <label class="field">
                    <span class="field__label">Kredity</span>
                    <input type="text" name="credits" value="<?= e($course['credits']) ?>" placeholder="např. 12 kreditů UNIFY ČR">
                </label>
                <label class="field grid__full">
                    <span class="field__label">Ubytování</span>
                    <input type="text" name="accommodation" value="<?= e($course['accommodation']) ?>">
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel__title">Popis kurzu</h2>
            <p class="muted">Hlavní text na stránce kurzu. Program jednotlivých termínů se píše přímo u termínu výše.</p>
            <div class="editor" data-editor>
                <textarea name="description" hidden><?= e(html_for_editor($course['description'])) ?></textarea>
            </div>
        </section>
    </div>

    <aside class="course-form__side">
        <section class="panel panel--sticky">
            <h2 class="panel__title">Zveřejnění</h2>
            <label class="check">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"<?= $checked($course['is_published']) ?>>
                <span><strong>Zobrazit na webu</strong><br><span class="muted small">Odškrtnutím kurz schováte, ale nesmažete.</span></span>
            </label>
            <label class="check">
                <input type="hidden" name="registration_open" value="0">
                <input type="checkbox" name="registration_open" value="1"<?= $checked($course['registration_open']) ?>>
                <span><strong>Přijímat přihlášky</strong><br><span class="muted small">Např. když je kurz plný, odškrtněte.</span></span>
            </label>
            <label class="check">
                <input type="hidden" name="ask_translation" value="0">
                <input type="checkbox" name="ask_translation" value="1"<?= $checked($course['ask_translation']) ?>>
                <span><strong>V přihlášce se ptát na překlad</strong></span>
            </label>
            <button class="btn btn--primary btn--block" type="submit"><?= $isNew ? 'Vytvořit kurz' : 'Uložit změny' ?></button>
            <?php if (!$isNew): ?>
                <p class="muted small center">Naposledy upraveno <?= e(format_datetime($course['updated_at'] ?? '')) ?></p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2 class="panel__title">Ke stažení</h2>
            <p class="muted small">Program v PDF, životopis lektora, seznam literatury… Zobrazí se na stránce kurzu.</p>
            <?php if ($course['files']): ?>
                <ul class="file-list">
                    <?php foreach ($course['files'] as $i => $file): ?>
                        <li>
                            <input type="hidden" name="files[<?= $i ?>][id]" value="<?= (int) $file['id'] ?>">
                            <label class="field">
                                <span class="field__label small">Název odkazu · <a href="<?= e(url($file['path'])) ?>" target="_blank" rel="noopener"><?= e(strtoupper(pathinfo($file['path'], PATHINFO_EXTENSION))) ?>, <?= e(file_size_label($file['path'])) ?></a></span>
                                <input type="text" name="files[<?= $i ?>][label]" value="<?= e($file['label']) ?>">
                            </label>
                            <label class="check small"><input type="checkbox" name="files[<?= $i ?>][remove]" value="1"> <span>Odebrat</span></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <label class="field<?= isset($errors['files']) ? ' has-error' : '' ?>">
                <span class="field__label">Přidat soubory</span>
                <input type="file" name="new_files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.jpg,.jpeg,.png">
                <span class="field__hint">Můžete vybrat víc souborů najednou. Název odkazu jde po uložení upravit.</span>
                <?= $err('files') ?>
            </label>
        </section>

        <section class="panel">
            <h2 class="panel__title">Obrázek</h2>
            <?php if ($course['image']): ?>
                <img class="image-preview" src="<?= e(url($course['image'])) ?>" alt="">
                <label class="check"><input type="checkbox" name="remove_image" value="1"> <span>Odebrat obrázek</span></label>
            <?php endif; ?>
            <label class="field<?= isset($errors['image']) ? ' has-error' : '' ?>">
                <span class="field__label"><?= $course['image'] ? 'Nahradit jiným' : 'Nahrát obrázek' ?></span>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                <span class="field__hint">JPG, PNG nebo WebP, ideálně na šířku. Bez obrázku má kartička barevné pozadí.</span>
                <?= $err('image') ?>
            </label>
        </section>
    </aside>
</form>

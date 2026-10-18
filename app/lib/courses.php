<?php
declare(strict_types=1);

const APPLICATION_STATUSES = [
    'nova' => 'Nová',
    'potvrzena' => 'Potvrzená',
    'zaplacena' => 'Zaplacená',
    'nahradnik' => 'Náhradník',
    'zrusena' => 'Zrušená',
];

/** Published courses for the homepage, with their terms attached. */
function published_courses(): array
{
    $courses = db_all('SELECT * FROM courses WHERE is_published = 1 ORDER BY sort_order, id');
    return attach_terms($courses);
}

function all_courses(): array
{
    return attach_terms(db_all('SELECT * FROM courses ORDER BY sort_order, id'));
}

function find_course(int $id): ?array
{
    $course = db_one('SELECT * FROM courses WHERE id = ?', [$id]);
    return $course ? attach_terms([$course])[0] : null;
}

function find_course_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    $course = db_one('SELECT * FROM courses WHERE slug = ?' . ($publishedOnly ? ' AND is_published = 1' : ''), [$slug]);
    return $course ? attach_terms([$course])[0] : null;
}

/** Find a course by one of its old Adobe Muse page names, for 301 redirects. */
function find_course_by_legacy_path(string $path): ?array
{
    foreach (db_all("SELECT id, slug, legacy_paths FROM courses WHERE legacy_paths <> ''") as $course) {
        $paths = array_filter(array_map('trim', explode("\n", $course['legacy_paths'])));
        foreach ($paths as $legacy) {
            if (mb_strtolower($legacy) === mb_strtolower($path)) {
                return $course + ['is_application' => str_starts_with(mb_strtolower($legacy), 'přihláška')];
            }
        }
    }
    return null;
}

function attach_terms(array $courses): array
{
    if (!$courses) {
        return [];
    }
    $ids = array_column($courses, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $terms = db_all("SELECT * FROM course_terms WHERE course_id IN ($placeholders) ORDER BY sort_order, id", $ids);
    $byCourse = [];
    foreach ($terms as $term) {
        $term['is_past'] = term_is_past($term);
        $byCourse[$term['course_id']][] = $term;
    }
    $files = db_all("SELECT * FROM course_files WHERE course_id IN ($placeholders) ORDER BY sort_order, id", $ids);
    $filesByCourse = [];
    foreach ($files as $file) {
        $filesByCourse[$file['course_id']][] = $file;
    }
    foreach ($courses as &$course) {
        $course['terms'] = $byCourse[$course['id']] ?? [];
        $course['files'] = $filesByCourse[$course['id']] ?? [];
    }
    return $courses;
}

function term_is_past(array $term): bool
{
    $last = $term['end_date'] ?: $term['start_date'];
    return $last !== null && $last !== '' && $last < today();
}

/** Terms a visitor can sign up for: registration open and not already over. */
function open_terms(array $course): array
{
    if (!$course['registration_open']) {
        return [];
    }
    return array_values(array_filter($course['terms'], fn ($t) => $t['registration_open'] && !$t['is_past']));
}

function course_accepts_applications(array $course): bool
{
    // A course without any terms (e.g. "dates to be announced") still collects expressions of interest.
    return (bool) $course['registration_open'] && (open_terms($course) || !$course['terms']);
}

/** A course whose every term already took place (courses without terms are never "past"). */
function course_is_past(array $course): bool
{
    return $course['terms'] && !array_filter($course['terms'], fn ($t) => !$t['is_past']);
}

/** The earliest upcoming start date of a course, used for the "nejbližší termín" label. */
function next_term(array $course): ?array
{
    $upcoming = array_filter($course['terms'], fn ($t) => $t['start_date'] && !$t['is_past']);
    usort($upcoming, fn ($a, $b) => strcmp($a['start_date'], $b['start_date']));
    return $upcoming[0] ?? null;
}

function unique_slug(string $slug, ?int $exceptId = null): string
{
    $base = slugify($slug);
    $candidate = $base;
    $i = 2;
    while (db_value('SELECT id FROM courses WHERE slug = ? AND id <> ?', [$candidate, $exceptId ?? 0])) {
        $candidate = $base . '-' . $i++;
    }
    return $candidate;
}

/** Save course fields + terms in one transaction. $data comes from the admin form. Returns the course id. */
function save_course(?int $id, array $data, array $terms): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $data['updated_at'] = now();
        if ($id) {
            db_update('courses', $id, $data);
        } else {
            // MySQL TEXT columns have no defaults, so new rows must name every one of them.
            $data += ['image' => '', 'legacy_paths' => ''];
            $data['created_at'] = now();
            $data['sort_order'] ??= (int) db_value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM courses');
            $id = db_insert('courses', $data);
        }

        $keep = [];
        foreach (array_values($terms) as $i => $term) {
            $row = [
                'label' => $term['label'],
                'date_text' => $term['date_text'],
                'start_date' => $term['start_date'] ?: null,
                'end_date' => $term['end_date'] ?: null,
                'registration_open' => $term['registration_open'] ? 1 : 0,
                'content' => $term['content'],
                'sort_order' => $i,
            ];
            $termId = (int) ($term['id'] ?? 0);
            if ($termId && db_value('SELECT id FROM course_terms WHERE id = ? AND course_id = ?', [$termId, $id])) {
                db_update('course_terms', $termId, $row);
            } else {
                $termId = db_insert('course_terms', $row + ['course_id' => $id]);
            }
            $keep[] = $termId;
        }
        $params = [$id, ...$keep];
        $notIn = $keep ? ' AND id NOT IN (' . implode(',', array_fill(0, count($keep), '?')) . ')' : '';
        db_exec('DELETE FROM course_terms WHERE course_id = ?' . $notIn, $params);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $id;
}

function delete_course(int $id): void
{
    $course = db_one('SELECT image FROM courses WHERE id = ?', [$id]);
    foreach (db_all('SELECT path FROM course_files WHERE course_id = ?', [$id]) as $file) {
        delete_uploaded_file($file['path']);
    }
    db_exec('DELETE FROM course_files WHERE course_id = ?', [$id]);
    db_exec('DELETE FROM course_terms WHERE course_id = ?', [$id]);
    db_exec('DELETE FROM courses WHERE id = ?', [$id]);
    if ($course && $course['image']) {
        delete_uploaded_image($course['image']);
    }
}

function duplicate_course(int $id): int
{
    $course = find_course($id);
    if (!$course) {
        throw new RuntimeException('Kurz nenalezen.');
    }
    $terms = $course['terms'];
    // Files are not copied: deleting one course must never remove another course's file.
    unset($course['id'], $course['terms'], $course['files'], $course['created_at'], $course['updated_at']);
    $course['title'] .= ' (kopie)';
    $course['slug'] = unique_slug($course['slug'] . '-kopie');
    $course['is_published'] = 0;
    $course['legacy_paths'] = '';
    $course['image'] = '';
    $course['sort_order'] = null;
    foreach ($terms as &$term) {
        unset($term['id'], $term['is_past'], $term['course_id']);
    }
    return save_course(null, $course, $terms);
}

/** Move a course one step up or down in the homepage order. */
function move_course(int $id, string $direction): void
{
    $ids = array_column(db_all('SELECT id FROM courses ORDER BY sort_order, id'), 'id');
    $pos = array_search($id, array_map('intval', $ids), true);
    $swap = $direction === 'up' ? $pos - 1 : $pos + 1;
    if ($pos === false || !isset($ids[$swap])) {
        return;
    }
    [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
    foreach ($ids as $i => $courseId) {
        db_exec('UPDATE courses SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $courseId]);
    }
}

/**
 * Apply the "Ke stažení" part of the course form: rename/reorder/remove existing files
 * and add newly uploaded ones. $existing is the list from $_POST['files'].
 */
function save_course_files(int $courseId, array $existing, array $uploads): void
{
    $current = db_all('SELECT * FROM course_files WHERE course_id = ?', [$courseId]);
    $byId = array_column($current, null, 'id');
    $order = 0;
    foreach ($existing as $row) {
        $id = (int) ($row['id'] ?? 0);
        if (!isset($byId[$id])) {
            continue;
        }
        if (!empty($row['remove'])) {
            delete_uploaded_file($byId[$id]['path']);
            db_exec('DELETE FROM course_files WHERE id = ?', [$id]);
            continue;
        }
        $label = trim((string) ($row['label'] ?? '')) ?: $byId[$id]['label'];
        db_update('course_files', $id, ['label' => mb_substr($label, 0, 200), 'sort_order' => $order++]);
    }
    foreach ($uploads as $upload) {
        db_insert('course_files', [
            'course_id' => $courseId,
            'label' => mb_substr($upload['label'], 0, 200),
            'path' => $upload['path'],
            'sort_order' => $order++,
        ]);
    }
}

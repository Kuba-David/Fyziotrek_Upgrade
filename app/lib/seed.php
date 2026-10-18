<?php
declare(strict_types=1);

/**
 * Import the courses taken over from the old Adobe Muse site (data/seed/courses.json)
 * together with their downloadable files. Used once, from the first-run setup.
 */
function import_seed_courses(): int
{
    $json = (string) file_get_contents(ROOT_DIR . '/data/seed/courses.json');
    $courses = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    $filesDir = ROOT_DIR . '/uploads/soubory';
    if (!is_dir($filesDir)) {
        mkdir($filesDir, 0755, true);
    }

    foreach ($courses as $i => $c) {
        $data = [
            'title' => $c['title'],
            'slug' => unique_slug($c['slug'] ?? $c['title']),
            'summary' => $c['summary'] ?? '',
            'badge' => $c['badge'] ?? '',
            'image' => '',
            'location' => $c['location'] ?? '',
            'lecturer' => $c['lecturer'] ?? '',
            'translation' => $c['translation'] ?? '',
            'price' => $c['price'] ?? '',
            'price_note' => $c['price_note'] ?? '',
            'accommodation' => $c['accommodation'] ?? '',
            'credits' => $c['credits'] ?? '',
            'description' => sanitize_html($c['description'] ?? ''),
            'ask_translation' => (int) ($c['ask_translation'] ?? 0),
            'registration_open' => 1,
            'is_published' => 1,
            'sort_order' => ($i + 1) * 10,
            'legacy_paths' => implode("\n", $c['legacy'] ?? []),
        ];
        $terms = array_map(fn ($t) => [
            'label' => $t['label'] ?? '',
            'date_text' => $t['date_text'] ?? '',
            'start_date' => $t['start_date'] ?? '',
            'end_date' => $t['end_date'] ?? '',
            'registration_open' => 1,
            'content' => sanitize_html($t['content'] ?? ''),
        ], $c['terms'] ?? []);
        $courseId = save_course(null, $data, $terms);

        $uploads = [];
        foreach ($c['files'] ?? [] as [$file, $label]) {
            $source = ROOT_DIR . '/data/seed/files/' . basename($file);
            if (!is_file($source)) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $name = substr(slugify(pathinfo($file, PATHINFO_FILENAME)), 0, 60) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
            copy($source, $filesDir . '/' . $name);
            $uploads[] = ['label' => $label, 'path' => 'uploads/soubory/' . $name];
        }
        save_course_files($courseId, [], $uploads);
    }
    return count($courses);
}

<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Evaluates one WordPress post without changing its content or builder metadata. */
final class DAA_Scanner
{
    public const MAX_POSTS = 100;

    public static function post(int $id): array
    {
        $post = get_post($id);
        if (!$post || !is_string($post->post_content)) {
            return array('status' => 'unavailable', 'issues' => array(), 'notes' => array());
        }
        $content = $post->post_content;
        $notes = array();
        $issues = array();

        // Embedded HTML only: Gutenberg shortcode and render-time dynamic blocks are
        // not interpreted as full pages. This may miss builder/theme generated markup.
        $html = DAA_Analyzer::analyze($content);
        if ($html['status'] === 'ok') {
            $issues = $html['issues'];
        } else {
            $notes[] = 'Contenu WordPress : analyse ' . $html['status'] . ', aucun verdict possible.';
        }
        $elementor = DAA_Builders::elementor($id);
        if ($elementor['detected']) {
            foreach ($elementor['issues'] as $issue) {
                if (count($issues) >= 100) { break; }
                $issues[] = $issue;
            }
            $notes = array_merge($notes, $elementor['notes']);
        }
        $divi = DAA_Builders::divi($content);
        if ($divi['detected']) {
            foreach ($divi['issues'] as $issue) {
                if (count($issues) >= 100) { break; }
                $issues[] = $issue;
            }
            $notes = array_merge($notes, $divi['notes']);
        }
        if (!$elementor['detected'] && !$divi['detected']) {
            $notes[] = 'Contenu stocké examiné ; le thème, le rendu public et les composants dynamiques ne sont pas couverts.';
        }
        return array(
            'status' => $html['status'],
            'incomplete' => $html['status'] !== 'ok' || !empty($elementor['partial']) || !empty($divi['partial']),
            'issues' => array_slice($issues, 0, 100),
            'notes' => array_slice($notes, 0, 12),
            'builders' => array('elementor' => $elementor['detected'], 'divi' => $divi['detected']),
        );
    }

    public static function batch(): array
    {
        $types = get_post_types(array('public' => true), 'names');
        $types = array_values(array_diff((array) $types, array('attachment')));
        if (!$types) {
            return array('posts' => array(), 'partial' => false, 'total_checked' => 0,
                'failed' => 0, 'incomplete' => 0, 'outside_limit' => 'none', 'progress' => 100, 'completed' => true);
        }
        try {
            $ids = get_posts(array(
                'post_type' => $types, 'post_status' => 'publish',
                'numberposts' => self::MAX_POSTS + 1,
                'orderby' => 'ID', 'order' => 'DESC',
                'fields' => 'ids', 'suppress_filters' => true,
            ));
        } catch (Throwable $error) {
            return array(
                'posts' => array(), 'partial' => true, 'total_checked' => 0,
                'failed' => 1, 'incomplete' => 0, 'outside_limit' => 'unknown',
                'progress' => 100, 'completed' => true, 'scan_error' => true,
            );
        }
        if (!is_array($ids)) {
            return array(
                'posts' => array(), 'partial' => true, 'total_checked' => 0,
                'failed' => 1, 'incomplete' => 0, 'outside_limit' => 'unknown',
                'progress' => 100, 'completed' => true, 'scan_error' => true,
            );
        }
        $partial = count($ids) > self::MAX_POSTS;
        $checked = array_slice($ids, 0, self::MAX_POSTS);
        $rows = array();
        $failed = 0;
        $incomplete = 0;
        foreach ($checked as $id) {
            $id = (int) $id;
            try {
                $audit = self::post($id);
                if (!in_array($audit['status'] ?? '', array('ok', 'too_large', 'unreadable', 'unsupported'), true)) {
                    throw new RuntimeException('Contenu non disponible.');
                }
                if (!empty($audit['incomplete'])) { ++$incomplete; }
            } catch (Throwable $error) {
                // One broken content / builder must never abort the other pages.
                ++$failed;
                $audit = array('status' => 'error', 'issues' => array(), 'incomplete' => true, 'builders' => array());
            }
            try {
                $title = (string) get_the_title($id);
            } catch (Throwable $error) {
                $title = 'Contenu #' . $id;
            }
            $rows[] = array(
                'id' => $id,
                'title' => $title,
                'count' => count($audit['issues']),
                'issues' => array_slice($audit['issues'], 0, 8),
                'status' => $audit['status'],
                'incomplete' => !empty($audit['incomplete']),
                'builders' => $audit['builders'] ?? array(),
            );
        }
        // Progress measures execution of the planned bounded pass, not site coverage.
        return array(
            'posts' => $rows,
            'partial' => $partial,
            'total_checked' => count($checked),
            'failed' => $failed,
            'incomplete' => $incomplete,
            'outside_limit' => $partial ? 'at_least_one' : 'none',
            'progress' => 100,
            'completed' => true,
        );
    }
}

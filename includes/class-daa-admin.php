<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

final class DAA_Admin
{
    public static function menu(): void
    {
        add_menu_page(
            'Dendrila Accessibility Assistant', 'Accessibilité',
            'manage_options', 'dendrila-accessibility-assistant',
            array(__CLASS__, 'render'), 'dashicons-universal-access-alt', 66
        );
    }

    public static function assets(string $hook): void
    {
        if ($hook === 'toplevel_page_dendrila-accessibility-assistant' ||
            str_contains($hook, 'post.php') || str_contains($hook, 'post-new.php')) {
            wp_enqueue_style('daa-admin', plugins_url('assets/admin.css', DAA_FILE), array(), DAA_VERSION);
        }
    }

    public static function scan(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Action réservée à l’administration.', 'Accès refusé', array('response' => 403));
        }
        check_admin_referer('daa_scan');
        $results = DAA_Scanner::batch();
        $results['scanned_at'] = gmdate('c');
        update_option('daa_last_audit', $results, false);
        wp_safe_redirect(admin_url('admin.php?page=dendrila-accessibility-assistant&daa_notice=scanned'));
        exit;
    }

    public static function meta_boxes(): void
    {
        $types = get_post_types(array('public' => true), 'names');
        foreach ((array) $types as $type) {
            if ($type === 'attachment') { continue; }
            add_meta_box('daa_editor_help', 'Dendrila · Aide accessibilité', array(__CLASS__, 'meta_box'),
                $type, 'side', 'default');
        }
    }

    public static function meta_box($post): void
    {
        if (!is_object($post) || empty($post->ID) || !current_user_can('edit_post', (int) $post->ID)) {
            echo '<p>Analyse indisponible.</p>';
            return;
        }
        $audit = DAA_Scanner::post((int) $post->ID);
        echo '<div class="daa-editor">';
        echo '<p><strong>Diagnostic du dernier contenu enregistré</strong> — les changements non sauvegardés ne sont pas analysés.</p>';
        if (!empty($audit['incomplete'])) {
            echo '<p class="daa-note" role="status"><strong>Analyse incomplète :</strong> certains éléments n’ont pas pu être examinés. Aucun résultat nul ne doit être interprété comme une validation.</p>';
        }
        if ($audit['issues']) {
            foreach (array_slice($audit['issues'], 0, 15) as $issue) {
                self::issue($issue);
            }
            if (count($audit['issues']) > 15) {
                echo '<p>Observations supplémentaires : ouvrez la synthèse ou sauvegardez le contenu et réexaminez-le.</p>';
            }
        } else {
            echo '<p>Aucune observation détectée dans les éléments examinés. Le rendu réel reste à tester.</p>';
        }
        foreach ($audit['notes'] as $note) {
            echo '<p class="daa-note">' . esc_html($note) . '</p>';
        }
        echo '</div>';
    }

    private static function issue(array $issue): void
    {
        $severity = ($issue['severity'] ?? '') === 'problem' ? 'problem' : 'review';
        echo '<div class="daa-issue daa-' . esc_attr($severity) . '">';
        echo '<strong>' . ($severity === 'problem' ? 'À corriger' : 'À vérifier') . '</strong> · ' .
            esc_html((string) ($issue['source'] ?? 'Contenu'));
        echo '<p>' . esc_html((string) ($issue['detail'] ?? '')) . '</p>';
        echo '<p class="daa-help">' . esc_html((string) ($issue['help'] ?? '')) . '</p>';
        echo '</div>';
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) { return; }
        $last = get_option('daa_last_audit', array());
        $last = is_array($last) ? $last : array();
        $rows = is_array($last['posts'] ?? null) ? $last['posts'] : array();
        $checked = (int) ($last['total_checked'] ?? 0);
        $found = array_sum(array_map(static fn($row) => (int) ($row['count'] ?? 0), $rows));
        $affected = count(array_filter($rows, static fn($row) => !empty($row['count'])));
        ?>
        <div class="wrap daa-wrap">
            <header class="daa-hero">
                <p class="daa-eyebrow">Dendrila · Accessibilité éditoriale</p>
                <h1>Accessibility Assistant <span class="daa-alpha">alpha</span></h1>
                <p>Des constats précis, des conseils pour les corriger et une lecture prudente d’Elementor et Divi, sans modifier votre site à votre place.</p>
            </header>
            <?php if (isset($_GET['daa_notice']) && $_GET['daa_notice'] === 'scanned') : ?>
                <div class="notice notice-success"><p>Analyse terminée. Résultats actualisés.</p></div>
            <?php endif; ?>
            <div class="daa-caution" role="note">
                <strong>Ce n’est pas un audit RGAA/WCAG.</strong>
                Les vérifications portent sur du contenu enregistré et quelques paramètres de constructeurs.
                Les couleurs calculées, contrastes réels, interactions clavier, lecteurs d’écran, scripts et rendu public nécessitent des tests distincts.
            </div>
            <section class="daa-panel daa-first">
                <div><h2>Analyser les contenus publiés</h2>
                    <p>Parcours local des 100 derniers contenus publics au maximum. Aucune requête extérieure, aucune modification des pages ou des constructeurs.</p>
                    <?php if (!empty($last['scanned_at'])) : ?>
                        <p class="daa-meta">Dernière analyse :
                        <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'),
                            strtotime($last['scanned_at']))); ?></p>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="daa_scan">
                    <?php wp_nonce_field('daa_scan'); ?>
                    <?php submit_button('Analyser maintenant', 'primary', 'submit', false); ?>
                </form>
            </section>
            <?php if (!empty($last['scanned_at'])) : ?>
                <div class="daa-stats" aria-label="Résumé des observations">
                    <div><strong><?php echo esc_html((string) $checked); ?></strong><span>Contenus analysés</span></div>
                    <div><strong><?php echo esc_html((string) $affected); ?></strong><span>Contenus avec observations</span></div>
                    <div><strong><?php echo esc_html((string) $found); ?></strong><span>Observations (pas un score RGAA)</span></div>
                </div>
                <?php if (!empty($last['partial'])) : ?>
                    <p class="daa-caution" role="status">Inventaire partiel : seuls les 100 contenus publiés les plus récents ont été parcourus.</p>
                <?php endif; ?>
                <section class="daa-panel"><h2>Résultats par contenu</h2>
                    <?php if (!$rows) : ?>
                        <p>Aucun contenu public trouvé dans le périmètre.</p>
                    <?php else : ?>
                        <div class="daa-results">
                        <?php foreach ($rows as $row) : ?>
                            <?php $id = (int) $row['id']; ?>
                            <article class="daa-result">
                                <div class="daa-result-head"><div><h3><?php echo esc_html($row['title'] ?: '(Sans titre)'); ?></h3>
                                    <p class="daa-meta">
                                        <?php echo esc_html((string) $row['count']); ?> observation(s) ·
                                        <?php
                                        $builders = array();
                                        if (!empty($row['builders']['elementor'])) { $builders[] = 'Elementor'; }
                                        if (!empty($row['builders']['divi'])) { $builders[] = 'Divi'; }
                                        echo esc_html($builders ? implode(' + ', $builders) : 'WordPress');
                                        ?>
                                    </p>
                                </div><a class="button button-secondary" href="<?php echo esc_url(get_edit_post_link($id, '')); ?>">Ouvrir pour corriger</a></div>
                                <?php if (empty($row['issues'])) : ?>
                                    <?php if (!empty($row['incomplete'])) : ?>
                                    <p class="daa-note" role="status">Analyse incomplète : certains contenus ou réglages du constructeur n’ont pas pu être lus.</p>
                                    <?php endif; ?>
                                    <p>Aucune observation affichée dans le périmètre analysé ; cela ne garantit pas une page accessible.</p>
                                <?php else : ?>
                                    <?php foreach ($row['issues'] as $issue) { self::issue($issue); } ?>
                                    <?php if ($row['count'] > count($row['issues'])) : ?>
                                        <p class="daa-note">D’autres observations sont disponibles dans l’éditeur de ce contenu.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (!empty($row['incomplete']) && !empty($row['issues'])) : ?>
                                    <p class="daa-note" role="status">Analyse partielle : d’autres problèmes peuvent ne pas avoir été repérés.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else : ?>
                <section class="daa-panel"><h2>Premier diagnostic</h2>
                    <p>Aucune analyse globale réalisée. Cliquez sur « Analyser maintenant » pour obtenir des pistes de correction, ou ouvrez un contenu dans l’éditeur pour voir son diagnostic local.</p>
                </section>
            <?php endif; ?>
            <p class="daa-meta">Le diagnostic d’un article ou d’une page s’affiche aussi dans son panneau « Dendrila · Aide accessibilité ». Pensez à enregistrer vos modifications avant de relancer les contrôles.</p>
        </div>
        <?php
    }
}

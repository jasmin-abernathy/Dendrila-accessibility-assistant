<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Media guidance. Uploading remains compatible with native WordPress/Elementor/
 * Divi uploads; new images stay pending until explicitly reviewed.
 * Existing attachments are never made invalid retroactively.
 */
final class DAA_Media
{
    public const META = '_daa_media_review';
    public const MAX_MEDIA = 100;

    public static function boot(): void
    {
        add_action('add_attachment', array(__CLASS__, 'new_attachment'));
        add_filter('attachment_fields_to_edit', array(__CLASS__, 'help_fields'), 10, 2);
        add_action('admin_post_daa_media_review', array(__CLASS__, 'save_review'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
    }

    public static function is_image(int $id): bool
    {
        return get_post_type($id) === 'attachment' &&
            str_starts_with((string) get_post_mime_type($id), 'image/');
    }

    public static function new_attachment(int $id): void
    {
        if (self::is_image($id) && !metadata_exists('post', $id, self::META)) {
            update_post_meta($id, self::META, 'pending');
        }
    }

    public static function reviewed(string $purpose, string $alt): bool
    {
        return ($purpose === 'informative' && trim($alt) !== '') ||
            ($purpose === 'decorative' && trim($alt) === '');
    }

    public static function status(int $id): string
    {
        $purpose = (string) get_post_meta($id, self::META, true);
        $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        if (self::reviewed($purpose, $alt)) {
            return $purpose;
        }
        if ($purpose === 'pending') { return 'pending'; }
        if ($purpose !== '') { return 'needs_review'; }
        // Older uploads are never blocked, with or without an existing alt.
        return trim($alt) === '' ? 'legacy_missing' : 'legacy_described';
    }

    public static function library(): array
    {
        $ids = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'numberposts' => self::MAX_MEDIA + 1,
            'orderby' => 'ID', 'order' => 'DESC',
            'fields' => 'ids',
        ));
        if (!is_array($ids)) {
            throw new RuntimeException('La médiathèque ne peut pas être parcourue.');
        }
        $partial = count($ids) > self::MAX_MEDIA;
        $rows = array();
        foreach (array_slice($ids, 0, self::MAX_MEDIA) as $id) {
            $id = (int) $id;
            try {
                if (!self::is_image($id)) { continue; }
                $state = self::status($id);
                $title = (string) get_the_title($id);
            } catch (Throwable $error) {
                $state = 'error';
                $title = 'Image non lisible';
            }
            $rows[] = array('id' => $id, 'title' => $title, 'status' => $state);
        }
        $pending = count(array_filter($rows, static fn ($row) =>
            in_array($row['status'], array('pending', 'needs_review', 'legacy_missing', 'error'), true)
        ));
        return array(
            'items' => $rows, 'pending' => $pending, 'scanned' => count($rows),
            'partial' => $partial, 'progress' => 100, 'completed' => true,
        );
    }

    public static function help_fields(array $fields, $attachment): array
    {
        if (!is_object($attachment) || !self::is_image((int) $attachment->ID)) {
            return $fields;
        }
        if (isset($fields['image_alt'])) {
            $fields['image_alt']['helps'] =
                'Décrivez ce que cette image apporte au contenu. Ne commencez pas par « image de ». ' .
                'Pour une image seulement décorative, le texte alternatif doit être laissé vide volontairement.';
        }
        if (isset($fields['post_excerpt'])) {
            $fields['post_excerpt']['helps'] =
                'La légende est un texte visible sous l’image, utile pour expliquer son contexte ou son crédit. Elle ne remplace pas le texte alternatif.';
        }
        if (isset($fields['post_content'])) {
            $fields['post_content']['helps'] =
                'La description peut apporter des détails complémentaires. Elle ne remplace pas le texte alternatif.';
        }
        return $fields;
    }

    public static function enqueue(string $hook): void
    {
        if (in_array($hook, array('upload.php', 'post.php', 'post-new.php'), true)) {
            wp_enqueue_script(
                'daa-media-help',
                plugins_url('assets/media-help.js', DAA_FILE),
                array(), DAA_VERSION, true
            );
        }
    }

    public static function save_review(): void
    {
        $id = isset($_POST['daa_id']) ? absint($_POST['daa_id']) : 0;
        if (!$id || !self::is_image($id) || !current_user_can('edit_post', $id)) {
            wp_die('Vous ne pouvez pas modifier cette image.', 'Accès refusé', array('response' => 403));
        }
        check_admin_referer('daa_media_review_' . $id);
        $purpose = isset($_POST['daa_purpose']) && is_string($_POST['daa_purpose'])
            ? sanitize_key(wp_unslash($_POST['daa_purpose'])) : '';
        $alt = isset($_POST['daa_alt']) && is_string($_POST['daa_alt'])
            ? sanitize_text_field(wp_unslash($_POST['daa_alt'])) : '';
        if (strlen($alt) > 1200 || !self::reviewed($purpose, $alt)) {
            wp_safe_redirect(add_query_arg(array(
                'page' => 'dendrila-accessibility-assistant',
                'daa_media' => $id,
                'daa_media_error' => '1',
            ), admin_url('admin.php')));
            exit;
        }
        // Media itself is never deleted, replaced, or modified beyond its
        // explicitly requested accessibility metadata.
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
        update_post_meta($id, self::META, $purpose);
        wp_safe_redirect(add_query_arg(array(
            'page' => 'dendrila-accessibility-assistant',
            'daa_media' => $id,
            'daa_media_saved' => '1',
        ), admin_url('admin.php')));
        exit;
    }

    public static function editor(int $id): void
    {
        if (!self::is_image($id) || !current_user_can('edit_post', $id)) {
            echo '<p>Cette image ne peut pas être modifiée ici.</p>';
            return;
        }
        $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        $purpose = (string) get_post_meta($id, self::META, true);
        $state = self::status($id);
        echo '<section class="daa-panel" id="daa-media-editor"><h2>Rendre cette image compréhensible</h2>';
        echo '<p><strong>' . esc_html((string) get_the_title($id)) . '</strong> · ' .
            esc_html($state === 'legacy_missing' ? 'Ancienne image à revoir' :
                ($state === 'pending' ? 'Nouvelle image à compléter' : 'Vérification des informations')) . '</p>';
        echo '<p>Avant de valider, indiquez si l’image apporte une information. Les anciennes images restent utilisables pendant leur mise à jour.</p>';
        if (isset($_GET['daa_media_error'])) {
            echo '<p class="daa-caution" role="alert">Choisissez le rôle de l’image. Une image informative doit avoir une description ; une image décorative doit avoir un texte alternatif vide.</p>';
        }
        if (isset($_GET['daa_media_saved'])) {
            echo '<p class="notice notice-success" role="status">Informations d’accessibilité enregistrées.</p>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="daa_media_review"><input type="hidden" name="daa_id" value="' . esc_attr((string) $id) . '">';
        wp_nonce_field('daa_media_review_' . $id);
        echo '<fieldset><legend><strong>Cette image sert à…</strong></legend>';
        echo '<label class="daa-choice"><input type="radio" name="daa_purpose" value="informative" ' .
            checked($purpose, 'informative', false) . '> Transmettre une information ou illustrer le propos</label>';
        echo '<label class="daa-choice"><input type="radio" name="daa_purpose" value="decorative" ' .
            checked($purpose, 'decorative', false) . '> Décorer uniquement, sans information à transmettre</label>';
        echo '</fieldset>';
        echo '<p><label for="daa_alt"><strong>Texte alternatif</strong></label><br><textarea id="daa_alt" name="daa_alt" rows="3" maxlength="1200" class="large-text">' .
            esc_textarea($alt) . '</textarea></p>';
        echo '<p class="description">Pour une image informative, décrivez son rôle dans la page, en quelques mots. Pour une image décorative, laissez ce champ vide et sélectionnez l’option « Décorer uniquement ».</p>';
        submit_button('Valider les informations de cette image');
        echo '</form><p><a href="' . esc_url(admin_url('upload.php')) . '">Retour à la médiathèque</a></p></section>';
    }
}

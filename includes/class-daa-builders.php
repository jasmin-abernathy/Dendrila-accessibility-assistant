<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Builders are read ONLY. Structures, presets and Elementor/Divi postmeta are never rewritten.
 * Based on the already verified read-only strategy in Dendrila Simple Edit.
 */
final class DAA_Builders
{
    public static function elementor(int $postId): array
    {
        $raw = get_post_meta($postId, '_elementor_data', true);
        if (!is_string($raw) || $raw === '') {
            return array('detected' => false, 'issues' => array(), 'notes' => array());
        }
        if (strlen($raw) > 1500000) {
            return array('detected' => true, 'issues' => array(), 'notes' => array('Elementor : données trop volumineuses pour cette analyse.'), 'partial' => true);
        }
        $tree = json_decode($raw, true);
        if (!is_array($tree)) {
            return array('detected' => true, 'issues' => array(), 'notes' => array('Elementor : structure JSON non lisible.'), 'partial' => true);
        }
        $issues = array(); $visited = 0;
        $stack = array(array($tree, 0));
        while ($stack && $visited < 500 && count($issues) < 60) {
            [$nodes, $depth] = array_pop($stack);
            if (!is_array($nodes) || $depth > 18) { continue; }
            ++$visited;
            if (isset($nodes['widgetType']) && is_string($nodes['widgetType'])) {
                $kind = $nodes['widgetType'];
                $settings = is_array($nodes['settings'] ?? null) ? $nodes['settings'] : array();
                $source = 'Elementor · ' . $kind;
                if ($kind === 'text-editor' && is_string($settings['editor'] ?? null)) {
                    self::append($issues, DAA_Analyzer::analyze($settings['editor'], $source)['issues']);
                }
                if ($kind === 'heading') {
                    $title = $settings['title'] ?? null;
                    if (is_string($title) && trim(wp_strip_all_tags($title)) === '') {
                        $issues[] = DAA_Analyzer::issue('elementor_empty_heading', 'review', $source,
                            'Widget titre sans texte visible.', 'Renseigner un titre utile ou supprimer ce widget.');
                    }
                }
                if ($kind === 'image') {
                    $image = is_array($settings['image'] ?? null) ? $settings['image'] : array();
                    $id = filter_var($image['id'] ?? null, FILTER_VALIDATE_INT);
                    if ($id && $id > 0 && get_post_type($id) === 'attachment'
                        && str_starts_with((string) get_post_mime_type($id), 'image/')) {
                        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                        if (!is_string($alt) || trim($alt) === '') {
                            $issues[] = DAA_Analyzer::issue('elementor_image_alt', 'review', $source,
                                'Image de médiathèque sans alternative renseignée (média #' . $id . ').',
                                'Vérifier si cette image est informative ; renseigner son texte alternatif dans la médiathèque, ou confirmer son caractère décoratif.');
                        }
                    } elseif (is_string($image['url'] ?? null) && $image['url'] !== '') {
                        $issues[] = DAA_Analyzer::issue('elementor_external_image', 'review', $source,
                            'Image sans correspondance vérifiable avec la médiathèque.',
                            'Inspecter le widget et vérifier manuellement le texte alternatif rendu.');
                    }
                }
                if ($kind === 'button' && is_string($settings['text'] ?? null) &&
                    trim(wp_strip_all_tags($settings['text'])) === '') {
                    $issues[] = DAA_Analyzer::issue('elementor_button_text', 'review', $source,
                        'Bouton sans texte visible dans ses réglages.',
                        'Vérifier le nom accessible du bouton (texte ou libellé ARIA).');
                }
            }
            if (isset($nodes['elements']) && is_array($nodes['elements'])) {
                $stack[] = array($nodes['elements'], $depth + 1);
            }
            if (array_is_list($nodes)) {
                foreach ($nodes as $node) {
                    if (is_array($node)) { $stack[] = array($node, $depth + 1); }
                }
            }
        }
        $notes = array('Elementor : analyse partielle des widgets Image, Texte, Titre et Bouton. Aucun rendu public calculé.');
        if ($stack) { $notes[] = 'Elementor : analyse bornée à 500 éléments / 18 niveaux.'; }
        return array('detected' => true, 'issues' => array_slice($issues, 0, 60), 'notes' => $notes, 'partial' => (bool) $stack);
    }

    public static function divi(string $content): array
    {
        $issues = array(); $notes = array(); $detected = false;
        if (strlen($content) > 1500000) {
            return array(
                'detected' => str_contains($content, '[et_pb_') || str_contains($content, 'wp:divi/'),
                'issues' => array(),
                'notes' => array('Divi : contenu trop volumineux, diagnostic ignoré pour éviter une analyse coûteuse.'),
                'partial' => true,
            );
        }
        // Legacy Divi 4 shortcodes. Missing explicit alt is a manual check because
        // Divi can derive alt through media/theme code: never claim a definite defect.
        if (preg_match_all('/(?<!\[)\[et_pb_image\b([^\]\r\n]{0,1200})\]/i', $content, $matches)) {
            $detected = true;
            foreach (array_slice($matches[1], 0, 35) as $attrsText) {
                $attrs = shortcode_parse_atts($attrsText);
                if (!is_array($attrs)) { continue; }
                $explicit = array_key_exists('alt', $attrs) && is_string($attrs['alt']);
                if (!$explicit || trim($attrs['alt']) === '') {
                    $issues[] = DAA_Analyzer::issue('divi_image_alt', 'review', 'Divi 4 · image',
                        'Module Image sans texte alternatif explicite dans le shortcode.',
                        'Contrôler le rendu réel et le média utilisé ; l’alternative peut provenir de WordPress. Si l’image est informative, renseigner une description adaptée.');
                }
            }
            $notes[] = 'Divi 4 : les modules Image sont inspectés en lecture seule ; les autres modules ne sont pas couverts.';
        }
        // Divi 5 may use native block attributes. Never guess its schema nor write into it.
        if (function_exists('parse_blocks')) {
            $blocks = parse_blocks($content);
            $found = 0; $stack = array(array($blocks, 0)); $steps = 0;
            while ($stack && $steps < 350) {
                [$nodes, $depth] = array_pop($stack);
                if (!is_array($nodes) || $depth > 18) { continue; }
                ++$steps;
                foreach ($nodes as $node) {
                    if (!is_array($node)) { continue; }
                    $name = is_string($node['blockName'] ?? null) ? $node['blockName'] : '';
                    if (str_starts_with($name, 'divi/') || str_starts_with($name, 'et/')) { ++$found; }
                    if (is_array($node['innerBlocks'] ?? null) && $depth < 18) {
                        $stack[] = array($node['innerBlocks'], $depth + 1);
                    }
                }
            }
            if ($found > 0) {
                $detected = true;
                $notes[] = 'Divi 5 : ' . $found . ' bloc(s) de namespace probable détecté(s). Les attributs natifs, presets et composants dynamiques exigent un contrôle sur pilote.';
            }
        }
        if (!$detected && str_contains($content, '[et_pb_')) {
            $detected = true;
            $notes[] = 'Divi : shortcodes repérés, mais aucun module Image compatible avec les contrôles actuels.';
        }
        return array('detected' => $detected, 'issues' => array_slice($issues, 0, 35), 'notes' => $notes, 'partial' => false);
    }

    private static function append(array &$target, array $issues): void
    {
        foreach ($issues as $issue) {
            if (count($target) >= 60) { break; }
            $target[] = $issue;
        }
    }
}

<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Read-only, bounded checks on stored fragments, not a rendered-page/WCAG audit. */
final class DAA_Analyzer
{
    public const MAX_BYTES = 160000;
    public const MAX_ISSUES = 70;

    public static function available(): bool
    {
        return class_exists('DOMDocument') && class_exists('DOMXPath');
    }

    public static function issue(string $rule, string $severity, string $source, string $detail, string $help): array
    {
        return compact('rule', 'severity', 'source', 'detail', 'help');
    }

    public static function analyze(string $html, string $source = 'Contenu WordPress'): array
    {
        if (!self::available()) {
            return array('status' => 'unsupported', 'issues' => array());
        }
        if (strlen($html) > self::MAX_BYTES) {
            return array('status' => 'too_large', 'issues' => array());
        }
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        try {
            $ok = $document->loadHTML(
                '<?xml encoding="utf-8"?><div id="daa-root">' . $html . '</div>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$ok) {
            return array('status' => 'unreadable', 'issues' => array());
        }
        $xpath = new DOMXPath($document);
        $root = $xpath->query('//*[@id="daa-root"]')->item(0);
        if (!$root instanceof DOMElement) {
            return array('status' => 'unreadable', 'issues' => array());
        }
        $issues = array();
        $add = static function (array $issue) use (&$issues): void {
            if (count($issues) < self::MAX_ISSUES) {
                $issues[] = $issue;
            }
        };
        foreach ($xpath->query('.//img', $root) as $image) {
            if (!$image instanceof DOMElement || $image->getAttribute('aria-hidden') === 'true'
                || in_array($image->getAttribute('role'), array('presentation', 'none'), true)) {
                continue;
            }
            $url = self::excerpt($image->getAttribute('src'));
            if (!$image->hasAttribute('alt')) {
                $add(self::issue('img_alt', 'problem', $source, 'Image sans attribut alt : ' . $url,
                    'Renseigner sa fonction ou son information. Si elle est décorative, saisir volontairement un attribut alt vide.'));
            } elseif (trim($image->getAttribute('alt')) !== '' &&
                preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', trim($image->getAttribute('alt')))) {
                $add(self::issue('img_filename', 'review', $source, 'Alternative semblable à un nom de fichier : ' . $url,
                    'Rédiger un texte alternatif utile et contextualisé ; ne pas recopier automatiquement le nom du fichier.'));
            }
        }

        $labels = array();
        foreach ($xpath->query('.//label[@for]', $root) as $label) {
            if ($label instanceof DOMElement && $label->getAttribute('for') !== '') {
                $labels[$label->getAttribute('for')] = true;
            }
        }
        foreach ($xpath->query('.//input|.//select|.//textarea', $root) as $field) {
            if (!$field instanceof DOMElement) { continue; }
            $type = strtolower($field->getAttribute('type'));
            if (in_array($type, array('hidden', 'submit', 'reset', 'button', 'image'), true)
                || $field->getAttribute('aria-hidden') === 'true') {
                continue;
            }
            $id = $field->getAttribute('id');
            $nested = false;
            for ($node = $field->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
                if (strtolower($node->tagName) === 'label') { $nested = true; break; }
                if ($node->isSameNode($root)) { break; }
            }
            if (!$nested && ($id === '' || !isset($labels[$id]))
                && trim($field->getAttribute('aria-label')) === ''
                && trim($field->getAttribute('aria-labelledby')) === '') {
                $add(self::issue('field_label', 'problem', $source,
                    'Champ de formulaire sans nom accessible détecté dans le fragment.',
                    'Ajouter un label associé à son id, un label englobant ou un nom accessible pertinent.'));
            }
        }

        foreach ($xpath->query('.//a[@href]|.//button', $root) as $link) {
            if (!$link instanceof DOMElement) { continue; }
            $name = trim(preg_replace('/\s+/u', ' ', $link->textContent ?? '') ?? '');
            $aria = trim($link->getAttribute('aria-label') . $link->getAttribute('aria-labelledby'));
            $imageName = '';
            foreach ($xpath->query('.//img[@alt]', $link) as $image) {
                if ($image instanceof DOMElement) { $imageName .= trim($image->getAttribute('alt')); }
            }
            if ($name === '' && $aria === '' && $imageName === '') {
                $add(self::issue('control_name', 'review', $source,
                    'Lien ou bouton sans nom accessible apparent.',
                    'Vérifier le nom annoncé au lecteur d’écran ; ajouter un libellé, une alternative d’image ou un attribut ARIA adapté.'));
            }
        }

        $lastHeading = 0;
        foreach ($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root) as $heading) {
            if (!$heading instanceof DOMElement) { continue; }
            $level = (int) substr($heading->tagName, 1);
            if (trim($heading->textContent ?? '') === '') {
                $add(self::issue('empty_heading', 'problem', $source,
                    'Titre de niveau ' . $level . ' vide.', 'Supprimer le titre vide ou lui donner un texte pertinent.'));
            }
            if ($lastHeading && $level > $lastHeading + 1) {
                $add(self::issue('heading_jump', 'review', $source,
                    'Passage de h' . $lastHeading . ' à h' . $level . '.',
                    'Vérifier l’ordre des titres dans la page complète ; le thème ou le constructeur peut ajouter d’autres titres.'));
            }
            $lastHeading = $level;
        }
        return array('status' => 'ok', 'issues' => $issues);
    }

    private static function excerpt(string $value): string
    {
        // Avoid storing arbitrary long URLs or query strings in the report.
        $path = (string) parse_url($value, PHP_URL_PATH);
        return substr(basename($path), 0, 85);
    }
}

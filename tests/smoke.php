<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
// WordPress-function stubs required by the standalone PHP smoke tests.
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_strip_all_tags($value) { return strip_tags((string) $value); }
function verify(bool $ok, string $label): void {
    if (!$ok) { throw new RuntimeException('Échec du test : ' . $label); }
}
$GLOBALS['meta'] = array(
    100 => array('_elementor_data' => json_encode(array(
        array('elType' => 'widget', 'widgetType' => 'image', 'settings' => array('image' => array('id' => 12))),
        array('elType' => 'container', 'elements' => array(
            array('elType' => 'widget', 'widgetType' => 'text-editor',
                'settings' => array('editor' => '<p><img src="exemple.jpg"></p>')),
            array('elType' => 'widget', 'widgetType' => 'heading', 'settings' => array('title' => ''))
        ))
    ))),
    101 => array('_elementor_data' => 'not-json'),
);
$GLOBALS['posts'] = array(
    100 => (object) array('ID' => 100, 'post_content' => '<h2>Présentation</h2><h4>Détails</h4><img src="photo.jpg">'),
    101 => (object) array('ID' => 101, 'post_content' => '[et_pb_image src="image.jpg" /]'),
);
function get_post_meta($id, $key, $single = false) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function get_post_type($id) { return $id === 12 ? 'attachment' : ''; }
function get_post_mime_type($id) { return $id === 12 ? 'image/jpeg' : ''; }
function shortcode_parse_atts($raw) {
    preg_match_all('/([a-z_]+)="([^"]*)"/', $raw, $matches, PREG_SET_ORDER);
    $out = array();
    foreach ($matches as $m) { $out[$m[1]] = $m[2]; }
    return $out;
}
function get_post($id) { if (!empty($GLOBALS['throw_post']) && (int)$id === (int)$GLOBALS['throw_post']) { throw new RuntimeException('Contenu temporairement inaccessible'); } return $GLOBALS['posts'][$id] ?? null; }
function get_post_types($args, $mode) { return array('post', 'page', 'attachment'); }
function get_posts($args) { return array_slice(array_keys($GLOBALS['posts']), 0, (int) $args['numberposts']); }
function get_the_title($id) { return 'Article ' . $id; }
function get_post_mime_type_placeholder() { return ''; }
function parse_blocks($content) {
    if (str_contains($content, 'et_pb_image')) { return array(); }
    return array(array('blockName' => 'divi/contact-form', 'innerBlocks' => array()));
}
require dirname(__DIR__) . '/includes/class-daa-analyzer.php';
require dirname(__DIR__) . '/includes/class-daa-builders.php';
require dirname(__DIR__) . '/includes/class-daa-scanner.php';

verify(DAA_Analyzer::available(), 'extension DOM obligatoire dans le test');
$checks = DAA_Analyzer::analyze(
    '<h2>Un titre</h2><h4>Titre éloigné</h4><img src="photo.jpg">' .
    '<img src="decor.svg" alt=""><a href="/quelque-part"></a>' .
    '<label for="ok">Nom</label><input id="ok"><input id="missing">' .
    '<button aria-label="Ouvrir"></button>'
);
verify($checks['status'] === 'ok', 'analyse HTML');
$rules = array_column($checks['issues'], 'rule');
verify(in_array('img_alt', $rules, true), 'image sans alt');
verify(in_array('heading_jump', $rules, true), 'saut de titres');
verify(in_array('field_label', $rules, true), 'champ non nommé');
verify(in_array('control_name', $rules, true), 'lien non nommé');
verify(count(array_filter($rules, fn($x) => $x === 'img_alt')) === 1, 'alternative décorative vide acceptée');
verify(DAA_Analyzer::analyze(str_repeat('a', DAA_Analyzer::MAX_BYTES + 1))['status'] === 'too_large', 'borne taille');
verify(DAA_Analyzer::analyze('<img alt="Image informative" src="a.jpg">')['issues'] === array(), 'image nommée');
$e = DAA_Builders::elementor(100);
verify($e['detected'] === true, 'Elementor détecté');
verify(in_array('elementor_image_alt', array_column($e['issues'], 'rule'), true), 'alt médiathèque Elementor');
verify(in_array('img_alt', array_column($e['issues'], 'rule'), true), 'texte Elementor HTML analysé');
verify(in_array('elementor_empty_heading', array_column($e['issues'], 'rule'), true), 'titre Elementor vide');
verify(DAA_Builders::elementor(101)['detected'] === true, 'JSON Elementor invalide signalé sans fausse assurance');
verify(DAA_Builders::elementor(101)['partial'] === true, 'structure illisible explicitement incomplète');
$d4 = DAA_Builders::divi('[et_pb_image src="image.jpg" /]');
verify($d4['detected'] === true, 'module Divi 4 détecté');
verify(in_array('divi_image_alt', array_column($d4['issues'], 'rule'), true), 'alt Divi 4 à vérifier');
$d5 = DAA_Builders::divi('Un bloc Gutenberg');
verify($d5['detected'] === true, 'namespace Divi 5 détecté');
verify(count($d5['issues']) === 0, 'pas de fausse alerte Divi 5');
$single = DAA_Scanner::post(100);
verify(count($single['issues']) >= 3, 'post et constructeur cumulés');
$batch = DAA_Scanner::batch();
verify($batch['total_checked'] === 2, 'inventaire borné');
verify(count($batch['posts']) === 2, 'résultats globaux');
verify(DAA_Scanner::post(101)['incomplete'] === true, 'audit partiel visible');
verify(DAA_Builders::divi(str_repeat('x', 1500001))['partial'] === true, 'limite de traitement Divi');
$GLOBALS['throw_post'] = 101;
$resilient = DAA_Scanner::batch();
verify($resilient['completed'] === true && $resilient['progress'] === 100,
    '100 pour cent décrit la fin du passage et non la couverture');
verify($resilient['failed'] === 1 && $resilient['total_checked'] === 2,
    'une page inaccessible est comptée sans arrêter le parcours');
verify(count($resilient['posts']) === 2 &&
    count(array_filter($resilient['posts'], static fn($row) => $row['status'] === 'ok')) === 1,
    'le contenu suivant reste analysé');
unset($GLOBALS['throw_post']);

// Aucune observation perdue silencieusement quand la limite est atteinte.
$tooMany = DAA_Analyzer::analyze(str_repeat('<img src="a.jpg">', DAA_Analyzer::MAX_ISSUES + 2));
verify($tooMany['status'] === 'limited', 'limite HTML explicitement signalée');
verify(count($tooMany['issues']) === DAA_Analyzer::MAX_ISSUES, 'observations HTML conservées jusque la limite');
$GLOBALS['posts'][102] = (object) array('ID' => 102,
    'post_content' => str_repeat('<img src="a.jpg">', DAA_Analyzer::MAX_ISSUES + 2));
$limitedPost = DAA_Scanner::post(102);
verify($limitedPost['status'] === 'limited' && $limitedPost['incomplete'],
    'la page à trop nombreuses observations reste incomplète');
verify(count($limitedPost['issues']) === DAA_Analyzer::MAX_ISSUES,
    'les observations déjà trouvées ne disparaissent pas');
$limitedBatch = DAA_Scanner::batch();
verify($limitedBatch['failed'] === 0 && $limitedBatch['incomplete'] >= 1,
    'la limite de détails ne compte pas comme une erreur fatale et le passage continue');
unset($GLOBALS['posts'][102]);

// Elementor : un widget de texte trop long doit rendre le contrôle incomplet.
$GLOBALS['meta'][103] = array('_elementor_data' => json_encode(array(
    array('elType' => 'widget', 'widgetType' => 'text-editor',
        'settings' => array('editor' => str_repeat('Texte ', DAA_Analyzer::MAX_BYTES)))
)));
$oversizedWidget = DAA_Builders::elementor(103);
verify($oversizedWidget['detected'] && $oversizedWidget['partial'],
    'widget HTML trop long signalé incomplet');
verify(str_contains(implode(' ', $oversizedWidget['notes']), 'trop long'),
    'note claire pour le widget texte ignoré');

// Elementor : ne pas ignorer silencieusement les éléments au-delà du niveau 18.
$deep = array('elType' => 'widget', 'widgetType' => 'heading',
    'settings' => array('title' => 'Titre profond'));
for ($level = 0; $level < 20; ++$level) {
    $deep = array('elType' => 'container', 'elements' => array($deep));
}
$GLOBALS['meta'][104] = array('_elementor_data' => json_encode(array($deep)));
verify(DAA_Builders::elementor(104)['partial'] === true,
    'arborescence Elementor trop profonde signalée incomplète');
unset($GLOBALS['meta'][103], $GLOBALS['meta'][104]);

echo "Dendrila Accessibility Assistant : contrats OK\n";

<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
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
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
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
echo "Dendrila Accessibility Assistant : contrats OK\n";

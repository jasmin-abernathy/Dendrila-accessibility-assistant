<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
$GLOBALS['mimes'] = array(10 => 'image/jpeg', 11 => 'image/png', 12 => 'image/webp', 13 => 'image/jpeg', 14 => 'image/jpeg', 15 => 'application/pdf');
$GLOBALS['alts'] = array(11 => '', 12 => '', 13 => 'Graphique présentant la répartition', 14 => 'Portrait du jardinier');
$GLOBALS['purpose'] = array(12 => 'decorative', 13 => 'informative');
function verify_media(bool $value, string $message): void {
    if (!$value) { throw new RuntimeException('Échec : ' . $message); }
}
function get_post_type($id): string { return isset($GLOBALS['mimes'][$id]) ? 'attachment' : 'post'; }
function get_post_mime_type($id): string { return $GLOBALS['mimes'][$id] ?? ''; }
function metadata_exists($type, $id, $key): bool { return array_key_exists($id, $GLOBALS['purpose']); }
function update_post_meta($id, $key, $value): bool {
    if ($key === '_daa_media_review') { $GLOBALS['purpose'][$id] = $value; }
    if ($key === '_wp_attachment_image_alt') { $GLOBALS['alts'][$id] = $value; }
    return true;
}
function get_post_meta($id, $key, $single = true) {
    return $key === '_daa_media_review' ? ($GLOBALS['purpose'][$id] ?? '') :
        ($key === '_wp_attachment_image_alt' ? ($GLOBALS['alts'][$id] ?? '') : '');
}
function get_posts($args): array { return array(10, 11, 12, 13, 14, 15); }
function get_the_title($id): string { return 'Photo ' . $id; }
require dirname(__DIR__) . '/includes/class-daa-media.php';
verify_media(DAA_Media::status(11) === 'legacy_missing', 'ancienne image sans alternative repérée');
verify_media(DAA_Media::status(14) === 'legacy_described', 'ancien texte conservé');
verify_media(DAA_Media::status(12) === 'decorative', 'décoration acceptée avec texte vide choisi');
verify_media(DAA_Media::status(13) === 'informative', 'image utile décrite');
verify_media(!DAA_Media::reviewed('informative', ''), 'nouvelle informative vide rejetée');
verify_media(!DAA_Media::reviewed('decorative', 'texte inutile'), 'décorative non vide rejetée');
verify_media(!DAA_Media::reviewed('', ''), 'choix obligatoire');
verify_media(DAA_Media::reviewed('informative', 'Trois personnes en train de jardiner'), 'bonne alternative');
DAA_Media::new_attachment(10);
verify_media(DAA_Media::status(10) === 'pending', 'nouvel import à compléter');
verify_media(DAA_Media::status(11) === 'legacy_missing',
    'les anciennes images ne changent pas de statut au scan');
verify_media(DAA_Media::status(15) === 'legacy_missing' || !DAA_Media::is_image(15),
    'le document non image reste hors périmètre');
$library = DAA_Media::library();
verify_media($library['completed'] && $library['progress'] === 100, 'pourcentage final');
verify_media($library['scanned'] === 5 && $library['pending'] === 2, 'compte des images et états');
$fields = DAA_Media::help_fields(array('image_alt' => array(), 'post_excerpt' => array(), 'post_content' => array()),
    (object) array('ID' => 10));
verify_media(str_contains($fields['image_alt']['helps'], 'décorative'), 'aide sous le texte alternatif');
verify_media(str_contains($fields['post_excerpt']['helps'], 'légende'), 'explication légende');
verify_media(str_contains($fields['post_content']['helps'], 'description'), 'explication description');
echo "Médiathèque : tests OK\n";

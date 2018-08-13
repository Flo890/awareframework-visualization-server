<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 12/08/2018
 * Time: 11:14
 *
 * username is the participant_id
 */


$realm = 'Geschützter Aware Bereich'; // to change, also change in DBReader


if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="' . $realm .
        '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) .
        '"');

    die('Text, der gesendet wird, falls der Benutzer auf Abbrechen drückt');
}

// Analysieren der Variable PHP_AUTH_DIGEST
if (!($daten = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']))) {
    header('HTTP/1.1 401 Unauthorized');
    die('Falsche Zugangsdaten! (digest parse)');
}

// ensure that login-username matches queried participant_id
if ($participant_id != $daten['username']){
    die('entered username does not match query participant_id');
}

// Erzeugen einer gültigen Antwort
$A1 = $db_reader->getDigestAnswer($daten['username']);
if ($A1 == false){
    header('HTTP/1.1 401 Unauthorized');
    die('Falsche Zugangsdaten! (db check)');
}
$A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $daten['uri']);
$gueltige_antwort = md5($A1 . ':' . $daten['nonce'] . ':' . $daten['nc'] .
    ':' . $daten['cnonce'] . ':' . $daten['qop'] . ':' .
    $A2);

if ($daten['response'] != $gueltige_antwort) {
    header('HTTP/1.1 401 Unauthorized');
    die('Falsche Zugangsdaten! (answer compare)');
}

// OK, gültige Benutzername & Passwort
//echo 'Sie sind angemeldet als: ' . $daten['username'];

// Funktion zum analysieren der HTTP-Auth-Header
function http_digest_parse($txt) {
    // gegen fehlende Daten schützen
    $noetige_teile = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1,
        'username'=>1, 'uri'=>1, 'response'=>1);
    $daten = array();
    $schluessel = implode('|', array_keys($noetige_teile));

    preg_match_all('@(' . $schluessel . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@',
        $txt, $treffer, PREG_SET_ORDER);

    foreach ($treffer as $t) {
        $daten[$t[1]] = $t[3] ? $t[3] : $t[4];
        unset($noetige_teile[$t[1]]);
    }

    return $noetige_teile ? false : $daten;
}
?>
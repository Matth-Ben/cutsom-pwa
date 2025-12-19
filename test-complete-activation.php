<?php
/**
 * Test complet d'activation du plugin Custom PWA
 * 
 * Usage: wp eval-file test-complete-activation.php --allow-root
 */

echo "========================================\n";
echo "  Test : Activation Complète\n";
echo "========================================\n\n";

// Test 1: Fichiers copiés
echo "Test 1: Fichiers PWA\n";
echo "--------------------\n";

$site_root = ABSPATH;
$files = array(
    'sw.js' => $site_root . 'sw.js',
    'offline.html' => $site_root . 'offline.html',
);

foreach ( $files as $name => $path ) {
    if ( file_exists( $path ) ) {
        $size = filesize( $path );
        $perms = substr( sprintf( '%o', fileperms( $path ) ), -4 );
        echo "  ✅ $name : " . round( $size / 1024, 1 ) . " KB (chmod $perms)\n";
    } else {
        echo "  ❌ $name : MANQUANT\n";
    }
}

echo "\n";

// Test 2: Options créées
echo "Test 2: Options WordPress\n";
echo "-------------------------\n";

$options = array(
    'custom_pwa_config',
    'custom_pwa_settings',
    'custom_pwa_push_rules',
    'custom_pwa_custom_scenarios',
    'custom_pwa_push',
    'custom_pwa_file_copy_status',
);

foreach ( $options as $option ) {
    $value = get_option( $option );
    if ( false !== $value ) {
        echo "  ✅ $option\n";
    } else {
        echo "  ❌ $option : MANQUANT\n";
    }
}

echo "\n";

// Test 3: Manifest accessible
echo "Test 3: Manifest Endpoint\n";
echo "--------------------------\n";

$manifest_url = home_url( '/manifest.webmanifest' );
$response = wp_remote_get( $manifest_url, array(
    'sslverify' => false,
    'timeout' => 5,
) );

if ( ! is_wp_error( $response ) ) {
    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    
    if ( 200 === $code ) {
        $json = json_decode( $body, true );
        if ( $json && isset( $json['name'] ) ) {
            echo "  ✅ Manifest accessible (200 OK)\n";
            echo "     App Name: {$json['name']}\n";
            echo "     Start URL: {$json['start_url']}\n";
        } else {
            echo "  ❌ Manifest accessible mais JSON invalide\n";
        }
    } else {
        echo "  ❌ Manifest retourne HTTP $code\n";
    }
} else {
    echo "  ❌ Erreur: " . $response->get_error_message() . "\n";
}

echo "\n";

// Test 4: Table BDD
echo "Test 4: Table de Base de Données\n";
echo "---------------------------------\n";

global $wpdb;
$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

if ( $table_exists ) {
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    echo "  ✅ Table $table_name existe ($count abonnements)\n";
} else {
    echo "  ❌ Table $table_name manquante\n";
}

echo "\n";

// Test 5: Scénarios initialisés
echo "Test 5: Scénarios Configurés\n";
echo "-----------------------------\n";

$rules = get_option( 'custom_pwa_push_rules', array() );
$post_types_count = count( $rules );
$total_scenarios = 0;

foreach ( $rules as $post_type => $config ) {
    $scenarios = isset( $config['scenarios'] ) ? count( $config['scenarios'] ) : 0;
    $total_scenarios += $scenarios;
}

echo "  ✅ $post_types_count post types configurés\n";
echo "  ✅ $total_scenarios scénarios au total\n";

if ( isset( $rules['post'] ) ) {
    $post_scenarios = array_keys( $rules['post']['scenarios'] );
    echo "     Post type 'post': " . implode( ', ', $post_scenarios ) . "\n";
}

echo "\n";

// Résumé
echo "========================================\n";
echo "  Résumé\n";
echo "========================================\n";

$all_ok = true;
$all_ok = $all_ok && file_exists( $site_root . 'sw.js' );
$all_ok = $all_ok && file_exists( $site_root . 'offline.html' );
$all_ok = $all_ok && false !== get_option( 'custom_pwa_config' );
$all_ok = $all_ok && $table_exists;
$all_ok = $all_ok && $post_types_count > 0;

if ( $all_ok ) {
    echo "✅ Installation COMPLÈTE et FONCTIONNELLE\n";
    echo "✅ Aucune intervention manuelle requise\n";
    echo "✅ Le plugin est prêt à être utilisé\n\n";
    echo "👉 Prochaines étapes :\n";
    echo "   1. Aller dans Custom PWA → Configuration\n";
    echo "   2. Activer PWA et/ou Push Notifications\n";
    echo "   3. Configurer les scénarios souhaités\n";
} else {
    echo "❌ Installation INCOMPLÈTE\n";
    echo "👉 Voir Custom PWA → Installation pour instructions manuelles\n";
}

echo "\n";

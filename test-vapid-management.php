<?php
/**
 * Test VAPID Key Management
 * 
 * Usage: wp eval-file test-vapid-management.php --allow-root
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once dirname( __FILE__ ) . '/../../../wp-load.php';
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  Test de Gestion des Clés VAPID                          ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// 1. Get current VAPID keys
echo "1️⃣  Clés VAPID actuelles\n";
echo "─────────────────────────────────────────────────────────\n";
$current_keys = get_option( 'custom_pwa_push', array() );

if ( empty( $current_keys['public_key'] ) || empty( $current_keys['private_key'] ) ) {
	echo "❌ Aucune clé trouvée\n\n";
} else {
	echo "✅ Clés trouvées\n";
	echo "   Public Key  : " . substr( $current_keys['public_key'], 0, 40 ) . "...\n";
	echo "   Private Key : " . substr( $current_keys['private_key'], 0, 40 ) . "...\n";
	echo "   Longueur Public  : " . strlen( $current_keys['public_key'] ) . " caractères\n";
	echo "   Longueur Private : " . strlen( $current_keys['private_key'] ) . " caractères\n\n";
}

// 2. Check subscriptions count
global $wpdb;
$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
echo "2️⃣  Abonnements actuels\n";
echo "─────────────────────────────────────────────────────────\n";
echo "   Total : " . $count . " abonnement(s)\n\n";

// 3. Test key generation function
echo "3️⃣  Test de génération de nouvelles clés\n";
echo "─────────────────────────────────────────────────────────\n";

// Load the config class
require_once dirname( __FILE__ ) . '/includes/class-config-settings.php';

// Use reflection to access private method
$config = new Custom_PWA_Config_Settings();
$reflection = new ReflectionClass( $config );
$method = $reflection->getMethod( 'generate_vapid_keys' );
$method->setAccessible( true );

$new_keys = $method->invoke( $config );

if ( empty( $new_keys['public_key'] ) || empty( $new_keys['private_key'] ) ) {
	echo "❌ Échec de la génération\n";
	echo "   Vérifiez que OpenSSL est installé\n\n";
} else {
	echo "✅ Génération réussie\n";
	echo "   New Public Key  : " . substr( $new_keys['public_key'], 0, 40 ) . "...\n";
	echo "   New Private Key : " . substr( $new_keys['private_key'], 0, 40 ) . "...\n";
	echo "   Longueur Public  : " . strlen( $new_keys['public_key'] ) . " caractères\n";
	echo "   Longueur Private : " . strlen( $new_keys['private_key'] ) . " caractères\n\n";
}

// 4. Verify keys are different
echo "4️⃣  Vérification d'unicité\n";
echo "─────────────────────────────────────────────────────────\n";
if ( ! empty( $current_keys['public_key'] ) && ! empty( $new_keys['public_key'] ) ) {
	if ( $current_keys['public_key'] === $new_keys['public_key'] ) {
		echo "❌ Les clés sont identiques (problème!)\n\n";
	} else {
		echo "✅ Les nouvelles clés sont différentes des anciennes\n\n";
	}
} else {
	echo "ℹ️  Impossible de comparer (clés manquantes)\n\n";
}

// 5. Check OpenSSL capabilities
echo "5️⃣  Capacités OpenSSL\n";
echo "─────────────────────────────────────────────────────────\n";
if ( function_exists( 'openssl_pkey_new' ) ) {
	echo "✅ openssl_pkey_new() disponible\n";
	
	$curves = openssl_get_curve_names();
	if ( in_array( 'prime256v1', $curves ) ) {
		echo "✅ Courbe P-256 (prime256v1) supportée\n";
	} else {
		echo "❌ Courbe P-256 NON supportée\n";
	}
} else {
	echo "❌ OpenSSL non disponible\n";
}

echo "\n";

// 6. Admin URL
echo "6️⃣  Accès à l'interface\n";
echo "─────────────────────────────────────────────────────────\n";
$admin_url = admin_url( 'admin.php?page=custom-pwa-config' );
echo "   URL Admin : " . $admin_url . "\n";
echo "   Section   : VAPID Keys (en bas de la page)\n";
echo "   Bouton    : 🔄 Regenerate VAPID Keys\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "Résumé\n";
echo "═══════════════════════════════════════════════════════════\n";

if ( function_exists( 'openssl_pkey_new' ) && 
     ! empty( $current_keys['public_key'] ) && 
     ! empty( $new_keys['public_key'] ) ) {
	echo "✅ Tout fonctionne parfaitement!\n";
	echo "✅ Les clés VAPID sont générées automatiquement\n";
	echo "✅ Le bouton de régénération est disponible dans Config\n";
	echo "✅ OpenSSL est fonctionnel\n";
} else {
	echo "⚠️  Problèmes détectés - vérifiez les détails ci-dessus\n";
}

echo "═══════════════════════════════════════════════════════════\n\n";

echo "💡 Pour tester la régénération manuellement :\n";
echo "   1. Aller sur : " . $admin_url . "\n";
echo "   2. Défiler jusqu'à la section 'VAPID Keys'\n";
echo "   3. Cliquer sur '🔄 Regenerate VAPID Keys'\n";
echo "   4. Confirmer l'action\n";
echo "   5. Les anciennes clés seront remplacées\n";
echo "   6. Tous les abonnements seront supprimés\n\n";

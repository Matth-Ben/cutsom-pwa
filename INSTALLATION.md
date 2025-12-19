# Installation et Activation

## 🚀 Activation automatique du plugin

Lorsque vous activez Custom PWA pour la première fois, le plugin effectue **automatiquement et sans intervention** toutes les étapes d'installation nécessaires.

### ✅ Ce qui se passe automatiquement :

#### 1. **Copie des fichiers essentiels**

Le plugin copie automatiquement les fichiers nécessaires à la racine de votre site :

- ✅ `sw.js` - Service Worker (depuis `assets/examples/sw-example.js`)
- ✅ `offline.html` - Page hors-ligne (depuis `assets/examples/offline-example.html`)

**Ces fichiers DOIVENT être à la racine** pour que le PWA fonctionne correctement. Le plugin le fait automatiquement pour vous !

#### 3. **Création de la table de base de données**
```sql
CREATE TABLE wp_custom_pwa_subscriptions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    endpoint text NOT NULL,
    user_public_key varchar(255) NOT NULL,
    user_auth_secret varchar(255) NOT NULL,
    user_agent text,
    ip_address varchar(45),
    subscribed_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY endpoint (endpoint(191))
)
```

Cette table stocke les abonnements push des utilisateurs.

#### 4. **Initialisation des options WordPress**

Les options suivantes sont créées dans `wp_options` :

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| `custom_pwa_config` | Configuration générale | PWA et Push désactivés, mode debug off |
| `custom_pwa_settings` | Paramètres PWA | Nom du site, couleurs, icônes |
| `custom_pwa_push_rules` | Règles de notification | Scénarios pour tous les post types publics |
| `custom_pwa_custom_scenarios` | Scénarios personnalisés | Tableau vide `[]` |
| `custom_pwa_push` | Clés VAPID | Générées automatiquement pour Web Push |
| `custom_pwa_file_copy_status` | Statut de copie | Fichiers copiés, erreurs, timestamp |

#### 5. **Génération des clés VAPID**

Le plugin génère automatiquement une paire de clés cryptographiques (VAPID) nécessaires pour les notifications push Web :

- Clé publique (partagée avec les navigateurs)
- Clé privée (conservée secrètement sur le serveur)

Ces clés utilisent la courbe elliptique P-256 (prime256v1) pour une sécurité maximale.

#### 6. **Configuration des scénarios par post type**

Le plugin détecte tous les **post types publics** de votre site et crée automatiquement les scénarios appropriés.

##### Exemples de détection intelligente :

**Post type `post` (Blog/Articles)** :
- ✅ Publication (nouveau article publié)
- ✅ Major Update (article mis à jour significativement)
- ✅ Featured (article mis en avant)

**Post type `product` (E-commerce)** :
- ✅ Publication (nouveau produit)
- ✅ Price Drop (baisse de prix)
- ✅ Back in Stock (retour en stock)
- ✅ Out of Stock (rupture de stock)
- ✅ Low Stock (stock faible)
- ✅ End of Life (produit discontinué)

**Post type `event` (Événements)** :
- ✅ Publication (nouvel événement)
- ✅ Sales Open (billetterie ouverte)
- ✅ Last Tickets (derniers billets)
- ✅ Sold Out (complet)
- ✅ Cancelled (annulé)
- ✅ Rescheduled (reporté)

**Autres post types (Generic)** :
- ✅ Publication
- ✅ Major Update
- ✅ Status Change

##### Mapping automatique :

Le plugin détecte automatiquement le rôle du post type :

```php
'post' → 'blog'
'product' → 'ecommerce'
'event', 'tribe_events' → 'events'
// Patterns dans le nom :
*event* → 'events'
*product*, *shop* → 'ecommerce'
*post*, *article* → 'blog'
// Par défaut :
* → 'generic'
```

#### 7. **Sécurité par défaut**

Pour votre sécurité, **tout est désactivé par défaut** :

- ❌ PWA désactivée
- ❌ Push désactivé
- ❌ Tous les post types désactivés
- ❌ Tous les scénarios désactivés

Vous devez **explicitement activer** ce que vous souhaitez utiliser.

---

## � Vérifier l'installation

### Via l'interface admin (recommandé)

1. **Allez dans Custom PWA → Installation**
2. Vous verrez un tableau avec le statut de tous les fichiers
3. Si tout est vert ✅, l'installation est réussie !

### Via WP-CLI

Après activation, vous pouvez vérifier l'installation avec WP-CLI :

```bash
# Vérifier les options créées
wp option get custom_pwa_config --format=json
wp option get custom_pwa_push_rules --format=json

# Vérifier la table
wp db query "SHOW TABLES LIKE 'wp_custom_pwa_subscriptions';"

# Vérification complète de l'installation (recommandé)
wp eval-file wp-content/plugins/cutsom-pwa/test-complete-activation.php --allow-root
```

### 🎯 Première configuration

Après l'activation, suivez ces étapes :

1. **Aller dans Custom PWA → Configuration**
   - Cocher "Enable PWA"
   - Cocher "Enable Push Notifications"
   - Sélectionner les post types à surveiller

2. **Aller dans Custom PWA → PWA**
   - Configurer le nom de l'application
   - Choisir les couleurs
   - Uploader une icône (192x192 minimum)

3. **Aller dans Custom PWA → Push → Post Type Configuration**
   - Sélectionner un post type (ex: Post)
   - Cocher "Enable Push Notifications for this post type"
   - Activer les scénarios souhaités
   - Personnaliser les templates de notification

4. **Tester !**
   - Publier un article
   - Vérifier les logs : `tail -f wp-content/debug.log`
   - Les notifications doivent être envoyées automatiquement

### 🔄 Réinstallation propre

Si vous souhaitez repartir de zéro :

```bash
# Désactiver le plugin
wp plugin deactivate cutsom-pwa --allow-root

# Supprimer les options
wp option delete custom_pwa_config --allow-root
wp option delete custom_pwa_settings --allow-root
wp option delete custom_pwa_push_rules --allow-root
wp option delete custom_pwa_custom_scenarios --allow-root
wp option delete custom_pwa_push --allow-root

# Supprimer la table
wp db query "DROP TABLE IF EXISTS wp_custom_pwa_subscriptions;" --allow-root

# Réactiver (réinitialisation complète)
wp plugin activate cutsom-pwa --allow-root
```

### ⚠️ Notes importantes

1. **OpenSSL requis** : Le plugin a besoin de l'extension PHP OpenSSL pour générer les clés VAPID. Si OpenSSL n'est pas disponible, les clés seront vides et les notifications push ne fonctionneront pas.

2. **HTTPS obligatoire** : Les notifications push et les PWA nécessitent HTTPS en production. Le plugin détecte automatiquement les environnements locaux (.local, .test, .dev, localhost) et active le mode développement.

3. **Permaliens** : Le plugin flush les rewrite rules pour enregistrer l'endpoint `/manifest.json`. Si vous avez des problèmes, allez dans Réglages → Permaliens et cliquez sur "Enregistrer".

4. **Post types custom** : Si vous installez un plugin qui ajoute des post types (WooCommerce, The Events Calendar, etc.) APRÈS l'activation de Custom PWA, vous devez :
   - Désactiver Custom PWA
   - Réactiver Custom PWA
   - Les nouveaux post types seront automatiquement configurés

5. **Migrations** : Le plugin détecte et migre automatiquement l'ancien format de données (pré-scénarios) vers le nouveau format lors du premier chargement de la page admin.

### 🆘 Dépannage

**Problème** : "Les scénarios ne sont pas créés"
- **Solution** : Vérifiez les logs `wp-content/debug.log`. Cherchez "Custom PWA: Initialized default scenarios".

**Problème** : "Les notifications ne sont pas envoyées"
- **Solution** : Vérifiez que :
  1. Push est activé dans Configuration
  2. Le post type est activé
  3. Au moins un scénario est activé
  4. Il y a au moins un abonné dans la table

**Problème** : "Clés VAPID vides"
- **Solution** : Vérifiez que OpenSSL est installé : `php -m | grep openssl`

**Problème** : "manifest.json retourne 404"
- **Solution** : Allez dans Réglages → Permaliens → Enregistrer

### 📚 Plus d'informations

- [Guide des scénarios](SCENARIOS-USAGE.md)
- [CHANGELOG](CHANGELOG.md)
- Support : [GitHub Issues](https://github.com/Matth-Ben/cutsom-pwa/issues)

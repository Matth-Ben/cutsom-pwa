# Guide d'utilisation des scénarios de notifications

Ce document explique comment fonctionnent les différents scénarios de notifications push et comment les déclencher.

## 🎯 Configuration des scénarios avec le sélecteur de champs meta

**Nouveauté importante** : Les scénarios peuvent maintenant être configurés directement depuis l'interface admin avec un sélecteur de champs personnalisés.

### Comment ça marche ?

1. **Allez dans** : Custom PWA → Push → Post Type Configuration
2. **Sélectionnez un post type** (ex: Post, Product, Event)
3. **Développez un scénario** qui utilise un champ meta (ex: Major Update, Price Drop, Sold Out)
4. **Utilisez le dropdown "Meta Key"** pour sélectionner le champ qui déclenche le scénario :
   - **Suggested** : Le champ par défaut recommandé (ex: `major_update`)
   - **Available Meta Keys** : Les champs réels trouvés dans votre base de données
   - **Custom** : Entrez manuellement un champ personnalisé

### Exemples pratiques

**Pour WooCommerce** :
- Scénario "Price Drop" → Sélectionnez `_price` dans le dropdown
- Scénario "Back in Stock" → Sélectionnez `_stock_status`
- Scénario "Low Stock" → Sélectionnez `_stock`

**Pour les événements** :
- Scénario "Sold Out" → Sélectionnez `event_sold_out` (si vous utilisez un plugin d'événements)
- Scénario "Cancelled" → Sélectionnez `event_status`

**Pour les articles de blog** :
- Scénario "Featured" → Sélectionnez votre champ ACF personnalisé (ex: `article_featured`)
- Scénario "Major Update" → Sélectionnez `major_update` ou créez votre propre champ

### Déclenchement automatique

Une fois configuré, le système surveille automatiquement le champ sélectionné :

```
✅ Champ configuré : _price
✅ Quand _price change → Le scénario "Price Drop" se déclenche
✅ Le template reçoit {meta_value} avec la nouvelle valeur
```

Vous pouvez utiliser `{meta_value}` dans vos templates pour afficher la nouvelle valeur :

```
Titre : Baisse de prix !
Corps : Le prix est maintenant de {meta_value}€
```

---

## 📋 Les 3 scénarios disponibles

### 1. 🆕 Publication (`publication`)

**Quand est-il déclenché ?**
- Automatiquement quand un post passe de l'état brouillon/en attente/planifié à l'état "publié"
- Se déclenche UNE SEULE FOIS lors de la première publication

**Comment le tester ?**
1. Créez un nouveau post (ou utilisez un brouillon existant)
2. Cliquez sur "Publier"
3. La notification est envoyée immédiatement

**Templates par défaut :**
```
Title: New: {post_title}
Body: {excerpt}
URL: {permalink}
```

**Exemple de notification :**
```
Titre: "New: Mon super article"
Corps: "Voici un court résumé de mon article..."
URL: "https://labo.local/mon-super-article"
```

---

### 2. 🔄 Mise à jour majeure (`major_update`)

**Quand est-il déclenché ?**
- Quand vous mettez à jour un post DÉJÀ PUBLIÉ
- **ET** que vous avez coché une case ou ajouté un meta `major_update`

**Comment le tester ?**

**Méthode 1 : Via le code**
```php
// Dans functions.php ou un plugin
update_post_meta( $post_id, 'major_update', true );
wp_update_post( array( 'ID' => $post_id ) );
```

**Méthode 2 : Ajouter une checkbox dans l'éditeur**
Ajoutez ce code dans `functions.php` :

```php
// Ajouter une meta box pour marquer une mise à jour majeure
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'major_update_notification',
        'Notification Push',
        'render_major_update_metabox',
        'post',
        'side',
        'high'
    );
});

function render_major_update_metabox( $post ) {
    $checked = get_post_meta( $post->ID, 'major_update', true );
    ?>
    <label>
        <input type="checkbox" name="major_update" value="1" <?php checked( $checked, true ); ?>>
        Envoyer une notification de mise à jour majeure
    </label>
    <p class="description">
        Cochez cette case pour notifier les abonnés que cet article a été mis à jour de façon significative.
    </p>
    <?php
}

// Sauvegarder le meta
add_action( 'save_post', function( $post_id ) {
    if ( isset( $_POST['major_update'] ) ) {
        update_post_meta( $post_id, 'major_update', true );
    } else {
        delete_post_meta( $post_id, 'major_update' );
    }
}, 10, 1 );
```

**Templates par défaut :**
```
Title: Updated: {post_title}
Body: This item has been updated.
URL: {permalink}
```

**Exemple de notification :**
```
Titre: "Updated: Mon super article"
Corps: "Cet article a été mis à jour."
URL: "https://labo.local/mon-super-article"
```

---

### 3. 📊 Changement de statut (`status_change`)

**Quand est-il déclenché ?**
- Quand vous modifiez le meta `status` d'un post publié
- Utile pour les sites événementiels (confirmé, annulé, reporté)

**Comment le tester ?**

**Méthode 1 : Via le code**
```php
// Mettre à jour le statut
update_post_meta( $post_id, 'status', 'Annulé' );
```

**Méthode 2 : Ajouter un champ dans l'éditeur**
Ajoutez ce code dans `functions.php` :

```php
// Ajouter une meta box pour le statut
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'status_notification',
        'Statut de l\'événement',
        'render_status_metabox',
        'post',
        'side',
        'default'
    );
});

function render_status_metabox( $post ) {
    $status = get_post_meta( $post->ID, 'status', true );
    $statuses = array(
        'Confirmé',
        'Reporté',
        'Annulé',
        'Complet',
        'En attente',
    );
    ?>
    <select name="event_status">
        <option value="">-- Sélectionner --</option>
        <?php foreach ( $statuses as $s ) : ?>
            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>>
                <?php echo esc_html( $s ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        Changez le statut pour notifier les abonnés.
    </p>
    <?php
}

// Sauvegarder et déclencher notification
add_action( 'save_post', function( $post_id ) {
    if ( isset( $_POST['event_status'] ) && ! empty( $_POST['event_status'] ) ) {
        $old_status = get_post_meta( $post_id, 'status', true );
        $new_status = sanitize_text_field( $_POST['event_status'] );
        
        // Mettre à jour seulement si le statut a changé
        if ( $old_status !== $new_status ) {
            update_post_meta( $post_id, 'status', $new_status );
            // La notification sera automatiquement envoyée via le hook 'updated_post_meta'
        }
    }
}, 10, 1 );
```

**Templates par défaut :**
```
Title: {status_label}: {post_title}
Body: Status has been updated.
URL: {permalink}
```

**Exemple de notification :**
```
Titre: "Annulé: Concert de Jazz"
Corps: "Le statut a été mis à jour."
URL: "https://labo.local/concert-de-jazz"
```

---

## 🎨 Placeholders disponibles

Les placeholders suivants sont disponibles dans tous les scénarios :

| Placeholder | Description | Exemple |
|------------|-------------|---------|
| `{post_title}` | Titre du post | "Mon Super Article" |
| `{excerpt}` | Extrait ou résumé (max 20 mots) | "Voici un court résumé..." |
| `{permalink}` | URL complète du post | "https://labo.local/mon-article" |
| `{post_type}` | Type de post | "post", "page", "event" |

### Placeholders additionnels pour sites événementiels

Si votre site est configuré comme "events" dans Config :

| Placeholder | Description | Meta key |
|------------|-------------|----------|
| `{event_date}` | Date de l'événement | `_event_date` |
| `{venue}` | Lieu de l'événement | `_venue` |
| `{status_label}` | Statut actuel | `_status_label` ou `status` |

### Placeholders spécifiques aux scénarios

**Pour `status_change` uniquement :**
- `{status_label}` : Le nouveau statut (ex: "Annulé", "Reporté")

---

## ⚙️ Configuration dans l'admin

1. Allez dans **Custom PWA → Push**
2. Sélectionnez l'onglet **Post Type Configuration**
3. Choisissez un post type dans la sidebar (ex: "Posts")
4. Pour chaque scénario :
   - ✅ Cochez "Enable this scenario"
   - ✏️ Personnalisez les templates (Title, Body, URL)
5. Cliquez sur **Save Changes**

---

## 🧪 Tests rapides

### Test 1 : Publication
```bash
# Via WP-CLI
wp post create --post_title="Test Notification" --post_status=publish
```

### Test 2 : Mise à jour majeure
```bash
# Via WP-CLI
wp post meta update 123 major_update 1
wp post update 123 --post_title="Titre modifié"
```

### Test 3 : Changement de statut
```bash
# Via WP-CLI
wp post meta update 123 status "Annulé"
```

---

## 🔍 Debug

Pour voir les logs du dispatcher, activez WP_DEBUG dans `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Les logs apparaîtront dans `wp-content/debug.log` :
```
[18-Dec-2025 16:00:00 UTC] Scenario "publication" not enabled for post type: post
[18-Dec-2025 16:00:01 UTC] Notification sent: "New: Mon Article" to 5 subscribers
```

---

## 📝 Créer des scénarios personnalisés

Vous pouvez créer vos propres scénarios dans **Custom PWA → Push → Manage Scenarios** :

### Étapes de création

1. Cliquez sur "Add New Scenario"
2. **ID unique** : Utilisez un identifiant comme `price_drop`, `back_in_stock`, `event_cancelled`
3. **Label** : Nom affiché dans l'admin (ex: "Baisse de prix", "Retour en stock")
4. **Description** : Expliquez ce que fait ce scénario
5. **Scope** : 
   - **Global** : Applicable à tous les post types
   - **Post Type Specific** : Uniquement pour certains post types (produits, événements, etc.)
6. **Trigger Type** : Choisissez quand la notification est envoyée
   - `on_publish` : À la première publication d'un post
   - `on_update` : À chaque modification d'un post publié
   - `on_meta_change` : Quand un meta spécifique change (nécessite un meta_key)
   - `on_status_change` : Quand le statut WordPress change
7. **Meta Key** (pour `on_meta_change`) : Le nom du champ personnalisé à surveiller (ex: `price`, `stock_status`, `event_status`)
8. **Templates** : Personnalisez les templates (Title, Body, URL)
9. **Fields Used** : Listez les metas utilisés dans vos templates pour un meilleur tracking
10. Sauvegardez

### Comment les scénarios personnalisés sont déclenchés

Les scénarios personnalisés s'exécutent **automatiquement** en parallèle des scénarios intégrés :

#### 🆕 Scénarios `on_publish`
- **Déclenchés** : Quand un post passe de brouillon/en attente → publié
- **En même temps que** : Le scénario intégré `publication`
- **Utilisation** : Annonces spéciales, promotions, lancements

**Exemple** : Scénario "new_product_launch"
```
Trigger: on_publish
Scope: Post Type Specific (product)
Title: 🎉 Nouveau : {post_title}
Body: Découvrez notre dernière nouveauté !
```

#### 🔄 Scénarios `on_update`
- **Déclenchés** : Quand un post PUBLIÉ est modifié
- **En même temps que** : Le scénario intégré `major_update` (si le flag major_update est set)
- **Utilisation** : Corrections, améliorations, ajouts de contenu

**Exemple** : Scénario "content_enhanced"
```
Trigger: on_update
Scope: Global
Title: 📝 Mis à jour : {post_title}
Body: Nouvelles informations ajoutées !
```

#### 🏷️ Scénarios `on_meta_change`
- **Déclenchés** : Quand un meta field spécifique est modifié
- **Condition** : Le `meta_key` du scénario doit correspondre au meta modifié
- **Utilisation** : Prix, stock, statuts personnalisés, dates

**Exemple 1** : Scénario "price_drop" (E-commerce)
```
Trigger: on_meta_change
Meta Key: price
Scope: Post Type Specific (product)
Title: 💰 Prix baissé : {post_title}
Body: Nouveau prix : {price}€ !
```

**Exemple 2** : Scénario "back_in_stock"
```
Trigger: on_meta_change
Meta Key: stock_status
Scope: Post Type Specific (product)
Title: ✅ De retour : {post_title}
Body: {post_title} est à nouveau disponible !
```

**Exemple 3** : Scénario "event_cancelled"
```
Trigger: on_meta_change
Meta Key: event_status
Scope: Post Type Specific (event)
Title: ⚠️ {event_status}: {post_title}
Body: L'événement a été {event_status}.
```

#### 📊 Scénarios `on_status_change`
- **Déclenchés** : Quand le statut WordPress change (publish → draft, publish → pending, etc.)
- **En même temps que** : Le scénario intégré `status_change`
- **Utilisation** : Dépublication, archivage, workflows

**Exemple** : Scénario "article_archived"
```
Trigger: on_status_change
Scope: Global
Title: 📦 Archivé : {post_title}
Body: Cet article n'est plus disponible.
```

### ⚙️ Activation des scénarios personnalisés

Une fois créés, les scénarios personnalisés apparaissent automatiquement dans **Post Type Configuration** :

1. Allez dans **Custom PWA → Push → Post Type Configuration**
2. Sélectionnez un post type (ex: "Posts")
3. Vous verrez :
   - Les 3 scénarios intégrés (publication, major_update, status_change)
   - **PLUS** tous vos scénarios personnalisés applicables à ce post type
4. Cochez "Enable this scenario" pour chaque scénario que vous voulez activer
5. Modifiez les templates si besoin (les templates par défaut viennent de la définition du scénario)
6. Sauvegardez

### 🎯 Cas d'usage avancés avec scénarios personnalisés

#### E-commerce - Site de vente en ligne

```php
// Dans functions.php : Hook pour détecter une baisse de prix
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key === 'price') {
        $old_price = get_post_meta($post_id, '_old_price', true);
        if ($old_price && $meta_value < $old_price) {
            // La baisse de prix déclenchera automatiquement le scénario "price_drop"
            update_post_meta($post_id, '_old_price', $meta_value);
        }
    }
}, 10, 4);
```

**Scénarios suggérés** :
- `price_drop` : on_meta_change (price)
- `back_in_stock` : on_meta_change (stock_status)
- `flash_sale` : on_meta_change (sale_start)
- `new_product` : on_publish

#### Site événementiel

```php
// Metabox pour gérer le statut d'événement
add_action('save_post', function($post_id) {
    if (isset($_POST['event_status'])) {
        $new_status = sanitize_text_field($_POST['event_status']);
        update_post_meta($post_id, 'event_status', $new_status);
        // Déclenchera automatiquement les scénarios avec on_meta_change (event_status)
    }
});
```

**Scénarios suggérés** :
- `event_confirmed` : on_meta_change (event_status)
- `event_cancelled` : on_meta_change (event_status)
- `event_postponed` : on_meta_change (event_status)
- `last_tickets` : on_meta_change (tickets_remaining)
- `venue_changed` : on_meta_change (venue)

#### Blog / Magazine

**Scénarios suggérés** :
- `breaking_news` : on_publish (avec category = "breaking")
- `article_corrected` : on_update
- `featured_article` : on_meta_change (featured)
- `series_new_episode` : on_publish (avec taxonomy = "series")

#### Site immobilier

```php
// Détecter un changement de prix sur une propriété
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key === 'property_price' && get_post_type($post_id) === 'property') {
        // Le scénario "property_price_change" sera déclenché automatiquement
    }
}, 10, 4);
```

**Scénarios suggérés** :
- `new_listing` : on_publish
- `price_reduced` : on_meta_change (property_price)
- `open_house` : on_meta_change (open_house_date)
- `status_sold` : on_meta_change (property_status)

### 🔍 Différences : Scénarios intégrés vs Scénarios personnalisés

| Aspect | Scénarios intégrés | Scénarios personnalisés |
|--------|-------------------|------------------------|
| **Nombre** | 3 fixes (publication, major_update, status_change) | Illimité |
| **Modification** | Templates modifiables, triggers fixes | Tout est personnalisable |
| **Suppression** | Impossibles à supprimer | Peuvent être supprimés |
| **Triggers** | Prédéfinis par le code | 4 types au choix |
| **Scope** | Global (tous post types) | Global OU post-type specific |
| **Meta key** | Fixes (major_update, status) | N'importe quel meta |
| **Stockage** | Dans `custom_pwa_push_rules` | Dans `custom_pwa_custom_scenarios` |

### ⚡ Ordre d'exécution

Quand un événement se produit (publication, mise à jour, changement de meta), voici l'ordre :

1. **Vérifications préalables** : Push activé ? Post type activé ?
2. **Scénarios intégrés** : Exécution du scénario intégré correspondant (s'il est activé)
3. **Scénarios personnalisés** : Exécution de TOUS les scénarios personnalisés correspondants (s'ils sont activés)

**Exemple** : Publication d'un produit avec scénarios activés :
```
1. Vérification : Push activé ✓, Post type 'product' activé ✓
2. Scénario intégré 'publication' → Notification envoyée
3. Scénario personnalisé 'new_product_launch' (on_publish) → Notification envoyée
4. Scénario personnalisé 'promo_launch' (on_publish) → Notification envoyée
```

**Résultat** : 3 notifications différentes sont envoyées !

### ⚠️ Bonnes pratiques

1. **Évitez les doublons** : Si vous créez un scénario personnalisé avec `on_publish`, désactivez le scénario intégré `publication` pour éviter d'envoyer 2 notifications similaires

2. **Nommage clair** : Utilisez des IDs descriptifs (`price_drop` plutôt que `scenario_1`)

3. **Templates précis** : Utilisez des placeholders spécifiques pour rendre le contenu pertinent

4. **Testez avant** : Créez des scénarios sur un site de staging avant de les activer en production

5. **Limitez le nombre** : Trop de notifications = désabonnements. Soyez sélectif.

6. **Meta keys valides** : Assurez-vous que les meta keys existent réellement dans votre base de données

7. **Documentation** : Documentez vos scénarios personnalisés pour les autres administrateurs

---

## 📝 Créer des scénarios personnalisés (ancienne version)

**Note** : Cette section est obsolète. Utilisez l'interface "Manage Scenarios" décrite ci-dessus.

---

## 🎯 Cas d'usage réels

### Blog / Magazine
- **Publication** : "Nouvel article : {post_title}"
- **Mise à jour** : "Article mis à jour : {post_title}"

### Site E-commerce
- **Publication** : "Nouveau produit : {post_title}"
- **Mise à jour** (avec meta `price_drop`) : "Prix baissé : {post_title}"

### Site Événementiel
- **Publication** : "Nouvel événement : {post_title} le {event_date}"
- **Status change** : "{status_label}: {post_title} à {venue}"

### Site de News
- **Publication** : "🔴 BREAKING: {post_title}"
- **Mise à jour** : "📰 Mise à jour : {post_title}"

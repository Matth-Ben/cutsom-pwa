# Personnalisation de la Popup de Notifications

## Vue d'ensemble

Une popup élégante apparaît automatiquement sur le frontend pour demander aux utilisateurs l'autorisation d'activer les notifications push.

## Fonctionnalités

✅ **Design moderne et responsive**
- Animation fluide d'apparition
- Support du mode sombre automatique
- Design adaptatif mobile/desktop
- Icône de cloche animée

✅ **Logique intelligente**
- Affichée seulement si les notifications ne sont pas déjà accordées
- Ne s'affiche pas si déjà refusée par l'utilisateur
- Se souvient du refus pendant 30 jours
- Délai de 2 secondes avant l'apparition

✅ **Expérience utilisateur optimale**
- Bouton "Activer les notifications"
- Bouton "Plus tard" pour reporter
- États de chargement pendant l'abonnement
- Messages de succès/erreur
- Fermeture avec bouton X, clic sur l'overlay ou touche ESC

## Personnalisation du texte

### Via un filtre WordPress

Vous pouvez personnaliser le texte de la popup en ajoutant ce code dans votre `functions.php` :

```php
add_action('wp_footer', function() {
    ?>
    <script>
    // Attendre que le DOM soit prêt
    document.addEventListener('DOMContentLoaded', function() {
        // Écouter la création de la popup
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.classList && node.classList.contains('custom-pwa-notification-overlay')) {
                            // Personnaliser le contenu
                            const title = node.querySelector('.custom-pwa-notification-title');
                            const description = node.querySelector('.custom-pwa-notification-description');
                            const primaryBtn = node.querySelector('.custom-pwa-notification-button-primary');
                            const secondaryBtn = node.querySelector('.custom-pwa-notification-button-secondary');
                            
                            if (title) title.textContent = 'Votre titre personnalisé';
                            if (description) description.textContent = 'Votre description personnalisée';
                            if (primaryBtn) primaryBtn.textContent = 'Oui, je veux !';
                            if (secondaryBtn) secondaryBtn.textContent = 'Non merci';
                            
                            observer.disconnect();
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, { childList: true });
    });
    </script>
    <?php
});
```

## Personnalisation du style

### Modifier les couleurs

Ajoutez ce CSS dans votre thème :

```css
/* Personnaliser le dégradé du bouton principal */
.custom-pwa-notification-button-primary {
    background: linear-gradient(135deg, #FF6B6B 0%, #FFE66D 100%) !important;
}

/* Personnaliser l'icône */
.custom-pwa-notification-icon {
    background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%) !important;
}

/* Personnaliser la couleur du thème */
.custom-pwa-notification-title {
    color: #2C3E50 !important;
}
```

## Désactiver la popup

Si vous préférez gérer vous-même la demande de permission, vous pouvez désactiver la popup :

```php
add_action('wp_enqueue_scripts', function() {
    wp_dequeue_script('custom-pwa-notification-popup');
    wp_dequeue_style('custom-pwa-notification-popup');
}, 20);
```

## Modifier le délai d'apparition

Par défaut, la popup apparaît après 2 secondes. Pour modifier ce délai, ajoutez ce code :

```javascript
// Dans votre fichier JavaScript personnalisé
document.addEventListener('DOMContentLoaded', function() {
    // Supprimer le comportement par défaut
    const originalScript = document.querySelector('script[src*="notification-popup.js"]');
    if (originalScript) {
        // Votre logique personnalisée ici
    }
});
```

## Modifier la durée de mémorisation du refus

La popup se souvient pendant 30 jours si l'utilisateur clique sur "Plus tard". Pour modifier cette durée, vous devrez modifier le fichier `assets/js/notification-popup.js` :

```javascript
// Ligne 14
const STORAGE_EXPIRY_DAYS = 7; // 7 jours au lieu de 30
```

## Classes CSS disponibles

- `.custom-pwa-notification-overlay` - Overlay de fond
- `.custom-pwa-notification-popup` - Container de la popup
- `.custom-pwa-notification-icon` - Icône de cloche
- `.custom-pwa-notification-title` - Titre
- `.custom-pwa-notification-description` - Description
- `.custom-pwa-notification-button-primary` - Bouton principal
- `.custom-pwa-notification-button-secondary` - Bouton secondaire
- `.custom-pwa-notification-message` - Zone de message
- `.custom-pwa-notification-close` - Bouton de fermeture

## Événements JavaScript

Vous pouvez écouter des événements personnalisés (à implémenter si besoin) :

```javascript
document.addEventListener('customPwaNotificationAllowed', function(e) {
    console.log('Utilisateur a accepté les notifications');
});

document.addEventListener('customPwaNotificationDismissed', function(e) {
    console.log('Utilisateur a reporté la décision');
});
```

## Compatibilité

- ✅ Chrome/Edge (Desktop & Mobile)
- ✅ Firefox (Desktop & Mobile)
- ✅ Safari (iOS 16.4+, macOS)
- ✅ Opera
- ⚠️ Safari iOS < 16.4 (Push API non supporté)

## Dépannage

### La popup n'apparaît pas

1. Vérifiez que les notifications push sont activées : **Custom PWA → Config → Enable Push Notifications**
2. Vérifiez que vous n'avez pas déjà accordé ou refusé les notifications
3. Ouvrez la console JavaScript pour voir les logs `[Custom PWA]`
4. Videz le cache du navigateur et le localStorage

### Réinitialiser l'état de la popup

Ouvrez la console du navigateur et exécutez :

```javascript
localStorage.removeItem('custom_pwa_notification_popup_dismissed');
location.reload();
```

## Support

Pour toute question ou personnalisation avancée, consultez la documentation complète du plugin.

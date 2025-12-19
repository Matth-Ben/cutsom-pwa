# Prérequis pour les Notifications Push

## ❌ mkcert N'EST PAS utilisé pour les Push

**mkcert** est un outil qui génère des certificats SSL/TLS **pour le développement local HTTPS**.

- ✅ Utile pour : Avoir `https://localhost` ou `https://labo.local` en dev
- ❌ PAS utilisé pour : L'envoi des notifications push
- ℹ️ Rôle : Seulement permettre aux Service Workers de fonctionner (ils nécessitent HTTPS)

## ✅ Ce qui est RÉELLEMENT utilisé : VAPID

### Web Push Protocol (RFC 8292)

Les notifications push Web utilisent le protocole **VAPID** (Voluntary Application Server Identification) :

```
┌─────────────────┐
│  Plugin WordPress│
│                 │
│  1. Génère clés │──── OpenSSL (extension PHP)
│     VAPID EC    │     Courbe: prime256v1 (P-256)
│     P-256       │
│                 │
│  2. Signe les   │──── JWT (JSON Web Token)
│     requêtes    │     Header: ES256 algorithm
│                 │
│  3. Envoie push │──── cURL ou wp_remote_post()
│     au navigateur│    HTTPS vers push service
└─────────────────┘
```

### Dépendances Système Requises

#### 1. **Extensions PHP** (✅ Toutes présentes)

| Extension | Usage | Statut |
|-----------|-------|--------|
| `openssl` | Génération des clés VAPID (EC P-256) | ✅ Installée |
| `curl` | Envoi des requêtes push aux navigateurs | ✅ Installée |
| `json` | Encodage des payloads de notification | ✅ Installée |
| `mbstring` | Manipulation des données binaires | ✅ Installée |

#### 2. **OpenSSL Capabilities** (✅ Tout supporté)

```php
// Le plugin utilise :
- openssl_pkey_new()       → Génération de paires de clés
- openssl_pkey_export()    → Export de la clé privée PEM
- openssl_pkey_get_details() → Extraction des coordonnées EC
- Courbe 'prime256v1'      → P-256 (65 bytes, uncompressed)
```

**Vérification** :
```bash
php -r "var_dump(in_array('prime256v1', openssl_get_curve_names()));"
# Résultat : bool(true) ✅
```

#### 3. **PHP Version** (✅ Compatible)

- **Requis** : PHP >= 8.0
- **Actuel** : PHP 8.4.11 ✅

#### 4. **HTTPS** (⚠️ Requis en production)

- **Pourquoi** : Les Service Workers ne fonctionnent qu'en HTTPS
- **Développement local** : 
  - ✅ Utiliser `mkcert` pour générer un certificat local
  - ✅ Ou certificat auto-signé
- **Production** :
  - ✅ Let's Encrypt (gratuit)
  - ✅ Certificat SSL/TLS commercial

**Note** : HTTPS est requis pour que le navigateur **enregistre** le Service Worker, mais pas pour l'**envoi** des notifications depuis le serveur.

## 🔐 Comment sont générées les clés VAPID ?

### Processus automatique lors de l'activation du plugin

```php
// Dans custom-pwa.php, méthode activate()
private function generate_vapid_keys() {
    // 1. Configuration de la courbe elliptique P-256
    $config = array(
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name'       => 'prime256v1',
    );
    
    // 2. Génération de la paire de clés
    $key_resource = openssl_pkey_new($config);
    
    // 3. Export de la clé privée (PEM format)
    openssl_pkey_export($key_resource, $private_key_pem);
    
    // 4. Extraction de la clé publique (raw EC point)
    $key_details = openssl_pkey_get_details($key_resource);
    $ec_key = $key_details['ec'];
    
    // 5. Construction de la clé publique uncompressed
    // Format: 0x04 + X (32 bytes) + Y (32 bytes) = 65 bytes
    $public_key_raw = "\x04" . $ec_key['x'] . $ec_key['y'];
    
    // 6. Encodage en base64url (standard Web Push)
    $public_key_base64url = base64url_encode($public_key_raw);
    $private_key_base64url = base64url_encode($private_key_pem);
    
    return array(
        'public_key'  => $public_key_base64url,
        'private_key' => $private_key_base64url,
    );
}
```

### Vérification des clés générées

```bash
# Vérifier que les clés existent
wp option get custom_pwa_push --format=json --allow-root

# Résultat attendu :
{
  "public_key": "BHSsbnKredB5f9LrRcIMiWIKAY75VTydzXxi6pyJUgyF...",
  "private_key": "LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JR0hBZ0VBT..."
}
```

## 📦 Qu'est-ce qui est installé automatiquement ?

### À l'activation du plugin :

1. ✅ **Clés VAPID** → Générées automatiquement (OpenSSL)
2. ✅ **Table database** → `wp_custom_pwa_subscriptions`
3. ✅ **Options WordPress** → `custom_pwa_push`, etc.
4. ✅ **Fichiers PWA** → `sw.js`, `offline.html` copiés à la racine
5. ✅ **Scénarios** → Configurés pour tous les post types

### Ce qui N'EST PAS installé :

- ❌ **mkcert** → Outil externe, pas nécessaire pour les push
- ❌ **Bibliothèque tierce** → Le plugin utilise OpenSSL natif PHP
- ❌ **Node.js ou npm** → Pas de dépendances JavaScript côté serveur
- ❌ **web-push library** → Implémentation custom en PHP

## 🔍 Vérification complète de l'environnement

Utilisez ce script pour vérifier tous les prérequis :

```bash
cd wp-content/plugins/custom-pwa
php /tmp/check_push_requirements.php
```

**Résultat attendu** :
```
✅ openssl - Génération des clés VAPID
✅ curl - Envoi des notifications push
✅ json - Encodage des payloads
✅ mbstring - Manipulation des données binaires
✅ openssl_pkey_new() - Génération de clés
✅ Courbe P-256 (prime256v1) - Supportée
✅ PHP 8.4.11 (requis: >= 8.0)
✅ Toutes les dépendances sont satisfaites!
```

## 🚀 Checklist Production

Avant de déployer en production, vérifiez :

- [ ] Extensions PHP : openssl, curl, json, mbstring
- [ ] PHP >= 8.0
- [ ] **HTTPS actif** (Let's Encrypt recommandé)
- [ ] Clés VAPID générées (vérifier `custom_pwa_push` option)
- [ ] Service Worker accessible à `https://votresite.com/sw.js`
- [ ] Manifest accessible à `https://votresite.com/manifest.webmanifest`
- [ ] Firewall autorise les connexions sortantes (push vers navigateurs)

## 📚 Ressources

- [Web Push Protocol (RFC 8292)](https://datatracker.ietf.org/doc/html/rfc8292)
- [VAPID Specification](https://datatracker.ietf.org/doc/html/draft-thomson-webpush-vapid-02)
- [Service Workers API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)

## ⚠️ Notes Importantes

1. **mkcert est SEULEMENT pour le dev local HTTPS** - Il n'a aucun rôle dans l'envoi des push
2. **Les clés VAPID sont générées par PHP/OpenSSL** - Aucune installation externe nécessaire
3. **HTTPS est requis pour les Service Workers** - Mais pas pour l'envoi serveur des push
4. **Toutes les dépendances sont standard** - Incluses dans PHP 8.0+ moderne

---

**Conclusion** : Le plugin est **100% autonome** et n'a besoin d'aucun outil externe (comme mkcert) pour envoyer des notifications push. Il utilise uniquement les extensions PHP standard (OpenSSL, cURL) qui sont présentes dans presque tous les environnements d'hébergement modernes.

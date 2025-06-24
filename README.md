# Mini ChatGPT

Un clone de ChatGPT développé avec Laravel et Vue.js, offrant une expérience de chat conversationnel complète avec support multi-conversations, partage et commandes personnalisées.

## 🚀 Fonctionnalités

### Chat Intelligent
- **Interface conversationnelle** similaire à ChatGPT
- **Support multi-modèles** (GPT-4, GPT-3.5-turbo, etc.)
- **Streaming en temps réel** des réponses IA
- **Rendu Markdown** avec coloration syntaxique pour le code

### Gestion des Conversations
- **Conversations multiples** avec navigation facile
- **Génération automatique de titres** basée sur le contenu
- **Partage de conversations** via liens uniques (UUID)
- **Historique persistant** des échanges

### Personnalisation Avancée
- **Instructions personnalisées** pour l'IA
- **Commandes personnalisées** (ex: `/meteo`, `/help`)
- **Préférences utilisateur** (modèle préféré, style de réponse)
- **Interface responsive** optimisée mobile/desktop

### Sécurité & Authentification
- **Laravel Jetstream** avec authentification 2FA
- **Gestion des sessions** sécurisée
- **Tokens d'API** pour l'accès programmatique
- **Validation CSRF** sur toutes les requêtes

## 🛠️ Stack Technique

### Backend
- **Laravel 11** - Framework PHP moderne
- **SQLite** - Base de données légère
- **Inertia.js** - Liaison frontend/backend
- **Laravel Sanctum** - Authentification API

### Frontend
- **Vue.js 3** (Composition API)
- **Tailwind CSS** - Framework CSS utilitaire
- **Vite** - Build tool moderne
- **Highlight.js** - Coloration syntaxique

### Services & Architecture
- **Services métier** : `ChatService`, `ConversationService`, `CustomCommandService`
- **Architecture MVC** avec séparation claire des responsabilités
- **Traits réutilisables** (`HasUuid` pour les identifiants uniques)

## 📁 Structure du Projet

```
├── app/
│   ├── Http/Controllers/          # Contrôleurs (Ask, Commands, Instructions)
│   ├── Models/                    # Modèles Eloquent (User, Conversation, Message)
│   ├── Services/                  # Logique métier
│   └── Traits/                    # Traits réutilisables
├── resources/
│   ├── js/Pages/Ask/             # Composants Vue du chat
│   └── views/                     # Templates Blade
├── database/
│   ├── migrations/               # Migrations de base de données
│   └── database.sqlite          # Base de données SQLite
└── tests/                        # Tests automatisés
```

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- NPM/Yarn

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone <repository-url>
cd mini-chatgpt
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances JavaScript**
```bash
npm install
```

4. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configuration de la base de données**
```bash
touch database/database.sqlite
php artisan migrate --seed
```

6. **Compilation des assets**
```bash
npm run dev
# ou pour la production
npm run build
```

7. **Lancer le serveur**
```bash
php artisan serve
```

## ⚙️ Configuration

### Variables d'environnement importantes
```env
# Base de données
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# API IA (à configurer selon votre provider)
OPENAI_API_KEY=your_api_key_here

# Application
APP_NAME="Mini ChatGPT"
APP_URL=http://localhost:8000
```

### Modèles IA supportés
- GPT-4
- GPT-3.5-turbo
- (Extensible via `ChatService`)

## 🧪 Tests

Le projet inclut une suite de tests complète :

```bash
# Lancer tous les tests
php artisan test

# Tests spécifiques
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Couverture de tests
- ✅ Tests d'authentification
- ✅ Tests de création/gestion des conversations
- ✅ Tests des services métier
- ✅ Tests d'intégration Inertia

## 📱 Fonctionnalités Détaillées

### Interface Chat
- **Sidebar responsive** avec menu hamburger mobile
- **Sélection de modèles** IA en temps réel
- **Indicateurs de chargement** et états de streaming
- **Gestion d'erreurs** avec affichage utilisateur

### Partage de Conversations
- **UUID uniques** pour chaque conversation
- **Pages publiques** pour les conversations partagées
- **Copie automatique** des liens de partage

### Commandes Personnalisées
- **Système extensible** de commandes slash
- **Service météo** intégré (exemple)
- **Interface de gestion** des commandes utilisateur

## 🔧 Développement

### Architecture des Services
- **`ChatService`** : Communication avec l'API IA
- **`ConversationService`** : Gestion CRUD des conversations
- **`CustomCommandService`** : Traitement des commandes personnalisées
- **`TitleGeneratorService`** : Génération automatique de titres

### Composants Vue Principaux
- **`Index.vue`** : Composant principal du chat
- **`Sidebar.vue`** : Navigation des conversations
- **`MessagesList.vue`** : Affichage des messages
- **`MessageInput.vue`** : Zone de saisie

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🤝 Contribution

Les contributions sont les bienvenues ! Merci de :
1. Fork le projet
2. Créer une branche feature
3. Commiter vos changements
4. Ouvrir une Pull Request

## 📞 Support

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur GitHub.

---

**Mini ChatGPT** - Une expérience de chat IA moderne et personnalisable 🚀

Sources
[1] arborescence.txt https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/69806952/e3742b27-5126-4f00-9bb7-119975cf4262/arborescence.txt

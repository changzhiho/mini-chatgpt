# Mini ChatGPT

A ChatGPT clone built with Laravel and Vue.js, offering a complete conversational chat experience with multi-conversation support, sharing, and custom commands.

## 🚀 Features

### Intelligent Chat
- **Conversational interface** similar to ChatGPT
- **Multi-model support** (GPT-4, GPT-3.5-turbo, etc.)
- **Real-time streaming** of AI responses
- **Markdown rendering** with syntax highlighting for code

### Conversation Management
- **Multiple conversations** with easy navigation
- **Automatic title generation** based on content
- **Conversation sharing** via unique links (UUID)
- **Persistent history** of exchanges

### Advanced Customization
- **Custom instructions** for AI
- **Custom commands** (e.g., `/weather`, `/help`)
- **User preferences** (preferred model, response style)
- **Responsive interface** optimized for mobile/desktop

### Security & Authentication
- **Laravel Jetstream** with 2FA authentication
- **Secure session management**
- **API tokens** for programmatic access
- **CSRF validation** on all requests

## 🛠️ Tech Stack

### Backend
- **Laravel 11** - Modern PHP framework
- **SQLite** - Lightweight database
- **Inertia.js** - Frontend/backend bridge
- **Laravel Sanctum** - API authentication

### Frontend
- **Vue.js 3** (Composition API)
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Modern build tool
- **Highlight.js** - Syntax highlighting

### Services & Architecture
- **Business services**: `ChatService`, `ConversationService`, `CustomCommandService`
- **MVC architecture** with clear separation of concerns
- **Reusable traits** (`HasUuid` for unique identifiers)

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/          # Controllers (Ask, Commands, Instructions)
│   ├── Models/                    # Eloquent Models (User, Conversation, Message)
│   ├── Services/                  # Business logic
│   └── Traits/                    # Reusable traits
├── resources/
│   ├── js/Pages/Ask/             # Vue chat components
│   └── views/                     # Blade templates
├── database/
│   ├── migrations/               # Database migrations
│   └── database.sqlite          # SQLite database
└── tests/                        # Automated tests
```

## 🚀 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- NPM/Yarn

### Installation Steps

1. **Clone the project**
```bash
git clone <repository-url>
cd mini-chatgpt
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install JavaScript dependencies**
```bash
npm install
```

4. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Database setup**
```bash
touch database/database.sqlite
php artisan migrate --seed
```

6. **Compile assets**
```bash
npm run dev
# or for production
npm run build
```

7. **Start the server**
```bash
php artisan serve
```

## ⚙️ Configuration

### Important environment variables
```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# AI API (configure according to your provider)
OPENAI_API_KEY=your_api_key_here

# Application
APP_NAME="Mini ChatGPT"
APP_URL=http://localhost:8000
```

### Supported AI models
- GPT-4
- GPT-3.5-turbo
- (Extensible via `ChatService`)

## 🧪 Testing

The project includes a comprehensive test suite:

```bash
# Run all tests
php artisan test

# Specific tests
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Test coverage
- ✅ Authentication tests
- ✅ Conversation creation/management tests
- ✅ Business service tests
- ✅ Inertia integration tests

## 📱 Detailed Features

### Chat Interface
- **Responsive sidebar** with mobile hamburger menu
- **Real-time AI model selection**
- **Loading indicators** and streaming states
- **Error handling** with user-friendly display

### Conversation Sharing
- **Unique UUIDs** for each conversation
- **Public pages** for shared conversations
- **Automatic copying** of share links

### Custom Commands
- **Extensible system** for slash commands
- **Integrated weather service** (example)
- **Management interface** for user commands

## 🔧 Development

### Service Architecture
- **`ChatService`**: AI API communication
- **`ConversationService`**: Conversation CRUD management
- **`CustomCommandService`**: Custom command processing
- **`TitleGeneratorService`**: Automatic title generation

### Main Vue Components
- **`Index.vue`**: Main chat component
- **`Sidebar.vue`**: Conversation navigation
- **`MessagesList.vue`**: Message display
- **`MessageInput.vue`**: Input area

## 📄 License

This project is licensed under the MIT License. See the `LICENSE` file for more details.

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the project
2. Create a feature branch
3. Commit your changes
4. Open a Pull Request

## 📞 Support

For any questions or issues, feel free to open an issue on GitHub.

---

**Mini ChatGPT** - A modern and customizable AI chat experience 🚀

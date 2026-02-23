# Polylang MCP Server

A WordPress plugin that lets AI assistants (Claude, Cursor, VS Code, etc.) translate your entire Polylang-powered website — posts, pages, custom post types, categories, tags, and strings.

## How it works

```
Your AI Assistant  ←→  MCP Protocol  ←→  This Plugin  ←→  Polylang
(Claude Desktop,                        (13 tools)      (languages,
 Cursor, etc.)                                           translations)
```

You connect your AI assistant to your WordPress site. The AI can then see all your content, understand what needs translating, and create translations directly — no manual copy-pasting or WP admin clicking required.

## Requirements

- WordPress 6.9+
- Polylang 3.7+ (free or Pro)
- PHP 8.0+

## Installation

1. Download or clone this repository into `wp-content/plugins/polylang-mcp/`
2. Activate the plugin in WP Admin → Plugins
3. Create an Application Password (see below)
4. Connect your AI assistant (see below)

No extra plugins or Composer commands needed — everything is bundled.

## Setup

### Step 1: Create an Application Password

1. Go to **WP Admin → Users → Profile**
2. Scroll to **Application Passwords**
3. Enter a name (e.g. "Claude Desktop") and click **Add New Application Password**
4. Copy the password — you'll need it in the next step

### Step 2: Connect your AI assistant

#### Claude Desktop

Open the config file:

- **Mac:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

Add this to the `mcpServers` section:

```json
{
  "mcpServers": {
    "polylang-mcp": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://yoursite.com/wp-json/polylang-mcp/mcp",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

Replace `yoursite.com`, `your-username`, and the password with your actual values.

Restart Claude Desktop. You should see 13 Polylang tools available.

#### Cursor / VS Code

Create `.cursor/mcp.json` or `.vscode/mcp.json` in your project:

```json
{
  "mcpServers": {
    "polylang-mcp": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://yoursite.com/wp-json/polylang-mcp/mcp",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

#### Local development (WP-CLI / STDIO)

If you run WordPress locally, you can skip the HTTP transport entirely:

```json
{
  "mcpServers": {
    "polylang-mcp": {
      "command": "wp",
      "args": [
        "--path=/path/to/wordpress",
        "mcp-adapter", "serve",
        "--server=polylang-mcp-server",
        "--user=admin"
      ]
    }
  }
}
```

## What can it do?

Once connected, your AI assistant has 13 tools:

### Understand the site
| Tool | What it does |
|------|-------------|
| `get-site-info` | Full site overview — WordPress version, theme, plugins, languages, content types |
| `get-translation-status` | Translation progress per content type and language |
| `list-languages` | All configured languages with details |
| `list-content-types` | Translatable post types and taxonomies |

### Find content to translate
| Tool | What it does |
|------|-------------|
| `get-untranslated-content` | Lists content missing translations for a target language |
| `get-content` | Full post content (title, body, excerpt, meta, categories) |
| `get-term` | Term details (name, description, taxonomy, parent) |
| `get-string-groups` | Registered string groups with translation status |

### Translate
| Tool | What it does |
|------|-------------|
| `translate-post` | Create or update a translated post (idempotent) |
| `translate-term` | Create or update a translated term (idempotent) |
| `translate-string` | Set a string translation |

### Manage languages
| Tool | What it does |
|------|-------------|
| `create-language` | Add a new language (only locale required) |
| `delete-language` | Remove a language |

## Example workflows

### "Translate my site to French"

Just tell your AI assistant:

> Translate all untranslated posts and pages to French

The AI will:
1. Call `get-untranslated-content` to find what's missing
2. Call `get-content` for each post to get the source text
3. Translate the content
4. Call `translate-post` to save each translation

### "What's my translation progress?"

> Show me the translation status for all languages

The AI calls `get-translation-status` and gives you a summary of translated vs untranslated content per language.

### "Add Japanese to my site"

> Add Japanese as a language and translate the 5 most recent posts

The AI calls `create-language` with locale `ja`, then translates the posts.

## Technical details

### Architecture

The plugin uses WordPress 6.9's [Abilities API](https://make.wordpress.org/core/) to register 13 abilities, and the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) to expose them as MCP tools. The adapter is bundled — no extra installation needed.

### Polylang API

Translations are created using Polylang's official API functions:
- `pll_insert_post()` / `pll_update_post()` for posts
- `pll_insert_term()` / `pll_update_term()` for terms
- `PLL_MO` for string translations

All write operations are **idempotent** — calling translate-post twice for the same source and target language updates the existing translation rather than creating a duplicate.

### Permissions

- Read-only tools: require `edit_posts` or `read` capability
- Write tools: require `manage_options` capability (administrator)

Authentication uses WordPress Application Passwords over HTTPS.

### Plugin structure

```
polylang-mcp/
├── polylang-mcp.php              # Plugin bootstrap
├── composer.json
├── src/
│   ├── Plugin.php                # Hook registration, MCP server setup
│   ├── Abilities/
│   │   ├── AbstractAbility.php   # Base class
│   │   ├── Environment/          # get-site-info, get-translation-status
│   │   ├── Languages/            # list, create, delete languages
│   │   ├── Content/              # list types, get content/terms, find untranslated
│   │   └── Translation/          # translate posts, terms, strings
│   └── Services/
│       ├── LanguageService.php   # Polylang language API wrapper
│       ├── ContentService.php    # Content queries
│       ├── TranslationService.php # Post/term translation
│       └── StringService.php     # String translation via PLL_MO
└── vendor/                       # Bundled dependencies (MCP adapter)
```

## License

GPL-2.0-or-later

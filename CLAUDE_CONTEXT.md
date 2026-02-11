# Newsmast Curator - Contexto de Desenvolvimento

## Resumo do Projeto
Plugin WordPress para coletar conteúdo de múltiplas fontes (Plone CMS, WordPress, Tainacan, Visite Museus), permitir curadoria administrativa e publicar automaticamente no Mastodon/Newsmast.

## Status Atual
- **Fase**: Interface administrativa funcional, conectores e serviços implementados
- **Layout**: Moderno com sidebar, tons pastéis, cards, modais, tabelas responsivas
- **API REST**: Funcional com 6 controllers (Sources, Items, Publications, Settings, Logs, Base)
- **Erros corrigidos**: URL duplicada, check_permissions protegido, script loading order

## Arquitetura

### Padrões Utilizados
- **Repository Pattern** para acesso a dados
- **Strategy Pattern** para conectores (Connector_Interface)
- **Singleton Pattern** para Plugin principal
- **MVC** com views PHP e REST API
- **Namespaces PSR-4**: `NewsmastCurator\Core`, `NewsmastCurator\Models`, etc.

### Banco de Dados (4 tabelas)
- `wp_nc_sources` - Fontes de conteúdo cadastradas
- `wp_nc_items` - Itens coletados das fontes
- `wp_nc_publications` - Fila de publicações para Mastodon
- `wp_nc_logs` - Logs de operações do sistema

### Estrutura de Arquivos
```
newsmast-curator/
├── newsmast-curator.php          # Entry point, constantes, autoloader PSR-4
├── uninstall.php                 # Limpeza ao desinstalar
├── README.md                     # Documentação
├── CLAUDE_CONTEXT.md             # Este arquivo
├── assets/
│   ├── css/admin.css             # CSS completo com variáveis, sidebar, modais, etc.
│   └── js/admin.js               # JS global: NC object, modais, notices, tabs
├── includes/
│   ├── Core/
│   │   ├── Plugin.php            # Singleton principal, init de tudo
│   │   ├── Database.php          # Criação/upgrade de tabelas (dbDelta)
│   │   ├── Activator.php         # Ativação: tabelas, options, capabilities, cron
│   │   └── Deactivator.php       # Desativação: limpa cron jobs
│   ├── Models/
│   │   ├── Source.php            # Entity de fonte com validação
│   │   ├── Item.php              # Entity de item com hash MD5
│   │   └── Publication.php       # Entity de publicação com retry
│   ├── Repositories/
│   │   ├── Base_Repository.php   # CRUD abstrato com $wpdb
│   │   ├── Source_Repository.php
│   │   ├── Item_Repository.php   # find_uncurated, mark_as_curated, get_curated_stats
│   │   └── Publication_Repository.php
│   ├── Services/
│   │   ├── Collection_Service.php  # Coordena coleta de todas as fontes
│   │   ├── Scheduler_Service.php   # Processa fila com job locks
│   │   ├── Mastodon_Service.php    # API Mastodon (post, upload media)
│   │   └── Logger_Service.php      # Sistema de logs centralizado
│   ├── Connectors/
│   │   ├── Connector_Interface.php # Interface: connect(), collect(), test()
│   │   ├── Connector_Registry.php  # Registry de conectores por tipo
│   │   ├── Plone_Connector.php     # Scraping HTML com CSS selectors
│   │   ├── WordPress_Connector.php # REST API wp/v2/posts
│   │   ├── Tainacan_Connector.php  # API Tainacan por collection_id
│   │   └── Visite_Museus_Connector.php # API visite.museus.gov.br
│   ├── API/
│   │   ├── Base_REST_Controller.php # Namespace newsmast-curator/v1
│   │   ├── Sources_Controller.php   # CRUD + collect
│   │   ├── Items_Controller.php     # Lista, curate, stats
│   │   ├── Publications_Controller.php # Agendar, publicar
│   │   ├── Settings_Controller.php  # Config Mastodon, templates
│   │   └── Logs_Controller.php      # Listagem e limpeza
│   └── Admin/
│       ├── Admin_Controller.php     # Menu, sidebar, render_page, enqueue
│       └── views/
│           ├── dashboard.php        # Stats cards, atividade recente
│           ├── sources.php          # CRUD fontes com instruções detalhadas
│           ├── curation.php         # Grid de itens, seleção múltipla
│           ├── queue.php            # Tabs agendadas/publicadas/falhas
│           ├── settings.php         # Config Mastodon, template, hashtags
│           └── system.php           # Info sistema, cron, logs
```

## Configurações Importantes

### wp_localize_script (ncData)
```php
'apiUrl' => '/newsmast-curator/v1'  // Caminho relativo (NÃO usar rest_url()!)
'nonce' => wp_create_nonce('wp_rest')
```

### Script Loading
```php
wp_enqueue_script('nc-admin', ..., ['jquery', 'wp-api-fetch'], $version, false);
// false = carrega no HEADER (obrigatório pois views têm scripts inline)
```

### REST API
- Namespace: `newsmast-curator/v1`
- Todas as rotas usam `check_permissions()` que DEVE ser **public** (não protected)
- Views usam `wp.apiFetch({path: ncData.apiUrl + '/endpoint'})`

### Capabilities WordPress
- `manage_nc_sources` - Gerenciar fontes
- `manage_nc_items` - Curadoria (principal capability)
- `manage_nc_publications` - Agendamento
- `manage_nc_settings` - Configurações (admin only)
- `view_nc_logs` - Ver logs

### Cron Jobs
- `nc_collect_sources` - Coleta periódica (hourly)
- `nc_process_publications` - Processar fila (every_5_minutes)
- `nc_cleanup_logs` - Limpeza de logs (daily)

## Bugs Conhecidos e Resolvidos

1. **URL duplicada** - `rest_url()` retorna URL completa, wp.apiFetch adiciona prefixo novamente
   - **Fix**: Usar caminho relativo `/newsmast-curator/v1` no wp_localize_script

2. **check_permissions protected** - WordPress REST API não consegue chamar métodos protected
   - **Fix**: Mudar para `public function check_permissions()`

3. **Script no footer** - admin.js carregava no footer, scripts inline das views executavam antes
   - **Fix**: Mudar último parâmetro de wp_enqueue_script para `false` (header)

## O Que Falta Implementar/Testar

### Prioridade Alta
- [ ] Testar coleta real de cada conector (Plone, WordPress, Tainacan, Visite Museus)
- [ ] Testar integração com API Mastodon (post + upload media)
- [ ] Verificar se cron jobs estão executando corretamente
- [ ] Página de Sistema/Logs precisa ser revisada
- [ ] Publicação manual e agendada (fluxo completo)

### Prioridade Média
- [ ] Edição de fonte existente (botão editar na tabela)
- [ ] Preview de post Mastodon antes de publicar
- [ ] Configurações avançadas (aba coleta, publicação)
- [ ] Download de imagens localmente
- [ ] Internacionalização completa (arquivos .po/.mo)

### Prioridade Baixa
- [ ] Exportação de logs em CSV
- [ ] Calendário visual de publicações
- [ ] Estatísticas avançadas no Dashboard
- [ ] Botões de ajuda [?] em todas as páginas (feito apenas em Fontes)
- [ ] Testes automatizados

## Ambiente de Desenvolvimento
- **WordPress**: 6.9.1
- **PHP**: 8.2 (XAMPP)
- **Path**: C:\xampp82\htdocs\wordpress\wp-content\plugins\newsmast-curator
- **GitHub**: https://github.com/marcossigismundo/newsmast-curator

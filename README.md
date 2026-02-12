# Newsmast Curator

Plugin WordPress para curadoria de conteúdo de múltiplas fontes e publicação automatizada no Newsmast/Mastodon.

## Funcionalidades

- **Coleta de múltiplas fontes**: Plone CMS, WordPress, Tainacan
- **Curadoria intuitiva**: Interface para aprovar/rejeitar itens coletados
- **Publicação automatizada**: Agendamento e publicação no Mastodon/Newsmast
- **Sistema de logs**: Rastreamento completo de todas as operações
- **REST API**: Endpoints para integração e automação

## Instalação

1. Faça upload da pasta `newsmast-curator` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure as credenciais do Mastodon em Newsmast > Configurações

## Requisitos

- WordPress 5.8 ou superior
- PHP 7.4 ou superior
- Extensões PHP: json, mbstring, curl

## Uso

### Adicionar Fonte

1. Acesse Newsmast > Fontes
2. Clique em "Nova Fonte"
3. Preencha URL e configurações do conector
4. Teste a conexão

### Curadoria

1. Acesse Newsmast > Curadoria
2. Revise os itens coletados
3. Aprove os que deseja publicar

### Agendar Publicação

1. Acesse Newsmast > Fila
2. Selecione item aprovado
3. Defina data/hora e edite o texto
4. Clique em "Agendar"

## Estrutura do Projeto

```
newsmast-curator/
├── includes/
│   ├── Core/           # Classes principais
│   ├── Models/         # Modelos de dados
│   ├── Repositories/   # Acesso a banco
│   ├── Services/       # Lógica de negócio
│   ├── Connectors/     # Integrações
│   ├── API/           # REST API
│   └── Admin/          # Interface admin
├── assets/             # CSS e JS
└── languages/          # Traduções
```

## Licença

GPL v2 ou posterior

## Autor

IBRAM - Instituto Brasileiro de Museus

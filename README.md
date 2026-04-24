<p align="center"><img src="public/icons/io-icon-128x128.png" alt="Indigos Organizer" width="128"></p>

# Indigos Organizer

Indigos Organizer e um sistema web para organizacao operacional e criativa de artistas e bandas.
O projeto cobre o ciclo completo de trabalho: ideias, planejamento, producao de conteudos,
tarefas, eventos, contatos, locais, informacoes uteis e configuracoes do ecossistema.

---

> *"Este sistema foi desenvolvido para resolver um problema REAL de organizacao da minha propria banda [Era Indigos](https://indigos.com.br). Cansado de perder tempo cacando documentos e midia perdidos em grupos de WhatsApp, da ineficiencia do Sheets e aplicativos de organizacao que nao se enquadravam no que a banda necessitava de forma eficiente, tomei a decisao de criar este aplicativo mais sob medida.*
>
> *Entendendo que pode ajudar muitas bandas e outros artistas, deixo o codigo aberto para uso nao comercial. Claro que e necessario servidor e conhecimentos tecnicos para colocar o sistema no ar. Se nao tiver um programador que possa realizar a instalacao, pode entrar em contato comigo ;)"*
>
> — **Renato Barba**, autor

---

## Visao Geral do Produto

O sistema foi desenhado para centralizar o dia a dia de equipes criativas com foco em:

- Organizacao por modulos especializados e integrados.
- Fluxos visuais (lista, kanban, calendario, programacao semanal e graficos).
- Colaboracao entre usuarios com controle de permissoes.
- Notificacoes multicanal e personalizaveis.
- Operacao local e em servidor (Sail/Docker + PostgreSQL).

## Modulos e Funcionalidades

### Dashboard
- Visao consolidada da semana (tarefas, conteudos e eventos).
- Tabelas de proximos itens operacionais.
- Indicadores para priorizacao rapida.

### Tarefas
- Cadastro com multiplos responsaveis.
- Lista, kanban, calendario completo e programacao semanal.
- Subtarefas e relacionamentos com conteudos, eventos e planejamentos.
- Status dinamicos e graficos de acompanhamento.

### Calendario Geral
- Agenda unificada de tarefas, conteudos e eventos.
- Visualizacao mensal com cores por modulo/status.

### Ideias
- Pipeline de ideias por status.
- Votacao e fluxo para levar ideia ao quadro.
- Colaboradores por ideia e regras de privacidade.

### Conteudos
- Pipeline de conteudo (fila, producao, publicado etc.).
- Categorias/estilos/plataformas com suporte multiplo.
- Programacao semanal e calendario completo.
- Graficos por tipo, categoria, estilo, status e plataforma.

### Planejamentos
- Planos com fases, previsoes e progresso.
- Relacionamento com tarefas e conteudos.
- Confirmacoes para conclusao de fases com pendencias.

### Notas Rapidas
- Blocos compartilhados com itens de checklist.
- Cards com resumo, prioridade e arquivamento.

### Eventos
- Agenda de eventos com presenca (participante/audiencia).
- Filtros rapidos e avancados.
- Modo lista e calendario.

### Locais
- Cadastro completo de venues com estilos multiplos.
- Geolocalizacao e mapa.
- Integracao com Google Maps para autocomplete/endereco.
- Avaliacao, historico de apresentacoes e contatos.

### Contatos
- Base de contatos relacionada a locais.
- Busca rapida e filtros dedicados.
- Link direto para WhatsApp.

### Informacoes Uteis
- Repositorio interno de conhecimento.
- Itens com arquivos, links e categorias.

### Usuarios e Perfil
- Gestao de usuarios, papeis admin/super-admin.
- Perfil com avatar (URL/upload), senha em modal e preferencias.
- Preferencias de notificacao por tipo e canal.

### Configuracoes
- Tipos, categorias, estilos, plataformas e status.
- Configuracoes de sistema e integracoes.
- Ajustes visuais e taxonomias globais.

### Tutorial
- Modulo de ajuda com documentacao por modulo em Markdown.
- Modal rapido por pagina via HelpTrigger.

## Decisoes de Arquitetura e Engenharia

- Backend em Laravel 13 com Inertia para aplicacao SPA server-driven.
- Frontend em Vue 3 com PrimeVue e Tailwind CSS.
- Modelagem orientada a dominios (controllers, requests, policies, observers, jobs e notifications).
- PostgreSQL como banco principal, com migracoes e seeders para ambiente local/demo.
- Fila e notificacoes para eventos assincronos (tarefas e ideias).
- Integracoes externas encapsuladas em servicos dedicados.
- Uso de componentes reutilizaveis para padrao visual e consistencia de UX.

## Stack Tecnica

### Backend
- PHP 8.3
- Laravel 13
- Inertia Laravel
- Eloquent ORM
- Policies, Observers, Jobs, Notifications

### Frontend
- Vue 3
- PrimeVue
- Tailwind CSS
- Vite
- FullCalendar
- Iconify

### Infra e Dados
- Docker / Laravel Sail
- PostgreSQL
- Redis
- Mailpit

### Integracoes e Recursos
- Google Maps API (autocomplete/geocodificacao/mapa)
- Login Social (Laravel Socialite)
- Web Push Notifications
- WhatsApp via Evolution API
- Dropbox e Google Drive (armazenamento em nuvem)
- Assistencia de voz/transcricao no frontend (componentes de apoio de speech-to-text)

## Execucao Local (Sail)

1. Suba os containers:

```bash
./vendor/bin/sail up -d
```

2. Rode migracoes e seed:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

3. Rode frontend em desenvolvimento:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

4. Build de producao:

```bash
./vendor/bin/sail npm run build
```

## ⚖️ Licenca e Termos de Uso

Este projeto e distribuido sob a licenca **Polyform Noncommercial 1.0.0**.

### ✅ O que e permitido:
- Uso pessoal e domestico.
- Rodar o sistema em sua propria infraestrutura (Local ou VPS) para uso proprio.
- Modificar o codigo para melhorias pessoais.

### ❌ O que e estritamente PROIBIDO:
- **Comercializacao:** Voce nao pode vender este software ou versoes derivadas.
- **Servicos Pagos:** E proibido cobrar de terceiros para instalar, configurar ou dar manutencao neste sistema.
- **Hospedagem Comercial (SaaS):** Voce nao pode oferecer este sistema como um servico pago para outros usuarios.

*Para qualquer uso que gere lucro ou compensacao financeira, entre em contato com o autor para obter uma licenca comercial.*

### Texto oficial da licenca

- URL oficial: https://polyformproject.org/licenses/noncommercial/1.0.0
- Arquivo local: `LICENSE`

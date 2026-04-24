# Configurações

## Visão Geral

O módulo de **Configurações** permite personalizar as listas de tipos, categorias, estilos, status e plataformas usados em todo o sistema. Apenas administradores têm acesso às ações de criar, editar e excluir nas tabelas de configuração.

---

## Subseções de Configuração

### Tipos
Gerencie os tipos de ideias/conteúdos, tipos de locais e tipos de eventos. Cada tipo pode ter um **ícone** (formato Iconify, ex: `mdi:music`) e uma **cor**.

### Categorias
Gerencie as categorias de ideias/conteúdos, categorias de locais e categorias de informações úteis.

### Estilos
Gerencie os estilos musicais utilizados em conteúdos, locais e ideias. Os estilos são separados por domínio (conteúdos / locais).

### Plataformas de Conteúdo
Cadastre as plataformas onde os conteúdos são publicados (YouTube, Instagram, Spotify, etc). Cada plataforma pode ter um ícone e cor próprios.

### Status de Tarefas
Gerencie os status do fluxo de trabalho das tarefas. É possível criar, editar, reordenar e excluir status.

> **Atenção:** Os status **Pendente**, **Em execução** e **Concluído** são referências do sistema e **não podem ser excluídos**.

### Configurações do Sistema
Configurações globais como integrações (Evolution API para WhatsApp), opções avançadas e outras configurações de administrador.

---

## Regras de Negócio

- Somente **administradores** podem criar, editar ou excluir configurações. Usuários comuns apenas visualizam.
- Os campos de cor usam o **ColorPicker** — o valor é salvo sem o `#` (ex: `ff6600`), que é adicionado automaticamente pelo sistema.
- Ícones devem estar no formato Iconify (ex: `mdi:account`, `ph:music-note-bold`).

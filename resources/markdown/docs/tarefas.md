# Tarefas

## Visão Geral

O módulo de **Tarefas** é o centro de gerenciamento de atividades do grupo. Aqui você cria, acompanha e organiza todas as tarefas, desde composição de músicas até obrigações administrativas. As tarefas podem ser atribuídas a um ou mais membros do grupo, agendadas para uma data específica e vinculadas a prazos (deadlines).

---

## Funcionalidades

### Como cadastrar uma tarefa
1. Na página de Tarefas, clique no botão **Nova Tarefa**.
2. Preencha o **Título** da tarefa.
3. Selecione o **Status** inicial (ex: "Pendente", "Em execução").
4. Defina a **Prioridade**: Baixa, Média, Alta ou Urgente.
5. Atribua um ou mais **Responsáveis** (membros do grupo).
6. Opcionalmente, preencha:
   - **Data de agendamento** — quando a tarefa será executada.
   - **Prazo (deadline)** — data limite para conclusão.
   - **Relacionamento** — vincule a tarefa a um Conteúdo, Evento ou Planejamento.
   - **Subtarefas** — divida a tarefa em partes menores.
7. Clique em **Salvar**.

### Como visualizar tarefas
O módulo oferece quatro modos de visualização:
- **Lista** — tabela com todas as tarefas, filtros e paginação.
- **Kanban** — colunas por status, com drag-and-drop para mover tarefas entre status.
- **Programação da Semana** — cards organizados por dia da semana (domingo ao próximo domingo).
- **Calendário Completo** — visão mensal com tarefas agendadas e deadlines.
- **Gráficos** — análise visual de status e prioridades.

### Como filtrar tarefas
Use a barra de filtros para encontrar tarefas por:
- **Responsável** — filtre por um membro específico.
- **Prioridade** — Baixa, Média, Alta ou Urgente.
- **Tipo de relacionamento** — conteúdo, evento, planejamento ou sem relacionamento.
- **Busca rápida** — pesquise pelo título.
- **Apenas atrasadas** — exibe somente tarefas com prazo vencido.
- **Apenas agendadas** — exibe somente tarefas com data de agendamento.
- **Incluir arquivadas** — exibe tarefas arquivadas.

### Como mover uma tarefa no Kanban
No modo Kanban, arraste o card da tarefa da coluna atual para a coluna do novo status. A alteração é salva automaticamente.

### Como concluir uma tarefa
- Na lista, clique no ícone de conclusão ao lado da tarefa.
- Na tela de detalhes (modal ou página), clique no botão **Marcar como Concluída**.
- Um diálogo de confirmação será exibido antes de finalizar.

### Subtarefas
Dentro de uma tarefa, você pode adicionar **subtarefas** para dividir o trabalho em etapas. Cada subtarefa pode ser marcada como concluída individualmente.

---

## Regras de Negócio

- Uma tarefa pode ter **múltiplos responsáveis** (incluindo todos os membros ou nenhum).
- O **deadline** no calendário somente aparece para tarefas vencidas ou sem data de agendamento, evitando duplicação visual.
- Status de referência do sistema (**Pendente**, **Em execução**, **Concluído**) não podem ser excluídos.
- Tarefas arquivadas ficam ocultas por padrão, mas podem ser visualizadas com o filtro correspondente.
- Ao atribuir novos responsáveis durante a edição, eles recebem uma notificação automática.

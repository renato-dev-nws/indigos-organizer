# Planejamentos

## Visão Geral

O módulo de **Planejamentos** permite organizar projetos maiores do grupo em fases e etapas claras, como um álbum, uma turnê ou uma campanha de lançamento. Cada planejamento agrupa tarefas e conteúdos relacionados, oferecendo uma visão macro do progresso.

---

## Funcionalidades

### Como criar um planejamento
1. Clique em **Novo Planejamento**.
2. Preencha o **Título** e a **Descrição**.
3. Defina as **datas de início e término** (opcional — podem ser preenchidas automaticamente pelas fases).
4. Selecione o **Status** (Fila, Em Andamento, Concluído, Pausado).
5. Adicione **Fases** ao planejamento:
   - Cada fase tem **Título**, **Descrição**, **Previsão de Início** e **Previsão Final**.
   - As fases são ordenadas pela posição na lista (arraste para reordenar).
6. Clique em **Salvar**.

### Datas automáticas
- Se a **data de início** do planejamento não estiver preenchida, ela assume a **previsão de início da primeira fase**.
- Se a **data final** não estiver preenchida, ela assume a **previsão final da última fase**.
- Quando a previsão da fase for anterior/posterior às datas do planejamento, as datas do planejamento são ajustadas automaticamente.

### Como visualizar o progresso
Na página de visualização do planejamento, você vê:
- O resumo geral (datas, status, total de tarefas e conteúdos relacionados).
- As **fases** com suas tarefas vinculadas.
- Progresso de conclusão de cada fase.

### Como concluir uma fase
Na visualização do planejamento, marque a fase como concluída. Caso existam:
- **Fases anteriores não concluídas** — um diálogo de confirmação será exibido.
- **Tarefas não concluídas** na fase — um aviso de confirmação também aparece.

### Reordenar fases
Use os botões de seta (↑ / ↓) ao lado de cada fase para alterar a ordem.

### Relacionamentos
- A lista de planejamentos exibe o total de **Tarefas Relacionadas** e **Conteúdos Relacionados**.
- Ao excluir uma fase que possui relacionamento com ideias, o relacionamento é removido automaticamente.

---

## Regras de Negócio

- Não é possível excluir uma fase que tenha **tarefas relacionadas**. Remova os relacionamentos antes de excluir.
- A ordem das fases determina a sequência do planejamento — não existe campo de ordem manual.

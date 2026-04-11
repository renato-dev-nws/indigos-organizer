# REFATORAÇÕES DO PROJETO

## CRIAÇÃO MÓDULO DE PLANOS

- O módulo de planos deve ter os campos Título, Descrição, Inicio, Fim, Progresso, Status (na fila, em execução, cancelado, concluído), Fases e Tarefas relacionadas às fases!

- Tarefas poderão ser relacionadas a Planos ou a Fase de Plano. O usuário pode incluir tarefa relacionada ao plano ou à fase de um plano. Quando uma tarefa for cadastrada diretamente na página de tarefas, o usuário poderá relacionar a tarefa a um plano. Se selecionada a fase do plano selecionado, a tarefa é vinculada à fase. (Explicações sobre alterações em tarefas na seção MODIFICAÇÕES deste documento).

- Faça o seed deste módulo simulando a gravação de um álbum com as fases: captação, mixagem, masterização e lançamento. Cada uma delas com suas tarefas. Crie esse seed de forma inteligente.

## CRIAÇÃO DE MÓDULO DE INFORMAÇÕES COMPARTILHADAS

Este módulo tem a finalidade de compartilhar informações em geral entre os usuários.

- O usuário coloca o título e a descrição da informação, podendo adicionar links (repeater) com titulo, url e descrição. Poderá também anexar documentos.

## MODIFICAÇÕES

- As tarefas devem ser atribuídas a usuários. Caso não seja atribuída a um usuário, será atribuída a TODOS
- As tarefas são centrais no sistema e devem ter um campo "related" (relacionado à). Podem ser relacionadas a conteúdo, plano, fase de plano ou administrativa. Fluxo:
    1. O usuário terá um campo (1º campo) de seleção para selecionar entre: Conteúdo, Plano e Administrativo;
    2. Seleções:
        - Caso selecione Plano no "Relacionada a", Um campo para selecionar o plano aparece (planos ativos -  na fila ou em execução). Ao selecionar o plano, o campo de fase aparece (com as fases do plano). Caso não selecione nenhuma fase, fica relacionado ao plano. Caso selecione, fica relacionado à fase;
        - Caso selecione Conteúdo no "Relacionada a", um campo para selecionar o conteúdo aparece (conteúdos ativos - na fila e em produção)
        - Caso selecione Administrativo não vincula a tarefa a nada e nenhum campo aparece.
    3. Os campos seguem nesta ordem: Título, Descrição, Responsável (deve vincular a um usuário ou TODOS) Prioridade, Prazo e  Status

- Modificações mais profundas no módulo de ideias. Deve existir um campo "related" (relacionado a). Uma nova ideia poderá ser relacionada a Conteúdo, a Planejamento, a fase de planejamento ou administrativa. Fluxo similar ao de tarefas:
    1. O usuário terá um campo (1º campo) de seleção para selecionar entre: Conteúdo, Plano, Conteúdo existente, Plano existente, Administrativa ou Nada;
    2. Seleções:
        - Caso selecione Plano Existente no "Relacionada a", Um campo para selecionar o plano aparece (planos ativos -  na fila ou em execução). Ao selecionar o plano, o campo de fase aparece (com as fases do plano). Caso não selecione nenhuma fase, fica relacionado ao plano. Caso selecione, fica relacionado à fase. Não é obrigatório selecionar o plano
        - Caso selecione Conteúdo Existente no "Relacionada a", um campo para selecionar o conteúdo aparece (conteúdos ativos - na fila e em produção)
        - Caso selecione Conteúdo, Plano, Administrativa ou Nada não vincula a ideia a nada e nenhum campo aparece.
- As ideias vão ter os seguintes status:
    - Na gaveta (guardada pra futura analise)
    - Na mesa (avaliada e pronta pra ser executada)
    - No quadro (este é um status que indica que precisa que os demais usuários vejam a ideia). Deve aparecer no dashboard dos usuários para votação (a explicar abaixo neste documento)
    - Em execução (atinge este status quando o conteúdo relacionado estiver em produção ou o plano ou fase de plano estiver em execução).
    - Executada (quando a ideia foi executada - Atige automaticamente este status quando o conteúdo relacionado estiver como publicado ou quando o plano relacionado ou fase relacionada é concluída)
    - No lixo (equivalente a descartada)

- Quando a ideia estiver com o status de "No quadro", os usuários poderão visualizar (se editar) e "votar" selecionando colocar "No lixo" (descarte) ou "Na mesa" (pra ser executado em breve) ou "Na gaveta" (pra guardar).
- No form de ideia, quando for selecionado o status "No quadro" um campo para selecionar os usuários que podem votar na ideia aparece. Somente para estes usuários a ideia aparcerá no dashboard para voto. Caso não selecione nenhum usuário, todos os usuários poderão votar.
- No form de ideia, quando selecionado o status "Na gaveta", um toggle deve aparecer para ativar a ideia como privada. Apenas o status "na gaveta" poderá ter a ideia como privada e os demais usuários não podem ver.
- Os status das ideias aparecem em portugues, mas são gravados como "in_drawer","on_table","on_board","executing","executed" e "trash"

- Em mobile os módulos de conteúdos, ideias e planos (a ser desenvolvido conforme descrito acima neste documento) devem ser exibidos em tabela somente na versão desktop. Na versão mobile, substitua as tabelas por cards com as informações devidamente formatados para exibir os mesmos dados da tabela com as actions no canto inferior direito.
- O logotipo e ícone das configurações do sistema podem ser SVG também. Caso seja SVG, mude o tag "<img>"
- A seleção de plataforma do form de conteúdo deve ser múltipla como categoria.

## CORREÇÕES E OUTRAS TAREFAS

- No tema dark na página de login, quando entra nos campos eles ficam brancos e a letra clara. O fundo dos campos não podem ser claros no login. Devem seguir o padrão do projeto
- Incluir 4 usuários no seed de usuários, simulando uma banda de 4 pessoas. Tire o demo@band.com
- No seed de tipos e categorias, altere para:
    - Tipos:
        - Video
        - Reel
        - Story
        - Post
        - Produção musical
        - Identidade visual

    - Categorias:
        - Divulgação
        - Marketing
        - Informativo
        - Série
        - Humor
        - História
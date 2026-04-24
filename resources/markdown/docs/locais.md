# Locais

## Visão Geral

O módulo de **Locais** é o catálogo de lugares onde o grupo se apresenta ou pretende se apresentar: bares, teatros, casas de show, estúdios e muito mais. Registre informações de contato, endereço, avaliação e histórico de apresentações.

---

## Funcionalidades

### Como cadastrar um local
1. Clique em **Novo Local**.
2. Preencha o **Nome** do local.
3. Selecione o **Tipo** e a **Categoria**.
4. Escolha os **Estilos** musicais aceitos no local (múltiplos).
5. Preencha o **Endereço**:
   - **Com a API do Google Maps** configurada: use o campo de autocomplete. O endereço é preenchido automaticamente com logradouro, número, bairro, cidade, UF, CEP, país, latitude e longitude. Adicione o **Complemento** manualmente.
   - **Sem a API do Google Maps** (ou modo manual): preencha os campos manualmente. O mapa embed aparece ao informar os campos necessários.
6. Informe os dados de **contato** (telefone, WhatsApp, e-mail).
7. Avalie o local com **estrelas** (1 a 5).
8. Informe quantas vezes o grupo **se apresentou** no local.
9. Adicione **equipamentos disponíveis** no local.
10. Clique em **Salvar**.

### Como visualizar locais
- **Lista** — tabela com filtros, avaliação em estrelas, link WhatsApp, estilos por ícone e cidade/UF.
- **Mapa** — visualize todos os locais cadastrados no mapa interativo. No mobile, use a busca por nome para localizar um local no mapa.
- **Gráficos** — análise estatística dos locais.

### Mapa interativo
No desktop, a lista de locais aparece ao lado do mapa. Clique em um marcador para ver as informações do local. No mobile, apenas o autocomplete de busca e o mapa são exibidos.

---

## Regras de Negócio

- Um local pode ter **múltiplos estilos** musicais.
- O número de apresentações é exibido como: "Nenhuma", "1 vez", "X vezes" ou "Não informado".
- Ao selecionar o endereço pelo autocomplete do Google, os campos de **latitude e longitude ficam desabilitados** (preenchidos automaticamente). Use o botão "X" para limpar o endereço e habilitar esses campos manualmente.
- O mapa com o marcador de endereço aparece automaticamente ao selecionar no autocomplete ou ao preencher latitude e longitude.

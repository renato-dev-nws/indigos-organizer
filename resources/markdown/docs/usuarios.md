# Usuários

## Visão Geral

O módulo de **Usuários** permite gerenciar os membros com acesso ao sistema. Administradores podem criar, editar e remover usuários, definindo nome, e-mail, avatar e perfil de acesso.

---

## Funcionalidades

### Como criar um usuário
1. Clique em **Novo Usuário**.
2. Preencha o **Nome**, **E-mail** e **Senha** inicial.
3. Defina se o usuário é **Administrador** ou usuário comum.
4. Adicione um **Avatar** (URL de imagem ou upload).
5. Configure as **Preferências de Notificação** (Push, E-mail, WhatsApp).
6. Clique em **Salvar**.

### Como editar um usuário
Clique em **Editar** ao lado do usuário. Administradores podem alterar todos os dados. O usuário pode editar o próprio perfil na página de **Perfil**.

### Troca de senha
A senha é alterada em um **modal separado** com os campos "Nova senha" e "Confirmar nova senha".

---

## Regras de Negócio

- Somente **Super-admins** podem promover outros usuários a administrador.
- **Admins** normais não podem criar outros admins.
- Usuários sem perfil de administrador não visualizam as colunas de ações nas tabelas de configuração.

# PetShop Manager

Sistema de gestão para pet shops desenvolvido em PHP, com controle de clientes, pets, serviços, agendamentos e módulo financeiro. Dados persistidos em arquivos JSON, com dashboard de indicadores.

## Funcionalidades

- Autenticação de usuários com perfis distintos (ex.: atendente, veterinário)
- Cadastro de clientes e seus respectivos pets
- Catálogo de serviços (banho, tosa, consultas, etc.)
- Agenda de atendimentos com controle de status (agendado/concluído)
- Módulo financeiro com registro de entradas e saídas, e cálculo automático de receita e lucro mensal
- Relatórios gerenciais
- Perfis de sistema com informações da equipe

## Tecnologias

- PHP (sem framework)
- HTML5 / CSS3
- Armazenamento de dados: JSON

## Como rodar localmente

1. Tenha o PHP instalado (versão 8+) ou use Laragon/XAMPP.
2. Clone o repositório e entre na pasta do projeto.
3. Inicie um servidor local:
   ```bash
   php -S localhost:8000
   ```
4. Acesse `http://localhost:8000` no navegador.

## Dados de demonstração

Os arquivos `clientes.json`, `pets.json`, `servicos.json`, `agendamentos.json` e `financeiro.json` já vêm com dados fictícios de exemplo para facilitar os testes. Nenhum dado real de cliente é utilizado.

## Segurança

- Senhas armazenadas com `password_hash()` (bcrypt) em `usuarios.json` e verificadas com `password_verify()` — nada de credencial em texto puro no código-fonte
- Proteção CSRF em todos os formulários que alteram dados (login, clientes, pets, agendamentos, serviços, financeiro, perfil), com token validado via `hash_equals()`

## Próximos passos (roadmap)

- Migrar armazenamento de JSON para um banco de dados relacional (PostgreSQL)
- Adicionar validação de formulários no front-end
- Adicionar testes automatizados

## Autor

Desenvolvido por **Arthur Sousa da Costa** como projeto de portfólio.

# PetShop Manager

Sistema de gestão para pet shops desenvolvido em PHP, com controle de clientes, pets, serviços, agendamentos e módulo financeiro.

## Funcionalidades

- Autenticação de usuários com perfis distintos (cliente, atendente, veterinário)
- Cadastro de clientes e seus respectivos pets
- Catálogo de serviços (banho, tosa, consultas, etc.)
- Agenda de atendimentos com controle de status
- Módulo financeiro com registro de entradas e saídas
- Relatórios gerenciais

## Tecnologias

- PHP 8 (sem framework)
- **PostgreSQL** via PDO, com prepared statements em toda consulta
- HTML5 / CSS3

## Arquitetura de dados

`ler_json()`/`salvar_json()`, em `functions.php`, viraram um adaptador: por baixo é PostgreSQL via PDO, por cima devolvem exatamente os arrays que `clientes.php`, `pets.php`, `servicos.php` e `agendamentos.php` já esperavam.

```
database/
├── schema.sql          # DDL completo (7 tabelas)
├── seed.sql             # dados de demonstração, migrados do JSON legado
└── dados_legado/        # JSONs originais, mantidos só como referência histórica
```

## Segurança

- Senhas com `password_hash()`/`password_verify()` (bcrypt)
- **Bloqueio de conta por força bruta**: 5 tentativas de login incorretas seguidas bloqueiam a conta por 15 minutos
- Proteção CSRF em todos os formulários que alteram dado
- Cookies de sessão com `httponly`, `samesite=Lax` e `secure` (quando em HTTPS)
- `.htaccess` bloqueando acesso direto a `.env`/`.sql`/`.log` e às pastas `app/`, `database/`

## Como rodar localmente

```bash
createdb petshop
psql petshop < database/schema.sql
psql petshop < database/seed.sql

cp .env.example .env

php -S localhost:8000
```

Acesse `http://localhost:8000`.

### Credenciais de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Cliente | cliente@petshop.com | cliente123 |
| Atendente | atendente@petshop.com | atendente123 |
| Veterinária | vet@petshop.com | vet123 |

## Roadmap

- [x] Testes automatizados
- [x] Paginação nas listagens

## Testes

```bash
composer install
createdb petshop_test
psql petshop_test < database/schema.sql
DB_NAME=petshop_test vendor/bin/phpunit
```

## Autor

Desenvolvido por **Arthur Sousa da Costa** — [LinkedIn](https://www.linkedin.com/in/arthur-sousa-ads/) · [GitHub](https://github.com/arthursousa-dev)

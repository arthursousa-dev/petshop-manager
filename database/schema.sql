-- ============================================================
-- PetShop Manager — Schema PostgreSQL
-- ============================================================

CREATE TYPE perfil_usuario AS ENUM ('cliente', 'atendente', 'vet');
CREATE TYPE status_agendamento AS ENUM ('agendado', 'em_atendimento', 'concluido', 'cancelado');
CREATE TYPE tipo_lancamento AS ENUM ('entrada', 'saida');

-- ------------------------------------------------------------
-- usuarios — e-mail como chave natural (mesma usada pra login)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    email            VARCHAR(150) PRIMARY KEY,
    senha_hash       VARCHAR(255) NOT NULL,
    perfil           perfil_usuario NOT NULL,
    nome             VARCHAR(150) NOT NULL,
    tentativas_login SMALLINT NOT NULL DEFAULT 0,
    bloqueado_ate    TIMESTAMP NULL,
    criado_em        TIMESTAMP NOT NULL DEFAULT now()
);

-- Dados de perfil (bio, telefone) da equipe interna — não existe
-- pra clientes, só pra atendente/vet.
CREATE TABLE perfis_bio (
    email     VARCHAR(150) PRIMARY KEY REFERENCES usuarios(email) ON DELETE CASCADE,
    nome      VARCHAR(150),
    telefone  VARCHAR(20),
    bio       TEXT
);

-- ------------------------------------------------------------
-- clientes — id em texto (compatível com o gerar_id() da app)
-- ------------------------------------------------------------
CREATE TABLE clientes (
    id        VARCHAR(40) PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    email     VARCHAR(150),
    telefone  VARCHAR(20),
    endereco  VARCHAR(200),
    criado_em TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- pets
-- ------------------------------------------------------------
CREATE TABLE pets (
    id          VARCHAR(40) PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    cliente_id  VARCHAR(40) NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    especie     VARCHAR(40) NOT NULL,
    raca        VARCHAR(80),
    peso        NUMERIC(5,2),
    alergias    TEXT,
    nascimento  DATE,
    criado_em   TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- servicos — catálogo
-- ------------------------------------------------------------
CREATE TABLE servicos (
    id        VARCHAR(40) PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco     NUMERIC(10,2) NOT NULL,
    duracao   VARCHAR(20),
    criado_em TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- agendamentos
-- ------------------------------------------------------------
CREATE TABLE agendamentos (
    id           VARCHAR(40) PRIMARY KEY,
    cliente_id   VARCHAR(40) NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    pet_id       VARCHAR(40) NOT NULL REFERENCES pets(id) ON DELETE CASCADE,
    servico_id   VARCHAR(40) NOT NULL REFERENCES servicos(id) ON DELETE RESTRICT,
    data_hora    TIMESTAMP NOT NULL,
    status       status_agendamento NOT NULL DEFAULT 'agendado',
    observacoes  TEXT,
    criado_em    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_agendamentos_data   ON agendamentos(data_hora);
CREATE INDEX idx_pets_cliente        ON pets(cliente_id);

-- ------------------------------------------------------------
-- financeiro_lancamentos — ficava vazio no JSON original
-- ------------------------------------------------------------
CREATE TABLE financeiro_lancamentos (
    id        SERIAL PRIMARY KEY,
    data      DATE NOT NULL,
    tipo      tipo_lancamento NOT NULL,
    categoria VARCHAR(80),
    valor     NUMERIC(10,2) NOT NULL,
    descricao VARCHAR(200),
    criado_em TIMESTAMP NOT NULL DEFAULT now()
);

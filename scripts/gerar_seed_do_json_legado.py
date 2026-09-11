"""
Ferramenta de migração usada uma vez: converte database/dados_legado/*.json
em INSERT para o PostgreSQL (database/seed.sql).
"""
import json, os

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BASE = os.path.join(RAIZ, "database", "dados_legado")

def load(name):
    with open(os.path.join(BASE, name), encoding="utf-8") as f:
        return json.load(f)

def esc(v):
    if v is None or v == '':
        return "NULL"
    if isinstance(v, bool):
        return "true" if v else "false"
    if isinstance(v, (int, float)):
        return str(v)
    return "'" + str(v).replace("'", "''") + "'"

out = ["-- PetShop Manager — dados de demonstração (migrados do JSON legado)\n"]

usuarios = load("usuarios.json")
out.append("-- usuarios")
out.append("INSERT INTO usuarios (email, senha_hash, perfil, nome) VALUES")
out.append(",\n".join(f"({esc(u['email'])}, {esc(u['senha'])}, {esc(u['perfil'])}, {esc(u['nome'])})" for u in usuarios) + ";\n")

perfis = load("perfis_sistema.json")
out.append("-- perfis_bio")
if perfis:
    out.append("INSERT INTO perfis_bio (email, nome, telefone, bio) VALUES")
    out.append(",\n".join(f"({esc(p['email'])}, {esc(p.get('nome'))}, {esc(p.get('telefone'))}, {esc(p.get('bio'))})" for p in perfis) + ";\n")

clientes = load("clientes.json")
out.append("-- clientes")
out.append("INSERT INTO clientes (id, nome, email, telefone, endereco, criado_em) VALUES")
out.append(",\n".join(f"({esc(c['id'])}, {esc(c['nome'])}, {esc(c.get('email'))}, {esc(c.get('telefone'))}, {esc(c.get('endereco'))}, {esc(c.get('criado_em'))}::timestamp)" for c in clientes) + ";\n")

pets = load("pets.json")
out.append("-- pets")
out.append("INSERT INTO pets (id, nome, cliente_id, especie, raca, peso, alergias, nascimento, criado_em) VALUES")
linhas = []
for p in pets:
    peso = p.get('peso')
    peso_sql = str(float(peso)) if peso not in (None, '') else 'NULL'
    linhas.append(f"({esc(p['id'])}, {esc(p['nome'])}, {esc(p['cliente_id'])}, {esc(p['especie'])}, {esc(p.get('raca'))}, {peso_sql}, {esc(p.get('alergias'))}, {esc(p.get('nascimento'))}, {esc(p.get('criado_em'))}::timestamp)")
out.append(",\n".join(linhas) + ";\n")

servicos = load("servicos.json")
out.append("-- servicos")
out.append("INSERT INTO servicos (id, nome, descricao, preco, duracao, criado_em) VALUES")
out.append(",\n".join(f"({esc(s['id'])}, {esc(s['nome'])}, {esc(s.get('descricao'))}, {s['preco']}, {esc(s.get('duracao'))}, {esc(s.get('criado_em'))}::timestamp)" for s in servicos) + ";\n")

agendamentos = load("agendamentos.json")
out.append("-- agendamentos")
out.append("INSERT INTO agendamentos (id, cliente_id, pet_id, servico_id, data_hora, status, observacoes, criado_em) VALUES")
linhas = [f"({esc(a['id'])}, {esc(a['cliente_id'])}, {esc(a['pet_id'])}, {esc(a['servico_id'])}, {esc(a['data_hora'])}::timestamp, {esc(a['status'])}, {esc(a.get('observacoes'))}, {esc(a.get('criado_em'))}::timestamp)" for a in agendamentos]
out.append(",\n".join(linhas) + ";\n")

with open(os.path.join(RAIZ, "database", "seed.sql"), "w", encoding="utf-8") as f:
    f.write("\n".join(out))

print("seed.sql gerado com sucesso")

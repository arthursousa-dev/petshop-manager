-- PetShop Manager — dados de demonstração (migrados do JSON legado)

-- usuarios
INSERT INTO usuarios (email, senha_hash, perfil, nome) VALUES
('cliente@petshop.com', '$2b$10$SlFfcWK5Vp952PcpI0GEGuWPeGwP6rZHOLuiUhOu/qye2vXk7u0wO', 'cliente', 'Maria Silva'),
('atendente@petshop.com', '$2b$10$Vi0G/rfB2jDg61e8aoB35OVtPgKjlzwPkU/vxK9byvPG7NyFhhhG6', 'atendente', 'João Atendente'),
('vet@petshop.com', '$2b$10$b2vHqlE7jXdLGk9hm6QbRumQYDWZx/vO8nM1YMsZo1pyIFHnyHj1K', 'vet', 'Dra. Ana Vet');

-- perfis_bio
INSERT INTO perfis_bio (email, nome, telefone, bio) VALUES
('atendente@petshop.com', 'João Atendente', '(67) 99111-2222', 'Atendente responsável pela recepção e agendamentos dos clientes.'),
('vet@petshop.com', 'Dra. Ana Vet', '(67) 99333-4444', 'Veterinária especialista em clínica geral e pequenos animais. CRMV-MS 12345.');

-- clientes
INSERT INTO clientes (id, nome, email, telefone, endereco, criado_em) VALUES
('cl001', 'Maria Silva', 'cliente@petshop.com', '(67) 99234-5678', 'Rua das Flores, 123 – Campo Grande, MS', '2026-01-10 09:00:00'::timestamp),
('cl002', 'Carlos Oliveira', 'carlos@email.com', '(67) 99876-4321', 'Av. Afonso Pena, 456 – Campo Grande, MS', '2026-02-15 14:30:00'::timestamp),
('cl003', 'Fernanda Costa', 'fernanda@email.com', '(67) 99555-1234', 'Rua 14 de Julho, 789 – Campo Grande, MS', '2026-02-20 10:00:00'::timestamp),
('cl004', 'Roberto Lima', 'roberto@email.com', '(67) 99444-5678', 'Av. Rachid Neder, 321 – Campo Grande, MS', '2026-03-05 11:00:00'::timestamp),
('cl005', 'Juliana Martins', 'juliana@email.com', '(67) 99333-8765', 'Rua José Antônio, 654 – Campo Grande, MS', '2026-03-18 09:30:00'::timestamp),
('cl006', 'Pedro Santos', 'pedro@email.com', '(67) 99222-3456', 'Rua Maracaju, 987 – Campo Grande, MS', '2026-04-02 14:00:00'::timestamp),
('cl007', 'Camila Ferreira', 'camila@email.com', '(67) 99111-2345', 'Av. Ceará, 1234 – Campo Grande, MS', '2026-04-15 10:00:00'::timestamp);

-- pets
INSERT INTO pets (id, nome, cliente_id, especie, raca, peso, alergias, nascimento, criado_em) VALUES
('pt001', 'Bolinha', 'cl001', 'Cão', 'Poodle', 5.2, 'Shampoo com sulfato', '2019-03-15', '2026-01-10 10:00:00'::timestamp),
('pt002', 'Mimi', 'cl002', 'Gato', 'Siamês', 3.8, NULL, '2021-07-22', '2026-02-15 09:30:00'::timestamp),
('pt003', 'Rex', 'cl003', 'Cão', 'Golden Retriever', 28.5, 'Intolerância à lactose', '2018-11-05', '2026-02-20 14:00:00'::timestamp),
('pt004', 'Thor', 'cl001', 'Cão', 'Labrador', 30.1, NULL, '2022-06-10', '2026-01-10 11:00:00'::timestamp),
('pt005', 'Mel', 'cl004', 'Cão', 'Bulldog Francês', 11.2, 'Farinha de trigo', '2023-02-14', '2026-03-05 11:30:00'::timestamp),
('pt006', 'Flash', 'cl004', 'Cão', 'Dachshund', 7.8, NULL, '2020-09-20', '2026-03-05 11:35:00'::timestamp),
('pt007', 'Pingo', 'cl005', 'Gato', 'Maine Coon', 6.5, NULL, '2022-01-08', '2026-03-18 10:00:00'::timestamp),
('pt008', 'Belinha', 'cl005', 'Cão', 'Shih Tzu', 4.9, 'Proteína bovina', '2021-04-25', '2026-03-18 10:05:00'::timestamp),
('pt009', 'Rocky', 'cl006', 'Cão', 'Pastor Alemão', 35.0, NULL, '2020-07-15', '2026-04-02 14:30:00'::timestamp),
('pt010', 'Luna', 'cl007', 'Gato', 'Persa', 4.1, 'Proteína de soja', '2023-11-03', '2026-04-15 10:30:00'::timestamp),
('pt011', 'Max', 'cl006', 'Cão', 'Husky Siberiano', 25.3, NULL, '2021-12-01', '2026-04-02 14:35:00'::timestamp),
('pt012', 'Lili', 'cl007', 'Gato', 'Angora', 3.2, NULL, '2024-03-10', '2026-04-15 10:35:00'::timestamp);

-- servicos
INSERT INTO servicos (id, nome, descricao, preco, duracao, criado_em) VALUES
('sv001', 'Banho e Tosa', 'Banho completo com escova, perfume e tosa higiênica', 80, '1h30min', '2026-01-05 08:00:00'::timestamp),
('sv002', 'Consulta Veterinária', 'Consulta clínica geral com veterinário especialista', 150, '45min', '2026-01-05 08:00:00'::timestamp),
('sv003', 'Vacinação', 'Aplicação de vacinas conforme calendário vacinal do pet', 120, '20min', '2026-01-05 08:00:00'::timestamp),
('sv004', 'Tosa Completa', 'Tosa artística ou funcional conforme padrão da raça', 100, '2h', '2026-01-05 08:00:00'::timestamp),
('sv005', 'Microchip / Identificação', 'Implante de microchip de identificação permanente ISO 11784', 200, '30min', '2026-01-05 08:00:00'::timestamp),
('sv006', 'Limpeza Dental', 'Profilaxia e limpeza dental com ultrassom veterinário', 280, '1h', '2026-01-05 08:00:00'::timestamp),
('sv007', 'Hemograma Completo', 'Exame de sangue completo com resultado em 2 horas', 180, '30min', '2026-01-05 08:00:00'::timestamp),
('sv008', 'Hospedagem Diária', 'Hospedagem com alimentação, brincadeiras e cuidados 24h', 120, '24h', '2026-01-05 08:00:00'::timestamp);

-- agendamentos
INSERT INTO agendamentos (id, cliente_id, pet_id, servico_id, data_hora, status, observacoes, criado_em) VALUES
('ag001', 'cl002', 'pt002', 'sv002', '2026-06-01 08:30'::timestamp, 'concluido', 'Mimi com queda de pelos intensa', '2026-05-25 10:00:00'::timestamp),
('ag002', 'cl001', 'pt001', 'sv007', '2026-06-01 09:00'::timestamp, 'concluido', 'Hemograma preventivo anual', '2026-05-28 09:00:00'::timestamp),
('ag003', 'cl003', 'pt003', 'sv001', '2026-06-01 09:30'::timestamp, 'concluido', 'Tosa verão Rex', '2026-05-28 14:00:00'::timestamp),
('ag004', 'cl004', 'pt005', 'sv001', '2026-06-01 10:00'::timestamp, 'em_atendimento', 'Primeira tosa da Mel', '2026-05-29 08:00:00'::timestamp),
('ag005', 'cl005', 'pt007', 'sv002', '2026-06-01 10:30'::timestamp, 'em_atendimento', 'Consulta rotina Pingo', '2026-05-29 09:00:00'::timestamp),
('ag006', 'cl006', 'pt009', 'sv004', '2026-06-01 11:00'::timestamp, 'agendado', 'Tosa verão Rocky', '2026-05-29 10:00:00'::timestamp),
('ag007', 'cl007', 'pt010', 'sv006', '2026-06-01 11:30'::timestamp, 'agendado', 'Limpeza dental Luna – primeira vez', '2026-05-30 09:00:00'::timestamp),
('ag008', 'cl001', 'pt004', 'sv003', '2026-06-01 13:00'::timestamp, 'agendado', 'V10 anual Thor', '2026-05-30 10:00:00'::timestamp),
('ag009', 'cl005', 'pt008', 'sv001', '2026-06-01 14:00'::timestamp, 'agendado', NULL, '2026-05-30 11:00:00'::timestamp),
('ag010', 'cl006', 'pt011', 'sv002', '2026-06-01 15:00'::timestamp, 'agendado', 'Max com suspeita de dermatite', '2026-05-31 08:00:00'::timestamp),
('ag011', 'cl007', 'pt012', 'sv005', '2026-06-01 16:00'::timestamp, 'agendado', 'Implante microchip Lili', '2026-05-31 09:00:00'::timestamp),
('ag012', 'cl004', 'pt006', 'sv003', '2026-06-01 16:30'::timestamp, 'agendado', 'Antirrábica Flash', '2026-05-31 10:00:00'::timestamp),
('ag013', 'cl002', 'pt002', 'sv001', '2026-06-05 09:00'::timestamp, 'agendado', NULL, '2026-06-01 08:00:00'::timestamp),
('ag014', 'cl001', 'pt001', 'sv006', '2026-06-08 10:00'::timestamp, 'agendado', 'Limpeza dental Bolinha', '2026-06-01 09:00:00'::timestamp),
('ag015', 'cl003', 'pt003', 'sv007', '2026-06-10 14:30'::timestamp, 'agendado', 'Retorno hemograma Rex', '2026-06-01 10:00:00'::timestamp),
('ag016', 'cl001', 'pt001', 'sv001', '2026-05-05 09:00'::timestamp, 'concluido', NULL, '2026-05-01 10:00:00'::timestamp),
('ag017', 'cl003', 'pt003', 'sv002', '2026-05-05 11:00'::timestamp, 'concluido', 'Consulta Rex – coxeando', '2026-05-01 11:00:00'::timestamp),
('ag018', 'cl002', 'pt002', 'sv003', '2026-05-08 09:30'::timestamp, 'concluido', 'V4 Felina Mimi', '2026-05-05 09:00:00'::timestamp),
('ag019', 'cl004', 'pt005', 'sv002', '2026-05-08 14:00'::timestamp, 'concluido', 'Consulta Mel – rotina', '2026-05-05 10:00:00'::timestamp),
('ag020', 'cl005', 'pt008', 'sv001', '2026-05-08 15:30'::timestamp, 'concluido', NULL, '2026-05-05 11:00:00'::timestamp),
('ag021', 'cl006', 'pt009', 'sv007', '2026-05-12 08:30'::timestamp, 'concluido', 'Hemograma preventivo Rocky', '2026-05-08 09:00:00'::timestamp),
('ag022', 'cl007', 'pt010', 'sv002', '2026-05-12 10:00'::timestamp, 'concluido', 'Luna perdendo peso', '2026-05-08 10:00:00'::timestamp),
('ag023', 'cl001', 'pt004', 'sv004', '2026-05-15 09:00'::timestamp, 'concluido', 'Tosa verão Thor', '2026-05-12 09:00:00'::timestamp),
('ag024', 'cl003', 'pt003', 'sv006', '2026-05-15 11:00'::timestamp, 'concluido', '1ª limpeza dental Rex', '2026-05-12 10:00:00'::timestamp),
('ag025', 'cl004', 'pt006', 'sv001', '2026-05-15 14:30'::timestamp, 'concluido', NULL, '2026-05-12 11:00:00'::timestamp),
('ag026', 'cl005', 'pt007', 'sv003', '2026-05-19 09:00'::timestamp, 'concluido', 'Antirrábica Pingo', '2026-05-15 09:00:00'::timestamp),
('ag027', 'cl006', 'pt011', 'sv004', '2026-05-19 11:00'::timestamp, 'concluido', 'Tosa Max', '2026-05-15 10:00:00'::timestamp),
('ag028', 'cl002', 'pt002', 'sv007', '2026-05-22 08:30'::timestamp, 'concluido', 'Mimi – retorno exame', '2026-05-19 09:00:00'::timestamp),
('ag029', 'cl007', 'pt012', 'sv001', '2026-05-22 10:00'::timestamp, 'concluido', NULL, '2026-05-19 10:00:00'::timestamp),
('ag030', 'cl001', 'pt001', 'sv002', '2026-05-22 14:00'::timestamp, 'concluido', 'Check-up Bolinha', '2026-05-19 11:00:00'::timestamp),
('ag031', 'cl004', 'pt005', 'sv006', '2026-05-26 09:00'::timestamp, 'concluido', 'Limpeza dental Mel', '2026-05-22 09:00:00'::timestamp),
('ag032', 'cl005', 'pt008', 'sv002', '2026-05-26 11:00'::timestamp, 'concluido', 'Belinha – vômitos frequentes', '2026-05-22 10:00:00'::timestamp),
('ag033', 'cl006', 'pt009', 'sv003', '2026-05-28 09:30'::timestamp, 'concluido', 'V10 Rocky', '2026-05-26 09:00:00'::timestamp),
('ag034', 'cl003', 'pt003', 'sv005', '2026-05-28 14:00'::timestamp, 'concluido', 'Microchip Rex implantado', '2026-05-26 10:00:00'::timestamp),
('ag035', 'cl007', 'pt010', 'sv001', '2026-05-29 10:00'::timestamp, 'concluido', NULL, '2026-05-26 11:00:00'::timestamp),
('ag036', 'cl002', 'pt002', 'sv008', '2026-05-29 12:00'::timestamp, 'concluido', 'Hospedagem 2 dias fim de semana', '2026-05-26 12:00:00'::timestamp);

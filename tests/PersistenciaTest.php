<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Config\Database;
use PDO;

final class PersistenciaTest extends TestCase
{
    private PDO $pdo;
    private string $idTeste = 'teste_persistencia_001';

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->limpar();
    }

    protected function tearDown(): void
    {
        $this->limpar();
    }

    private function limpar(): void
    {
        $this->pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute([':id' => $this->idTeste]);
    }

    public function test_salvarJson_insere_cliente_novo(): void
    {
        $clientes = ler_json('clientes.json');
        $clientes[] = [
            'id' => $this->idTeste, 'nome' => 'Cliente de Teste',
            'email' => 'cliente.teste@petshop.local', 'telefone' => '(67) 90000-0000',
            'endereco' => 'Rua de Teste, 1',
        ];

        $this->assertTrue(salvar_json('clientes.json', $clientes));

        $linha = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id');
        $linha->execute([':id' => $this->idTeste]);
        $registro = $linha->fetch();

        $this->assertNotFalse($registro);
        $this->assertSame('Cliente de Teste', $registro['nome']);
    }

    public function test_salvarJson_remove_cliente_que_saiu_do_array(): void
    {
        $clientes = ler_json('clientes.json');
        $clientes[] = ['id' => $this->idTeste, 'nome' => 'Cliente Temporário', 'email' => null, 'telefone' => null, 'endereco' => null];
        salvar_json('clientes.json', $clientes);

        // remove do array e salva de novo — simula o que clientes.php faz ao excluir
        $clientesSemOTeste = array_values(array_filter($clientes, fn($c) => $c['id'] !== $this->idTeste));
        salvar_json('clientes.json', $clientesSemOTeste);

        $linha = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id');
        $linha->execute([':id' => $this->idTeste]);

        $this->assertFalse($linha->fetch(), 'cliente deveria ter sido removido do banco');
    }

    public function test_arquivo_desconhecido_devolve_array_vazio(): void
    {
        $this->assertSame([], ler_json('arquivo_que_nao_existe.json'));
    }
}

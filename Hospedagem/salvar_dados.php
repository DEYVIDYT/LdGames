<?php
header('Content-Type: application/json');

// Arquivo para armazenar os dados
$arquivo = 'dados.json';

// Método GET para obter os dados
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($arquivo)) {
        echo file_get_contents($arquivo);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Método POST para salvar os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
    $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : '';
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : ''; // Novo campo

    if (!empty($nome) && !empty($descricao) && !empty($url) && !empty($tipo)) {
        $novoDado = [
            'nome' => $nome,
            'descricao' => $descricao,
            'url' => $url,
            'tipo' => $tipo, // Salvar o novo campo
        ];

        if (file_exists($arquivo)) {
            $dadosAtuais = json_decode(file_get_contents($arquivo), true);
            if (!is_array($dadosAtuais)) {
                $dadosAtuais = [];
            }
        } else {
            $dadosAtuais = [];
        }

        $dadosAtuais[] = $novoDado;

        if (file_put_contents($arquivo, json_encode($dadosAtuais, JSON_PRETTY_PRINT))) {
            echo json_encode(['status' => 'success', 'message' => 'Dados salvos com sucesso!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar os dados!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos!']);
    }
    exit;
}

// Caso o método não seja GET nem POST
echo json_encode(['status' => 'error', 'message' => 'Método inválido!']);
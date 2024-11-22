<?php
header('Content-Type: application/json');

// Configurações do GitHub
$token = '';
$repositorio = 'DEYVIDYT/LdGames';
$arquivoGitHub = 'Hospedagem/dados.json';
$urlGitHub = "https://api.github.com/repos/$repositorio/contents/$arquivoGitHub";

// Função para obter o conteúdo atual do arquivo no GitHub
function obterConteudoGitHub($url, $token) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: PHP-App"
    ]);
    $resposta = curl_exec($curl);
    curl_close($curl);
    return json_decode($resposta, true);
}

// Função para atualizar o conteúdo do arquivo no GitHub
function atualizarConteudoGitHub($url, $token, $conteudo, $sha) {
    $dados = [
        'message' => 'Atualizando dados.json via API',
        'content' => base64_encode($conteudo),
        'sha' => $sha
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: PHP-App",
        "Content-Type: application/json"
    ]);
    $resposta = curl_exec($curl);
    curl_close($curl);
    return json_decode($resposta, true);
}

// Método GET para obter os dados
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conteudo = obterConteudoGitHub($urlGitHub, $token);
    if (isset($conteudo['content'])) {
        echo base64_decode($conteudo['content']);
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

        $conteudoAtual = obterConteudoGitHub($urlGitHub, $token);
        $dadosAtuais = [];

        if (isset($conteudoAtual['content'])) {
            $dadosAtuais = json_decode(base64_decode($conteudoAtual['content']), true);
        }

        $dadosAtuais[] = $novoDado;
        $novoConteudo = json_encode($dadosAtuais, JSON_PRETTY_PRINT);

        $resultado = atualizarConteudoGitHub($urlGitHub, $token, $novoConteudo, $conteudoAtual['sha'] ?? null);

        if (isset($resultado['commit'])) {
            echo json_encode(['status' => 'success', 'message' => 'Dados salvos com sucesso no GitHub!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar os dados no GitHub!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos!']);
    }
    exit;
}

// Caso o método não seja GET nem POST
echo json_encode(['status' => 'error', 'message' => 'Método inválido!']);

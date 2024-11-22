<?php
// Suas credenciais da Twitch
$client_id = "";
$client_secret = "";

// URL para solicitar o token de acesso
$url_token = "https://id.twitch.tv/oauth2/token";

// Configurar os parâmetros da requisição para obter o token de acesso
$data = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'client_credentials'
];

// Iniciar a requisição cURL para obter o token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_token);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Executa a requisição e decodifica a resposta
$response = curl_exec($ch);
curl_close($ch);

// Decodificar a resposta JSON para obter o token
$response_data = json_decode($response, true);

if (isset($response_data['access_token'])) {
    $access_token = $response_data['access_token'];
} else {
    die("Erro ao obter o token de acesso: " . $response_data['message']);
}

// Obter parâmetros da URL
$type = isset($_GET['type']) ? $_GET['type'] : 'popular';
$name = isset($_GET['name']) ? $_GET['name'] : null;

// Mapear os gêneros para seus respectivos IDs no IGDB
$generos = [
    "Ação e Aventura" => 31,
    "Corrida" => 10,
    "Luta" => 4,
    "Terror" => 14,
    "FPS" => 5,
    "Puzzle" => 9
];

// Função para fazer a requisição à API do IGDB e retornar os dados
function buscarJogos($query, $url, $access_token, $client_id) {
    $ch = curl_init($url);

    // Definir os cabeçalhos da requisição com o token e o ID do cliente
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Client-ID: $client_id",
        "Authorization: Bearer $access_token"
    ]);

    // Configurar o método POST e enviar a query
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executar a requisição e obter a resposta
    $response = curl_exec($ch);
    curl_close($ch);

    // Decodificar a resposta JSON e retornar os dados
    return json_decode($response, true);
}

// Função para corrigir e decodificar caracteres especiais
function corrigirGeneros($genero) {
    return $genero;  // Simplesmente retorna o gênero sem modificar
}

// Definir a consulta para a API do IGDB com base no parâmetro `type` ou `name`
$url_igdb = "https://api.igdb.com/v4/games";
$jogos_completos = [];

if ($name) {
    // Consulta para buscar pelo nome do jogo
    $query = "fields name, genres, rating, cover.url, storyline, summary, first_release_date, screenshots.url, videos.video_id; search \"$name\"; limit 20;";
    $jogos = buscarJogos($query, $url_igdb, $access_token, $client_id);

    foreach ($jogos as $jogo) {
        $jogo_data = [
            'title' => $jogo['name'],
            'release_date' => isset($jogo['first_release_date']) ? date('Y-m-d', $jogo['first_release_date']) : null,
            'platform' => "PC",
            'game_link' => "https://thegamesdb.net/game.php?id=" . (isset($jogo['id']) ? $jogo['id'] : ''),
            'description' => isset($jogo['summary']) ? $jogo['summary'] : (isset($jogo['storyline']) ? $jogo['storyline'] : null),
            'genre' => isset($jogo['genres']) ? corrigirGeneros(implode(' | ', array_map(function($g) use ($generos) { return array_search($g, $generos); }, $jogo['genres']))) : '',
            'screenshots' => isset($jogo['screenshots']) ? array_map(function($screenshot) {
                return str_replace('t_thumb', 't_screenshot_huge', "https:" . $screenshot['url']);
            }, $jogo['screenshots']) : [],
            'video_url' => isset($jogo['videos']) && !empty($jogo['videos']) ? 'https://www.youtube.com/embed/' . $jogo['videos'][0]['video_id'] : null
        ];

        if (isset($jogo['cover']['url']) && !empty($jogo['cover']['url'])) {
            $jogo_data['image_url'] = str_replace('t_thumb', 't_cover_big', "https:" . $jogo['cover']['url']);
        }

        $jogos_completos[] = $jogo_data;
    }
} elseif ($type == "generos") {
    foreach ($generos as $genero_nome => $genero_id) {
        $query = "fields name, genres, rating, cover.url, storyline, summary, first_release_date, screenshots.url, videos.video_id; where genres = $genero_id; sort rating desc; limit 20;";
        $jogos = buscarJogos($query, $url_igdb, $access_token, $client_id);

        foreach ($jogos as $jogo) {
            $jogo_data = [
                'title' => $jogo['name'],
                'release_date' => isset($jogo['first_release_date']) ? date('Y-m-d', $jogo['first_release_date']) : null,
                'platform' => "PC",
                'game_link' => "https://thegamesdb.net/game.php?id=" . (isset($jogo['id']) ? $jogo['id'] : ''),
                'description' => isset($jogo['summary']) ? $jogo['summary'] : (isset($jogo['storyline']) ? $jogo['storyline'] : null),
                'genre' => corrigirGeneros($genero_nome),
                'screenshots' => isset($jogo['screenshots']) ? array_map(function($screenshot) {
                    return str_replace('t_thumb', 't_screenshot_huge', "https:" . $screenshot['url']);
                }, $jogo['screenshots']) : [],
                'video_url' => isset($jogo['videos']) && !empty($jogo['videos']) ? 'https://www.youtube.com/embed/' . $jogo['videos'][0]['video_id'] : null
            ];

            if (isset($jogo['cover']['url']) && !empty($jogo['cover']['url'])) {
                $jogo_data['image_url'] = str_replace('t_thumb', 't_cover_big', "https:" . $jogo['cover']['url']);
            }

            $jogos_completos[] = $jogo_data;
        }
    }
} elseif ($type == "lançamento") { 
    // Consulta para buscar os lançamentos
    $query = "fields name, genres, rating, cover.url, storyline, summary, first_release_date, screenshots.url, videos.video_id; sort first_release_date desc; limit 20;";
    $jogos = buscarJogos($query, $url_igdb, $access_token, $client_id);

    foreach ($jogos as $jogo) {
        $jogo_data = [
            'title' => $jogo['name'],
            'release_date' => isset($jogo['first_release_date']) ? date('Y-m-d', $jogo['first_release_date']) : null,
            'platform' => "PC",
            'game_link' => "https://thegamesdb.net/game.php?id=" . (isset($jogo['id']) ? $jogo['id'] : ''),
            'description' => isset($jogo['summary']) ? $jogo['summary'] : (isset($jogo['storyline']) ? $jogo['storyline'] : null),
            'genre' => isset($jogo['genres']) ? corrigirGeneros(implode(' | ', array_map(function($g) use ($generos) { return array_search($g, $generos); }, $jogo['genres']))) : '',
            'screenshots' => isset($jogo['screenshots']) ? array_map(function($screenshot) {
                return str_replace('t_thumb', 't_screenshot_huge', "https:" . $screenshot['url']);
            }, $jogo['screenshots']) : [],
            'video_url' => isset($jogo['videos']) && !empty($jogo['videos']) ? 'https://www.youtube.com/embed/' . $jogo['videos'][0]['video_id'] : null
        ];

        if (isset($jogo['cover']['url']) && !empty($jogo['cover']['url'])) {
            $jogo_data['image_url'] = str_replace('t_thumb', 't_cover_big', "https:" . $jogo['cover']['url']);
        }

        $jogos_completos[] = $jogo_data;
    }
}

echo json_encode($jogos_completos);
?>

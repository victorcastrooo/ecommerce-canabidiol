<?php
// public/index.php

// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Definir constantes de caminho
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Carregar autoloader do Composer
require ROOT_PATH . '/vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Iniciar a sessão
session_start();

// Configurar tratamento de erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

set_exception_handler(function($exception) {
    error_log($exception);
    http_response_code(500);
    
    if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
        echo "<h1>Erro na aplicação</h1>";
        echo "<p><strong>Mensagem:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $exception->getFile() . " na linha " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        require APP_PATH . '/views/errors/500.php';
    }
    
    exit;
});

// Inicializar a aplicação
try {
    // Carregar configurações do banco de dados
    $dbConfig = require APP_PATH . '/config/database.php';
    
    // Inicializar o banco de dados
    $db = new \App\Lib\Database($dbConfig);
    
    // Inicializar o roteador
    $router = new \App\Lib\Router();
    
    // Carregar rotas
    require APP_PATH . '/config/routes.php';
    
    // Executar a rota correspondente
    $router->dispatch();
    
} catch (\Exception $e) {
    // Log do erro
    error_log($e->getMessage());
    
    // Resposta de erro
    http_response_code(500);
    echo "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.";
    
    if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
        echo "<br><br>Detalhes: " . $e->getMessage();
    }
}
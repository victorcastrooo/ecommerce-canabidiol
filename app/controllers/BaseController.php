<?php
namespace App\Controllers;

use App\Lib\Database;
use App\Lib\Auth;
use App\Lib\View;

class BaseController {
    protected $db;
    protected $auth;
    protected $view;

    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth($this->db);
        $this->view = new View();
    }

    protected function render($viewPath, $data = []) {
        return $this->view->render($viewPath, $data);
    }

    protected function redirect($url) {
        header("Location: $url");
        exit;
    }

    protected function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
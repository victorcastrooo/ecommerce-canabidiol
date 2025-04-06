<?php
namespace App\Lib;

class View {
    public function render($viewPath, $data = []) {
        extract($data);
        
        $viewFile = __DIR__ . '/../../app/views/' . $viewPath . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewPath}");
        }
        
        ob_start();
        require $viewFile;
        return ob_get_clean();
    }
}
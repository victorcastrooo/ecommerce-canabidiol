<?php
namespace App\Lib;

use PDOException;
use PDO;
use Exception;
/**
 * Validator Class - Data validation and sanitization
 * 
 * Handles validation of user input with medical cannabis specific rules
 */
class Validator {
    private $data;
    private $errors = [];
    private $customMessages = [];
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules, array $messages = []) {
        $this->data = $data;
        $this->customMessages = $messages;

        foreach ($rules as $field => $ruleSet) {
            $rules = explode('|', $ruleSet);
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply single validation rule
     */
    private function applyRule($field, $rule) {
        $params = [];
        
        // Check for rule parameters
        if (strpos($rule, ':') !== false) {
            list($rule, $params) = explode(':', $rule, 2);
            $params = explode(',', $params);
        }

        $value = $this->getValue($field);

        // Skip validation if field is empty and not required
        if ($rule !== 'required' && $this->isEmpty($field)) {
            return;
        }

        $method = "validate" . ucfirst($rule);
        
        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $params)) {
                $this->addError($field, $rule, $params);
            }
        } else {
            throw new Exception("Validation rule {$rule} does not exist");
        }
    }

    /**
     * Get field value with array notation support
     */
    private function getValue($field) {
        // Handle array notation (e.g., user[name])
        if (strpos($field, '[') !== false) {
            preg_match('/([^\[]*)\[([^\]]*)\]/', $field, $matches);
            $arrayField = $matches[1];
            $arrayKey = $matches[2];

            return $this->data[$arrayField][$arrayKey] ?? null;
        }

        return $this->data[$field] ?? null;
    }

    /**
     * Check if field is empty
     */
    private function isEmpty($field) {
        $value = $this->getValue($field);
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Add validation error
     */
    private function addError($field, $rule, $params = []) {
        $message = $this->customMessages["{$field}.{$rule}"] ?? $this->getDefaultMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     */
    private function getDefaultMessage($field, $rule, $params) {
        $messages = [
            'required' => "O campo {$field} é obrigatório",
            'email' => "O campo {$field} deve ser um e-mail válido",
            'min' => "O campo {$field} deve ter no mínimo {$params[0]} caracteres",
            'max' => "O campo {$field} deve ter no máximo {$params[0]} caracteres",
            'between' => "O campo {$field} deve ter entre {$params[0]} e {$params[1]} caracteres",
            'confirmed' => "A confirmação do campo {$field} não corresponde",
            'unique' => "O valor informado para {$field} já está em uso",
            'exists' => "O valor informado para {$field} não existe",
            'numeric' => "O campo {$field} deve ser um número",
            'integer' => "O campo {$field} deve ser um número inteiro",
            'date' => "O campo {$field} deve ser uma data válida",
            'date_format' => "O campo {$field} deve estar no formato {$params[0]}",
            'in' => "O campo {$field} deve ser um dos seguintes valores: " . implode(', ', $params),
            'not_in' => "O campo {$field} não pode ser um dos seguintes valores: " . implode(', ', $params),
            'regex' => "O campo {$field} não está no formato correto",
            'cpf' => "O CPF informado é inválido",
            'cnpj' => "O CNPJ informado é inválido",
            'cep' => "O CEP informado é inválido",
            'phone' => "O telefone informado é inválido",
            'birthdate' => "A data de nascimento deve ser válida e o usuário deve ter pelo menos 18 anos",
            'crm' => "O CRM médico informado é inválido",
            'anvisa' => "O registro ANVISA informado é inválido",
            'prescription' => "A receita médica deve conter informações válidas"
        ];

        return $messages[$rule] ?? "O campo {$field} não passou na validação {$rule}";
    }

    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get first error for field
     */
    public function first($field) {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Validation Rules
     */

    private function validateRequired($field, $value) {
        return !$this->isEmpty($field);
    }

    private function validateEmail($field, $value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin($field, $value, $params) {
        $length = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
        return $length >= $params[0];
    }

    private function validateMax($field, $value, $params) {
        $length = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
        return $length <= $params[0];
    }

    private function validateBetween($field, $value, $params) {
        $length = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
        return $length >= $params[0] && $length <= $params[1];
    }

    private function validateConfirmed($field, $value) {
        $confirmationField = "{$field}_confirmation";
        return isset($this->data[$confirmationField]) && $value === $this->data[$confirmationField];
    }

    private function validateUnique($field, $value, $params) {
        if (!$this->db) {
            throw new Exception("Database instance is required for unique validation");
        }

        $table = $params[0] ?? $field;
        $column = $params[1] ?? $field;
        $ignoreId = $params[2] ?? null;

        $this->db->query("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value");
        $this->db->bind(':value', $value);
        
        if ($ignoreId) {
            $this->db->query .= " AND id != :ignore_id";
            $this->db->bind(':ignore_id', $ignoreId);
        }

        $result = $this->db->single();
        return $result->count == 0;
    }

    private function validateExists($field, $value, $params) {
        if (!$this->db) {
            throw new Exception("Database instance is required for exists validation");
        }

        $table = $params[0] ?? $field;
        $column = $params[1] ?? $field;

        $this->db->query("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value");
        $this->db->bind(':value', $value);
        $result = $this->db->single();
        return $result->count > 0;
    }

    private function validateNumeric($field, $value) {
        return is_numeric($value);
    }

    private function validateInteger($field, $value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateDate($field, $value) {
        return strtotime($value) !== false;
    }

    private function validateDate_format($field, $value, $params) {
        $date = DateTime::createFromFormat($params[0], $value);
        return $date && $date->format($params[0]) === $value;
    }

    private function validateIn($field, $value, $params) {
        return in_array($value, $params);
    }

    private function validateNot_in($field, $value, $params) {
        return !in_array($value, $params);
    }

    private function validateRegex($field, $value, $params) {
        return preg_match($params[0], $value) === 1;
    }

    /**
     * Medical Cannabis Specific Validators
     */

    private function validateCpf($field, $value) {
        // Remove non-numeric characters
        $cpf = preg_replace('/[^0-9]/', '', $value);
        
        // Check length
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Validate CPF algorithm
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    private function validateCnpj($field, $value) {
        // Remove non-numeric characters
        $cnpj = preg_replace('/[^0-9]/', '', $value);
        
        // Check length
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Validate CNPJ algorithm
        for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        
        $rest = $sum % 11;
        if ($cnpj[12] != ($rest < 2 ? 0 : 11 - $rest)) {
            return false;
        }
        
        for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        
        $rest = $sum % 11;
        return $cnpj[13] == ($rest < 2 ? 0 : 11 - $rest);
    }

    private function validateCep($field, $value) {
        $cep = preg_replace('/[^0-9]/', '', $value);
        return strlen($cep) === 8;
    }

    private function validatePhone($field, $value) {
        $phone = preg_replace('/[^0-9]/', '', $value);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }

    private function validateBirthdate($field, $value) {
        if (!$this->validateDate($field, $value)) {
            return false;
        }
        
        $birthdate = new DateTime($value);
        $today = new DateTime();
        $age = $today->diff($birthdate)->y;
        
        return $age >= 18;
    }

    private function validateCrm($field, $value) {
        // Basic CRM validation (format: CRM/UF 123456)
        return preg_match('/^CRM\/[A-Z]{2} [0-9]{4,6}$/', $value) === 1;
    }

    private function validateAnvisa($field, $value) {
        // Basic ANVISA registration validation
        return preg_match('/^[0-9]{13}$/', $value) === 1;
    }

    private function validatePrescription($field, $value) {
        // Validate prescription data structure
        if (!is_array($value)) {
            return false;
        }
        
        $requiredFields = ['doctor_name', 'crm', 'uf_crm', 'patient_name', 'prescription_date'];
        foreach ($requiredFields as $field) {
            if (empty($value[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Sanitize input data
     */
    public function sanitize($data, $rules = []) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $rule = $rules[$key] ?? null;
                $sanitized[$key] = $this->cleanValue($value, $rule);
            }
            return $sanitized;
        }
        
        return $this->cleanValue($data);
    }

    private function cleanValue($value, $rule = null) {
        if (is_array($value)) {
            return array_map([$this, 'cleanValue'], $value);
        }
        
        $value = trim($value);
        $value = stripslashes($value);
        
        if ($rule) {
            if (strpos($rule, 'email') !== false) {
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            } elseif (strpos($rule, 'numeric') !== false || strpos($rule, 'integer') !== false) {
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            } elseif (strpos($rule, 'string') !== false) {
                return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            }
        }
        
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
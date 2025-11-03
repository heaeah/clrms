<?php
// classes/BaseModel.php

require_once 'DataValidator.php';

abstract class BaseModel {
    /** @var PDO */
    protected $conn;
    /** @var string */
    protected $table_name;
    /** @var array */
    protected $validation_rules = [];

    public function __construct($conn, $table_name) {
        $this->conn = $conn;
        $this->table_name = $table_name;
    }

    // Basic getter for table name
    public function getTableName() {
        return $this->table_name;
    }

    // Abstract CRUD methods to be implemented by child classes
    abstract public function getAll();
    abstract public function getById($id);
    abstract public function create($data);
    abstract public function update($id, $data);
    abstract public function delete($id);

    /**
     * Validate data using DataValidator
     * @param array $data Data to validate
     * @return array Validated and sanitized data
     */
    protected function validateData($data) {
        if (empty($this->validation_rules)) {
            return $data;
        }
        
        return DataValidator::sanitizeData($data, $this->validation_rules);
    }

    /**
     * Validate a single field
     * @param mixed $value Value to validate
     * @param string $field Field name
     * @return mixed Validated value
     */
    protected function validateField($value, $field) {
        if (!isset($this->validation_rules[$field])) {
            return $value;
        }
        
        $rule = $this->validation_rules[$field];
        
        switch ($rule['type']) {
            case 'string':
                return DataValidator::validateString(
                    $value, 
                    $rule['default'] ?? '', 
                    $rule['maxLength'] ?? 255,
                    $rule['allowHtml'] ?? false
                );
            case 'integer':
                return DataValidator::validateInteger(
                    $value, 
                    $rule['default'] ?? 0, 
                    $rule['min'] ?? 0, 
                    $rule['max'] ?? 999999
                );
            case 'float':
                return DataValidator::validateFloat(
                    $value, 
                    $rule['default'] ?? 0.0, 
                    $rule['min'] ?? 0.0, 
                    $rule['max'] ?? 999999.99
                );
            case 'date':
                return DataValidator::validateDate(
                    $value, 
                    $rule['default'] ?? null, 
                    $rule['format'] ?? 'Y-m-d H:i:s'
                );
            case 'email':
                return DataValidator::validateEmail(
                    $value, 
                    $rule['default'] ?? ''
                );
            case 'phone':
                return DataValidator::validatePhone(
                    $value, 
                    $rule['default'] ?? ''
                );
            case 'enum':
                return DataValidator::validateEnum(
                    $value, 
                    $rule['allowedValues'] ?? [], 
                    $rule['default'] ?? ''
                );
            default:
                return $value;
        }
    }

    /**
     * Perform data integrity check for this model
     * @return array Array of integrity issues
     */
    public function checkDataIntegrity() {
        return DataValidator::performDataIntegrityCheck($this->conn);
    }

    /**
     * Log data integrity issues for this model
     * @param array $issues Array of integrity issues
     */
    protected function logIntegrityIssues($issues) {
        DataValidator::logIntegrityIssues($issues, get_class($this));
    }

    /**
     * Safe database query execution with error handling
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return PDOStatement|false
     */
    protected function safeExecute($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('[BaseModel] Database error in ' . get_class($this) . ': ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    /**
     * Get validation rules for this model
     * @return array Validation rules
     */
    public function getValidationRules() {
        return $this->validation_rules;
    }

    /**
     * Set validation rules for this model
     * @param array $rules Validation rules
     */
    public function setValidationRules($rules) {
        $this->validation_rules = $rules;
    }
} 
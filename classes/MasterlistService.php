<?php
require_once 'Database.php';

class MasterlistService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all active items from a masterlist table
     */
    public function getMasterlist($table, $activeOnly = true) {
        $query = "SELECT * FROM {$table}";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment categories
     */
    public function getEquipmentCategories() {
        return $this->getMasterlist('equipment_categories');
    }
    
    /**
     * Get equipment status options
     */
    public function getEquipmentStatus() {
        return $this->getMasterlist('equipment_status');
    }
    
    /**
     * Get lab locations from labs table
     */
    public function getLabLocations() {
        $query = "SELECT id, lab_name as name, location, capacity FROM labs ORDER BY lab_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get departments
     */
    public function getDepartments() {
        return $this->getMasterlist('departments_master');
    }
    
    /**
     * Get maintenance types
     */
    public function getMaintenanceTypes() {
        return $this->getMasterlist('maintenance_types');
    }
    
    /**
     * Get user roles
     */
    public function getUserRoles() {
        return $this->getMasterlist('user_roles');
    }
    
    /**
     * Validate if a value exists in a masterlist
     */
    public function validateMasterlistValue($table, $field, $value) {
        $query = "SELECT id FROM {$table} WHERE {$field} = :value AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Validate equipment item name against masterlist
     * @param string $category The equipment category
     * @param string $itemName The item name to validate
     * @return bool True if valid, false otherwise
     */
    public function validateEquipmentItemName($category, $itemName) {
        $query = "SELECT id FROM equipment_items_master WHERE category = :category AND item_name = :item_name AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':item_name', $itemName);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get equipment item options for a specific category
     * @param string $category The equipment category
     * @return string HTML options for the dropdown
     */
    public function getEquipmentItemOptions($category = '') {
        $query = "SELECT DISTINCT item_name FROM equipment_items_master WHERE is_active = 1";
        $params = [];
        
        if (!empty($category)) {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY item_name";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $options = '<option value="">Select Item Name</option>';
        foreach ($items as $item) {
            $options .= "<option value=\"{$item}\">{$item}</option>";
        }
        
        return $options;
    }
    
    /**
     * Get equipment items by category
     * @param string $category The equipment category
     * @return array Array of item names
     */
    public function getEquipmentItemsByCategory($category) {
        $query = "SELECT item_name FROM equipment_items_master WHERE category = :category AND is_active = 1 ORDER BY item_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get masterlist options as HTML select options
     */
    public function getSelectOptions($table, $selectedValue = '', $includeEmpty = true, $emptyText = 'Select...') {
        $items = $this->getMasterlist($table);
        $options = '';
        
        if ($includeEmpty) {
            $options .= "<option value=\"\">{$emptyText}</option>";
        }
        
        foreach ($items as $item) {
            $selected = ($selectedValue == $item['name']) ? 'selected' : '';
            $options .= "<option value=\"{$item['name']}\" {$selected}>{$item['name']}</option>";
        }
        
        return $options;
    }
    
    /**
     * Get equipment categories as select options
     */
    public function getEquipmentCategoryOptions($selectedValue = '') {
        return $this->getSelectOptions('equipment_categories', $selectedValue, true, 'Select Category');
    }
    
    /**
     * Get equipment status as select options
     */
    public function getEquipmentStatusOptions($selectedValue = '') {
        return $this->getSelectOptions('equipment_status', $selectedValue, true, 'Select Status');
    }
    
    /**
     * Get lab locations as select options from labs table
     */
    public function getLabLocationOptions($selectedValue = '') {
        $labs = $this->getLabLocations();
        $options = '<option value="">Select Location</option>';
        
        foreach ($labs as $lab) {
            $selected = ($selectedValue == $lab['name']) ? 'selected' : '';
            $options .= "<option value=\"{$lab['name']}\" {$selected}>{$lab['name']}</option>";
        }
        
        return $options;
    }
    
    /**
     * Get departments as select options
     */
    public function getDepartmentOptions($selectedValue = '') {
        return $this->getSelectOptions('departments_master', $selectedValue, true, 'Select Department');
    }
    
    /**
     * Get maintenance types as select options
     */
    public function getMaintenanceTypeOptions($selectedValue = '') {
        return $this->getSelectOptions('maintenance_types', $selectedValue, true, 'Select Type');
    }
    
    /**
     * Get user roles as select options
     */
    public function getUserRoleOptions($selectedValue = '') {
        return $this->getSelectOptions('user_roles', $selectedValue, true, 'Select Role');
    }
    
    /**
     * Get equipment models by category
     */
    public function getEquipmentModelsByCategory($category) {
        $query = "SELECT model_name, manufacturer FROM equipment_models_master 
                  WHERE category = :category AND is_active = 1 
                  ORDER BY model_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment models as select options by category
     */
    public function getEquipmentModelOptions($category = '', $selectedValue = '') {
        $options = '<option value="">Select Model (Optional)</option>';
        
        if (!empty($category)) {
            $models = $this->getEquipmentModelsByCategory($category);
            foreach ($models as $model) {
                $modelName = htmlspecialchars($model['model_name']);
                $manufacturer = htmlspecialchars($model['manufacturer'] ?? '');
                $displayName = $manufacturer ? "$modelName ($manufacturer)" : $modelName;
                $selected = ($modelName === $selectedValue) ? 'selected' : '';
                $options .= "<option value=\"{$modelName}\" {$selected}>{$displayName}</option>";
            }
        }
        
        return $options;
    }
}
?>

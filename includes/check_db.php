<?php
require_once 'config.php';

function checkAndCreateTables() {
    global $db;
    
    try {
        // Check if tables exist
        $tables = [
            'products' => "
                CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    barcode VARCHAR(50) NULL,
                    description TEXT NULL,
                    category_id INT NOT NULL,
                    brand_id INT NULL,
                    unit_id INT NOT NULL,
                    cost_price DECIMAL(15,2) NOT NULL DEFAULT 0,
                    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
                    min_stock INT NOT NULL DEFAULT 0,
                    max_stock INT NOT NULL DEFAULT 0,
                    tax_method ENUM('exclusive', 'inclusive') DEFAULT 'exclusive',
                    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
                    weight DECIMAL(10,2) NULL,
                    length DECIMAL(10,2) NULL,
                    width DECIMAL(10,2) NULL,
                    height DECIMAL(10,2) NULL,
                    manufacturer VARCHAR(255) NULL,
                    model VARCHAR(255) NULL,
                    default_supplier_id INT NULL,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_by INT NULL,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'categories' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    parent_id INT NULL,
                    description TEXT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    sort_order INT DEFAULT 0,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_by INT NULL,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'units' => "
                CREATE TABLE IF NOT EXISTS units (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'brands' => "
                CREATE TABLE IF NOT EXISTS brands (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'taxes' => "
                CREATE TABLE IF NOT EXISTS taxes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    rate DECIMAL(5,2) NOT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'suppliers' => "
                CREATE TABLE IF NOT EXISTS suppliers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_name VARCHAR(255) NOT NULL,
                    contact_name VARCHAR(255) NULL,
                    email VARCHAR(255) NULL,
                    phone VARCHAR(50) NULL,
                    address TEXT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'warehouses' => "
                CREATE TABLE IF NOT EXISTS warehouses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    address TEXT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'settings' => "
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    module VARCHAR(50) NOT NULL,
                    `key` VARCHAR(100) NOT NULL,
                    value TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY module_key (module, `key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            "
        ];

        // Create tables if they don't exist
        foreach ($tables as $tableName => $query) {
            $db->exec($query);
        }

        // Insert default data
        $defaultData = [
            "INSERT IGNORE INTO units (id, name, code, status, created_by) VALUES 
                (1, 'عدد', 'PCS', 'active', 1),
                (2, 'کیلوگرم', 'KG', 'active', 1),
                (3, 'متر', 'M', 'active', 1);",
            
            "INSERT IGNORE INTO settings (module, `key`, value) VALUES 
                ('products', 'default_min_stock', '0'),
                ('products', 'default_max_stock', '999999'),
                ('products', 'default_tax_method', 'exclusive');"
        ];

        foreach ($defaultData as $query) {
            $db->exec($query);
        }

        return true;

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

// Check and create tables
if (!checkAndCreateTables()) {
    die("Error setting up database tables. Check error log for details.");
}
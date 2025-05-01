-- اطمینان از عدم وجود جداول قبلی
DROP TABLE IF EXISTS tax_units;
DROP TABLE IF EXISTS tax_types;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS currencies;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_prices;
DROP TABLE IF EXISTS product_inventory;
DROP TABLE IF EXISTS products;

-- ایجاد جدول دسته‌بندی‌ها
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول ارزها
CREATE TABLE currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول انواع مالیات
CREATE TABLE tax_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول واحدهای مالیاتی
CREATE TABLE tax_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول محصولات
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    accounting_code VARCHAR(50),
    barcodes TEXT,
    category_id INT,
    main_unit VARCHAR(50) NOT NULL,
    sub_unit VARCHAR(50),
    unit_description TEXT,
    stock_control TINYINT(1) DEFAULT 0,
    current_stock INT DEFAULT 0,
    min_order INT DEFAULT 1,
    lead_time INT DEFAULT 0,
    sales_description TEXT,
    purchase_description TEXT,
    sales_tax_enabled TINYINT(1) DEFAULT 0,
    sales_tax_rate DECIMAL(5,2) DEFAULT 9.00,
    purchase_tax_enabled TINYINT(1) DEFAULT 0,
    purchase_tax_rate DECIMAL(5,2) DEFAULT 9.00,
    tax_type_id INT,
    tax_unit_id INT,
    tax_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (tax_type_id) REFERENCES tax_types(id) ON DELETE SET NULL,
    FOREIGN KEY (tax_unit_id) REFERENCES tax_units(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول تصاویر محصول
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول قیمت‌های محصول
CREATE TABLE product_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price_type VARCHAR(50) NOT NULL,
    currency_id INT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول موجودی محصول
CREATE TABLE product_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity INT DEFAULT 0,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- درج داده‌های اولیه برای ارزها
INSERT INTO currencies (code, name, symbol) VALUES 
('IRR', 'ریال', '﷼'),
('USD', 'دلار', '$'),
('EUR', 'یورو', '€');

-- درج داده‌های اولیه برای انواع مالیات
INSERT INTO tax_types (name, description) VALUES 
('ارزش افزوده', 'مالیات بر ارزش افزوده'),
('تکلیفی', 'مالیات تکلیفی'),
('مستقیم', 'مالیات مستقیم');

-- درج داده‌های اولیه برای واحدهای مالیاتی
INSERT INTO tax_units (name, description) VALUES 
('اداره مالیات تهران', 'واحد مالیاتی تهران'),
('اداره مالیات اصفهان', 'واحد مالیاتی اصفهان'),
('اداره مالیات مشهد', 'واحد مالیاتی مشهد');

-- درج داده‌های اولیه برای دسته‌بندی‌ها
INSERT INTO categories (name, parent_id) VALUES 
('کالاهای دیجیتال', NULL),
('لوازم جانبی موبایل', 1),
('لوازم خانگی', NULL),
('صوتی و تصویری', 3),
('ابزار و یراق', NULL);
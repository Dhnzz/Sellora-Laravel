-- Central Users Table for Authentication (used by all roles)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
-- Description:
-- Stores authentication credentials for all system users (Owner, Admin, Sales, Warehouse Manager, Customer).
-- Role management is handled separately by Spatie Laravel-permission.


-- Owner Table (Super Admin)
CREATE TABLE owners (
    user_id BIGINT UNSIGNED PRIMARY KEY,  -- PK, FK to users.id, 1:1
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_owners_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Description: Stores owner personal data. One owner per user_id.


-- Admin Table
CREATE TABLE admins (
    user_id BIGINT UNSIGNED PRIMARY KEY, -- PK, FK to users.id, 1:1
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_admins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Description: Stores admin personal data. Allows CRUD operations for many modules.


-- Sales Agent Table
CREATE TABLE sales_agents (
    user_id BIGINT UNSIGNED PRIMARY KEY, -- PK, FK to users.id, 1:1
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Description: Stores sales agent personal data. Sales agents create POs and manage customers.


-- Warehouse Manager Table
CREATE TABLE warehouse_managers (
    user_id BIGINT UNSIGNED PRIMARY KEY, -- PK, FK to users.id, 1:1
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_warehouse_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Description: Stores warehouse managers personal data. Manage stock and incoming goods.


-- Customers Table (Registered Customers)
CREATE TABLE customers (
    user_id BIGINT UNSIGNED PRIMARY KEY, -- PK, FK to users.id, 1:1
    store_name VARCHAR(255) NOT NULL,
    store_address TEXT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    responsible_sales_agent_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_customers_sales_agent FOREIGN KEY (responsible_sales_agent_user_id) REFERENCES sales_agents(user_id) ON DELETE RESTRICT
);
-- Description:
-- Registered customers owning a store.
-- responsible_sales_agent_user_id: Links to sales_agents.user_id to assign a responsible sales agent.
-- Sales agents can only assign themselves (business logic to enforce in app).


-- Product Units Table
CREATE TABLE product_units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_name VARCHAR(50) NOT NULL UNIQUE,  -- e.g., pcs, pack, carton, sachet
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
-- Description: Defines measurement units for products.


-- Product Brands Table (product groups by brand)
CREATE TABLE product_brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
-- Description: Brand / group of products (e.g., Nestle).


-- Products Table
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_brand_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    minimum_selling_unit_id BIGINT UNSIGNED NOT NULL, -- FK to product_units.id
    conversion_factors JSON NULL, 
      -- JSON format example: { "pack": 10, "carton": 60 }
      -- keys are unit names, values are integer factors to minimum_selling_unit
    selling_price DECIMAL(12,4) NOT NULL CHECK (selling_price >= 0),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_products_brand FOREIGN KEY (product_brand_id) REFERENCES product_brands(id) ON DELETE RESTRICT,
    CONSTRAINT fk_products_min_unit FOREIGN KEY (minimum_selling_unit_id) REFERENCES product_units(id) ON DELETE RESTRICT,
    UNIQUE (product_brand_id, product_name) -- unique product names within a brand
);
-- Description:
-- Products identified by brand & name.
-- Minimum selling unit FK for unit apps.
-- Conversion factors for larger units stored as JSON.


-- Suppliers Table
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL UNIQUE,
    supplier_address TEXT NULL,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
-- Description:
-- Supplier details used to source incoming goods.


-- Stocks Table
CREATE TABLE stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL UNIQUE, 
      -- One stock record per product (office stock),
      -- can be extended per warehouse/location if multi-warehouse needed later
    quantity INT NOT NULL DEFAULT 0, -- stored in minimum selling units
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
-- Description:
-- Tracks office stock quantity per product (in minimal selling units).
-- Warehouse/distribution points stock could be a future extension.


-- Purchase Orders (POs)
CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    sales_agent_user_id BIGINT UNSIGNED NOT NULL, -- Sales agent responsible on PO creation
    order_date DATE NOT NULL, -- PO creation date
    delivery_due_date DATE NOT NULL, -- delivery ≤ 7 days after order_date
    status ENUM('pending','confirmed','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
    discount_percent DECIMAL(5,2) NULL CHECK (discount_percent >= 0 AND discount_percent <= 100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_po_customer FOREIGN KEY (customer_user_id) REFERENCES customers(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_po_sales_agent FOREIGN KEY (sales_agent_user_id) REFERENCES sales_agents(user_id) ON DELETE RESTRICT,
    CONSTRAINT chk_po_dates CHECK (delivery_due_date >= DATE_ADD(order_date, INTERVAL 3 DAY) AND delivery_due_date <= DATE_ADD(order_date, INTERVAL 7 DAY))
);
-- Description:
-- Customer purchase orders with timeline constraints (≥3 days advance, delivery within 7 days).
-- discount_percent applied by Admin during confirmation.
-- PO status tracks lifecycle.


-- PO Items Table (ordered items within PO)
CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0), -- quantity ordered (minimal unit)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE(purchase_order_id, product_id)
);
-- Description:
-- Lists products and quantities ordered in each PO.


-- Sales Transactions (Invoice) Table
-- These are created by Admin confirming POs.
CREATE TABLE sales_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL UNIQUE,
      -- 1:1 with PO in confirmed state
    admin_user_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    discount_percent DECIMAL(5,2) NULL CHECK (discount_percent >= 0 AND discount_percent <= 100),
    total_amount DECIMAL(15,4) NOT NULL CHECK (total_amount >= 0), -- final invoice amount after discount
    payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    delivery_confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_transaction_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaction_admin FOREIGN KEY (admin_user_id) REFERENCES admins(user_id) ON DELETE RESTRICT
);
-- Description:
-- Invoice records after Admin confirms PO.
-- One sales transaction per PO.
-- Discounts applied here.
-- Payment on delivery: payment_status updated upon payment.


-- Sales Transaction Items Table
CREATE TABLE sales_transaction_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_ordered INT NOT NULL CHECK (quantity_ordered > 0),
    quantity_sold INT NOT NULL CHECK (quantity_sold >= 0), -- can be less if admin adjusts for discrepancies
    unit_price DECIMAL(12,4) NOT NULL CHECK (unit_price >= 0), -- price per minimum unit (before discount)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_sti_transaction FOREIGN KEY (sales_transaction_id) REFERENCES sales_transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_sti_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE (sales_transaction_id, product_id)
);
-- Description:
-- Records actual quantities sold per product.
-- quantity_sold may be reduced at confirmation if discrepancy found.
-- Unit price stored for history.


-- Product Returns Table (Customer initiated returns)
CREATE TABLE product_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    sales_transaction_id BIGINT UNSIGNED NOT NULL,
    return_date DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_return_customer FOREIGN KEY (customer_user_id) REFERENCES customers(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_transaction FOREIGN KEY (sales_transaction_id) REFERENCES sales_transactions(id) ON DELETE RESTRICT
);
-- Description:
-- Customer product returns referencing original sales transaction.


-- Product Return Items Table
CREATE TABLE product_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_returned INT NOT NULL CHECK (quantity_returned > 0),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_pri_return FOREIGN KEY (product_return_id) REFERENCES product_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_pri_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE(product_return_id, product_id)
);
-- Description:
-- Items and quantities returned within a product return.


-- Incoming Supplier Transactions Table (Warehouse Manager)
CREATE TABLE incoming_supplier_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_manager_user_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_incoming_manager FOREIGN KEY (warehouse_manager_user_id) REFERENCES warehouse_managers(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_incoming_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);
-- Description:
-- Records incoming goods deliveries from suppliers to warehouse.


-- Incoming Supplier Transaction Items Table
CREATE TABLE incoming_supplier_transaction_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incoming_supplier_transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_received INT NOT NULL CHECK (quantity_received > 0), -- minimal selling unit
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_incoming_item_transaction FOREIGN KEY (incoming_supplier_transaction_id) REFERENCES incoming_supplier_transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_incoming_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE (incoming_supplier_transaction_id, product_id)
);
-- Description:
-- Details of received items and quantities for each incoming transaction.


-- Stock Adjustments Table (Warehouse Manager corrections)
CREATE TABLE stock_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_manager_user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    adjustment_reason TEXT NOT NULL,
    quantity_adjusted INT NOT NULL, -- positive or negative number
    adjustment_date DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_stock_adj_manager FOREIGN KEY (warehouse_manager_user_id) REFERENCES warehouse_managers(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_stock_adj_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);
-- Description:
-- Records manual stock adjustments due to discrepancies.


-- Product Bundles Table
CREATE TABLE product_bundles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bundle_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
-- Description:
-- Bundled products offers for customers.


-- Product Bundle Items Table
CREATE TABLE product_bundle_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_bundle_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0), -- in minimal selling unit
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_bundle_item_bundle FOREIGN KEY (product_bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
    CONSTRAINT fk_bundle_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE (product_bundle_id, product_id)
);
-- Description:
-- Specifies what products & quantities are included in each bundle.


-- Monthly Book Closing Table
CREATE TABLE monthly_book_closings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    closing_month YEAR(4) NOT NULL,
    closing_year YEAR(4) NOT NULL,
    closed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT uniq_month_year UNIQUE (closing_month, closing_year),
    CONSTRAINT fk_closing_admin FOREIGN KEY (closed_by_admin_user_id) REFERENCES admins(user_id) ON DELETE RESTRICT
);
-- Description:
-- Apart from app logic,
-- Once closed, all transactions with invoice_date in that month/year are immutable.


---------------------------------------------------------------------------------------------------
-- Indexing Guidelines:
-- users.email: UNIQUE index included for authentication speed.
-- Foreign keys automatically create indexes for join optimization.
-- products: index on product_brand_id for filtering by brand.
-- purchase_orders: index on customer_user_id to quickly find POs by customer.
-- sales_transactions.invoice_date: index for reporting and revenue prediction queries.
-- stocks.product_id: UNIQUE index ensures quick stock lookups.
-- product_units.unit_name: UNIQUE index for quick lookup.
-- Optional: Create composite indexes on purchase_order_items (purchase_order_id, product_id)
-- for fast item retrieval during PO processing.

---------------------------------------------------------------------------------------------------
-- Relationships Summary:
-- 1:1 between users and role-tables (owners, admins, sales_agents, warehouse_managers, customers), via user_id unique FK.
-- Customers linked to sales_agents via responsible_sales_agent_user_id.
-- Products grouped by brands; each product has minimal selling unit.
-- Stocks one per product (office stock).
-- POs created by customers, linked to responsible sales agents.
-- POs contain multiple products with quantities.
-- Sales transactions (invoice) made by admins confirm POs, may adjust quantities/prices.
-- Product returns link to customer and original sales transaction.
-- Incoming goods linked to warehouse managers and suppliers.
-- Stock adjustments linked to warehouse managers and products.
-- Product bundles define special grouped offers.

---------------------------------------------------------------------------------------------------
-- Future Expansion Considerations:
-- 1. Multi-warehouse stock locations: Add location/warehouse tables and stock per location.
-- 2. Detailed payment records & payment methods table for advanced financial tracking.
-- 3. Audit logs tables to track changes/deletions on critical tables.
-- 4. Support for multi-currency and taxations.
-- 5. Versioning for product price changes and bundle offers.
-- 6. Integration tables for 3rd party delivery or accounting apps.
-- 7. Add triggers or app-level jobs to update stock on sales, returns, incoming transactions, and adjustments to maintain stock integrity.

---------------------------------------------------------------------------------------------------
-- End of Schema Design for Sellora Distribution Management Application
-- PASEREXPRESS DATABASE SCHEMA (FINAL)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===============================
-- USERS TABLE
-- ===============================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    role ENUM('pelanggan','driver','admin') DEFAULT 'pelanggan',
    is_admin TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===============================
-- ADDRESSES TABLE
-- ===============================
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(100),
    alamat TEXT NOT NULL,
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===============================
-- ORDERS TABLE
-- ===============================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    driver_id INT DEFAULT NULL,

    pickup_address TEXT NOT NULL,
    dropoff_address TEXT NOT NULL,

    harga INT NOT NULL,
    jarak_km DECIMAL(10,2) NOT NULL,

    status ENUM('pending','diproses','dikirim','selesai','batal') DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (pelanggan_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===============================
-- DRIVERS TABLE
-- ===============================
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kendaraan VARCHAR(100),
    plat_nomor VARCHAR(50),
    siap_kerja TINYINT(1) DEFAULT 1,
    rating DECIMAL(3,2) DEFAULT 5.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS = 1;

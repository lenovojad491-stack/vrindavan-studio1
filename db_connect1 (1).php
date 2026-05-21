<?php
// ============================================================
// VRINDAVAN GLAM STUDIO - Database Connection File
// File: db_connect.php
// Place this file in the same folder as vrindavan_studio.php
// ============================================================

// ---- DATABASE CONFIGURATION ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vrindavan_studio');

// ---- DATABASE CONNECTION & SETUP ----
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            error_log("DB Connection Failed: " . $conn->connect_error);
            return null; // Returns null → app falls back to demo mode
        }

        // Create database if not exists
        $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $conn->select_db(DB_NAME);

        // Set charset
        $conn->set_charset('utf8mb4');

        // ---- CREATE TABLES ----

        // Users table
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100)  NOT NULL,
            email      VARCHAR(100)  UNIQUE NOT NULL,
            phone      VARCHAR(20),
            password   VARCHAR(255)  NOT NULL,
            role       ENUM('admin','user') DEFAULT 'user',
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Bookings table
        $conn->query("CREATE TABLE IF NOT EXISTS bookings (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT,
            name       VARCHAR(100)  NOT NULL,
            email      VARCHAR(100)  NOT NULL,
            phone      VARCHAR(20)   NOT NULL,
            service    VARCHAR(100)  NOT NULL,
            date       DATE          NOT NULL,
            time_slot  VARCHAR(30)   NOT NULL,
            message    TEXT,
            status     ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ---- SEED DEFAULT ADMIN (if not exists) ----
        $adminCheck = $conn->query("SELECT id FROM users WHERE email='admin@vrindavan.com'");
        if ($adminCheck && $adminCheck->num_rows === 0) {
            $adminPass = password_hash('Admin@123', PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (name, email, phone, password, role) VALUES 
                ('Studio Admin', 'admin@vrindavan.com', '9999999999', '$adminPass', 'admin')");
        }
    }
    return $conn;
}

// ---- QUERY HELPER FUNCTIONS ----

/**
 * Get all bookings (Admin use), optionally filtered by status.
 */
function getBookings($db, $filter = '') {
    if (!$db) return [];
    $sql = "SELECT b.*, u.name AS user_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id";
    if ($filter) {
        $sql .= " WHERE b.status = '" . $db->real_escape_string($filter) . "'";
    }
    $sql .= " ORDER BY b.created_at DESC";
    $r = $db->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get bookings belonging to a specific user.
 */
function getUserBookings($db, $uid) {
    if (!$db) return [];
    $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all registered users.
 */
function getAllUsers($db) {
    if (!$db) return [];
    $r = $db->query("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

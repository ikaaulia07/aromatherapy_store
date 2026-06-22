CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    telepon VARCHAR(20),
    alamat TEXT,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contoh data user/admin (password: 1234)
INSERT INTO users (nama_lengkap, email, password, telepon, alamat, role) VALUES 
('Ika Admin', 'ika@admin.com', '$2y$10$cy0Jbsyl49YUpjuM7V1uWumJhP5jbJhRcNEHl5uvmMGmTvYUFK4hq', '081234567890', 'Kantor Aromatherapy Store', 'admin');


CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100)
);

-- Contoh data kategori
INSERT INTO kategori (nama_kategori) VALUES 
('Essential Oil'),
('Diffuser'),
('Aromatherapy Candle'),
('Reed Diffuser');

CREATE TABLE produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT,
    nama_produk VARCHAR(150),
    deskripsi TEXT,
    harga DECIMAL(10,2),
    stok INT,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
);

CREATE TABLE keranjang (
    id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    id_produk INT,
    jumlah INT,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk)
);

CREATE TABLE pesanan (
    id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    tanggal_pesanan DATETIME,
    total_harga DECIMAL(12,2),
    status ENUM('Pending', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan') DEFAULT 'Pending',
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE detail_pesanan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT,
    id_produk INT,
    jumlah INT,
    harga DECIMAL(10,2),
    subtotal DECIMAL(12,2),
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan),
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk)
);

CREATE TABLE pembayaran (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT,
    metode_pembayaran VARCHAR(50),
    bukti_pembayaran VARCHAR(255),
    tanggal_bayar DATETIME,
    status_verifikasi ENUM('Menunggu', 'Diterima', 'Ditolak') DEFAULT 'Menunggu',
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan)
);

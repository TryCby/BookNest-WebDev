CREATE DATABASE IF NOT EXISTS booknest_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE booknest_db;

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 20,
    cover_image VARCHAR(255) NOT NULL,
    is_bestseller TINYINT(1) DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    net_total DECIMAL(10,2) NOT NULL,
    slip_image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'paid', 'shipped', 'rejected') DEFAULT 'pending',
    tracking_no VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price_each DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

INSERT INTO categories (category_id, category_name) VALUES
(1, 'ทักษะการคิด'),
(2, 'สื่อสารและการทำงาน'),
(3, 'ผู้นำและการบริหาร'),
(4, 'ชีวิตและครอบครัว'),
(5, 'อารยธรรมและการสร้างชาติ');

INSERT INTO books (category_id, title, price, stock, cover_image, is_bestseller) VALUES
(1, 'การคิดเชิงวิพากษ์', 200.00, 20, 'Image/Thinking/Thinking_1.jpg', 1),
(1, 'การคิดเชิงวิเคราะห์', 200.00, 20, 'Image/Thinking/Thinking_2.jpg', 0),
(1, 'การคิดเชิงสร้างสรรค์', 200.00, 20, 'Image/Thinking/Thinking_3.jpg', 0),
(1, 'การคิดเชิงประยุกต์', 200.00, 20, 'Image/Thinking/Thinking_4.jpg', 0),
(1, 'การคิดเชิงกลยุทธ์', 200.00, 20, 'Image/Thinking/Thinking_5.jpg', 0),
(2, '20 กฎ มธุรสวาจา', 350.00, 15, 'Image/Communication/Communication_1.jpg', 0),
(2, 'นักพูดขั้นเทพ', 160.00, 25, 'Image/Communication/Communication_2.jpg', 1),
(2, 'นักฟังขั้นเซียน', 160.00, 20, 'Image/Communication/Communication_3.jpg', 0),
(2, 'พูดชนะใจ', 175.00, 20, 'Image/Communication/Communication_4.jpg', 0),
(2, 'เรียนให้รู้ใช้ข้อมูลอย่างผู้ชนะ', 185.00, 20, 'Image/Communication/Communication_5.jpg', 0),
(3, 'ผู้นำสร้างได้', 210.00, 20, 'Image/Leadership/Leadership_1.jpg', 1),
(3, 'ผู้บริหารสร้างได้ สไตล์ ดร.แดน โมเดลบริหารลงลึก', 170.00, 20, 'Image/Leadership/Leadership_2.jpg', 0),
(3, 'นำและบริหารยามวิกฤต : บทเรียนจากโควิด-19', 270.00, 15, 'Image/Leadership/Leadership_3.jpg', 0),
(3, 'โมเดลทายาท 3ท', 250.00, 15, 'Image/Leadership/Leadership_4.jpg', 0),
(3, 'โมเดลประสิทธิภาพ 8E', 285.00, 20, 'Image/Leadership/Leadership_5.jpg', 0),
(4, 'พลังสุดนุ่มนวล : หนุนเนื่องความสำเร็จ', 285.00, 20, 'Image/Lifestyle/Lifestyle_1.jpg', 0),
(4, 'บันไดทองชีวิต : สิบพลังก้าวขึ้นสู่ความสำเร็จ', 250.00, 20, 'Image/Lifestyle/Lifestyle_2.jpg', 1),
(4, 'ผลึกชีวิต : 123 ความคิดเพื่อความสำเร็จในชีวิตและงาน', 250.00, 20, 'Image/Lifestyle/Lifestyle_3.jpg', 0),
(4, 'ยอดคน กุญแจแห่งชัยชนะ 20 ดอก', 225.00, 20, 'Image/Lifestyle/Lifestyle_4.jpg', 0),
(4, 'เลือกคู่ ครองรัก', 150.00, 25, 'Image/Lifestyle/Lifestyle_5.jpg', 0),
(5, 'SSC ชุมชนยั่งยืนด้วยตนเอง', 275.00, 15, 'Image/Civilization/Civilization_1.jpg', 0),
(5, 'คนดีสร้างได้ : โมเดลบริบูรณ์ธรรม', 225.00, 20, 'Image/Civilization/Civilization_2.jpg', 1),
(5, 'คนเก่งสร้างได้ : อารยโมเดล สรรถนะ KSL31220', 225.00, 20, 'Image/Civilization/Civilization_3.jpg', 0),
(5, 'คนกล้าสร้างได้ : โมเดลบ้านสามหลัง', 225.00, 20, 'Image/Civilization/Civilization_4.jpg', 0),
(5, 'อารยอีโก้', 250.00, 20, 'Image/Civilization/Civilization_5.jpg', 0);

INSERT INTO users (user_id, fullname, email, password, phone, address, role) VALUES
(1, 'Admin BookNest', 'admin@booknest.com', 'admin1234', '0812345678', 'สำนักงานใหญ่ กรุงเทพฯ', 'admin'),
(2, 'สมชาย สุขสบาย', 'somchai@gmail.com', 'pass1234', '0891112222', '123/45 จตุจักร กรุงเทพฯ', 'customer'),
(3, 'สมหญิง จริงใจ', 'somying@gmail.com', 'pass1234', '0893334444', '88/9 ต.สุเทพ เชียงใหม่', 'customer');

INSERT INTO orders (order_id, user_id, subtotal, discount_amount, shipping_fee, net_total, slip_image, status, tracking_no) VALUES
(1, 2, 635.00, 63.50, 0.00, 571.50, 'slip1.jpg', 'paid', 'TH0123456789'),
(2, 3, 570.00, 0.00, 0.00, 570.00, 'slip2.jpg', 'pending', NULL),
(3, 2, 450.00, 0.00, 50.00, 500.00, 'slip3.jpg', 'pending', NULL);

INSERT INTO order_items (order_id, book_id, quantity, price_each) VALUES
(1, 4, 1, 200.00),
(1, 10, 1, 185.00),
(1, 17, 1, 250.00),
(2, 1, 1, 200.00),
(2, 6, 1, 350.00),
(3, 2, 1, 200.00),
(3, 18, 1, 250.00);
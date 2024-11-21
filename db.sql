
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(255),
    second_password VARCHAR(255),
    balance int default 0,
    email varchar(45),
    role ENUM('user', 'admin') DEFAULT 'user' NOT NULL
);
CREATE TABLE tbl_permissions (
    user_id INT NOT NULL,
    permission VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE tbl_card (
    id_card INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_number VARCHAR(30) NOT NULL,
    expDate VARCHAR(30) NOT NULL,
    cvv VARCHAR(3) NOT NULL,
    firstName VARCHAR(30) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    status ENUM('0', '1', '2') DEFAULT '0' NOT NULL,
    total_amount_success INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE tbl_history (
    id_history INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(255),
    id_card INT,
    otp varchar(30),
    status ENUM('0', '1', '2') DEFAULT '0' NOT NULL,
    amount INT,
    address_wallet VARCHAR(255),
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_card) REFERENCES tbl_card(id_card),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE tbl_fee (
    id_fee INT AUTO_INCREMENT PRIMARY KEY,
    fix int,
    rate int
);

create table tbl_history_balance (
 id int auto_increment primary key,
 balance_fluctuation int not null,
 user_id int not null,
 transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
 id_history int not null,
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (id_history) REFERENCES tbl_history(id_history)
);
INSERT INTO users (username, password, role) VALUES ('user', 123, 'user');
INSERT INTO users (username, password, role) VALUES ('admin', 123, 'admin');

update users set balance = 100000000 where id = 1;

INSERT INTO tbl_permissions (user_id, permission) VALUES
((SELECT id FROM users WHERE username = 'admin'), 'manage_users'),
((SELECT id FROM users WHERE username = 'admin'), 'approve_card_withdraw'),
((SELECT id FROM users WHERE username = 'admin'), 'approve_account_withdraw'),
((SELECT id FROM users WHERE username = 'admin'), 'approve_add_card');

-- Bỏ trường lastName
ALTER TABLE tbl_card 
DROP COLUMN lastName;

-- Đổi tên trường firstName thành card_name
ALTER TABLE tbl_card 
CHANGE COLUMN firstName card_name varchar(30) NOT NULL;

-- Bổ sung trường issue_date
ALTER TABLE tbl_card 
ADD COLUMN issue_date VARCHAR(30) DEFAULT NULL AFTER billing_address;

-- Chuyển trường cvv thành không bắt buộc
ALTER TABLE tbl_card 
MODIFY COLUMN cvv varchar(3) DEFAULT NULL;

-- Bổ sung thêm trường card_type
ALTER TABLE tbl_card 
ADD COLUMN card_type ENUM('ATM', 'VISA nội địa', 'VISA quốc tế') DEFAULT NULL AFTER issue_date;
-- Xóa khóa ngoại từ tbl_card
ALTER TABLE tbl_card DROP FOREIGN KEY tbl_card_ibfk_1;


-- Bỏ trường otp_card
ALTER TABLE tbl_history 
DROP COLUMN otp_card;

-- Thêm trường real_with_draw_amount
ALTER TABLE tbl_history 
ADD COLUMN real_with_draw_amount INT DEFAULT NULL AFTER amount;


-- Xóa khóa ngoại từ tbl_history
ALTER TABLE tbl_history DROP FOREIGN KEY tbl_history_ibfk_1;
ALTER TABLE tbl_history DROP FOREIGN KEY tbl_history_ibfk_2;

-- Xóa khóa ngoại từ tbl_permissions
ALTER TABLE tbl_permissions DROP FOREIGN KEY tbl_permissions_ibfk_1;

-- Xóa khóa ngoại từ tbl_history_balance
ALTER TABLE tbl_history_balance DROP FOREIGN KEY tbl_history_balance_ibfk_1;
ALTER TABLE tbl_history_balance DROP FOREIGN KEY tbl_history_balance_ibfk_2;


-- Thêm index vào cột user_id trong tbl_card
CREATE INDEX idx_user_id ON tbl_card(user_id);

-- Thêm index vào cột id_card trong tbl_history
CREATE INDEX idx_id_card ON tbl_history(id_card);

-- Thêm index vào cột user_id trong tbl_history
CREATE INDEX idx_user_id_history ON tbl_history(user_id);

-- Thêm index vào cột user_id trong tbl_permissions
CREATE INDEX idx_user_id_permissions ON tbl_permissions(user_id);

-- Thêm index vào cột user_id và id_history trong tbl_history_balance
CREATE INDEX idx_user_id_balance ON tbl_history_balance(user_id);
CREATE INDEX idx_id_history_balance ON tbl_history_balance(id_history);



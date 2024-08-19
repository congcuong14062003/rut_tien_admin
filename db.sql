
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
-- MySQL schema for ChatBot Messaging

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) UNIQUE NOT NULL,
  password_hash VARCHAR(191) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) DEFAULT NULL,
  is_group TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE conversation_users (
  conversation_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY(conversation_id, user_id)
);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  user_id INT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE conversation_bot_settings (
  conversation_id INT PRIMARY KEY,
  bot_enabled TINYINT(1) DEFAULT 1
);

-- Create a bot user seed (used to persist bot messages)
INSERT INTO users (name,email,password_hash) VALUES ('Bot','bot@local','') ON DUPLICATE KEY UPDATE email=email;

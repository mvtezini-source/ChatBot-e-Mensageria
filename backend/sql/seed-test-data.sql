-- Seed data for local testing
-- Run this after creating the schema: mysql -u root -p chat < seed-test-data.sql

-- Insert test users
INSERT INTO users (name,email,password_hash) VALUES 
('Alice', 'alice@test.local', '$2y$10$abcdefghijklmnopqrstuvwxyz'),  -- password: password123
('Bob', 'bob@test.local', '$2y$10$abcdefghijklmnopqrstuvwxyz')
ON DUPLICATE KEY UPDATE email=email;

-- Insert test conversations
INSERT INTO conversations (title,is_group,created_at) VALUES 
('Chat com Bot', 0, NOW()),
('Grupo Geral', 1, NOW())
ON DUPLICATE KEY UPDATE title=title;

-- Add users to conversations
INSERT INTO conversation_users (conversation_id,user_id) VALUES 
(1, 1), (1, 2), (2, 1), (2, 2)
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Enable bot for both conversations
INSERT INTO conversation_bot_settings (conversation_id, bot_enabled) VALUES 
(1, 1), (2, 1)
ON DUPLICATE KEY UPDATE bot_enabled=bot_enabled;

-- Insert some welcome messages
INSERT INTO messages (conversation_id,user_id,content,created_at) VALUES 
(1, 1, 'Oi! Bem-vindo ao chat com bot', NOW()),
(2, 2, 'Olá grupo!', NOW())
ON DUPLICATE KEY UPDATE content=content;

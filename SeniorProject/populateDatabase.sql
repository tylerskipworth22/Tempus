-- Users
INSERT INTO Users (username, email, password_hash, role) VALUES
('alice', 'alice@example.com', 'hash_pass_alice', 'user'),
('bob', 'bob@example.com', 'hash_pass_bob', 'moderator'),
('charlie', 'charlie@example.com', 'hash_pass_charlie', 'user'),
('dale', 'dale@example.com', 'hash_pass_dale', 'user'),
('tskipworth', 'skip@example.com', 'hash_pass_skip', 'admin');

-- Capsules
INSERT INTO Capsule (title, description, state, status, release_date, isReviewed) VALUES
('My First Capsule', 'A collection of memories from 2020', 'released', 'approved', '2025-12-01', TRUE),
('Travel Vlog', 'Videos from my trip to Japan', 'locked', 'pending', '2025-11-10', FALSE),
('Music Collection', 'Audio clips from local bands', 'draft', 'pending', '2025-12-25', FALSE),
('Family Photos', 'Old family pictures digitized', 'locked', 'approved', '2025-10-30', TRUE);

-- User_Capsules
INSERT INTO User_Capsules (user_id, capsule_id, role) VALUES
(1, 1, 'owner'), 
(2, 2, 'owner'), 
(3, 3, 'owner'),
(4, 4, 'owner'),
(1, 4, 'contributor'), 
(4, 2, 'contributor');

-- Media Types
INSERT INTO MediaType (name, file_type, max_size_mb) VALUES
('image', 'jpg,jpeg,png', 10),
('video', 'mp4,mov,avi', 200),
('audio', 'mp3,wav,aac', 50),
('document', 'pdf,docx,txt', 20);

-- Media
INSERT INTO Media (capsule_id, uploader_id, media_type_id, filename, file_path, file_size, upload_date, description) VALUES
(1, 1, 1, 'beach_photo.jpg', '/uploads/alice/beach_photo.jpg', 5, NOW(), 'Sunny day at the beach with friends.'),
(2, 2, 2, 'tokyo_vlog.mp4', '/uploads/bob/tokyo_vlog.mp4', 190, NOW(), 'First part of my Tokyo trip vlog.'),
(3, 3, 3, 'indie_song.mp3', '/uploads/charlie/indie_song.mp3', 45, NOW(), 'A live track from a local indie band.'),
(4, 4, 1, 'family_reunion.jpg', '/uploads/dale/family_reunion.jpg', 7, NOW(), 'Our family reunion last summer.'),
(4, 4, 2, 'family_reunion.mp4', '/uploads/dale/family_reunion.mp4', 150, NOW(), 'Video footage from the reunion.'),
(2, 4, 2, 'tokyo_attraction.mp4', '/uploads/dale/tokyo_attraction.mp4', 176, NOW(), 'Short video from Tokyo Tower.');

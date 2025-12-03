CREATE TABLE Users (
	user_id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) NOT NULL UNIQUE,
	email VARCHAR(100) NOT NULL UNIQUE,
	role ENUM('user', 'moderator', 'admin') NOT NULL default 'user'
);

CREATE TABLE Capsule (
    capsule_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    state ENUM('draft','locked','released') DEFAULT 'draft',
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    release_date DATETIME,
    isReviewed BOOLEAN DEFAULT FALSE,
    rejection_reason TEXT DEFAULT NULL
);

CREATE TABLE User_Capsules (
    user_capsule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    capsule_id INT NOT NULL,
    role ENUM('owner','contributor') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (capsule_id) REFERENCES Capsule(capsule_id)
);

CREATE TABLE Capsule_Invites (
    invite_id INT AUTO_INCREMENT PRIMARY KEY,
    capsule_id INT NOT NULL,
    inviter_id INT NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    status ENUM('pending','accepted','declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (capsule_id) REFERENCES Capsule(capsule_id),
    FOREIGN KEY (inviter_id) REFERENCES Users(user_id)
);

CREATE TABLE MediaType (
	media_type_id INT AUTO_INCREMENT PRIMARY KEY,
	name ENUM('image', 'video', 'audio', 'document') NOT NULL,
	file_type VARCHAR(50) NOT NULL,
	max_size_mb INT NOT NULL
);

CREATE TABLE Media (
	media_id INT AUTO_INCREMENT	PRIMARY KEY,
	capsule_id INT NOT NULL,
	uploader_id INT NOT NULL,
	media_type_id INT NOT NULL,
	filename VARCHAR(255) NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	file_size INT NOT NULL,
	upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255) DEFAULT NULL,
	FOREIGN KEY (capsule_id) REFERENCES Capsule(capsule_id),
	FOREIGN KEY (uploader_id) REFERENCES Users(user_id),
	FOREIGN KEY (media_type_id) REFERENCES MediaType(media_type_id)
);

CREATE TABLE ModerationWarnings (
    warning_id INT AUTO_INCREMENT PRIMARY KEY,
    capsule_id INT NOT NULL,
    moderator_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    date_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (capsule_id) REFERENCES Capsule(capsule_id),
    FOREIGN KEY (moderator_id) REFERENCES Users(user_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE Flag (
    flag_id INT AUTO_INCREMENT PRIMARY KEY,
    capsule_id INT NOT NULL,
    user_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (capsule_id) REFERENCES Capsule(capsule_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

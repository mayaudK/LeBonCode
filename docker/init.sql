-- create the user and give privileges to the database
CREATE USER 'leboncode-user'@'%' IDENTIFIED BY 'leboncode-password';
GRANT ALL PRIVILEGES ON `leboncode`.* TO 'leboncode-user'@'%';

FLUSH PRIVILEGES;
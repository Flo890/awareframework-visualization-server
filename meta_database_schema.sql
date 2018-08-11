# schema of the meta database, which enriches the logged data with other information

CREATE TABLE IF NOT EXISTS wifi_location(
  _id INT PRIMARY KEY AUTO_INCREMENT,
  device_id VARCHAR(150) NOT NULL,
  wifi_ssid TEXT NOT NULL,
  location ENUM('home','work')
);
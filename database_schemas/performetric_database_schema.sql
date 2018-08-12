# to be executed in the aware study database

CREATE TABLE IF NOT EXISTS performetric_fatigue_report(
  _id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user VARCHAR(255) NOT NULL,
  fatigue_avg FLOAT,
  minutes_no_fatigue INT,
  minutes_moderate_fatigue INT,
  minutes_extreme_fatigue INT,
  rest_breaks INT,
  fatigue_messages INT,
 `from` DATETIME NOT NULL,
  `to` DATETIME NOT NULL
);
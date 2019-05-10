Create Table User (
  Id int not null auto_increment,
  Name varchar(64) not null,
  Email varchar(64),
  Password char(32),
  PRIMARY KEY (Id)
) engine = InnoDB;
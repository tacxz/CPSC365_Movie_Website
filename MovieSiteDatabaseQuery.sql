CREATE DATABASE moviesitedb;

USE moviesitedb;

CREATE TABLE User(
    user_ID INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(25) NOT NULL,
    user_password VARCHAR(1000) NOT NULL,
    profile_picture VARCHAR(50) NOT NULL,
    admin_access BOOL NOT NULL DEFAULT 0
);

CREATE TABLE Movies(
    movie_title VARCHAR(50) NOT NULL,
    genre VARCHAR(50) NULL DEFAULT 0
    movie_ID INT AUTO_INCREMENT PRIMARY KEY,
    movie_rating SMALLINT NOT NULL DEFAULT 0,
);
CREATE TABLE Movies_Rated(
    movie_ID INT,
    movie_title VARCHAR(50) NOT NULL,
    personal_movie_rating SMALLINT NULL DEFAULT 0,
    user_ID INT,
    FOREIGN KEY (user_ID) REFERENCES User(user_ID),
    FOREIGN KEY (movie_ID) REFERENCES Movies(movie_ID)
);


CREATE TABLE Admin(
    admin_ID SMALLINT AUTO_INCREMENT PRIMARY KEY,
    user_ID INT,
    admin_access BOOL NULL DEFAULT 0
    Foreign Key (user_ID) REFERENCES User(user_ID)
);

CREATE TABLE Friends(
    friend_ID INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(25) NOT NULL
);

CREATE TABLE Comment(
    comment_ID INT AUTO_INCREMENT PRIMARY KEY,
    comment_date DATE NOT NULL,
    comment_content VARCHAR(1000) NOT NULL DEFAULT 0,
    user_commented VARCHAR(25) NOT NULL,
    movie_ID INT,
    FOREIGN KEY (movie_ID) REFERENCES Movies(movie_ID)

);

CREATE TABLE Reviews(
    friend_ID INT PRIMARY KEY,
    user_ID INT,
    movie_ID INT,
    comment_ID INT,
    FOREIGN KEY (user_ID) REFERENCES User(user_ID),
    FOREIGN KEY (movie_ID) REFERENCES Movies(movie_ID),
    FOREIGN KEY (comment_ID) REFERENCES Comment(comment_ID)

);

CREATE TABLE User_Has_Friends(
    user_ID INT,
    friend_ID INT,
    FOREIGN KEY (friend_ID) REFERENCES Friends(friend_ID),
    FOREIGN KEY (user_ID) REFERENCES User(user_ID)
);
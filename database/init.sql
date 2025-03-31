use todo_app;

create table users (
    id int auto_increment primary key,
    name varchar(32) unique not null,
    pass varchar(256) not null
);
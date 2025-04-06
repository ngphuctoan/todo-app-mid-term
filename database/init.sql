use todo_app;

create table users (
    id int auto_increment primary key,
    name varchar(32) unique not null,
    pass varchar(256) not null
);

create table todos (
    id int auto_increment primary key,
    title varchar(256) not null,
    description text,
    is_completed boolean default false,
    reminder datetime,
    user_id int,
    foreign key (user_id) references users(id) on delete cascade
);
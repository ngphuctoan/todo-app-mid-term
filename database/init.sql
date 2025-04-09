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
    reminder_sent boolean default false,
    user_id int,
    foreign key (user_id) references users(id) on delete cascade
);

create table auth_token_blacklist (
    token varchar(512) primary key,
    expires_at datetime not null
);

create table push_subscriptions (
    id int unsigned auto_increment primary key,
    endpoint varchar(256) not null unique,
    public_key varchar(256) not null,
    push_auth varchar(256) not null,
    created_at timestamp default current_timestamp,
    user_id int,
    foreign key (user_id) references users(id) on delete cascade
);
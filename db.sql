create table bookmark
(
    id         int auto_increment
        primary key,
    user_id    int                                  not null,
    novel_id   int                                  not null,
    chapter_id int                                  null,
    created_at datetime default current_timestamp() not null,
    updated_at datetime default current_timestamp() not null
);

create index idx_uid
    on bookmark (user_id);

create index idx_uid_nid
    on bookmark (user_id, novel_id);

create table chapter
(
    id         int unsigned auto_increment
        primary key,
    author_id  int                                     null,
    novel_id   int                                     null,
    name       varchar(255)                            null,
    content    longtext                                null,
    tags       longtext                                null,
    ext_data   longtext                                null,
    text_count int                                     null,
    word_count int                                     null,
    status     varchar(20)                             null,
    source_id  varchar(255)                            null,
    created_at timestamp default current_timestamp()   not null,
    updated_at timestamp default '0000-00-00 00:00:00' not null
);

create index idx_aid
    on chapter (author_id);

create index idx_nid
    on chapter (novel_id);

create fulltext index idx_tags
    on chapter (tags);

create table novel
(
    id           int unsigned auto_increment
        primary key,
    author_id    int                                     null,
    cover        varchar(255)                            null,
    name         varchar(255)                            null,
    `desc`       text                                    null,
    tags         text                                    null,
    source       varchar(64)                             null,
    source_id    varchar(64)                             null,
    ext_data     text                                    null,
    view_count   int                                     null,
    sync_status  int                           null,
    status       varchar(64)                             null,
    created_at   timestamp default current_timestamp()   not null,
    updated_at   timestamp default '0000-00-00 00:00:00' not null,
    fetched_at   timestamp default '0000-00-00 00:00:00' not null
);

create index idx_fetched_time
    on novel (fetched_at);

create index idx_source
    on novel (source);

create index idx_source_id
    on novel (source_id);

create index idx_status
    on novel (status);

create index idx_uid
    on novel (author_id);

create index idx_update_time
    on novel (updated_at);

create table user
(
    id         int auto_increment
        primary key,
    type       varchar(20)                            not null,
    name       varchar(50)                            not null,
    password   varchar(100)                           not null,
    nickname   varchar(50)                            not null,
    `desc`     text                                   not null,
    status     varchar(20)                            not null,
    ext_data   text                                   not null,
    created_at datetime default '0000-00-00 00:00:00' null,
    updated_at datetime default '0000-00-00 00:00:00' null
)
    charset = utf8mb4;

create index user_type_index
    on user (type);


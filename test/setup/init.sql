create table users
(
    id                    int auto_increment primary key,
    username              varchar(255)      not null,
    email                 varchar(255)      not null,
    validts               timestamp         null,
    confirmed             tinyint           not null,
    checked               tinyint           not null,
    valid                 tinyint           not null,
    locked_for_validation timestamp         null,
    locked_for_sending    timestamp         null,
    notified1             timestamp         null,
    notified3             timestamp         null,
    can_receive_emails    tinyint default 0 not null
);

create index can_receive_emails_validts_ind on users (can_receive_emails, validts);
create index checked_confirmed_validts_ind on users (checked, confirmed, validts);
create index checked_ind on users (checked);
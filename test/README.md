# Email Notification Service

This project sets up a service for sending email notifications about expiring subscriptions using Docker.

## Requirements

- Docker
- Docker Compose

## Setup

### Build and start the Docker containers:

```sh
docker-compose up --build
```

This will start the containers and initialize the database.

### Populate the Database
After the containers are running, you need to populate the users table with 5 million records.

Run the following command to populate the users table:

```sh
docker-compose exec php php /usr/src/myapp/setup/PopulateUsers.php -p 10 -u 5000000
```
This will create 5 million user records in the users table.

## Verify the Setup
You can verify that the setup is correct and that the data is being processed as expected.

### Connecting to the Database
You can connect to the MySQL database using a MySQL client. The connection details are as follows:

- Host: localhost
- Port: 3306
- Database: testdb
- User: testuser
- Password: testpassword

Example using the MySQL CLI:

```sh
mysql -h 127.0.0.1 -P 3306 -u testuser -p testdb
```

Enter the password testpassword when prompted.

### Checking the Data
You can use the following SQL query to check that both scripts are working correctly. This query will show the counts of users with checked and valid fields updated, as well as users with notified1 and notified3 fields updated:

```sql
SELECT
    COUNT(*) AS total_users,
    SUM(CASE WHEN checked = 1 THEN 1 ELSE 0 END) AS emails_checked,
    SUM(CASE WHEN valid = 1 THEN 1 ELSE 0 END) AS emails_valid,
    SUM(CASE WHEN notified1 IS NOT NULL THEN 1 ELSE 0 END) AS notified_1_day,
    SUM(CASE WHEN notified3 IS NOT NULL THEN 1 ELSE 0 END) AS notified_3_days
FROM users;
```

## Logs
The logs for the scripts can be found in the logs directory:

- `send_notification.log`: Logs for the SendNotificationsScript.php
- `validate_emails.log`: Logs for the ValidateEmailsScript.php

## Cron Jobs
The cron jobs are set up to run every minute using flock to ensure that only one instance of each script runs at a time. The cron jobs are defined in the setup/crontab file and are copied to the Docker container.

The cron jobs are configured to run the following scripts:

- `SendNotificationsScript.php`: This script is responsible for sending email notifications to users whose subscriptions are expiring soon. It checks the database for users whose subscriptions will expire within the next 1 to 3 days and sends them an email notification.
- `ValidateEmailsScript.php`: This script is responsible for validating user email addresses. It checks the database for users whose email addresses need to be validated and marks them as valid or invalid based on the validation result.

The cron jobs are defined as follows:

```crontab
* * * * * /usr/bin/flock -n /var/lock/send_notifications.lock /usr/local/bin/php /usr/src/myapp/php/SendNotificationsScript.php -l 100 -p 10 >> /var/log/send_notification.log 2>&1
* * * * * /usr/bin/flock -n /var/lock/validate_emails.lock /usr/local/bin/php /usr/src/myapp/php/ValidateEmailsScript.php -l 100 -p 10 >> /var/log/validate_emails.log 2>&1
```

## File Descriptions
- `Dockerfile`: Defines the Docker image for the PHP application.
- `docker-compose.yml`: Defines the Docker services.
- `setup/init.sql`: Initializes the MySQL database with a users table.
- `setup/PopulateUsers.php`: Populates the users table with 5 million records.
- `setup/crontab`: Defines the cron jobs to run the PHP scripts.
- `php/CheckEmail.php`: Dummy function to simulate email validity checks.
- `php/Common.php`: Contains common functions, such as running tasks in parallel.
- `php/Db.php`: Script for connecting to the database.
- `php/SendEmail.php`: Dummy function to simulate sending emails.
- `php/SendNotificationsScript.php`: Script for sending email notifications.
- `php/ValidateEmailsScript.php`: Script for validating email addresses.
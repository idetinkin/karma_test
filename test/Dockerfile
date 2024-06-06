# Use official PHP image as base
FROM php:8.3-cli

# Install cron, PCNTL, and PDO MySQL driver
RUN apt-get update && apt-get install -y cron && docker-php-ext-install pcntl pdo pdo_mysql

# Copy crontab file to the cron.d directory
COPY setup/crontab /etc/cron.d/simple-cron

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/simple-cron

# Apply cron job
RUN crontab /etc/cron.d/simple-cron

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

# Set the working directory
WORKDIR /usr/src/myapp

# Start the cron service
CMD ["cron", "-f"]

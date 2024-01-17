# Original task:
## Technical test for the Backend developer role

Hello and thank you very much for taking the time to perform this technical test for the Backend developer role to demonstrate your programming skills and knowledge.
We assume that the whole task will take four to six hours to get done. However, there is no time limit other than sending the results back to us within 48 hours after you have received this briefing.

### PHP based invitation system
Write a REST API based invitation system that allows the following actions:

1. One user aka the Sender can send an invitation to another user aka the
Invited.
2. The Sender can cancel a sent invitation.
3. The Invited can either accept or decline an invitation.

• All endpoint shall respond JSON.
• The database for the project must be developed with MySQL.
• The project must include functional and/or unit tests written in the PHPUnit framework to demonstrate how the various API endpoints behave in relation to each other.

Complete the project using PHP 7 (ideally 7.4) and Symfony Framework (version 3.4 or newer, ideally 4.4)
We hope that you enjoy this exercise and we are looking very much forward to talk about your results.

Good luck!


# Solution

## Installation

### Requirements

- PHP 7.4
- Composer
- MySQL 5.7
- Symfony CLI
- docker

### Steps

1. Clone the repository
2. Run `docker-compose up -d` to start
3. Run `composer install` inside the container to install dependencies
4. Run `php bin/console doctrine:migrations:migrate` inside the container to create the database
5. Use the postman collection to test the API

### PHPUnit tests

- Use dedicated database for tests: `php bin/console doctrine:database:create --env=test` and `php bin/console doctrine:migrations:migrate --env=test`
- Run `php bin/phpunit` inside the container to run the tests

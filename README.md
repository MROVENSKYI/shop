Introduction

1.Requirements:
-Docker installed on your system.
-installed PHP, Symfony
-A PHP project with a suitable Dockerfile.

2.Clone the repository:

      git clone <repository-url>

3.Navigate to the project directory in your terminal.

      cd <project-directory>

4.Then, run:

      docker-compose build && docker-compose up -d

5.Then, run:

      composer install

6.Then, run:

       symfony server:start -d

               or

       php -S 127.0.0.1:8000 -t public


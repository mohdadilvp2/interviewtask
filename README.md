# [Sample Laravel App]

----------

# Getting started
Simple application to import csv to trengo
## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/9.x/installation)



Clone the repository

    git clone https://github.com/mohdadilvp2/interviewtask.git

Switch to the repo folder

    cd interviewtask

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env
1- goto config/trengo.php and see the configs there, you need to add `TRENGO_API_KEY` and `TRENGO_CHANNEL_ID` in .env file to update to your trengo api key and channel_id
Generate a new application key

    php artisan key:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

Start the local development server

    php artisan serve

You can now access the server at http://localhost:8000

You need to add 
        `* * * * * cd /interviewtask && php artisan schedule:run >> /dev/null 2>&1`
to crontab to process your import request

There is command `php artisan process:file_uploads` which will run in every 10 minutes and will pickup new uploads. Then it will process it.
`


----------


## Environment variables

- `.env` - Environment variables can be set in this file

***Note*** : You can quickly set the database information and other variables in this file and have the application fully working.

----------
## Project Description

1- Upload your companies.csv and contacts.csv 
2- There is command `php artisan process:file_uploads` which will run in every 10 minutes and will pickup new uploads. Then it will process it.

# ![image](https://github.com/xdpirate/calorific/assets/1757462/9389f140-a52f-4515-9d87-a8416dfaff58) Calorific

Dead simple self-hosted calorie tracker.

![2023-11-29_06-35](https://github.com/xdpirate/calorific/assets/1757462/a84fd01f-6b19-4fa0-a992-6f78cdbfa06f)

Calorific is aimed at people who more or less know what they're doing and don't need to micromanage their macro-nutrients. Just add your calorie count.

## Features

* Log meals
* Save commonly used meals
* Save commonly used ingredients
* Build saved meals from ingredients list
* [Nord](https://www.nordtheme.com/)

## Requirements

You can run Calorific on your own AMP stack, or via Docker.

### AMP stack

* A web server
* A SQL server
* PHP

Calorific is developed and tested using Apache2 and MySQL, but other web servers and SQL servers will probably work. Maybe. 

### Docker

* Docker and Docker Compose

## Installation/Usage

### Running on preinstalled AMP stack

1. Clone this repository, or [grab the latest release](https://github.com/xdpirate/calorific/releases/latest)
2. Put the `calorific` directory in your web server document root (typically `/var/www/html`).
3. Create `credentials.php` within the same directory as `index.php`, and populate it with the following:

```
<?php
$mysqlHost = "your-sql-hostname";
$mysqlUser = "your-sql-username";
$mysqlPassword = "your-sql-password";
?>
```

4. Replace the values of the variables to fit your database configuration. Calorific will set up the database structure by itself.
5. To update calorific, run `git pull` in the repository directory (requires `git` to be installed).

### Running with Docker

1. Clone this repository, or [grab the latest release](https://github.com/xdpirate/calorific/releases/latest)
2. `cd` to the directory with the repository.
3. Build and run the image with `docker-compose up -d`
4. Wait 10-20 seconds after the first run to let the database start up.
5. Visit `http://localhost:1338/` in your browser to use the application.
6. To stop, run `docker-compose stop` in the repository directory.
7. To update Calorific, run `git pull` in the repository directory (requires `git` to be installed).

## Clean-up/Uninstallation

### AMP stack

* Delete the `calorific` directory from the web server document root.
* Delete the `calorific` database from the MySQL server -`DROP DATABASE calorific`

### Docker

Run `docker-compose down` from inside the repository directory, then delete it. Note that running this command destroys your stored Calorific data; don't do it unless you wish for that to happen.

## Security

There are currently zero security measures implemented. For external access, you can use `.htaccess` based authentication or a reverse proxy with authentication. Alternatively, you can make sure the application isn't exposed outside your local network.

If you are running Calorific in Docker and also exposing it outside your own network, you need to change the MySQL username and password in `docker-compose.yml` and `index.php` to something unique! If you don't, your database will be vulnerable, as the default credentials are included in plain text in this repository.

## License

Calorific is free and open source software, licensed under the GNU General Public License v3.0.

    Calorific -  Dead simple self-hosted calorie tracker
    Copyright ©️ 2023 xdpirate

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.

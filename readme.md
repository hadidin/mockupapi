## About localpsm
local module to be install in each parking site to store season pass user and to control entry and exit logic 
its to control integration with LPR backend and access control on parking site.


# .env example

	--------------------------To be add-----------
	APP_NAME=kiplepark-localpsm
	APP_ENV=local
	APP_KEY=base64:hpSuXojlZ9psIvtEjlTPcNkuRRJSb64s2iw8aIhShMc=
	APP_DEBUG=true
	APP_URL=http://localhost

	LOG_CHANNEL=stack

	DB_CONNECTION=mysql
	DB_HOST=127.0.0.1
	DB_PORT=3306
	DB_DATABASE=kiplepark-localpsm
	DB_USERNAME=root
	DB_PASSWORD=

	BROADCAST_DRIVER=log
	CACHE_DRIVER=file
	QUEUE_CONNECTION=sync
	SESSION_DRIVER=file
	SESSION_LIFETIME=120

	REDIS_HOST=127.0.0.1
	REDIS_PASSWORD=null
	REDIS_PORT=6379

	MAIL_DRIVER=smtp
	MAIL_HOST=smtp.mailtrap.io
	MAIL_PORT=2525
	MAIL_USERNAME=null
	MAIL_PASSWORD=null
	MAIL_ENCRYPTION=null

	PUSHER_APP_ID=
	PUSHER_APP_KEY=
	PUSHER_APP_SECRET=
	PUSHER_APP_CLUSTER=mt1

	MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
	MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

	LPR_BACKEND_HOST=localhost
	LPR_BACKEND_PORT=8081


## Installation

	to be run
	-sudo apt-get install php7.2-mbstring
	
	sudo apt install php-xml
    
     
    
    sudo apt install php7.2-mbstring
    
    sudo apt install php-zip
	
	-composer install
	-php artisan config:cache
	-php artisan passport:install
	-php artisan key:generate
	-php artisan migrate
	-php artisan db:seed
	-php artisan config:cache  ##run again
	
	for ubuntu fresh installation
	#install php 7.2 and its depedency	
	sudo apt install php7.2 libapache2-mod-php7.2 php7.2-mbstring php7.2-xmlrpc php7.2-soap php7.2-gd php7.2-xml php7.2-cli php7.2-zip
	
	
	#depedency on php.ini
	sudo apt-get install php5-gd, then 
	sudo apt-get install php5-intl and last one was 
	sudo apt-get install php5-xsl
	
	
	#for cron task
	sudo apt-get install php7.2-mysql
	sudo apt install php-pear
	sudo apt-get install php-dev
	sudo apt-get install unixodbc-dev
	
	#mssql driver
	sudo pecl install sqlsrv	
	sudo pecl install pdo_sqlsrv
    sudo su
    echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.:\s||"`/30-pdo_sqlsrv.ini
    echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.:\s||"`/20-sqlsrv.ini
    exit
    
    #odbc
	wget https://packages.microsoft.com/ubuntu/16.04/prod/pool/main/m/msodbcsql17/msodbcsql17_17.2.0.1-1_amd64.deb
	dpkg -i msodbcsql17_17.2.0.1-1_amd64.deb
	
	
	
	-sudo apt-get install php-curl
	
	and you good to go
	
## Postman collection
	https://www.getpostman.com/collections/2311c786c24f91bdb186
	

	
1. Install docker
2. run `docker compose up -d` in the root directory
3. On http://localhost/ you have the app

Note:
if you need composer and not having it locally you need to log into the container
`docker exec -it php-apache bash`
and follow this for example:
https://www.digitalocean.com/community/tutorials/how-to-install-and-use-composer-on-ubuntu-20-04
Then you can use composer
If you want to install phpunit you need to have git first:
`docker exec -it php-apache bash`
apt-get -y update
apt-get -y install git
composer install

Everytime you restart the container you have to do all these steps because they are not part of the image
I assumed you do not use docker and that's why I did not get deeper creating Docker file with all setup or
image with all installed.




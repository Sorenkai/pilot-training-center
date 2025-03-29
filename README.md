## Pilot Training Center (PTC)

Training Management System created by [Daniel L.](https://github.com/blt950) (1352906) and others from Web Department at VATSIM Scandinavia. Adapted for pilot training.

Original: https://github.com/Vatsim-Scandinavia/controlcenter

## How to install

Installing Pilot Training Center can be done through manual web server setup or by using the docker image provided.
*An SQL server is not included in the docker image*

- Edit the `.env.example` file and fill out the required details (SQL & App URL)
- Change the name of `.env.example` to `.env`
- Run `composer install`
- Run `npm run build`
- Run `php artisan key:generate` & `php artisan migrate`
- In order to run PTC in a test environment you can seed the database with `php artisan db:seed`
- To give yourself admin run `php artisan user:makeadmin` Enter any CID from 10000001 - 10000010



*Open a new issue if you find something that you think could be improved*

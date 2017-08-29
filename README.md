User Management API 
====================

A RESTful API to manage users built on top of Symfony 3 and some REST utilities.

- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) - Authentication handling
- [JMSSerializerBundle](https://github.com/schmittjoh/JMSSerializerBundle) - Object serialization
- [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) - Easy API documentation

#### Note:
 Default format: JSON

## Requirements

- [Git](https://git-scm.com/)
- PHP 7.0 or Higher
- Mysql 5.7.17 or Higher
- [Composer](https://getcomposer.org/)
- [PHPUnit 6](https://phpunit.de/)

## Installing & Setting up

1.- Clone the project:

````
$ git clone https://github.com/franjs/user-management-api.git
````

2.- Generate the SSH keys :

``` bash
$ cd user-management-api/
$ openssl genrsa -out var/jwt/private.pem -aes256 4096
$ openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem
```

3.- Install vendors:

````
$ composer install
````

Now Composer will ask you for the values of any undefined parameter. You can skip this step and set the parameters later at:

`app/config/parameters.yml`

You can see the sample at: `app/config/parameters.yml.dist`

4.- Create database and schema

````
$ php bin/console doctrine:database:create
$ php bin/console doctrine:schema:create
````

5.- Load Data Fixtures

`$ php bin/console doctrine:fixtures:load`

This command will create a User admin:

- username: admin
- password: admin

6.- Run the application:

`$ php bin/console server:run`

#### You can browse the API documentation at: 

`http://localhost:8000/api/doc`

## Running Tests

- Create database and schema for test environment:

````
$ php bin/console doctrine:database:create --env=test
$ php bin/console doctrine:schema:create --env=test
````

- Run the application:

`$ php bin/console server:run`

- Run the tests:

`$ phpunit`

It's recommended to use the latest stable PHPUnit version, installed as PHAR.

## How to authenticate the user
The first step is to authenticate the user admin using its credentials.

- POST /api/login

If it works, you will receive something like this:

````
{
    "token": "eyJhbGciOiJSUzI1NiJ9.eyJ1c2VybmFtZSI6InRlc3QiLCJleHAiOjE1MDQwMjI1MjksImlhdCI6MTUwNDAxODkyOX0.Y9lc7eXmFdkP-y65SRA2lebYanN5UiP5X8yAqOXL0DbkhOqN70_FF5C0xQyc1-gYQ5nnmJEq_ozYgiOgg8bu_NJLfCowXMN9eAG__23sBTbVkOpiFvo25nLv5YFVOIhkMLVZv9XiClkIuUkCSrG9fRytLlb8PsvGBvM9SH5vvxAqMKTTw9v3zhe6AYf_jDNiDevmBkN8QdIxi7tsGM4Q8g0PU7v7RGBOryEWFaA_NvEOFDEIV4cGVMEVRy_NOJnFXfBhUeTI1XSNA5YVG8qGI12PKPawLdwXDGM1oTpOVc01WtHjhT0L-3eRJK87kl3qPHhViNuyPtKtPfs9tjIHX9x-vCL97NaxoSDNzeM1idR4jm4W9UJKzQ5IBWpO3BhQJf6jpfdzmgDv-e6Af8cxRzeRZajHT8WEMfFFmO97zF4KQOWmoYa34qehZaNkf7-XPfmgFYEJtFOtzDUUs3Uh-eMZuzP9Td41WR-zfO47hadV0VZSMnMshtg446lhgIajllJ_bxMu01wd0VGcTh6ato5hl7PBuhZf17trhZvS9vGLjLJ1etewgmeuZRvOxwQwFi4gpkjJCRQHu56lBRAL254n4OpSZur5tCcrKa55ikvgj8E8m15AIwJznv7ghEftuQ3imOqFN0dmDAX_Edwb0WB0YSEnkCMrApU62Z27JBg"
}
````

Store it (client side), the JWT is reusable until its ttl has expired (3600 seconds by default).

Simply pass the JWT on each request to the protected firewall, either as an authorization header or as a query parameter.

By default only the authorization header mode is enabled : 

`Authorization: Bearer {token}`
<br>

### [Use Cases](docs/USE_CASES.md)
<br>
<hr>

### License
Open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
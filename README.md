## Duppy API

API for the duppy client.

This is a modular web app API server for the [Duppy Client](https://git.yasfu.net/duppy/client). 
It can support enabling and disabling different **Mods** to extend and maintain custom functionality separately and easily.

### License Info

More information on individual licensing can be found [here](https://dup.drm.gg).

This software is private and copyright 2021 (c) LittleBigBug (Ethan Jones)
The state of the software's license may change at any time, and may be sub-licensed out to clients.
For more information on each client's license please visit the above link

'Mods' are extensions of Duppy API created with tools provided by the software.
They are located in the Mods/ directory and each should have author and copyright information in the info.toml file or the top of the main PHP file
Mods developed for the Duppy API are owned by the owner and are allowed to be sold and licensed separately.


For example, the duppy base API and software can be licensed out but the Mod could be owned or separately licensed.
Client mods will be completely owned solely by clients but the main Duppy software will only be under a limited license.

### About

This API is designed to be stateless and to be adaptable to many other scenarios.
Originally this was created for [Dreamin.gg](https://dreamin.gg)'s website and backend API.
It was built in a modular way so that the code could be as reusable as possible.

The plan was that if the project were to fail the code wouldn't go to waste, and even if it didn't I could use it as a base for client projects.
It's already being used as a client project for [GoAirheads](https://goairheads.com).

## Features

There are many tools within the API to help create new API endpoints and persist and process data easily.

**Advanced routing and endpoints.**
All you need to do to create a new Endpoint is just to create a new class that inherits `Duppy\Abstracts\AbstractEndpoint` in the main `Endpoints/` folder or any mod's endpoint folder and set variables.
You can have multiple paths in one class to one function or multiple, define what HTTP requests to accept and what functions to delegate them to.
It's straightforward and easy to do but is flexible where it needs to be.

**Settings System.**
Built in settings system to allow system settings (admin settings), and individual user settings assigned to each WebUser.
Settings can be easily created similarly to Endpoints (above), instead within the `Settings/` folder and extending `AbstractSetting`.

**Mod System.**
Extensibility was obviously a big goal, so the ability to create *Mods* was made.
Mods are usually in their own repository and define some things like author, version, etc in a `.toml` config file.
Mods can just be dragged and dropped into the `Mods/` folder.

**User and Authentication System.**
User system that editable with Settings. There's a regular email/password system with optional email whitelist checks, or email verification.
Authentication with third-party providers like Google and Steam are implemented with [HybridAuth](https://github.com/hybridauth/hybridauth/).
Users can have multiple login methods, and are authenticated with a JWT (Javascript Web Token).
Authorative API Clients like those server-sided on Dreamin.gg can use the API on behalf of users who connect.
Users (if allowed) can also create API Tokens to authorize as themselves from an API token instead of requesting a JWT.

**Group and Permissions System.**
Users can be assigned to *Groups* which can be inherited to other groups.
Users or groups can be assigned permissions as well as inherit permissions from groups they are in.
Groups can be assigned *weight* so if a user tries to do an action against a user with a group with higher weight it won't let them.

**Environments.**
Environments are a concept I made for Dreamin.gg in mind for multiple game servers that can allow the API to run separate configurations and permissions on the same API.
For example, the same user can be registered to this API but if their client specifies a certain environment, different data will be accessed or given.
The same user can have a group in one environment, but a different group in another.
Groups or permissions can also be assigned globally.

### Backend and other stuff

The Bootstrapper for duppy loads all the services and builders for the application.
It handles lazy dependency injection, so it's easy to know where to get objects and makes it easier to test.

Services are an abstract class that allow creation of helper functions that can be mocked in testing easily if needed.
On top of that they serve temporary values per-request like user authentication.
To create a service create a class that extends *AbstractService* and services' singletons are created and managed when needed.

Duppy utilizes [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/index.html) to manage data storage and database stuff.
It makes it extremely easy to get data from databases that PHP can understand.

File Builders allow the functionality to iterate any folder in the duppy `src/` directory to build a list of class names.
It allows filters and stuff and is used for the Router builder to allow easy Endpoint creation and Settings/SettingType creation

Many things in the application are lazy-loaded or optimized so that each request loads the least amount possible (only what is needed).
This is currently being used with Slim Framework 4.0 Along with PHP 8.0 and [OpenLiteSpeed](https://openlitespeed.org/) it's pretty fast.

This is being developed in mind for use with [Workerman](https://github.com/walkor/Workerman) (see server.php) which proves [very impressive benchmarks](https://github.com/the-benchmarker/web-frameworks).
It can still run within a webserver like apache.

Slim Framework uses [FastRoute](https://github.com/nikic/FastRoute) but using something like [TreeRoute](https://github.com/baryshev/TreeRoute) or even [Siler](https://siler.leocavalcante.dev/) may be more beneficial for performance.

## Installing

### Requirements

- \>= PHP 8.0
- And the following PHP extensions:
  - mysql extension 
  - cURL extension
  - MBString extension
  - GMP extension
  - XML Extension
- Suggested: [Phive](https://phar.io/) (/scripts/install-phive.sh) To get these PHP tools:
  - PHPUnit
  - PHPLOC
  - PHPMD
  - PHPCPD
  - PHPDox
  - PHPCS
  - PHPCBF
  - Composer
- [URL Rewriting](https://gist.github.com/bramus/5332525) (Webserver Configuration)

Install php dependencies on a debian based OS:

```shell script
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-mbstring php8.0-gmp php8.0-xml 
```

If you use windows an easy way to set up a development server is to use [ApacheFriends XAMPP](https://www.apachefriends.org/download.html) which is also compatible with linux systems.

### Configuring

Use `.env.example` as a template for your own environment configuration:
`cp .env.example .env`

Most of it should be pretty self-explanatory, however here's some useful clarifications:

`JWT_SECRET` is needed to sign JWTs, its like a password and is best practice changing this every few weeks for the best security.

`JWT_ENCRYPT` is a boolean if the tokens should also be encrypted (after being signed).

`API_URL` is the url of this API, and authoritative over signing tokens

Some mods might have extra env configurations.

### Setting Up

Duppy API uses [Phing](https://www.phing.info/) to automate stuff like tests and other useful tools.

```shell script
# You can update composer and then deploy ORM migrations in one go
phing prepare

# Or individually
phing update # or phing update-dependencies
phing migrations # or phing deploy-migrations

# You can run PHPUnit Tests and other report tools
phing test
```

If you use PHPStorm you can [enable phing support](https://www.jetbrains.com/help/phpstorm/enabling-phing-support.html) to get a nice menu to run these targets through the UI instead.

This project uses some PHPStorm Attributes to help mostly with Documentation stuff notably the [#[Pure]](https://github.com/JetBrains/phpstorm-stubs/blob/master/meta/attributes/Pure.php) Attribute and you can see all PHPStorm attributes [here](https://github.com/JetBrains/phpstorm-stubs/tree/master/meta/attributes).

You can still use the old bash or bat scripts in `scripts/`

#### ORM Note

On Linux It is recommended that you set `/tmp` folder permissions so that its less likely ORM proxies will be messed with.

```shell script
sudo chmod 1777 /tmp
```

### Debugging

It's recommended to install `xDebug` to help pinpoint errors especially during unit testing.
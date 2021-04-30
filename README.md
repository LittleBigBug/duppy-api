## Duppy API

Modular REST API / 'Headless CMS' for developers in PHP 8

It can support **Mods** to extend and add your own business logic separately and easily.

Visit https://dup.drm.gg for some more info

## Features

There are many tools within the API to help create new API endpoints and persist and process data easily.

### Advanced routing and endpoints.

It's easy to create new endpoints in code, while staying efficient and organized.
REST Endpoints for Entities are automatically created

### Settings System

User and System-wide settings system

### Mod System

Powerful developer extensibility capabilities with easy drop-in functionality

### Flexible User and Authentication System

Secure user authentication with hashed passwords or numerous third-party bindings using HybridAuth

### Group and Permissions System

Easily assign users to multiple inheritable groups with permissions or other attributes, or apply special permissions directly to users.
Leverage an advanced permissions system with wildcards (like minecraft!)

### Separate Environments

Allow the same api server instance to have different settings, groups, permissions and more.

### Entity System

Automatic REST/OpenAPI/Swagger API generation for Entities, with support for [GraphQL](https://graphql.org/)

## Installing

### Requirements

- \>= PHP 8.0
- And the following PHP extensions:
  - mysql extension 
  - cURL extension
  - MBString extension
  - GMP extension
  - XML Extension
  - Memcached (Rate Limiting)
- Suggested: [Phive](https://phar.io/) (/scripts/install-phive.sh) to install tools below
- Used PHP tools:
  - Composer
  - PHPUnit
  - PHPLOC
  - PHPMD
  - PHPCPD
  - PHPDox
  - PHPCS
  - PHPCBF
- [URL Rewriting](https://gist.github.com/bramus/5332525) (Webserver Configuration)

Install dependencies on a debian based OS:

```shell script
sudo apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-mbstring php8.0-gmp php8.0-xml php8.0-memcached memcached libmemcached-dev
```

Windows with [Chocolatey](https://chocolatey.org):

```batch
choco install php
```

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

On Linux It is recommended that you set `/tmp` folder permissions properly so that its less likely ORM proxies will be messed with.

```shell script
sudo chmod 1777 /tmp
```

On linux, whatever web service running Duppy may not have access to the tmp files if `PrivateTmp=true` within the `/lib/systemd/system/___.service` file

## Contributing

The best ways you can contribute to Duppy's development is by donating or actually contributing back to the codebase!

Anyone is welcome to send pull requests, or fork!

See [CONTRIBUTING.md](CONTRIBUTING.md)

### Modding

You can create **Mods** on the api to extend Duppy for custom business logic or Entities/Settings/etc.

Duppy API is licensed under GNU LGPL v3, which means any modifications must use the same license, *but* you can create mods under any license you like.

The [contributing file](CONTRIBUTING.md) contains some useful info to help to get you familiar with the code base.

### Extra Technical Notes

Most things in the application are lazy-loaded or optimized so that each request loads the least amount possible (only what is needed).
This is currently being used with Slim Framework 4.0 Along with PHP 8.0 and being tested with [OpenLiteSpeed](https://openlitespeed.org/), it's pretty fast.

This is being developed in mind for use with [Workerman](https://github.com/walkor/Workerman) (as standalone, see server.php) which proves [very](https://www.techempower.com/benchmarks/#section=data-r20&hw=ph&test=plaintext&l=zik073-1r) impressive [benchmarks](https://github.com/the-benchmarker/web-frameworks).
It can still run within a webserver like apache.

### Debugging

It's recommended to install `xDebug` to help pinpoint errors especially during unit testing.
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

## Installing

### Requirements

- PHP 8.0
  - mysql extension 
  - cURL extension
  - MBString extension
  - GMP extension
  - OpenSSL library & extension
  - XML Extension
- Phive (/scripts/install-phive.sh)
  - PHPUnit
  - PHPLOC
  - PHPMD
  - PHPCPD
  - PHPDox
  - PHPCS
  - PHPCBF
  - Composer
- [URL Rewriting](https://gist.github.com/bramus/5332525)

Install php dependencies on debian based OS:

```shell script
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-mbstring php8.0-gmp
```

### Configuring

Use `.env.example` as a template for your own environment configuration:
`cp .env.example .env`

Most of it should be pretty self-explanatory, however here's some useful clarifications:

`APP_ROOT_PATH` should only be used if your API installation is in a different folder.
`JWT_SECRET` is needed to sign and encrypt JWTs, its like a password and is best practice changing this every few weeks for the best security.
`JWT_ENCRYPT` is a boolean if the tokens should also be encrypted (after being signed).
`CLIENT_URL` is the url of the 'official' Duppy client for this API, the root of the api redirects to this.

### Using

You can use the shell script to pull from git, update composer and deploy DB migrations

```shell script
chmod +x update.sh
./update.sh

# You can skip updating composer
./update.sh -sc # or --skip-composer

# You can skip pulling from git
./update.sh -sg # or --skip-git

# Skip both of those
./update.sh -s # or --skip-all
```

You can run update.bat on windows

On Linux It is recommended that you set `/tmp` folder permissions so that its less likely ORM proxies will be messed with

```shell script
sudo chmod 1777 /tmp
```
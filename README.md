## Duppy API

API for the duppy client.

This is a modular web app API server for the [Duppy Client](https://git.yasfu.net/duppy/client). 
It can support enabling and disabling different **Mods** to extend and maintain custom functionality separately and easily.

## Installing

### Requirements

- PHP 8.0
  - mysql extension 
  - cURL extension
  - MBString extension
  - GMP extension
  - OpenSSL library & extension
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
`CLIENT_URL` is the url of the 'official' Duppy client for this API, the root of the api redirects to this.

### Using

You can use the shell script to update composer and deploy DB migrations

```shell script
chmod +x update.sh
./update.sh

# You can skip updating composer
./update -s --skip-composer
```

You can run update.bat on windows
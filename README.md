## Duppy API

API for the duppy client.

This is a modular web app API server for the [Duppy Client](https://git.yasfu.net/duppy/client). 
It can support enabling and disabling different **Mods** to extend and maintain custom functionality separately and easily.

## Installing

### Requirements

- PHP 7.4
- Composer
- [URL Rewriting](https://gist.github.com/bramus/5332525)

You can use the shell script to update composer and deploy DB migrations

```shell script
chmod +x update.sh
./update.sh

# You can skip updating composer
./update -s --skip-composer
```

You can run update.bat on windows
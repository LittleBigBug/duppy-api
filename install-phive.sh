# Installs Phive ( phar.io ) to /usr/local/bin
# This script should be ran as sudo

if [ ! `id -u` -eq 0 ]
then
        echo "This script should be ran with sudo or as root!"
        exit 1
fi

wget -O phive.phar "https://phar.io/releases/phive.phar"
wget -O phive.phar.asc "https://phar.io/releases/phive.phar.asc"
gpg --keyserver hkps.pool.sks-keyservers.net --recv-keys 0x9D8A98B29B2D5D79
gpg --verify phive.phar.asc phive.phar
rm phive.phar.asc
chmod +x phive.phar
mv phive.phar /usr/local/bin/phive
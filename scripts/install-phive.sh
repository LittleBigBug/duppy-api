# Installs Phive ( phar.io ) to /usr/local/bin
# This script should be ran as sudo

# Build tools now overshadow this script
# Set working directory to the base dir
cd "${0%/*}"
cd ../

local=0

while [ "$#" -gt 0 ]; do
    case $1 in
        -l|--local) local=1 ;;
        *) echo "Unknown parameter passed: $1"; exit 1 ;;
    esac
    shift
done

if [ "$local" -eq "0" ] && [ ! `id -u` -eq 0 ]
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

if [ "$local" -eq "0" ]
then
  mv phive.phar /usr/local/bin/phive
else
  mv phive.phar ./tools/phive
fi
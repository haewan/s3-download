## S3-Download

We needed a script to sync an entire bucket down – using the shell. 

Because we use `s3cmd` as well, this script will read the same configuration file from `$HOME/.s3cfg`.

This is nothing crazy, but more or less a backup of a script I don't want to write again and again.

### Requirements

    aptitude install s3cmd
    s3cmd --configure
    aptitude install php5-cli
    aptitude install php-pear
    pear install Services_Amazon_S3-alpha

### Usage

    ./scripts/s3-download.php -b BUCKETNAME

The above syncs the bucket into the current folder. Use `-l` to specify another path/location.

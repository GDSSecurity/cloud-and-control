#!/bin/sh

if [ $# != 2 ]; then
    echo "Usage: $0 architecture version"
    exit
elif [ `expr index $1 '32'` == 0 -a `expr index $1 '64'` == 0 ]; then
    echo "Architecture needs to include bitlength.  E.G. x32-nocona"
    exit
fi

echo "Creating the form john_$2_$1"

cp -r ~/workingdir/build/john-master/john-1.7.6/run john/john_$2_$1
mv john/john_$2_$1/john-boinc john/john_$2_$1/john_$2_$1
rm -rf john/john_$2_$1/.svn
rm john/john_$2_$1/john.conf john/john_$2_$1/stats
rm john/john_$2_$1/*.rb john/john_$2_$1/*.py john/john_$2_$1/*.pl
chown -R apache *
chgrp -R apache *
chmod -R 755 john/
echo "Done"
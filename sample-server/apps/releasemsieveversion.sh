#!/bin/sh

if [ $# != 2 ]; then
    echo "Usage: $0 architecture version"
    exit
elif [ `expr index $1 '32'` == 0 -a `expr index $1 '64'` == 0 ]; then
    echo "Architecture needs to include bitlength.  E.G. x32-nocona"
    exit
fi

echo "Creating the form msieve_$2_$1"

mv msieve-boinc msieve/msieve_$2_$1
chown -R apache *
chgrp -R apache *
echo "Done"
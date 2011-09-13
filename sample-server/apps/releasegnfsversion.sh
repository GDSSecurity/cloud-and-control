#!/bin/sh

if [ $# != 3 ]; then
    echo "Usage: $0 tar architecture version"
    exit
elif [ `expr index $2 '32'` == 0 -a `expr index $2 '64'` == 0 ]; then
    echo "Architecture needs to include bitlength.  E.G. x32-nocona"
    exit
fi

echo "Working on $1 creating the form gnfs-boinc-lasieve4I11e_$3_$2"

tar xf $1

mv gnfs-boinc-lasieve4I11e gnfslasieve4I11e/gnfs-boinc-lasieve4I11e_$3_$2
mv gnfs-boinc-lasieve4I12e gnfslasieve4I12e/gnfs-boinc-lasieve4I12e_$3_$2
mv gnfs-boinc-lasieve4I13e gnfslasieve4I13e/gnfs-boinc-lasieve4I13e_$3_$2
mv gnfs-boinc-lasieve4I14e gnfslasieve4I14e/gnfs-boinc-lasieve4I14e_$3_$2
mv gnfs-boinc-lasieve4I15e gnfslasieve4I15e/gnfs-boinc-lasieve4I15e_$3_$2
mv gnfs-boinc-lasieve4I16e gnfslasieve4I16e/gnfs-boinc-lasieve4I16e_$3_$2
rm -rf gnfs-lasieve4I1* gnfs-boinc-lasieve4I1*.o
chown -R apache *
chgrp -R apache *
echo "Done"
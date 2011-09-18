#!/bin/sh

echo "This script does not work completely.  It has a couple bugs in it that"
echo " will only be found through trialrun testing."
echo "You must edit the script to enable it."
exit


if [ $# != 1 ];
then
    echo "Usage: $0 basenamematchstring"
    exit
fi

echo "Using $1 as a matchstring..."

matches=$(find download/ -name *$1*potfile_* -or -name *$1*_hashcat_hashfile_* | wc -l)

echo "Found $matches matches to replace, top 3 of each type:"

find download/ -name *$1*potfile_* | xargs -I [] -- echo "   " [] 2>/dev/null | head -n 3 
find download/ -name *$1*_hashcat_hashfile_* | xargs -I [] -- echo "   " [] 2>/dev/null | head -n 3 

for i in $(seq 10); do
    echo -n "$i"
    sleep 1
done
echo ""
echo "Beginning infinite loop..."

function replacejohnstyle()
{
#    cp $masterpot-johnstyle $masterpot-tmp
#    mv $masterpot-tmp $1
	echo "You must switch the comment on these lines."
    echo "cp $masterpot-johnstyle $masterpot-tmp"
    echo "mv $masterpot-tmp $1"
}
function replacehashcatstyle()
{
#    comm -23 $1 $masterpot-hcstyle | sort -u > $masterpot-tmp
#    mv $masterpot-tmp $1
	echo "You must switch the comment on these lines."
    echo "comm -23 $1 $masterpot-hcstyle > $masterpot-tmp"
    echo "mv $masterpot-tmp $1"
}



masterpot="tmp_pseudocidal/masterpotfile_$1"
while [ 1 = 1 ];
do
    potfiles=$(find sample_results/ -maxdepth 1 -name *$1*_0 | wc -l)
    echo "Found $potfiles to cat this iteration, sending them to $masterpot..."
    
    rm -f $masterpot
    touch $masterpot
    find download/ -name *$1*potfile_* -print0 | while read -d $'\0' i; do cat $i >> $masterpot; done
    ./gunzipresults.sh $1
    if [ $potfiles -ge 1 ];
    then
	for i in sample_results/*$1*_0;
	do
	    TYPE=$(file $i)
	    if [[ $TYPE =~ "gzip compressed" ]]; then
		#do nothing, we got super unlucky
		echo -n ""
	    else
		cat $masterpot $i | sort | uniq > $masterpot-tmpfile
		mv $masterpot-tmpfile $masterpot
	    fi
	done
    fi

    # Regex matches $NT$ and $LM$ because hashcat doesn't want those,
    # but not $1$ which hascat DOES expect that one.  /sigh
    # lurking bug - could match a password

    prefix=$( (cut -f 2 --delim=: $masterpot ; cut -f 1 --delim=: $masterpot) | /bin/grep -e "^\\$\([A-Z]\+\)\\$" | head | sed 's/^\$\([A-Z]\+\)\$\([A-Za-z0-9]\+\)/\$\1\$/' |sort | uniq )
    echo "    Replacing john-syle potfiles using a prefix of $prefix..."
    cat $masterpot | sed 's/^\$\([A-Z]\+\)\$//' | awk -v prefix=$prefix '{ print prefix$0 }' | sort | uniq > $masterpot-johnstyle
    find download/ -name *$1*_potfile_* -print0 | while read -d $'\0' i; do replacejohnstyle "$i"; done

    echo "    Replacing hashcat-style hashfiles..."
    cut -f 1 --delim=: $masterpot | sed 's/^\$\([A-Z]\+\)\$//' | sort | uniq > $masterpot-hcstyle
    find download/ -name *$1*_hashcat_hashfile_* -print0 | while read -d $'\0' i; do replacehashcatstyle "$i"; done

    sleep 5
done

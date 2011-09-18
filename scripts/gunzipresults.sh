#!/bin/sh

if [ $# != 1 ];
then
	filter=""
else
	filter=$1
fi

for i in sample_results/*$filter*; do

	TYPE=$(file $i)
	if [[ $TYPE =~ "gzip compressed" ]]; then
		mv $i $i.gz
		gunzip $i.gz
		echo -n "."
	fi

done
echo ""

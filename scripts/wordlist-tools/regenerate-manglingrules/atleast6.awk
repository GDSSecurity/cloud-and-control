#!/bin/awk -f

{
    if(length <= 6)
	;#print $0
    else
	print $0
}

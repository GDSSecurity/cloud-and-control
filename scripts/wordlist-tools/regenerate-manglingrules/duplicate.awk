#!/bin/awk -f

{
    if(length >= 7)
	;#print $0
    else if(length == 1)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else
	print $0 $0
}

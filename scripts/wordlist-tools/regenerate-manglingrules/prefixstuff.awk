#!/bin/awk -f

{
    if(length <= 2)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else
    {
	prefixes = "1234567890"
	for(i=1; i<=length(prefixes); i++)
	    print substr(prefixes, i, 1) $0
    }
}

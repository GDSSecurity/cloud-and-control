#!/bin/awk -f

{
    if(length <= 2)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else
    {
	suffixes = "1234567890.?!"
	for(i=1; i<=length(suffixes); i++)
	    print $0 substr(suffixes, i, 1)
    }
}

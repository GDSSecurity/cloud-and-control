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
	    print toupper(substr($0, 1, 1)) substr($0, 2) substr(suffixes, i, 1)
    }
}

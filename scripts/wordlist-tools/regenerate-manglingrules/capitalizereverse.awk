#!/bin/awk -f

{
    if(length <= 2)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else if($0 !~ /^[a-z]/ )
	;#print $0
    else
    {
	capitalize = toupper(substr($0, 1, 1)) substr($0, 2) 
	revword = ""
	
	for(i=length; i> 0; i--)
	    revword = revword substr(capitalize, i, 1)

	if(revword == capitalize)
	    ;
	else
	    print revword
    }
}

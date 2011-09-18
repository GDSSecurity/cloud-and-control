#!/bin/awk -f

{
    if(length <= 3)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else
    {
	word = $0
	revword = ""
	
	for(i=length; i> 0; i--)
	    revword = revword substr(word, i, 1)

	if(revword == word)
	    ;
	else
	    print revword
    }
}

#!/bin/awk -f

{
    if(length <= 2)
	;#print $0
    else if($0 !~ /^[a-zA-Z0-9]+$/ )
	;#print $0
    else
    {
	print toupper($0)
    }
}

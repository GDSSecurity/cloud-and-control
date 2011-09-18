#!/bin/awk -f

{
    if(length <= 1)
	;#print $0
    if(length > 7)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else
    {
	capitalize = toupper(substr($0, 1, 1)) substr($0, 2) 
	print capitalize capitalize
    }
}
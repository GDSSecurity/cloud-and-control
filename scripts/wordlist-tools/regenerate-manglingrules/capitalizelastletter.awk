#!/bin/awk -f

{
    if(length <= 2)
	;#print $0
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0
    else if($0 ~ /[A-Z]$/ )
	;#print $0
    else
    {
	print  substr($0, 1, length-1) toupper(substr($0, length, 1))
    }
}

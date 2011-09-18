#!/bin/awk -f

{
    if (length <= 2)
	;#print $0;
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0;
    else
    {
	#This isn't the most sophisticated pluraization code (Microsoft has better), but it matches john's
	word = $0;
	secondtolastchar = substr(word, length-1, 1);
	lastchar = substr(word, length, 1);
	
	if((lastchar == "s" || lastchar == "x" || lastchar == "z") ||
	   (lastchar == "h" && (secondtolastchar == "c" || secondtolastchar == "h")))
	    pluralized = word "es";
	
	else if(lastchar == "f")
	    pluralized = substr(word, 1, length-1) "ves";
	
	else if(lastchar == "e" && secondtolastchar == "f")
	    pluralized = substr(word, 1, length-2) "ves";
	
	else if(lastchar == "y" && 
		(secondtolastchar != "a" && secondtolastchar != "e" && secondtolastchar != "i" &&
		 secondtolastchar != "o" && secondtolastchar != "u"))
	    pluralized = substr(word, 1, length-1) "ies";
	
	else
	    pluralized = word "s";
	
	print pluralized;
    }
}

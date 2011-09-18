#!/bin/awk -f

{
    if (length <= 2)
	;#print $0;
    else if($0 !~ /^[a-zA-Z]+$/ )
	;#print $0;
    else
    {
	#Add -ed
	if($0 !~ /ed$/)
	{
	    word = $0;
	    secondtolastchar = substr(word, length-1, 1);
	    lastchar = substr(word, length, 1);
	    
	    if(lastchar == "y") {
		word = substr(word, 1, length-1) "i"
		lastchar = "i"
	    }
	    else if((lastchar == "b" || lastchar == "g" || lastchar == "p") &&
		    (secondtolastchar != "b" && secondtolastchar != "g" && secondtolastchar != "p"))
	    {
		word = word lastchar
	    }
	    
	    if(lastchar == "e")
		word = word "d"
	    else
		word = word "ed"
	    
	    print word;
	}
	#Add -ing
	if($0 !~ /ing$/)
	{
	    word = $0;
	    secondtolastchar = substr(word, length-1, 1);
	    lastchar = substr(word, length, 1);
	    
	    if(lastchar == "a" || lastchar == "e" || lastchar == "i" || lastchar == "o" || lastchar == "u")
	    {
		word = substr(word, 1, length-1) "ing"
	    }
	    else 
	    {
		if((lastchar == "b" || lastchar == "g" || lastchar == "p") &&
		   (secondtolastchar != "b" && secondtolastchar != "g" && secondtolastchar != "p"))
		{
		    word = word lastchar
		}
		word = word "ing"
	    }
	    print word;
	}
    }
}

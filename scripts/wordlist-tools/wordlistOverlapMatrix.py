#!/usr/bin/python

import sys, os, commands

def calculateoverlap(a,b):
    c ="comm " + a + " " + b + " -1 -3 | wc -l"
    onlyinb = commands.getoutput(c)
    c = "cat " + b + " | wc -l "
    lenofb = commands.getoutput(c)
    return round(float(onlyinb) / float(lenofb), 4) * 100;

if __name__ == "__main__":
	# =============================================================
	# Get the wordlists
	wordlists = []
	for root, dirs, files in os.walk('.'):
		if root.find(".git") > -1 or root == ".":
			continue
		if root.find("onlyuppercase") > -1:
			continue

		root = root[2:]
		for i in files:
			wordlists.append( root + "/" + i );

	# =============================================================
	# Calculate the Overlap
	overlap = {};
	for a in wordlists:
		for b in wordlists:
			if a not in overlap:
				overlap[a] = {}
			overlap[a][b] = calculateoverlap(a, b);
			print 'Comparing ',a,b,': b is ', str(overlap[a][b]), ' percent unique'

	# =============================================================		
	# Write results out to a file
	f = open('overlap.csv', 'w')
	delim = ','
	f.write(delim)#blank first column
	for p in wordlists:#first row of benchmark names
		f.write(p + delim)
	f.write("\n")

	for a in wordlists:
		f.write(a + delim)
		for b in wordlists:
			f.write(str(overlap[a][b]) + delim)
		f.write("\n")
	f.close()

				
